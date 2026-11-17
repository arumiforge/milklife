# F7-18 Istilah seragam mengikuti juknis

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 16 November 2026 |

## Tujuan

Sekolah, panitia, dan juknis memakai istilah yang sama: nomor atlet (BIB), ofisial, pertemuan teknik, dan mixed (K113).

## Hasil

- [x] Teks yang tampil di aplikasi (string PHP dan HTML, bukan komentar kode atau nama kolom) diganti dengan skrip berbasis tokenizer PHP; teks JavaScript diganti manual
- [x] "Nomor dada" → "nomor atlet (BIB)" / "No. Atlet"; "penanggung jawab" dan "official" → "ofisial"; "technical meeting" → "pertemuan teknik (technical meeting)"
- [x] Kategori `mix` ditulis "mixed" di nama nomor lomba (`NomorLombaModel::namaLengkap()`, label kategori, kueri rekap, daftar atlet, dan dasbor); kode di basis data tetap `mix`
- [x] Tanggal tanpa pukul pada keterangan pengurutan nomor atlet dan kaki bukti PDF
- [x] Rancangan revisi 14, panduan produksi, README, KONTEKS, KEPUTUSAN K113
- [x] Uji disesuaikan

## Selesai bila

`composer uji` lulus lokal dan di CI.

## Catatan

- PR: [#87](https://github.com/arumiforge/milklife/pull/87).
- Isian admin yang sudah tersimpan di server (judul jadwal, isi halaman depan) tidak diubah otomatis.
- Rute `admin/atlet/nomor-dada`, nama tabel, dan kolom tetap memakai nama lama.
