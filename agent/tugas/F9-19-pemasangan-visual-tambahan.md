# F9-19 Pemasangan visual tambahan

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Gambar tambahan dari Codex (G9-08 sampai G9-12, K150) terpasang di halaman yang tepat, dan halaman tetap rapi selama gambarnya belum ada.

## Hasil

- [x] Pembantu gambar opsional: gambar hanya dirender bila berkasnya ada (`is_file`), tanpa galat dan tanpa ruang kosong bila belum ada
- [x] Halaman masuk, sapaan dasbor, daftar ulang, cetak BIB, input hasil, dan keadaan kosong admin memakai G9-08
- [x] `_judul_halaman_tw` menerima parameter gambar; halaman publik yang memakai pita judul memasang ilustrasi G9-09 (disembunyikan di bawah `md`)
- [x] `og:image`, `og:title`, `og:description`, dan `twitter:card` di tata letak publik, gambar G9-10 per halaman dengan cadangan `umum.jpg`
- [x] Favicon PNG, `apple-touch-icon`, dan `manifest.webmanifest` (G9-11) tanpa melanggar CSP (`manifest-src` bila perlu); `ikon.svg` tetap
- [x] Hiasan G9-12 dipakai hemat (beranda, layar berhasil, halaman masuk) dan disembunyikan dari pembaca layar

## Berkas dan uji

`app/Views/tata_letak/`, view admin dan publik terkait, `app/Helpers/`, `public/manifest.webmanifest`, `app/Config/ContentSecurityPolicy.php` bila perlu. Uji: tampilan dengan dan tanpa berkas gambar, `KepalaKeamananTest`.

## Selesai bila

Halaman tampil benar dengan dan tanpa berkas gambar di Chromium 360, 390, 1280 px (minify menyala) tanpa galat konsol/CSP; semua uji lulus.

## Catatan

Bergantung pada F9-14 dan F9-16 (tata letak admin dan halaman masuk). Tidak menunggu gambar Codex: kode dipasang dulu, gambar muncul sendiri setelah PR Codex digabung. Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`.
