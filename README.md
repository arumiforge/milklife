# Web Pendaftaran MilkLife Athletics Challenge

Web pendaftaran MilkLife Athletics Challenge Seri 2 – 2026 untuk sekolah se-Karesidenan Pati: pendaftaran, cek pendaftaran, dan rekap panitia.

**Status:** Tahap 1 (kerangka) sedang dikerjakan. CodeIgniter sudah terpasang dan bisa dibuka; halaman depan dan dasbor admin masih halaman sementara.

## Mulai dari sini

| Ingin tahu | Baca |
|---|---|
| Apa yang dibangun | [`dokumen/final/rancangan-web-pendaftaran.md`](dokumen/final/rancangan-web-pendaftaran.md) |
| Sampai mana pengerjaannya | [`agent/PROGRES.md`](agent/PROGRES.md) |
| Aturan kerja di repo ini | [`agent/ALUR-KERJA.md`](agent/ALUR-KERJA.md) |
| Keputusan yang sudah diambil | [`agent/KEPUTUSAN.md`](agent/KEPUTUSAN.md) |

## Memasang di Laragon

Yang dibutuhkan: Laragon dengan PHP 8.2 atau lebih baru (disarankan 8.3), MySQL 8, dan Composer (sudah ada di Laragon).

1. Letakkan folder proyek di folder `www` Laragon dengan nama `milklife`, lalu tekan **Reload** di Laragon. Alamatnya menjadi `http://milklife.test`.
2. Buka terminal Laragon di folder proyek, lalu pasang pustaka:

   ```
   composer install
   ```

3. Salin `.env.example` menjadi `.env`, lalu buat kunci rahasia:

   ```
   copy .env.example .env
   php spark key:generate
   ```

   Isi `.env` bawaan sudah cocok untuk Laragon (pengguna MySQL `root` tanpa sandi).
4. Buat dua basis data kosong, `milklife` (data utama) dan `milklife_uji` (untuk pengujian, isinya dihapus setiap uji dijalankan). Bisa lewat HeidiSQL, atau di terminal:

   ```
   mysql -u root -e "CREATE DATABASE milklife CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -e "CREATE DATABASE milklife_uji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```

5. Buat tabel:

   ```
   php spark migrate --all
   ```

6. Jalankan semua uji. Hasilnya harus diakhiri `OK`:

   ```
   composer uji
   ```

7. Buka `http://milklife.test`. Untuk sementara halaman ini hanya bertuliskan "Pendaftaran sedang disiapkan".

Langkah impor daftar sekolah dan pembuatan akun admin ditambahkan di sini setelah fiturnya selesai.

## Struktur

| Folder | Isi |
|---|---|
| `agent/` | Konteks, progres, keputusan, alur kerja, dan berkas tugas |
| `dokumen/` | Draf, dokumen final, PDF, dan templat |
| `sumber/` | Bahan asli dari pemilik (flyer, juknis) |
| `data/` | Data siap impor (daftar sekolah) |
| `app/` | Kode aplikasi: pengendali, tampilan, konfigurasi, migrasi |
| `app/Views/tata_letak/` | Tata letak halaman: `publik.php`, `admin.php`, `polos.php` |
| `public/` | Folder yang dibuka peramban: `index.php` dan aset |
| `public/aset/` | CSS, JavaScript, dan pustaka tampilan (Bootstrap, Bootstrap Icons) |
| `tests/` | Uji: `unit/` (aturan murni), `basisdata/` (migrasi dan model), `fitur/` (halaman dan rute) |
| `writable/` | Sesi, log, dan berkas sementara buatan aplikasi; isinya tidak masuk git |
| `vendor/` | Pustaka dari Composer; dibuat oleh `composer install`, tidak masuk git |

## Teknologi

CodeIgniter 4.7, PHP 8.2+, MySQL 8, Nginx (Laragon), Bootstrap 5.3 dan Bootstrap Icons (disimpan lokal, tanpa CDN).
