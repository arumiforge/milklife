# F11-13 Tombol unduh ponsel

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (sisipan setelah penutupan) |
| Dibuat | 23 November 2026 |

## Tujuan

Di ponsel, tombol unduh ikon tidak langsung mengunduh saat teksnya belum terlihat. Ketukan pertama menampilkan teks, ketukan berikutnya mengunduh (K177, menggantikan butir #224 di K176).

## Hasil

- [x] Layar sentuh: bila label tertutup, ketukan pertama hanya membuka label dan mengumumkan "Ketuk sekali lagi untuk mengunduh …" lewat `role="status"`; ketukan saat label terbuka langsung mengunduh. Label tertutup lagi bila mengetuk di luar, membuka tombol unduh lain, atau setelah 4 detik tanpa ketukan.
- [x] Desktop: label tampil saat kursor di atasnya, klik langsung mengunduh. Papan ketik: fokus menampilkan label, Enter dan Spasi langsung mengunduh. Nama aksesibel tetap lengkap.
- [x] Tombol unduh hasil publik memakai `komponen('tombol_unduh')`; `hasil/_unduh_ikon.php` dihapus.

## Berkas dan uji

`public/aset/js/tombol-unduh.js` (dimuat `admin_tw` dan `publik_tw`), `public/aset/js/panel-ringkas.js`, `app/Views/komponen/tombol_unduh.php`, `app/Views/hasil/nomor.php`, `app/Views/hasil/klasemen.php`, CSS hasil bangun. Uji `--filter HasilLomba`, `TataLetakHalaman`, `KomponenTampilan`; uji peramban `npm run uji:unduh` (`alat/uji-unduh.mjs`, iPhone 13 dan desktop, juga di CI). Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Uji peramban membuktikan ketukan pertama di ponsel tidak mengunduh dan ketukan kedua mengunduh, sedangkan klik desktop dan Enter langsung mengunduh. `composer uji` lulus.
