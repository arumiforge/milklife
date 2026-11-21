# F9-06 Galeri tautan luar

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Pengunjung melihat album foto acara dan langsung dibawa ke Instagram atau Google Drive panitia.

## Hasil

- [ ] Tabel `galeri` (judul, keterangan, tautan, sampul unggahan, urutan, aktif), kelola di Pengaturan > Galeri, sampul dikecilkan ke WebP
- [ ] `/galeri` kartu bersampul, tautan dibuka di tab baru dengan `rel="noopener"`; sakelar tampil (bawaan mati)
- [ ] Album bawaan "Seri sebelumnya" bersampul foto `bahan-rombak`, tautannya diisi admin
- [ ] Ikut Backup data/impor (K76)

## Berkas dan uji

Migrasi `galeri`, model, `GaleriController`, `Admin/Pengaturan/GaleriController`, views. Uji baru `GaleriTest`.

## Selesai bila

`/galeri` tampil di ponsel, admin bisa mengelola album, uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 11.
