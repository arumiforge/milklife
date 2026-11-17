# F7-21 Uji lengkap di MariaDB dan pemeriksaan peramban

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Memastikan web juga berjalan di MariaDB (selain MySQL 8) dan semua halaman utama berfungsi di peramban, di layar lebar maupun ponsel.

## Hasil

- [x] `composer uji` dijalankan penuh di MariaDB 10.11 (paket Ubuntu 24.04). Putaran pertama: 1.417 dari 1.423 uji lulus; 6 gagal, semuanya sudah diperbaiki:
  - 5 uji struktur tabel membandingkan tipe kolom apa adanya, padahal MariaDB menulis lebar tampilan (`int(10) unsigned`, MySQL 8: `int unsigned`) dan nilai awal berbeda (`NULL` sebagai teks, teks bertanda kutip). Uji kini menyeragamkan keduanya lewat `UjiBasisData::bakukanKolom()`. Bukan masalah aplikasi.
  - Grafik "nomor lomba terbanyak" di dasbor tidak menentukan urutan bila jumlah peserta dan urutannya sama, sehingga hasilnya beda antar-mesin basis data. Kini diurutkan juga menurut ID nomor lomba (`RingkasanDasbor::nomorLomba()`).
- [x] Pemeriksaan peramban (Playwright, Chromium) di atas MariaDB dengan 4.766 sekolah asli:
  - Wizard pendaftaran dari langkah 1 sampai kirim, termasuk usulan nama sekolah, foto ofisial, dua atlet, dan bukti bayar; tanpa galat konsol atau HTTP
  - Panel: semua halaman menu dan Pengaturan terbuka (200); tandai Lunas memberi nomor atlet; setujui usulan nama; Tambahkan nama desa (494 sekolah); unduhan Excel dan PDF rekap
  - Cek Pendaftaran lewat ID dan NPSN menampilkan nama baru, status Lunas, dan nomor atlet; bukti PDF terunduh
  - `php spark pembaruan:periksa` di MariaDB: basis data kosong, setelah migrasi, dan setelah impor (beres)
- [x] Perbaikan tampilan ponsel yang ditemukan:
  - Halaman Tambahkan nama desa bergeser 17 piksel ke samping: teks tersembunyi "Tindakan" (posisi absolut) di kepala tabel keluar dari wadah gulir. Wadah tabel kini `position-relative`
  - Halaman berjudul panjang (Urutkan Nomor Atlet (BIB)) mendorong tombol Keluar ke luar layar: kelompok tombol di kepala halaman tidak lagi menyusut, judul yang dipotong dengan elipsis. Diperiksa di lebar 320, 360, 412, 768, dan 1024 piksel

## Selesai bila

`composer uji` lulus penuh di MariaDB dan MySQL 8 lokal, dan CI lulus.

## Catatan

- MariaDB dijalankan dari paket resmi Ubuntu yang diekstrak ke folder sementara di port 3307, di samping MySQL; tidak ada yang dipasang permanen.
- CI GitHub tetap memakai MySQL 8, sesuai produksi.
