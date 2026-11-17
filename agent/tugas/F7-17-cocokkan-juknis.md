# F7-17 Cocokkan aturan web dengan juknis resmi

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 16 November 2026 |

## Tujuan

Aturan dan data awal web sesuai dengan juknis resmi Seri 2 – 2026, dan halaman depan memakai istilah yang seragam (K112).

## Hasil

- [x] Juknis ditinjau terhadap data awal dan aturan: nomor lomba, jumlah inti, regu campuran, biaya, batas nomor individu, regu lebih dari satu, dan masa pendaftaran sudah sesuai
- [x] KU 12 lahir 2014–2015: data awal dan migrasi `2026-11-16-000200_KelompokUmur12SesuaiJuknis` (hanya bila KU 12 masih bawaan dan tidak beririsan)
- [x] Juknis di `sumber/juknis/petunjuk-teknis-seri2-2026.pdf`, dipakai berkas unduhan bawaan; berkas contoh kosong `data/unduhan/petunjuk-teknis.pdf` dihapus
- [x] Jadwal data awal: pertemuan teknik (technical meeting) dan pembagian nomor atlet (BIB) 6 Desember 2026 10.00 di Super Cafe SSA Kudus; waktu lomba pagi dan sore
- [x] Halaman depan: "Kelompok usia" menjadi "Kelompok umur"
- [x] Rancangan revisi 13, panduan produksi revisi 2, KEPUTUSAN K112, README sumber dan data

## Selesai bila

`composer uji` lulus lokal dan di CI; di server, KU 12 tampil 2014–2015 setelah `php spark migrate --all`.

## Catatan

- PR: [#87](https://github.com/arumiforge/milklife/pull/87).
- Jadwal dan unduhan di server produksi diubah admin di panel (data awal hanya untuk pemasangan baru).
