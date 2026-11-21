# F9-11 Metode klasemen

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Admin bisa memilih klasemen juara umum berdasarkan medali (juknis) atau poin per peringkat.

## Hasil

- [x] Pengaturan > Hasil: metode Medali (bawaan) atau Poin per peringkat (poin peringkat 1–8, bawaan 7-5-4-3-2-1)
- [x] Kolom poin tampil di klasemen admin, publik, PDF, dan Excel; pemecah seri metode poin: juara I, II, III
- [x] `Hasil/Klasemen` tetap murni dengan uji unit kedua metode
- [x] `agent/ATURAN.md` (K124 butir 5) dan rancangan final diperbarui

## Berkas dan uji

`app/Libraries/Hasil/Klasemen.php`, `UnduhanKlasemen.php`, pengaturan hasil, views klasemen. Uji: `HasilKlasemenTest`, `KlasemenSekolahTest`.

## Selesai bila

Kedua metode menghasilkan urutan benar pada data contoh; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 7a.
