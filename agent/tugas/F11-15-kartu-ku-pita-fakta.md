# F11-15 Kartu KU dan pita fakta halaman depan

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 23 November 2026 |

## Tujuan

Pita fakta di bawah hero lebih ramping di ponsel, dan kartu kelompok umur lebih khas: angka KU memenuhi kartu dan terpotong kotak, dengan jumlah nomor berupa ikon dan angka di bawahnya (K179, menggantikan bagian kartu KU di K175 butir 3).

## Hasil

- [x] Pita fakta: di ponsel "Supersoccer Arena" ditulis "SSA" sehingga kotaknya satu baris; nama lengkap tetap tampil mulai layar sm dan dibaca pembaca layar. Tempat lain tidak disingkat.
- [x] Angka KU memenuhi kartu, ukurannya mengikuti lebar kartu; angka melewati tepi bawah kartu dan digit kedua melewati tepi kanan, terpotong kotak. Isian bertekstur G11-01 dan cadangan gradien tetap.
- [x] Jumlah nomor di bawah, cukup ikon dan angka (boleh menimpa angka KU), dengan nama aksesibel lengkap; jumlah 0 tidak tampil. Tanpa garis miring dan tanpa panah pojok.
- [x] Diperiksa dengan Playwright di iPhone SE (320 px), iPhone 13, tablet, dan desktop: tanpa gulir mendatar, kartu rapi di semua kelompok umur.

## Berkas dan uji

`app/Views/beranda/_kepala.php`, `public/aset/tailwind/masukan.css` (bagian F11-15), CSS hasil bangun. Uji `--filter HalamanDepan|Beranda|TataLetak`.

## Selesai bila

Pita fakta dan kartu KU sesuai K179 di ponsel dan desktop, dengan atau tanpa gambar G11-01. `composer uji` lulus.
