<?php
require_once 'koneksi.php';

$pesan_error = '';
$pesan_sukses = '';

// ==========================================
// FITUR 2: INPUT TRANSAKSI (CREATE + VALIDASI)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_transaksi'])) {
    $nama_transaksi = trim($_POST['nama_transaksi']);
    $jenis          = $_POST['jenis'] ?? '';
    $nominal        = $_POST['nominal'] ?? 0;
    $tanggal        = $_POST['tanggal'] ?? '';

    // Validasi Server-Side
    if (empty($nama_transaksi) || empty($jenis) || empty($nominal) || empty($tanggal)) {
        $pesan_error = "Semua field wajib diisi!";
    } elseif ($nominal <= 0) {
        $pesan_error = "Nominal harus lebih besar dari 0!";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO transaksi (nama_transaksi, jenis, nominal, tanggal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $nama_transaksi, $jenis, $nominal, $tanggal);
        
        if ($stmt->execute()) {
            $pesan_sukses = "Transaksi berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menyimpan data: " . $koneksi->error;
        }
        $stmt->close();
    }
}

// ==========================================
// FITUR 5: HAPUS DATA (DELETE)
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->bind_param("i", $id_hapus);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        $pesan_error = "Gagal menghapus data!";
    }
    $stmt->close();
}

// ==========================================
// FITUR 4: FILTER BULAN & TAHUN
// ==========================================
$bulan_filter = $_GET['bulan'] ?? '';
$tahun_filter = $_GET['tahun'] ?? '';

$where_clause = "";
if (!empty($bulan_filter) && !empty($tahun_filter)) {
    $where_clause = "WHERE MONTH(tanggal) = '$bulan_filter' AND YEAR(tanggal) = '$tahun_filter'";
} elseif (!empty($tahun_filter)) {
    $where_clause = "WHERE YEAR(tanggal) = '$tahun_filter'";
} elseif (!empty($bulan_filter)) {
    $where_clause = "WHERE MONTH(tanggal) = '$bulan_filter'";
}

// Query Tampil Data Riwayat (Urut tanggal terbaru)
$query_transaksi = "SELECT * FROM transaksi $where_clause ORDER BY tanggal DESC, id DESC";
$result_transaksi = mysqli_query($koneksi, $query_transaksi);

// ==========================================
// FITUR 3: RINGKASAN SALDO (AGREGASI SUM)
// ==========================================
$q_masuk = mysqli_query($koneksi, "SELECT SUM(nominal) AS total FROM transaksi WHERE jenis = 'masuk'");
$total_masuk = mysqli_fetch_assoc($q_masuk)['total'] ?? 0;

$q_keluar = mysqli_query($koneksi, "SELECT SUM(nominal) AS total FROM transaksi WHERE jenis = 'keluar'");
$total_keluar = mysqli_fetch_assoc($q_keluar)['total'] ?? 0;

$saldo_akhir = $total_masuk - $total_keluar;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Cashflow Dashboard</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* Warna latar baris tabel */
        .bg-masuk {
            background-color: #d1e7dd !important;
        }
        .bg-keluar {
            background-color: #f8d7da !important;
        }

        /* Styling Kartu Ringkasan Pro */
        .card-stat {
            border: none;
            border-radius: 16px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            user-select: none;
        }

        .card-stat:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
        }

        /* Ikon Ilustrasi Latar Belakang Kartu */
        .card-icon-illustration {
            position: absolute;
            right: 15px;
            bottom: 10px;
            font-size: 4.5rem;
            opacity: 0.25;
            transition: transform 0.4s ease, opacity 0.4s ease;
        }

        .card-stat:hover .card-icon-illustration {
            transform: scale(1.15) rotate(-10deg);
            opacity: 0.4;
        }

        /* Animasi Pulsing / Bouncing saat Kartu Dipencet (Click Effect) */
        .card-stat:active {
            transform: scale(0.96) translateY(-2px);
            transition: transform 0.1s ease;
        }

        .card-stat.is-clicked .card-icon-illustration {
            animation: iconPulse 0.5s ease;
        }

        @keyframes iconPulse {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.4) rotate(15deg); }
            100% { transform: scale(1.15) rotate(-10deg); }
        }

        /* Visual Badge Status Ringkasan */
        .stat-icon-badge {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4 text-center text-primary fw-bold">MINI CASHFLOW DASHBOARD</h2>

    <!-- Alert Notifikasi -->
    <?php if ($pesan_error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $pesan_error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $pesan_sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- RINGKASAN SALDO (Kartu Interaktif & Beranimasi) -->
    <div class="row g-3 mb-4">
        <!-- Kartu Uang Masuk -->
        <div class="col-md-4">
            <div class="card card-stat text-white bg-success shadow-sm p-2" onclick="animateCard(this)">
                <div class="card-body position-relative z-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="stat-icon-badge">
                            <i class="bi bi-arrow-down-left-circle-fill fs-4"></i>
                        </div>
                        <h6 class="card-title m-0 fw-semibold text-uppercase opacity-75">Total Uang Masuk</h6>
                    </div>
                    <h3 class="fw-bold m-0 mt-2">Rp <?= number_format($total_masuk, 0, ',', '.'); ?></h3>
                    <i class="bi bi-graph-up-arrow card-icon-illustration"></i>
                </div>
            </div>
        </div>

        <!-- Kartu Uang Keluar -->
        <div class="col-md-4">
            <div class="card card-stat text-white bg-danger shadow-sm p-2" onclick="animateCard(this)">
                <div class="card-body position-relative z-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="stat-icon-badge">
                            <i class="bi bi-arrow-up-right-circle-fill fs-4"></i>
                        </div>
                        <h6 class="card-title m-0 fw-semibold text-uppercase opacity-75">Total Uang Keluar</h6>
                    </div>
                    <h3 class="fw-bold m-0 mt-2">Rp <?= number_format($total_keluar, 0, ',', '.'); ?></h3>
                    <i class="bi bi-graph-down-arrow card-icon-illustration"></i>
                </div>
            </div>
        </div>

        <!-- Kartu Saldo Akhir -->
        <div class="col-md-4">
            <div class="card card-stat text-white bg-primary shadow-sm p-2" onclick="animateCard(this)">
                <div class="card-body position-relative z-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="stat-icon-badge">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                        <h6 class="card-title m-0 fw-semibold text-uppercase opacity-75">Saldo Akhir</h6>
                    </div>
                    <h3 class="fw-bold m-0 mt-2">Rp <?= number_format($saldo_akhir, 0, ',', '.'); ?></h3>
                    <i class="bi bi-piggy-bank card-icon-illustration"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- FORM INPUT TRANSAKSI -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white font-weight-bold fw-bold">Tambah Transaksi Baru</div>
        <div class="card-body">
            <form action="index.php" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nama Transaksi</label>
                    <input type="text" name="nama_transaksi" class="form-control" placeholder="Contoh: Gaji / Beli Makan" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis</label>
                    <select name="jenis" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="masuk">Masuk</option>
                        <option value="keluar">Keluar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" name="nominal" class="form-control" min="1" placeholder="50000" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" name="tambah_transaksi" class="btn btn-primary px-4">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FILTER & TABEL RIWAYAT TRANSAKSI -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="m-0 fw-bold">Riwayat Transaksi</h5>
            
            <!-- FORM FILTER BULAN & TAHUN -->
            <form action="index.php" method="GET" class="d-flex gap-2">
                <select name="bulan" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">-- Semua Bulan --</option>
                    <?php
                    $nama_bulan = [1=>'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    foreach ($nama_bulan as $num => $str):
                        $selected = ($bulan_filter == $num) ? 'selected' : '';
                        echo "<option value='$num' $selected>$str</option>";
                    endforeach;
                    ?>
                </select>
                <input type="number" name="tahun" class="form-control form-control-sm" placeholder="Tahun (YYYY)" value="<?= htmlspecialchars($tahun_filter); ?>" style="width: 120px;">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Transaksi</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result_transaksi) > 0):
                            while ($row = mysqli_fetch_assoc($result_transaksi)): 
                                $class_row = ($row['jenis'] == 'masuk') ? 'bg-masuk' : 'bg-keluar';
                        ?>
                            <tr class="<?= $class_row; ?>">
                                <td><?= $no++; ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td><?= htmlspecialchars($row['nama_transaksi']); ?></td>
                                <td>
                                    <span class="badge <?= ($row['jenis'] == 'masuk') ? 'bg-success' : 'bg-danger'; ?>">
                                        <?= ucfirst($row['jenis']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <!-- FITUR 5: PROTEKSI KONFIRMSI JAVASCRIPT confirm() -->
                                    <a href="index.php?hapus=<?= $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data transaksi ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">Belum ada data transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Fungsi JavaScript untuk memicu efek animasi saat kartu dipencet
function animateCard(cardElement) {
    cardElement.classList.add('is-clicked');
    setTimeout(() => {
        cardElement.classList.remove('is-clicked');
    }, 500);
}
</script>
</body>
</html>