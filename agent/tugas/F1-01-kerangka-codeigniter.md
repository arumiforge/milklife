# F1-01 Kerangka CodeIgniter

| | |
|---|---|
| Status | rencana |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Repo berisi CodeIgniter 4.7 resmi yang bisa dijalankan dan diuji, lengkap dengan kesepakatan bersama (konfigurasi, aset, tata letak kerangka, rute) yang dipakai empat tugas paralel berikutnya.

Acuan: `dokumen/draf/rencana-implementasi-tahap-1.md`, bagian "Kesepakatan bersama".

## Hasil

- [ ] CodeIgniter dari `composer create-project codeigniter4/appstarter` berada di akar repo (`app/`, `public/`, `writable/`, `tests/`, `spark`, `composer.json`, `composer.lock`), tanpa menimpa berkas yang sudah ada (`README.md`, `.env.example`, `.gitignore`, `agent/`, `dokumen/`, `data/`, `sumber/`)
- [ ] `composer.json`: `php ^8.2`, `codeigniter4/framework ^4.7`, `codeigniter4/settings ^2.4`, `codeigniter4/translations ^4.7`; skrip `uji`
- [ ] `.gitignore` mencakup `vendor/`, `.env`, isi `writable/` (kecuali `.htaccess` dan `index.html`), dan hasil PHPUnit
- [ ] Berkas bawaan yang tidak dipakai dibuang: halaman selamat datang, `LICENSE` dan `README` bawaan appstarter, `.github/` bila ada
- [ ] Konfigurasi: bahasa `id`, zona waktu `Asia/Jakarta`, `indexPage` kosong, CSRF aktif berbasis sesi, sesi berkas 7200 detik
- [ ] `Config\Aplikasi` dengan `minify` dari `.env`
- [ ] `app/Helpers/aplikasi_helper.php` dengan fungsi `aset()`, dimuat otomatis
- [ ] Bootstrap 5.3.8 dan Bootstrap Icons 1.13.2 di `public/aset/vendor/`, dengan `README.md` yang mencatat versi dan asal
- [ ] Tata letak kerangka `publik.php`, `admin.php`, `polos.php` di `app/Views/tata_letak/` dengan bagian `judul`, `isi`, `skrip`; memuat Bootstrap lokal; belum bergaya
- [ ] Rute `/` (halaman sementara "Pendaftaran sedang disiapkan"), `/admin`, dan `/admin/dasbor` (halaman sementara)
- [ ] Grup basis data `tests` dari `.env`, `phpunit.xml.dist`, folder `tests/unit`, `tests/basisdata`, `tests/fitur`
- [ ] Uji: halaman depan terbuka, dasbor terbuka dengan sesi admin, `aset()` memilih berkas yang benar, tidak ada alamat `http` luar di tata letak
- [ ] `.env.example` tetap berlaku apa adanya; kunci baru ditambahkan bila perlu, tanpa nilai rahasia
- [ ] `README.md` memuat cara memasang di Laragon dan menjalankan uji

## Selesai bila

- `composer install`, `php spark migrate --all`, dan `composer uji` berjalan tanpa galat pada salinan baru repo dengan `.env` dari `.env.example`.
- `php spark serve` menampilkan `/` dan `/admin/dasbor` dengan Bootstrap lokal, tanpa permintaan ke luar.
- Tidak ada folder `.github/`.

## Catatan

- Tanpa CI GitHub (K15, K16).
- Halaman sementara ditulis dalam bahasa Indonesia yang ramah; tampilan jadinya dikerjakan F1-05.
