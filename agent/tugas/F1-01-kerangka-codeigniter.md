# F1-01 Kerangka CodeIgniter

| | |
|---|---|
| Status | selesai |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Repo berisi CodeIgniter 4.7 resmi yang bisa dijalankan dan diuji, lengkap dengan kesepakatan bersama (konfigurasi, aset, tata letak kerangka, rute) yang dipakai empat tugas paralel berikutnya.

Acuan: `dokumen/draf/rencana-implementasi-tahap-1.md`, bagian "Kesepakatan bersama".

## Hasil

- [x] CodeIgniter dari `composer create-project codeigniter4/appstarter` berada di akar repo (`app/`, `public/`, `writable/`, `tests/`, `spark`, `composer.json`, `composer.lock`), tanpa menimpa berkas yang sudah ada (`README.md`, `.env.example`, `.gitignore`, `agent/`, `dokumen/`, `data/`, `sumber/`)
- [x] `composer.json`: `php ^8.2`, `codeigniter4/framework ^4.7`, `codeigniter4/settings ^2.4`, `codeigniter4/translations ^4.7`; skrip `uji`
- [x] `.gitignore` mencakup `vendor/`, `.env`, isi `writable/` (kecuali `.htaccess` dan `index.html`), dan hasil PHPUnit
- [x] Berkas bawaan yang tidak dipakai dibuang: halaman selamat datang, `LICENSE` dan `README` bawaan appstarter, `.github/` bila ada
- [x] Konfigurasi: bahasa `id`, zona waktu `Asia/Jakarta`, `indexPage` kosong, CSRF aktif berbasis sesi, sesi berkas 7200 detik
- [x] `Config\Aplikasi` dengan `minify` dari `.env`
- [x] `app/Helpers/aplikasi_helper.php` dengan fungsi `aset()`, dimuat otomatis
- [x] Bootstrap 5.3.8 dan Bootstrap Icons 1.13.2 di `public/aset/vendor/`, dengan `README.md` yang mencatat versi dan asal
- [x] Tata letak kerangka `publik.php`, `admin.php`, `polos.php` di `app/Views/tata_letak/` dengan bagian `judul`, `isi`, `skrip`; memuat Bootstrap lokal; belum bergaya
- [x] Rute `/` (halaman sementara "Pendaftaran sedang disiapkan"), `/admin`, dan `/admin/dasbor` (halaman sementara)
- [x] Grup basis data `tests` dari `.env`, `phpunit.xml.dist`, folder `tests/unit`, `tests/basisdata`, `tests/fitur`
- [x] Uji: halaman depan terbuka, dasbor terbuka dengan sesi admin, `aset()` memilih berkas yang benar, tidak ada alamat `http` luar di tata letak
- [x] `.env.example` tetap berlaku apa adanya; kunci baru ditambahkan bila perlu, tanpa nilai rahasia
- [x] `README.md` memuat cara memasang di Laragon dan menjalankan uji

## Selesai bila

- `composer install`, `php spark migrate --all`, dan `composer uji` berjalan tanpa galat pada salinan baru repo dengan `.env` dari `.env.example`.
- `php spark serve` menampilkan `/` dan `/admin/dasbor` dengan Bootstrap lokal, tanpa permintaan ke luar.
- Tidak ada folder `.github/`.

## Catatan

- Tanpa CI GitHub (K15, K16). Folder `.github/` bawaan appstarter tidak disalin.
- Halaman sementara ditulis dalam bahasa Indonesia yang ramah; tampilan jadinya dikerjakan F1-05.
- Terpasang: CodeIgniter 4.7.6, `codeigniter4/settings` 2.4.0, `codeigniter4/translations` 4.7.4, PHPUnit 10.5.
- Berkas pengaturan PHPUnit bernama `phpunit.dist.xml`, mengikuti nama bawaan appstarter 4.7 (rencana menyebut `phpunit.xml.dist`; PHPUnit membaca keduanya). Isinya tiga rangkaian uji: `unit`, `basisdata`, `fitur`.
- Kelas dasar uji di `tests/_support/`: `Tests\Support\UjiBasisData` (basis data uji, migrasi semua namespace sebelum setiap uji) dan `Tests\Support\UjiHalaman` (uji halaman, dengan `SESI_ADMIN`).
- `.env.example` tidak diubah. `session.savePath = null` terbaca sebagai teks "null", jadi `Config\Session` mengartikannya (dan nilai kosong) sebagai `writable/session`.
- Mode ketat MySQL (`strictOn`) dinyalakan untuk grup `default` dan `tests`, supaya data yang tidak muat ditolak, bukan dipotong diam-diam, dan uji berperilaku sama dengan produksi.
- CSRF dipasang sebagai filter global `before` (memeriksa `POST` dan metode pengubah lain), bukan lewat `$methods['POST']`, supaya permintaan yang menyamarkan metodenya tetap diperiksa.
- Rute admin ditulis dalam grup `admin` di `app/Config/Routes.php`, sehingga F1-04 cukup menambahkan filter pada grup itu.
- Uji tata letak kerangka bernama `tests/fitur/KerangkaTataLetakTest.php`, bukan `TataLetak*`, supaya tidak bertabrakan dengan berkas uji milik F1-05.
- `composer.json` memakai `"license": "proprietary"`; lisensi repo diputuskan nanti (K20).
- Digabung lewat PR #3 pada 11 November 2026.
