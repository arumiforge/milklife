# F9-01 Fondasi Flowbite Pro

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Proyek punya kerangka tampilan Flowbite Pro (Tailwind 4) yang siap dipakai halaman lain, tanpa mengubah halaman yang sudah berjalan.

## Hasil

- [x] `package.json` dan `package-lock.json` di akar repo meniru `package.json` templat Pro (`template-flowbites/`): `tailwindcss` 4, `@tailwindcss/cli`, `flowbite`, `flowbite-typography`, `flowbite-datepicker`; skrip `npm run css`, `npm run css:pantau`, `npm run vendor`; `node_modules/` di `.gitignore`
- [x] `public/aset/tailwind/masukan.css` mengikuti `src/app.css` templat Pro (plugin dan tema Flowbite, `@source` ke `app/Views/` dan `public/aset/js/`), dibangun ke `public/aset/css/tw.css` (di-commit)
- [x] Langkah CI (Node) yang membangun ulang CSS dan gagal bila `tw.css` berbeda dari hasil bangun
- [x] Token `@theme`: warna `primary` Pro diganti palet `--ml-*` + magenta `#F0005A`; huruf lokal tanpa Google Fonts; radius, bayangan, gradien
- [x] JS Flowbite (`flowbite.min.js`, `datepicker.min.js`) disalin `npm run vendor` ke `public/aset/vendor/flowbite/`; sprite Lucide dan Three.js modul di `public/aset/vendor/`; semua tercatat di `vendor/README.md` (paket, versi, lisensi, asal)
- [x] Pembantu `ikon()` untuk Lucide
- [x] Potongan `app/Views/komponen/` diambil dari templat Pro (tombol, kartu, isian, pilihan, sakelar, lencana, peringatan, modal, tabel, skeleton, kosong, langkah); tata letak `publik_tw.php` (Landing Pages Pro) dan `admin_tw.php` (Admin Dashboard Pro)
- [x] Komponen Flowbite yang menyuntik gaya sebaris diperiksa terhadap CSP (K127) dan dicatat di PR
- [x] Halaman Katalog komponen `admin/gaya` dengan semua komponen dan contoh adegan Three.js yang mundur ke gambar diam
- [x] README menjelaskan `npm install` dan `npm run css` untuk pengembang; panduan produksi tidak perlu langkah baru (CSS dan JS sudah di-commit, server tanpa Node)

## Berkas dan uji

`package.json`, `package-lock.json`, `.gitignore`, `public/aset/tailwind/`, `public/aset/css/tw.css`, `public/aset/vendor/{flowbite,lucide,three}/`, `app/Helpers/`, `app/Views/komponen/`, `app/Views/tata_letak/*_tw.php`, `.github/workflows/uji.yml`, `README.md`, `agent/PETA-KODE.md`. Uji: `KepalaKeamananTest` (CSP), uji baru `KomponenTampilanTest`.

## Selesai bila

`admin/gaya` terbuka tanpa galat konsol dan tanpa pelanggaran CSP di 390 dan 1280 piksel, `npm run css` menghasilkan `tw.css` yang sama dengan yang di-commit, `composer uji` dan `composer analisis` lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 2–4, K141 butir 1, K142. Templat Hugo Pro tidak dipakai; hanya potongan HTML dan konfigurasi bangunnya. Tidak ada halaman lama yang berubah tampilan di tugas ini.

- Versi terpasang: `tailwindcss` 4.3.5, `flowbite` 3.1.2, `flowbite-typography` 1.0.5, `flowbite-datepicker` 1.3.2 (semua dalam rentang `package.json` templat Pro), `three` 0.171, `lucide-static` 0.469, Inter 5.3 (OFL).
- JS Flowbite diperiksa: tidak memakai `setAttribute('style')`, `eval`, atau `new Function`; gaya posisi dropdown/tooltip memakai CSSOM yang tidak diblok CSP. Halaman Katalog tampilan tanpa pelanggaran CSP di Chromium 390 dan 1280 piksel.
- Pemilih tanggal Flowbite sudah ada di `flowbite.min.js`; locale Indonesia dipasang tugas yang pertama memakainya (F9-07), karena berkas locale paket berupa modul ES.
- Menu admin baru (`_menu_admin_tw.php`) sementara ditulis terpisah dari menu lama; F9-14 menyatukannya.
- Tugas lain cukup memakai kelas; bila kelas baru belum ada di `tw.css`, jalankan `npm run css` dan commit hasilnya (job CI memeriksa).
