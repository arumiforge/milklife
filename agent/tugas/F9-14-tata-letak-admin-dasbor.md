# F9-14 Tata letak admin dan dasbor

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Panel admin memakai tampilan baru dengan menu berkelompok yang menampung menu-menu baru.

## Hasil

- [x] `admin_tw.php`: sidebar berkelompok (Pendaftaran, Hari lomba, Hasil, Konten, Pengaturan), kepala, menu bawah ponsel K133
- [x] Dasbor dan Pengecekan pindah; grafik ApexCharts dengan skeleton
- [x] Gaya DataTables untuk Tailwind (CSS sendiri), dipakai halaman berikutnya
- [x] Halaman admin yang belum pindah tetap memakai tata letak lama dengan menu yang sama isinya

## Berkas dan uji

`app/Views/tata_letak/admin_tw.php`, `_menu_admin.php`, `admin/dasbor.php`, `admin/pengecekan.php`, `public/aset/js/dasbor.js`, `tabel-admin.js`. Uji: `DasborKelengkapanTest`, `RingkasanDasborTest`, menu ponsel.

## Selesai bila

Dasbor tampil di 390 dan 1280 piksel tanpa galat konsol/CSP; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K131–K133 tetap.
