# RailTick

RailTick adalah aplikasi web pemesanan tiket kereta berbasis PHP dan MySQL. Aplikasi ini menyediakan halaman publik untuk pencarian jadwal, dashboard pengguna untuk pemesanan tiket, panel admin untuk mengelola data operasional, serta dashboard petugas untuk melihat jadwal tugas.

## Fitur Utama

- Pencarian jadwal kereta berdasarkan stasiun asal, tujuan, dan tanggal.
- Registrasi dan login pengguna.
- Pemesanan tiket dengan pemilihan kursi.
- Riwayat pemesanan tiket pengguna.
- Dashboard admin untuk mengelola:
  - stasiun,
  - kereta,
  - kursi,
  - rute,
  - jadwal,
  - perjalanan harian,
  - pemesanan,
  - petugas,
  - penugasan kru.
- Dashboard petugas untuk role `masinis`, `kondektur`, dan `pramuniaga`.
- Ekspor laporan pemesanan dari panel admin.

## Teknologi

- PHP native
- MySQL/MariaDB
- XAMPP
- Tailwind CSS CDN
- Font Awesome CDN

## Struktur Folder

```text
transportasi/
+-- admin/                         # Halaman dan modul admin
+-- assets/                        # File CSS tambahan
+-- auth/                          # Login, register, logout
+-- config/                        # Koneksi database dan status operasional
+-- pictures/                      # Gambar aplikasi
+-- staff/                         # Dashboard petugas
+-- user/                          # Dashboard, jadwal, pesan tiket, riwayat
+-- about_us.php
+-- contact.php
+-- database_operasional_update.sql
+-- index.php
```

## Kebutuhan Sistem

- XAMPP dengan Apache dan MySQL aktif.
- PHP 7.4 atau lebih baru.
- Browser modern.

## Cara Menjalankan Project

1. Clone atau salin project ke folder `htdocs`.

   ```powershell
   C:\xampp\htdocs\transportasi
   ```

2. Jalankan Apache dan MySQL dari XAMPP Control Panel.

3. Buat database MySQL dengan nama:

   ```sql
   CREATE DATABASE sistem_transportasi_kereta;
   ```

4. Import struktur dan data database utama jika tersedia.

   Setelah itu jalankan file update operasional:

   ```sql
   database_operasional_update.sql
   ```

   File tersebut menambahkan tabel dan data untuk fitur petugas, perjalanan harian, dan penugasan kru.

5. Pastikan konfigurasi database di `config/koneksi.php` sesuai dengan environment lokal:

   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "sistem_transportasi_kereta";
   ```

6. Buka aplikasi melalui browser:

   ```text
   http://localhost/transportasi/
   ```

## Akun Contoh Petugas

File `database_operasional_update.sql` menyediakan akun contoh berikut:

| Role | Username | Password |
| --- | --- | --- |
| Masinis | `masinis1` | `masinis123` |
| Kondektur | `kondektur1` | `kondektur123` |
| Pramuniaga | `pramuniaga1` | `pramuniaga123` |

Untuk akun pengguna biasa, gunakan halaman register. Untuk akun admin, pastikan data user dengan role `admin` tersedia di database.

## Halaman Penting

- Beranda: `index.php`
- Login: `auth/login.php`
- Register: `auth/register.php`
- Dashboard user: `user/dashboard.php`
- Dashboard admin: `admin/index.php`
- Dashboard petugas: `staff/dashboard.php`

## Catatan Keamanan

Project ini masih menggunakan autentikasi sederhana dengan password plain text. Untuk penggunaan produksi, disarankan untuk:

- menggunakan `password_hash()` dan `password_verify()`,
- memindahkan konfigurasi database ke file environment,
- menambahkan validasi input yang lebih ketat,
- menggunakan prepared statement untuk query database,
- menonaktifkan pesan error detail di production.

## Lisensi

Project ini dibuat untuk kebutuhan pembelajaran dan pengembangan sistem informasi pemesanan tiket kereta.
