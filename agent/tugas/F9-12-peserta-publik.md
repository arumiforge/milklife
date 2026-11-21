# F9-12 Peserta publik

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Siswa dan ofisial bisa memastikan namanya terdaftar dan melihat nomor atlet serta nomor lombanya.

## Hasil

- [ ] `/peserta`: cari nama, sekolah, atau nomor atlet; saring kelompok umur, nomor lomba, jenis kelamin
- [ ] Isi: nama, sekolah, nomor atlet, nomor lomba; hanya Lunas; tanpa NISN, tanggal lahir, foto, data ofisial
- [ ] Paginasi sisi server dengan skeleton; dibatasi `throttler`
- [ ] Sakelar tampil di Pengaturan (bawaan mati)

## Berkas dan uji

`PesertaController`, `app/Libraries/PesertaPublik.php`, views, `public/aset/js/peserta.js`. Uji baru `PesertaPublikTest` (termasuk memastikan NISN dan tanggal lahir tidak pernah ada di keluaran).

## Selesai bila

`/peserta` berjalan di ponsel dengan 1.500 atlet contoh; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 10.
