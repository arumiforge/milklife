# F7-16 Urutkan ulang nomor dada per sekolah

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 16 November 2026 |

## Tujuan

Sebelum technical meeting, panitia bisa membuat ulang semua nomor dada sehingga nomor atlet satu sekolah berurutan, dan official melihat keterangannya di Cek Pendaftaran dan bukti PDF (K111).

## Hasil

- [x] `UrutkanNomorDada`: nomor baru untuk atlet pendaftaran Lunas, mulai dari `001` per awalan kelompok umur, sekolah urut menurut atlet pertamanya, atlet urut mendaftar; nomor atlet Menunggu dikosongkan; `nomor_terakhir` disamakan; awalan kurang = tidak ada yang berubah; waktu dan admin disimpan (`Config\NomorDada`)
- [x] Halaman `admin/atlet/nomor-dada` (ringkasan per kelompok umur, kecukupan awalan, waktu terakhir, ketik URUTKAN + jendela konfirmasi); tombol "Urutkan nomor dada" di halaman Atlet
- [x] Keterangan di Cek Pendaftaran (di atas tabel atlet), bukti PDF (dasar halaman terakhir), halaman Atlet, dan rincian pendaftaran
- [x] `nomor-dada:reset` menghapus keterangan pengurutan
- [x] Rancangan revisi 13, KEPUTUSAN K110–K111, KONTEKS
- [x] Uji `PanelUrutkanNomorDadaTest` dan `PanelUrutkanNomorDadaHalamanTest`

## Selesai bila

`composer uji` lulus lokal dan di CI.

## Catatan

- PR: [#87](https://github.com/arumiforge/milklife/pull/87), bersama F7-15 dan F7-17 karena sesi ini memakai satu cabang.
- Tanpa migrasi. Pendaftaran yang Lunas setelah pengurutan mendapat nomor lanjutan; urutkan lagi sebelum technical meeting bila perlu.
