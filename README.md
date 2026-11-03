# Mini Cashflow

Aplikasi Manajemen dan Pencatatan Arus Kas (Cashflow) Keuangan Sederhana berbasis PHP Native dan MySQL dengan Antarmuka Responsif Bootstrap 5.

## Requirement

Pastikan sudah terinstall:

* Git
* Web Server (Apache / XAMPP)
* PHP (v7.4 atau v8.x)
* MySQL / MariaDB
* Web Browser modern (Google Chrome, Microsoft Edge, Mozilla Firefox)

Cek Git:

```bash
git --version
```

Cek PHP:

```bash
php -v
```

## Instalasi

Clone repository:

```bash
git clone https://github.com/rusiadristi/mini_cashflow.git
cd mini_cashflow
```

Pindahkan atau tempatkan folder project ke dalam direktori web server (`htdocs`):

```bash
# Contoh direktori pada XAMPP Windows:
C:\xampp\htdocs\mini_cashflow
```

## Konfigurasi Database

1. Buka XAMPP Control Panel dan aktifkan modul **Apache** dan **MySQL**.
2. Akses phpMyAdmin melalui browser:

```text
http://localhost/phpmyadmin
```

3. Buat database baru dengan nama:

```text
mini_cashflow
```

4. Import file database `mini_cashflow.sql` yang tersedia di dalam repository ini ke dalam database `mini_cashflow`.
5. Pastikan konfigurasi koneksi pada file `koneksi.php` sudah sesuai:

```php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "mini_cashflow";

$koneksi = mysqli_connect($host, $user, $password, $database);
```

## Menjalankan Aplikasi

Pastikan service Apache dan MySQL pada XAMPP sudah berjalan, kemudian buka URL berikut di browser:

```text
http://localhost/mini_cashflow/
```

## Fitur Utama Sistem

* **Ringkasan Saldo Real-Time (Card Summary):** Menampilkan akumulasi total pemasukan, total pengeluaran, dan saldo akhir secara instan dengan kalkulasi agregasi SQL `SUM()`.
* **Pencatatan Transaksi Masuk & Keluar (Create Data):** Formulir pencatatan transaksi yang dilengkapi validasi sisi server (*server-side*) dan sisi klien (*client-side*), pencegahan input nominal negatif atau kosong, serta keamanan parameterized query (*prepared statements* `bind_param`).
* **Visualisasi Riwayat Transaksi Berwarna:** Tabel riwayat transaksi dengan highlight baris visual (warna hijau lembut untuk dana masuk dan warna merah lembut untuk dana keluar) serta format mata uang Rupiah.
* **Filter Periode (Bulan & Tahun):** Navigasi penelusuran mutasi keuangan berdasarkan bulan dan tahun transaksi yang dipilih.
* **Manajemen Hapus Transaksi (Delete Data):** Fitur penghapusan catatan kas dengan dialog konfirmasi untuk menjaga integritas data keuangan.
* **Desain Responsif & Modern:** Dibangun menggunakan Bootstrap 5 dan Bootstrap Icons sehingga fleksibel diakses dari berbagai ukuran layar (mobile smartphone maupun desktop).

> Pastikan database `mini_cashflow.sql` telah di-import ke phpMyAdmin sebelum mengakses aplikasi.
