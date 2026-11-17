# F7-30 Pecah berkas besar tanpa mengubah perilaku

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Berkas terbesar lebih mudah ditinjau dan dibaca, tanpa perubahan perilaku (S9).

## Hasil

- [ ] `public/aset/js/pendaftaran.js` dipecah per langkah atau per bagian (tanpa alat bangun; urutan muat di view)
- [ ] `app/Libraries/Panel/UbahPendaftaran.php` dan `app/Libraries/Pendaftaran/PemeriksaPendaftaran.php` dipecah per aturan
- [ ] Uji yang ada lulus tanpa diubah maknanya; `aset:minify` dan `pembaruan:periksa` tetap mengenali berkas baru

## Selesai bila

`composer uji` lulus, wizard dicoba di peramban.

## Catatan

K121. Hanya dimulai setelah F7-26, F7-27, dan F8-01 masuk `main`.
