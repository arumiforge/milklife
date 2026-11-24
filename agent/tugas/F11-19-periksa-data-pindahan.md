# F11-19 Pemeriksaan pembaruan untuk data pindahan dari Laragon

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 25 November 2026 |

## Tujuan

Saat produksi pindah dari Laragon ke Docker (F11-17, panduan produksi bagian 8), basis data lama dipulihkan lalu dilengkapi dengan `migrate --all`. Basis data dari server sebelum Tahap 9 belum punya tanya-jawab Bantuan, foto carousel, dan album galeri bawaan, tetapi `pembaruan:periksa` sebelumnya tetap menulis "beres". Setelah tugas ini, ketiganya ikut disebut beserta perintahnya.

## Hasil

- [x] `pembaruan:periksa` menyebut `faq:awal` bila tabel tanya-jawab kosong.
- [x] `pembaruan:periksa` menyebut `halaman-depan:carousel` bila pengaturan carousel belum pernah diisi. Daftar kosong karena admin menghapus semua foto dianggap sudah diatur.
- [x] `pembaruan:periksa` menyebut `galeri:awal` bila album bawaan belum pernah dipasang dan galeri masih kosong.
- [x] Urutan perintah: tabel skor, tanya-jawab, carousel, galeri, lalu `data:sesuaikan`, karena `data:sesuaikan` menambah tanya-jawab Tahap 11 ke tanya-jawab yang sudah ada.
- [x] Rancangan revisi 42 dan README.
- [x] Panduan produksi revisi 15 bagian 8 (K185): zip Backup data ikut diunduh sebagai pembanding dan jalan cadangan, isian `cadangan.folder` bila `cadangan:buat` menolak, pemeriksaan jumlah atlet, langkah jalur zip bila basis data gagal dimuat, dan jalan mundur yang memperbarui kode Laragon dulu.

## Berkas dan uji

`app/Libraries/PemeriksaPembaruan.php`, `tests/basisdata/PeriksaPembaruanTest.php`, `README.md`, `dokumen/final/rancangan-web-pendaftaran.md`, `dokumen/final/panduan-pemasangan-produksi.md` dan PDF-nya, K185, ATURAN. Uji `vendor/bin/phpunit --filter PeriksaPembaruan`.

## Selesai bila

Basis data dari server Laragon (Tahap 8) yang dipulihkan lalu dimigrasi menghasilkan `[PERLU]` untuk tabel skor, tanya-jawab, carousel, dan galeri. Setelah perintah yang disebut dijalankan, `pembaruan:periksa` menulis "Tidak ada perintah lain yang perlu dijalankan".

## Catatan

- Dicoba dengan dump basis data dan zip Backup data dari server Laragon pada 25 November 2026, di kontainer pengembang dan tanpa menyimpan isinya. Jalur zip (`pendaftaran:impor`) juga lulus: jumlah pendaftaran, atlet, nomor, penanggung jawab, bukti bayar, regu, gabungan, atlet ditolak, dan riwayat sama dengan dump.
- Pemilik memilih jalur dump lengkap dengan zip sebagai cadangan (K185).
- Pengaturan admin seperti batas tahun lahir kelompok umur, rekening, jadwal, dan gambar unggahan hanya ikut lewat jalur dump, tidak lewat zip pendaftaran.
