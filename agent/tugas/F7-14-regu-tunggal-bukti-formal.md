# F7-14 Regu tunggal tanpa nama regu dan bukti PDF lebih formal

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 15 November 2026 |

## Tujuan

Sekolah yang hanya mendaftarkan satu regu di satu nomor tidak perlu memikirkan nama regu: wizard menyembunyikan pilihan regu, dan wizard, cek, serta bukti tidak menulis "Regu A". Bukti PDF memiliki kepala di setiap halaman dan tata letak data yang lebih formal (K108).

## Hasil

- [x] `Regu::nama()` menerima `tunggal` (nama nomor saja); `KelengkapanTim::periksa()` dan pesan konfirmasi susulan memakainya bila sekolah hanya punya satu regu di nomor itu (bukan untuk admin)
- [x] Ringkasan biaya membawa `nomor_lomba_id` dan `regu_tunggal` per regu
- [x] `DataPendaftaran` menghitung regu sekolah per nomor dari semua pendaftaran ber-NPSN sama (tanpa NPSN: pendaftaran itu saja) dan menambah `regu_tunggal`; cek dan bukti mengikutinya
- [x] Wizard: pilihan regu tersembunyi selama sekolah punya satu regu di nomor itu, atlet otomatis masuk regu itu (cadangan bila inti lengkap); tautan "Daftarkan regu lain" membuka pilihan dan memilih regu baru; nomor dengan satu regu yang dibolehkan tidak pernah menampilkan pilihan; kartu daftar sementara, konfirmasi susulan, dan ringkasan tanpa nama regu tunggal
- [x] Bukti PDF: kepala (logo, judul, garis oranye) dan kaki di setiap halaman (`@page` mPDF, margin atas mengikuti tinggi kepala); nama sekolah di blok sendiri paling atas; data lain dalam tabel berlabel (ID, tanggal, NPSN, jenjang, kabupaten, status, penanggung jawab); sel gabungan No, nomor lomba, regu, dan biaya per regu; kolom regu hanya bila ada regu yang bukan tunggal
- [x] Rancangan final revisi 11, KONTEKS, KEPUTUSAN (K108)
- [x] Uji diperbarui dan ditambah

## Selesai bila

`composer uji` lulus lokal dan di CI; wizard satu regu dan dua regu serta bukti PDF satu dan tiga halaman diperiksa di peramban.

## Catatan

- PR: [#86](https://github.com/arumiforge/milklife/pull/86).
- Tanpa migrasi. Setelah memasang versi ini di server: `php spark aset:minify`.
- Bila sekolah menambah regu kedua lewat susulan, bukti yang diunduh ulang menulis "Regu A" untuk regu pertama; cetakan lama tidak (diterima pemilik).
