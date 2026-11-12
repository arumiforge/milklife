# Web Pendaftaran MilkLife Athletics Challenge

Web pendaftaran MilkLife Athletics Challenge Seri 2 – 2026 untuk sekolah se-Karesidenan Pati: pendaftaran, cek pendaftaran, dan rekap panitia.

**Status:** Tahap 1 (kerangka) selesai. Aplikasi bisa dipasang, daftar sekolah bisa diimpor, dan admin bisa masuk. Halaman pengaturan, pendaftaran, cek, dan rekap dikerjakan di tahap berikutnya.

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

6. Isi daftar sekolah (4.729 sekolah di lima kabupaten). Perintah ini aman dijalankan ulang kapan saja:

   ```
   php spark sekolah:impor
   ```

7. Isi data awal dari flyer Seri 2: data acara, jadwal pendaftaran, tanggal patokan usia, lima kelompok umur beserta awalan nomor dadanya (KU 8 = 1 sampai KU 18 = 5), dan semua nomor lomba. Perintah ini hanya dijalankan **sekali** saat pemasangan; bila data sudah ada, perintah menolak supaya isian admin tidak tertimpa.

   ```
   php spark data:awal
   ```

   Batas usia, jenjang sekolah, biaya individu, dan batas anggota nomor beregu sengaja dikosongkan; admin mengisinya dari menu **Pengaturan**. Awalan nomor dada bisa diubah di sana juga.
8. Buat akun admin. Perintah menanyakan nama pengguna, nama, dan kata sandi (minimal 8 karakter). Perintah yang sama dipakai untuk mengganti kata sandi.

   ```
   php spark admin:buat
   ```

   Di cmd Windows kata sandi terlihat saat diketik. Supaya tersembunyi, jalankan perintah ini dari Git Bash.
9. Jalankan semua uji. Hasilnya harus diakhiri `OK`:

   ```
   composer uji
   ```

10. Buka `http://milklife.test` untuk halaman depan, dan `http://milklife.test/admin/masuk` untuk panel admin.

### Batas unggah berkas

Admin mengunggah gambar (paling besar 2 MB) dan berkas unduhan seperti juknis (paling besar 10 MB). Batas bawaan PHP biasanya hanya 2 MB, jadi naikkan dulu:

1. Di Laragon, klik kanan → **PHP** → **php.ini**.
2. Ubah atau tambahkan dua baris ini:

   ```
   upload_max_filesize = 10M
   post_max_size = 12M
   ```

3. Simpan, lalu tekan **Reload** di Laragon.

Berkas unggahan disimpan di `public/unggahan/` (dibuat otomatis, tidak masuk git). Sertakan folder ini saat membuat cadangan.

Bila admin mengunggah berkas yang lebih besar dari `post_max_size`, halaman kembali ke formulir dengan pesan yang menyebut batasnya, bukan halaman galat.

### Cloudflare Tunnel

Web dibuka dari internet lewat Cloudflare Tunnel (K31). Susunannya:

- `cloudflared` dipasang di **komputer server yang sama** dengan Laragon, dan meneruskan alamat publik ke Nginx lokal, misalnya `http://milklife.test` atau `http://127.0.0.1:80`.
- Di mata aplikasi, semua pengunjung lewat tunnel datang dari komputer itu sendiri (`127.0.0.1` atau `::1`). Alamat pengunjung yang asli dikirim Cloudflare dalam kepala `CF-Connecting-IP`.
- Aplikasi memercayai kepala `CF-Connecting-IP` **hanya** dari `127.0.0.1` dan `::1` (`app/Config/App.php`, `$proxyIPs`). Permintaan dari alamat lain, misalnya komputer lain di jaringan sekolah, tetap dicatat dengan alamatnya sendiri walaupun membawa kepala itu, jadi alamat tidak bisa dipalsukan.
- Dengan begitu, pembatasan percobaan masuk admin (dan nanti pembatasan laju di halaman pendaftaran dan cek) dihitung per pengunjung, bukan semua pengunjung dianggap satu alamat.

Jangan jalankan `cloudflared` di komputer lain yang meneruskan ke server ini; bila terpaksa, alamat komputer itu harus ditambahkan ke `$proxyIPs`.

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
| `public/unggahan/` | Gambar dan berkas unggahan admin; tidak masuk git |
| `public/aset/` | CSS, JavaScript, dan pustaka tampilan (Bootstrap, Bootstrap Icons) |
| `tests/` | Uji: `unit/` (aturan murni), `basisdata/` (migrasi dan model), `fitur/` (halaman dan rute) |
| `writable/` | Sesi, log, dan berkas sementara buatan aplikasi; isinya tidak masuk git |
| `vendor/` | Pustaka dari Composer; dibuat oleh `composer install`, tidak masuk git |

## Teknologi

CodeIgniter 4.7, PHP 8.2+, MySQL 8, Nginx (Laragon), Bootstrap 5.3 dan Bootstrap Icons (disimpan lokal, tanpa CDN).
