# F9-17 Skeleton, keadaan kosong, dan perapian

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Seluruh web terasa rapi dan konsisten: tidak ada layar kosong yang membingungkan, tombol seragam, dan pustaka lama dilepas.

## Hasil

- [ ] Skeleton di tabel admin, cari NPSN, grafik dasbor, peserta publik, videotron, carousel
- [ ] Keadaan kosong dan halaman galat 404/500 memakai ilustrasi G9-04
- [ ] Tinjau ulang semua tombol: satu tombol utama per layar, label kata kerja, ikon Lucide, keadaan memuat/nonaktif, konfirmasi tindakan yang tidak bisa dibatalkan
- [ ] Bootstrap, Bootstrap Icons, dan CSS lama dilepas bila tidak ada halaman yang memakainya; `tema.css` dirampingkan
- [ ] Ilustrasi nomor lomba G9-05 di halaman Peserta (saringan atau kartu), memakai pemeta nama nomor milik F9-03 (K144)
- [ ] Pemeriksaan aksesibilitas (kontras, fokus, label) di halaman utama

## Berkas dan uji

Semua view, `public/aset/tailwind/masukan.css`, `public/aset/vendor/`, `app/Views/errors/html/`. Uji: semua, `KepalaKeamananTest`.

## Selesai bila

Tidak ada lagi halaman yang memuat Bootstrap; daftar periksa tombol terlampir di PR; semua uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 14.
