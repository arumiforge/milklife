# F11-17 Migrasi server ke Docker

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 23 November 2026 |

## Tujuan

Staging dan produksi bisa dipasang di VPS Ubuntu 24.04 dengan Docker Compose (PHP 8.5.13 di FrankenPHP, MySQL 8.4, cloudflared), dan produksi bisa dipindah dari Laragon sebelum pendaftaran tutup 30 November 2026 (K182).

## Hasil

- [x] `docker/Dockerfile`: FrankenPHP 1.14 + PHP 8.5.13, ekstensi intl, mysqli, gd, zip, exif, klien MySQL 8.4 dari repo APT resmi, Composer tanpa dev, `aset:minify` saat bangun, berjalan sebagai `www-data` di port 8080, dengan pemeriksaan kesehatan.
- [x] `docker/compose.yaml` dan `docker/.env.example`: app, db MySQL 8.4, cloudflared; tanpa `ports:`; subnet dan nama proyek per lingkungan; volume `writable`, `unggahan`, `mysql`; folder cadangan host.
- [x] `docker/Caddyfile`: hanya `index.php` dan `arumi.php` yang dijalankan, berkas tersembunyi ditolak, `HTTPS off` supaya `app.forceGlobalSecureRequests` bekerja di balik tunnel. `docker/php.ini`: batas unggah 10/12 MB, OPcache tanpa cek tanggal berkas.
- [x] `App::$proxyIPs` ditambah subnet dari `MILKLIFE_SUBNET_PROXY` (isian salah atau terlalu luas diabaikan); `public/arumi.php` memakai daftar yang sama.
- [x] `composer.json` `"php": "^8.5.13"`; CI memakai PHP 8.5, MySQL 8.4, Node 24, ditambah job "Image Docker"; `package.json` `engines.node >=24`.
- [x] Panduan pemasangan produksi revisi 13 ditulis ulang untuk Ubuntu dan Docker, termasuk pindah dari server Laragon dan jalan mundurnya; PDF dibuat ulang.
- [x] K182; ATURAN, KONTEKS, PETA-KODE, README, SETUP-CLOUD, `.env.example`, dan `.env.staging.example` disesuaikan.

## Berkas dan uji

`docker/*`, `.dockerignore`, `app/Config/App.php`, `public/arumi.php`, `app/Config/Cadangan.php`, `app/Libraries/Cadangan/PembuatCadangan.php`, `app/Libraries/PemeriksaPembaruan.php`, `composer.json`, `composer.lock`, `.github/workflows/uji.yml`, dokumen di atas. Uji `vendor/bin/phpunit --filter HttpsTerowongan|EnvContoh|EnvProduksi|PemantauArumi`.

## Selesai bila

`composer uji` lulus di PHP 8.5.13 dan MySQL 8.4 (image tahap `dasar`), job CI "Image Docker" hijau, dan di container: `migrate --all`, `sekolah:impor`, `data:awal`, `pembaruan:periksa`, `cadangan:buat`, dan pemulihan cadangan harian berjalan; halaman depan, masuk admin, dan Monitoring terbuka lewat jaringan compose; permintaan tanpa `X-Forwarded-Proto: https` dialihkan ke `https://`.

## Catatan

- Pengaturan basis data tetap di `.env` aplikasi, bukan variabel lingkungan compose: PHP mengubah titik di nama variabel lingkungan menjadi garis bawah, sehingga isian `.env` selalu menang. Nilai `DB_*` di `docker/.env` harus sama dengan `database.default.*`.
- FrankenPHP mengisi `HTTPS` dengan teks kosong untuk HTTP biasa, yang dibaca CodeIgniter sebagai HTTPS menyala; karena itu `env HTTPS off` di Caddyfile.
- Pemindahan produksi sendiri (bagian 8 panduan) dijalankan pengembang di server setelah PR ini digabung dan dicoba di staging.
