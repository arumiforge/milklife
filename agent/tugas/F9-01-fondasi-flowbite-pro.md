# F9-01 Fondasi Flowbite Pro

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Proyek punya kerangka tampilan Flowbite Pro (Tailwind 4) yang siap dipakai halaman lain, tanpa mengubah halaman yang sudah berjalan.

## Hasil

- [ ] `package.json` dan `package-lock.json` di akar repo meniru `package.json` templat Pro (`template-flowbites/`): `tailwindcss` 4, `@tailwindcss/cli`, `flowbite`, `flowbite-typography`, `flowbite-datepicker`; skrip `npm run css`, `npm run css:pantau`, `npm run vendor`; `node_modules/` di `.gitignore`
- [ ] `public/aset/tailwind/masukan.css` mengikuti `src/app.css` templat Pro (plugin dan tema Flowbite, `@source` ke `app/Views/` dan `public/aset/js/`), dibangun ke `public/aset/css/tw.css` (di-commit)
- [ ] Langkah CI (Node) yang membangun ulang CSS dan gagal bila `tw.css` berbeda dari hasil bangun
- [ ] Token `@theme`: warna `primary` Pro diganti palet `--ml-*` + magenta `#F0005A`; huruf lokal tanpa Google Fonts; radius, bayangan, gradien
- [ ] JS Flowbite (`flowbite.min.js`, `datepicker.min.js`) disalin `npm run vendor` ke `public/aset/vendor/flowbite/`; sprite Lucide dan Three.js modul di `public/aset/vendor/`; semua tercatat di `vendor/README.md` (paket, versi, lisensi, asal)
- [ ] Pembantu `ikon()` untuk Lucide
- [ ] Potongan `app/Views/komponen/` diambil dari templat Pro (tombol, kartu, isian, pilihan, sakelar, lencana, peringatan, modal, tabel, skeleton, kosong, langkah); tata letak `publik_tw.php` (Landing Pages Pro) dan `admin_tw.php` (Admin Dashboard Pro)
- [ ] Komponen Flowbite yang menyuntik gaya sebaris diperiksa terhadap CSP (K127) dan dicatat di PR
- [ ] Halaman Katalog komponen `admin/gaya` dengan semua komponen dan contoh adegan Three.js yang mundur ke gambar diam
- [ ] README menjelaskan `npm install` dan `npm run css` untuk pengembang; panduan produksi tidak perlu langkah baru (CSS dan JS sudah di-commit, server tanpa Node)

## Berkas dan uji

`package.json`, `package-lock.json`, `.gitignore`, `public/aset/tailwind/`, `public/aset/css/tw.css`, `public/aset/vendor/{flowbite,lucide,three}/`, `app/Helpers/`, `app/Views/komponen/`, `app/Views/tata_letak/*_tw.php`, `.github/workflows/uji.yml`, `README.md`, `agent/PETA-KODE.md`. Uji: `KepalaKeamananTest` (CSP), uji baru `KomponenTampilanTest`.

## Selesai bila

`admin/gaya` terbuka tanpa galat konsol dan tanpa pelanggaran CSP di 390 dan 1280 piksel, `npm run css` menghasilkan `tw.css` yang sama dengan yang di-commit, `composer uji` dan `composer analisis` lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 2–4, K141 butir 1. Templat Hugo Pro tidak dipakai; hanya potongan HTML dan konfigurasi bangunnya. Tidak ada halaman lama yang berubah tampilan di tugas ini.
