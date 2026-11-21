# F9-01 Fondasi Tailwind

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Proyek punya kerangka tampilan Tailwind 4 yang siap dipakai halaman lain, tanpa mengubah halaman yang sudah berjalan.

## Hasil

- [ ] Perintah `php spark aset:tailwind` (`--watch`): unduh Tailwind CLI mandiri versi terpatok ke `writable/alat/` dengan cek SHA-256, bangun `public/aset/tailwind/masukan.css` → `public/aset/css/tw.css` (di-commit)
- [ ] Langkah CI yang membangun ulang dan gagal bila `tw.css` berbeda dari hasil bangun
- [ ] Token `@theme`: palet `--ml-*` + magenta `#F0005A`, huruf, radius, bayangan, gradien
- [ ] Flowbite JS gratis (MIT), sprite Lucide, dan Three.js modul di `public/aset/vendor/`, tercatat di `vendor/README.md` (versi, lisensi, asal, sha1)
- [ ] Pembantu `ikon()` untuk Lucide
- [ ] Potongan `app/Views/komponen/` (tombol, kartu, isian, pilihan, sakelar, lencana, peringatan, modal, tabel, skeleton, kosong, langkah) dan tata letak `publik_tw.php`, `admin_tw.php`
- [ ] Halaman Katalog komponen `admin/gaya` dengan semua komponen dan contoh adegan Three.js yang mundur ke gambar diam
- [ ] Panduan produksi bagian 10 dan `pembaruan:periksa` tidak perlu langkah baru (CSS sudah di-commit); README menjelaskan `aset:tailwind` untuk pengembang

## Berkas dan uji

`app/Commands/AsetTailwind.php`, `public/aset/tailwind/`, `public/aset/css/tw.css`, `public/aset/vendor/{flowbite-js,lucide,three}/`, `app/Helpers/`, `app/Views/komponen/`, `app/Views/tata_letak/*_tw.php`, `.github/workflows/uji.yml`, `agent/PETA-KODE.md`. Uji: `KepalaKeamananTest` (CSP), uji baru `KomponenTampilanTest`, `AsetTailwindTest` (tanpa jaringan).

## Selesai bila

`admin/gaya` terbuka tanpa galat konsol dan tanpa pelanggaran CSP di 390 dan 1280 piksel, `composer uji` dan `composer analisis` lulus, CI memeriksa `tw.css`.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 2–4. Tidak ada halaman lama yang berubah tampilan di tugas ini.
