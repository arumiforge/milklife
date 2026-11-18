# F7-06 Uji menyeluruh

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Seluruh web terbukti bekerja bersama di mode produksi dengan minify aktif, di ponsel dan komputer, sebelum dipasang.

## Hasil

- [x] Pemasangan bersih mengikuti panduan F7-05 sejauh berlaku di Linux, `CI_ENVIRONMENT = production`, `aplikasi.minify = true`
- [x] Semua halaman publik dan admin dicoba di Chromium 390 dan 1280 piksel tanpa galat konsol, termasuk wizard dengan susulan, cek, bukti PDF, panel lengkap, rekap Excel/PDF, pengaturan, ganti sandi dengan dua sesi, halaman galat
- [x] `tests/fitur/AlurMenyeluruhTest.php`: alur lengkap dari simpan pendaftaran sampai hapus, dengan minify aktif
- [x] Skenario B1 (token CSRF lama di dua tab), B2 (kirim ulang setelah sambungan putus), Turnstile dengan kunci uji Cloudflare, dan fitur Tahap 8 (daftar ulang, BIB, daftar peserta, hasil, klasemen) ikut dicoba (K118, K119)
- [x] Perbaikan sambungan seperlunya, disebut satu per satu di PR
- [x] Hasil (dicoba, diperbaiki, tersisa) ditulis di bagian Catatan

## Selesai bila

`composer uji` lulus lokal dan di CI pada `main` setelah F7-01 sampai F7-05 digabung, dan daftar hasil di berkas ini lengkap.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Uji menyeluruh". Asumsi 7. Gelombang 3. Tangkapan layar tidak masuk git.

Diperluas 17 November 2026 (K119): dijalankan setelah F7-24 sampai F7-30 dan F8-01 sampai F8-05 masuk `main`, bersama F8-06.

### Hasil 18 November 2026 (bersama F8-06)

**Dicoba**

- Pemasangan bersih di kontainer Linux mengikuti panduan bagian 3–6: `git clone`, `composer install --no-dev --optimize-autoloader`, basis data `utf8mb4_unicode_ci` dengan pengguna MySQL khusus (bukan root), `.env` dari `.env.example` + `php spark key:generate`, `CI_ENVIRONMENT = production`, `logger.threshold = 4`, `aplikasi.minify = true`, lalu `migrate --all`, `sekolah:impor` (4.766 sekolah), `data:awal`, `admin:buat`, `aset:minify` (31 berkas), dan `pembaruan:periksa` (semua beres). Yang tidak berlaku di Linux: Laragon, Nginx, Cloudflare Tunnel; server memakai `php -S` dengan `rewrite.php` CodeIgniter, dan karena tanpa HTTPS `app.forceGlobalSecureRequests` serta `cookie.secure` tetap `false`.
- Chromium 390 dan 1280 piksel terhadap pemasangan itu (mode produksi, minify, CSP menyala), dengan pemeriksaan galat konsol, pelanggaran CSP, dan jawaban HTTP 4xx/5xx:
  - Wizard lengkap: cari NPSN, ofisial dengan foto, dua atlet lewat pemilih tanggal, biaya, unggah bukti transfer, jendela konfirmasi, halaman selesai; pendaftaran susulan dari NPSN yang sama; Cek Pendaftaran lewat ID; bukti PDF.
  - Panel: masuk, daftar pendaftaran dan Excel, unggah bukti bayar admin, Lunas (nomor atlet terbit) dan Lunas susulan yang digabung (K109), halaman Ubah, Atlet dan Excel, kelima rekap Excel dan PDF, rekap gabungan, Backup data, semua halaman pengaturan, hapus pendaftaran, halaman galat 404, 400, dan 404 admin.
  - Ganti sandi dengan dua sesi: sesi lain langsung diminta masuk lagi.
  - Tahap 8: daftar ulang (cari ID, hadir semua), cetak BIB PDF, daftar peserta PDF/Excel, hasil (Final, isi prestasi, Terbitkan, PDF/Excel), klasemen admin dan publik (PDF/Excel).
  - Semua langkah tanpa galat konsol dan tanpa pelanggaran CSP; jawaban 4xx hanya halaman galat yang memang dibuka.
- B1 (K118): formulir Pengaturan Acara dibuka di dua tab, disimpan dari tab pertama lalu dari tab kedua dengan token lama: keduanya tersimpan.
- B2 (K118): jawaban kiriman pertama wizard diputus setelah server menyimpan; wizard menampilkan "Belum tersambung ke server. Periksa sambungan internet, lalu coba lagi. Isian tetap tersimpan."; kirim ulang menghasilkan ID yang sama dan hanya satu baris pendaftaran.
- Turnstile dengan kunci uji Cloudflare (`1x00000000000000000000AA` dan pasangan rahasianya): widget termuat lewat CSP tanpa pelanggaran dan peramban menerima token; siteverify menjawab `success`, tetapi server menolak dengan pesan ramah (403) karena jawaban kunci uji tidak memuat `action` dan memakai nama host `example.com`. Pemeriksaan `action` dan nama host itu disengaja (K122), jadi jalur berhasil hanya bisa dicoba dengan kunci asli di server.
- `tests/fitur/AlurMenyeluruhTest.php` (minify aktif): simpan pendaftaran dan susulan dengan konfirmasi tim, kirim ulang idempoten, halaman diperkecil, cek, bukti PDF, bukti bayar, Lunas dan gabung, Ubah, rekap Excel/PDF, daftar ulang, BIB, daftar peserta, hasil dan Terbitkan, halaman publik hasil dan klasemen, hapus (hasil ikut terhapus, NISN bisa didaftarkan lagi).

**Diperbaiki**

- Tidak ada perbaikan sambungan di PR ini. Temuan saat menyalakan CSP (gaya dan skrip sebaris, `<style>` ApexCharts, halaman galat) sudah diperbaiki di F7-32 (#115).

**Tersisa (untuk pemilik)**

- Turnstile dengan kunci asli setelah HTTPS lewat Cloudflare Tunnel berjalan (panduan bagian 7): coba kirim pendaftaran, Cek Pendaftaran, dan masuk admin sekali.
- Pemasangan nyata di Laragon Windows mengikuti panduan (asumsi 7 rencana Tahap 7).
- Konfirmasi tim susulan beregu dicoba lewat uji fitur; di peramban yang dicoba susulan nomor individu.
- Di mode produksi halaman galat 404 dikirim tanpa kepala CSP (tampilannya benar). Rincian admin pendaftaran yang sudah digabung menampilkan halaman tidak ditemukan, bukan dialihkan ke pendaftaran gabungannya.
