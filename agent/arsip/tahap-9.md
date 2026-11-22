# Arsip Tahap 9 — Rombak tampilan dan fitur publik hari lomba

Selesai: 22 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-9.md`](../../dokumen/draf/rencana-implementasi-tahap-9.md), disetujui pemilik beserta delapan asumsinya (K142). Dasar: K138 (repo privat, lisensi), K139 (staging), K140 dan K141 (cakupan, Flowbite Pro, videotron cair). Dikerjakan paralel paling banyak 5 sub-sesi dan dijalankan koordinator otomatis sampai selesai (K145, K149); setiap PR aman langsung dipasang di produksi karena staging dan produksi sama-sama mengambil `main` (K140 butir 1). Jawaban pertanyaan PR: K143 (gelombang A), K144 (F9-12), K146 (gelombang A dan B), K151 (gelombang C).

| Tugas | Status | Gelombang | PR |
|---|---|---|---|
| [F9-01 Fondasi Flowbite Pro](../tugas/F9-01-fondasi-flowbite-pro.md) | selesai | 0 | #122 |
| [F9-02 Saluran Codex](../tugas/F9-02-saluran-codex.md) | selesai | 0 | #121 |
| [F9-03 Tata letak publik dan halaman depan](../tugas/F9-03-tata-letak-publik-beranda.md) | selesai | A | #125 |
| [F9-04 Bantuan dan FAQ](../tugas/F9-04-bantuan-faq.md) | selesai | A | #123, #126, #129 |
| [F9-07 Jadwal pertandingan](../tugas/F9-07-jadwal-pertandingan.md) | selesai | A | #131, #134 |
| [F9-10 Kalkulator poin multi-lomba](../tugas/F9-10-kalkulator-poin-multilomba.md) | selesai | A | #124 |
| [F9-08 Seri, lintasan, dan alur babak](../tugas/F9-08-seri-lintasan-bagan.md) | selesai | B | #137 |
| [F9-09 Aturan nomor dan lembar juri](../tugas/F9-09-aturan-nomor-lembar-juri.md) | selesai | B | #140 |
| [F9-12 Peserta publik](../tugas/F9-12-peserta-publik.md) | selesai | B | #127 |
| [F9-13 Papan videotron](../tugas/F9-13-papan-videotron.md) | selesai | B | #132, #138 |
| [F9-11 Metode klasemen](../tugas/F9-11-metode-klasemen.md) | selesai | B | #136 |
| [F9-06 Galeri tautan luar](../tugas/F9-06-galeri-tautan.md) | selesai | B | #135 |
| [F9-05 Wizard, cek, dan bukti ke Tailwind](../tugas/F9-05-wizard-cek-tailwind.md) | selesai | C | #144 |
| [F9-14 Tata letak admin dan dasbor](../tugas/F9-14-tata-letak-admin-dasbor.md) | selesai | C | #145 |
| [F9-15 Halaman admin pendaftaran ke Tailwind](../tugas/F9-15-admin-pendaftaran-tailwind.md) | selesai | C | #148 |
| [F9-16 Halaman admin hari lomba dan pengaturan ke Tailwind](../tugas/F9-16-admin-hari-lomba-pengaturan-tailwind.md) | selesai | C | #146 |
| [F9-17 Skeleton, keadaan kosong, dan perapian](../tugas/F9-17-skeleton-perapian.md) | selesai | C | #149 |
| [F9-19 Pemasangan visual tambahan](../tugas/F9-19-pemasangan-visual-tambahan.md) | selesai | C | #150 |
| [F9-18 Penutupan Tahap 9](../tugas/F9-18-penutupan-tahap-9.md) | selesai | akhir | #151 |

PR dokumen koordinator: #121 (rencana), #128, #130, #133, #139, #143, #147.

## Gambar (Codex)

Brief di `desain/tugas/`, aturan Codex di `AGENTS.md` (K140 butir 5, K150). Kode pemasangan semua gambar sudah di `main`: setiap gambar hanya tampil bila berkasnya ada (`is_file()`), jadi halaman aman tanpa berkasnya (K41). Keadaan saat Tahap 9 ditutup:

| Tugas gambar | Keadaan | Dipasang oleh |
|---|---|---|
| G9-01 Latar BIB | selesai (#142) | F9-16 |
| G9-02 Ilustrasi halaman depan | menunggu Codex, kode pemasangan siap | F9-03 |
| G9-03 Panduan pendaftaran | menunggu Codex, kode pemasangan siap | F9-04 |
| G9-04 Keadaan kosong dan galat | menunggu Codex, kode pemasangan siap | F9-17 |
| G9-05 Ilustrasi nomor lomba | menunggu Codex, kode pemasangan siap | F9-03, F9-17 |
| G9-06 Videotron dan podium | menunggu Codex, kode pemasangan siap | F9-13 |
| G9-07 Kategori Bantuan | menunggu Codex, kode pemasangan siap | F9-04 |
| G9-08 sampai G9-12 (panel admin, pita judul, gambar bagikan, ikon aplikasi, pola latar) | menunggu Codex, kode pemasangan siap | F9-19 |

Status tugas gambar yang masih berjalan tetap dicatat di `agent/PROGRES.md`.

## Hasil

- **Tampilan baru** (F9-01, F9-03, F9-05, F9-14 sampai F9-17): Flowbite Pro di atas Tailwind CSS 4 (K141), ikon Lucide, hiasan Three.js, komponen `komponen()` dan Katalog tampilan `admin/gaya`; semua halaman publik dan admin pindah utuh per PR; Bootstrap 5, Bootstrap Icons, `tema.css`, dan `admin.css` dilepas (F9-17). CSS `tw.css` dan pustaka di `public/aset/vendor/` hasil `npm run bangun`, di-commit; server tanpa Node. CSP tetap menyala (K127).
- **Halaman depan** (F9-03): carousel foto acara sebelumnya dengan indikator dan tombol jeda, angka acara, hasil dan juara umum terbaru (sakelar "Tampilkan hasil di atas"), FAQ teratas, menu publik baru.
- **Bantuan dan FAQ** (F9-04): tabel `faq`, 54 tanya-jawab bawaan dengan isian otomatis, `/bantuan` bisa dicari, laci Bantuan di tiap langkah wizard; selalu tampil.
- **Hari lomba**: jadwal pertandingan per babak dan `/jadwal` (F9-07); seri, lintasan, aturan Q/q, bagan, daftar seri PDF/Excel (F9-08); percobaan lompat/lempar, pemecah seri, lembar juri ponsel (F9-09); kalkulator poin multi-lomba dengan tabel skor panitia (F9-10, menggantikan K124 butir 3); metode klasemen Medali atau Poin (F9-11).
- **Halaman publik bersakelar** (bawaan mati, dinyalakan admin): Jadwal, Seri dan lintasan, Peserta (F9-12, hanya Lunas, tanpa data pribadi), Galeri (F9-06), dan papan videotron `/layar` cair untuk ukuran layar apa pun (F9-13, K141) dengan uji Playwright di delapan ukuran.
- **Panel admin** (F9-14): sidebar berkelompok satu sumber `MenuAdmin` (Pendaftaran, Hari lomba, Hasil, Konten, Pengaturan), menu bawah ponsel tetap K133.
- **Penutupan** (F9-18): konfirmasi modal saat mencentang hapus gambar/logo/sampul/latar dan "Pakai pesan awal"; `bahan-rombak/` dipindah ke `sumber/foto-acara/` (asumsi 7); rancangan final revisi 29, panduan produksi revisi 9, `KONTEKS.md`, `PETA-KODE.md`, README.
- Tabel baru (`faq`, `galeri`, `tabel_skor`, `tabel_skor_baris`) dan kolom baru `babak`, `hasil`, `nomor_lomba` ikut Backup data/impor (K76), penggabungan (K109), dan hapus pendaftaran. Migrasi hanya menambah.

## Perintah sekali jalan di server

Server yang dipasang sebelum Tahap 9, sekali setelah `git pull` (panduan produksi bagian 10):

```
php spark migrate --all
php spark faq:awal
php spark tabel-skor:impor --trilomba
php spark halaman-depan:carousel
php spark galeri:awal
php spark aset:minify
```

Semuanya aman diulang dan tidak menimpa isian admin. Sakelar fitur publik baru **bawaan mati**: `HalamanDepan.tampil_jadwal`, `HalamanDepan.tampil_seri`, `HalamanDepan.tampil_peserta` (Pengaturan > Halaman Depan atau halaman Jadwal pertandingan), `Galeri.tampil` (Pengaturan > Galeri), `Videotron.tampil` (Pengaturan > Videotron), dan `HalamanDepan.hasil_di_atas`. Pemilik mencobanya di staging, lalu admin menyalakannya di produksi.

## Uji penutupan (F9-18)

- Otomatis: `composer uji` lulus; CI tiga job (PHPUnit, Hasil bangun tampilan, Tampilan videotron) hijau di PR penutupan.
- Staging https://staging.mac2026.web.id terjangkau dari sesi, tetapi saat diperiksa masih kode sebelum F9-03 dan berjalan dalam mode development (debugbar tampil); dilaporkan ke koordinator. Uji peramban karena itu dijalankan di pemasangan lokal mode produksi.
- Peramban (Chromium, 360, 390, 1280 piksel, videotron 1920×1080) terhadap pemasangan lokal mode produksi dengan `aplikasi.minify = true` dan CSP menyala, data uji karangan (tiga pendaftaran lewat wizard, dua Lunas, lima sakelar publik dinyalakan, Final diisi dan terbit): semua halaman publik dan admin lulus tanpa galat konsol, pelanggaran CSP, aset gagal, dan gulir mendatar. Satu temuan diperbaiki: kolom Sekolah juara umum di `/layar` pecah per huruf pada ponsel tegak; layar sempit kini hanya memuat kolom dasar metode klasemen (`metode` ikut di `layar/data`). Semua perintah sekali jalan Tahap 9 berjalan tanpa galat. Daftar periksa lengkap di deskripsi PR penutupan.

## Yang dibawa ke tahap berikutnya

- Gambar G9-02 sampai G9-12 selesai 22 November 2026 (#153 sampai #163; G9-11 diganti logo resmi pemilik, K152, #158 ditutup) dan dipasang lewat #164, #166, #167, dan #168. Cadangan yang sengaja tidak dipakai: `keadaan/memuat.webp` (skeleton tetap ringan), serta `judul/kalkulator.webp` dan `judul/aturan.webp` (belum ada halaman publiknya).
- **Tindakan server untuk pengelola staging** (temuan F9-18, sudah diteruskan koordinator ke pemilik): (1) `git pull` ke `main` lalu perintah sekali jalan Tahap 9 di atas; (2) `CI_ENVIRONMENT = production` di `.env` staging, karena saat diperiksa debugbar, Kint, komentar `DEBUG-VIEW`, dan jalur berkas Windows tampil di HTML publik; (3) kepala `X-Robots-Tag: noindex` lewat Cloudflare (K139 butir 3). Setelah itu pemilik mencoba fitur bersakelar di staging.
- Sakelar fitur publik dinyalakan admin di produksi setelah dicoba di staging.
