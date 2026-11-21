# F9-08 Seri, lintasan, dan alur babak

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Panitia mendapat daftar seri dan lintasan otomatis untuk pertemuan teknik 6 Desember, dan publik bisa melihat siapa lolos ke Final.

## Hasil

- [ ] Pengaturan per nomor: lintasan per seri (bawaan 8), aturan lolos Q/q, urutan lintasan unggulan
- [ ] Kelas murni pembagi seri: rata, sekolah sama dipisah sebisanya, acak dengan benih tersimpan; uji unit
- [ ] Halaman admin Seri dan lintasan: bagi otomatis, geser atlet antarseri/lintasan, kunci babak
- [ ] Babak berikut terisi otomatis dari Q/q setelah hasil lengkap; admin bisa mengoreksi
- [ ] Unduhan daftar seri PDF/Excel per nomor dan per hari
- [ ] Publik: seri dan lintasan per babak, bagan alur babak per nomor; sakelar tampil (bawaan mati)

## Berkas dan uji

Migrasi `seri_babak`/kolom lintasan di `hasil`, `app/Libraries/Hasil/PembagiSeri.php`, `Admin/SeriController`, views admin dan publik. Uji baru `HasilPembagiSeriTest` (unit), `SeriLintasanTest`; `HasilLombaTest`.

## Selesai bila

Daftar seri PDF untuk satu hari lomba contoh bisa dicetak, bagan publik tampil, uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 8. Target 4 Desember (sebelum pertemuan teknik). `KONTEKS.md` dan rancangan final diperbarui: urutan pelari kini dalam cakupan.
