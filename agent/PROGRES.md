# Progres

Diperbarui: 21 November 2026

## Sedang berjalan

**Tahap 9 — Rombak tampilan dan fitur publik hari lomba** (K140, K141). Rencana: [`dokumen/draf/rencana-implementasi-tahap-9.md`](../dokumen/draf/rencana-implementasi-tahap-9.md), disetujui pemilik beserta delapan asumsinya (K142). Dikerjakan paralel, paling banyak 5 sub-sesi (K142, K145). Jawaban pertanyaan gelombang A: K143; F9-12: K144. Koordinator menjalankan semua tugas otomatis sampai selesai (K145). Staging: https://staging.mac2026.web.id (K139). Pendaftaran tutup 30 November, pertemuan teknik 6 Desember, lomba 9–12 Desember.

| Tugas | Status | Prioritas | Gelombang |
|---|---|---|---|
| [F9-01 Fondasi Flowbite Pro](tugas/F9-01-fondasi-flowbite-pro.md) | selesai (#122) | P1 | 0 |
| [F9-02 Saluran Codex](tugas/F9-02-saluran-codex.md) | selesai (#121) | P1 | 0 |
| [F9-03 Tata letak publik dan halaman depan](tugas/F9-03-tata-letak-publik-beranda.md) | selesai (#125) | P1 | A |
| [F9-04 Bantuan dan FAQ](tugas/F9-04-bantuan-faq.md) | selesai (#123) | P1 | A |
| [F9-07 Jadwal pertandingan](tugas/F9-07-jadwal-pertandingan.md) | selesai (#131) | P1 | A |
| [F9-10 Kalkulator poin multi-lomba](tugas/F9-10-kalkulator-poin-multilomba.md) | selesai (#124) | P1 | A |
| [F9-08 Seri, lintasan, dan alur babak](tugas/F9-08-seri-lintasan-bagan.md) | selesai (#137) | P1 | B |
| [F9-09 Aturan nomor dan lembar juri](tugas/F9-09-aturan-nomor-lembar-juri.md) | selesai (#140) | P1 | B |
| [F9-12 Peserta publik](tugas/F9-12-peserta-publik.md) | selesai (#127) | P1 | B |
| [F9-13 Papan videotron](tugas/F9-13-papan-videotron.md) | selesai (#132, #138) | P1 | B |
| [F9-11 Metode klasemen](tugas/F9-11-metode-klasemen.md) | selesai (#136) | P2 | B |
| [F9-06 Galeri tautan luar](tugas/F9-06-galeri-tautan.md) | selesai (#135) | P2 | B |
| [F9-05 Wizard, cek, dan bukti ke Tailwind](tugas/F9-05-wizard-cek-tailwind.md) | rencana | P2 | C (setelah 30 November) |
| [F9-14 Tata letak admin dan dasbor](tugas/F9-14-tata-letak-admin-dasbor.md) | dikerjakan | P2 | C |
| [F9-15 Halaman admin pendaftaran ke Tailwind](tugas/F9-15-admin-pendaftaran-tailwind.md) | rencana | P2 | C |
| [F9-16 Halaman admin hari lomba dan pengaturan ke Tailwind](tugas/F9-16-admin-hari-lomba-pengaturan-tailwind.md) | dikerjakan | P2 | C |
| [F9-17 Skeleton, keadaan kosong, dan perapian](tugas/F9-17-skeleton-perapian.md) | rencana | P2 | C |
| [F9-18 Penutupan Tahap 9](tugas/F9-18-penutupan-tahap-9.md) | rencana | P1 | akhir |

### Gambar (Codex)

Dikerjakan Codex dari [`desain/tugas/`](../desain/tugas/), ditinjau dan digabung koordinator.

| Tugas gambar | Status |
|---|---|
| [G9-01 Latar BIB](../desain/tugas/G9-01-latar-bib.md) | rencana |
| [G9-02 Ilustrasi halaman depan](../desain/tugas/G9-02-ilustrasi-halaman-depan.md) | rencana |
| [G9-03 Panduan pendaftaran](../desain/tugas/G9-03-panduan-pendaftaran.md) | rencana |
| [G9-04 Keadaan kosong dan galat](../desain/tugas/G9-04-keadaan-kosong-galat.md) | rencana |
| [G9-05 Ilustrasi nomor lomba](../desain/tugas/G9-05-ikon-nomor-lomba.md) | rencana |
| [G9-06 Videotron dan podium](../desain/tugas/G9-06-videotron-podium.md) | rencana |
| [G9-07 Kategori Bantuan](../desain/tugas/G9-07-bantuan-kategori.md) | rencana |

## Berikutnya

Gelombang A dan B selesai (F9-09 lewat #140). Perbaikan kecil masuk lewat #134 (nama Agenda di Pengaturan, pola transaksi) dan #138 (uji tampilan bersih). Gelombang C: F9-14 dan F9-16 dikerjakan sejak 21 November (K147), F9-15 setelah F9-14, F9-05 mulai 1 Desember setelah pendaftaran tutup; kemudian F9-17 dan F9-18. Jawaban pertanyaan gelombang A dan B: K146. Setelah pembaruan server: `php spark migrate --all`, `faq:awal`, `tabel-skor:impor --trilomba`, `halaman-depan:carousel`, `galeri:awal`, lalu `aset:minify`. Pembaruan server mengikuti bagian 10 panduan produksi; server yang dipasang sebelum repo privat perlu deploy key sekali (K138). Kesiapan server ditangani pemilik (K119).

## Selesai

- Tahap 0 — Fondasi: [`arsip/tahap-0.md`](arsip/tahap-0.md).
- Tahap 1 — Kerangka: [`arsip/tahap-1.md`](arsip/tahap-1.md).
- Tahap 2 — Pengaturan: [`arsip/tahap-2.md`](arsip/tahap-2.md).
- Tahap 3 — Halaman depan: [`arsip/tahap-3.md`](arsip/tahap-3.md).
- Tahap 4 — Pendaftaran: [`arsip/tahap-4.md`](arsip/tahap-4.md).
- Tahap 5 — Bukti dan cek: [`arsip/tahap-5.md`](arsip/tahap-5.md). Selesai 12 November 2026, lebih dulu dari Tahap 4; uji gabungan wizard → cek → bukti dijalankan penutupan Tahap 4.
- Tahap 6 — Panel data: [`arsip/tahap-6.md`](arsip/tahap-6.md). Selesai 12 November 2026; F6-01 sampai F6-07 (#47, #50, #49, #51, #52, #54, #53) dan penutupan F6-08.
- Tahap 7 — Penyelesaian: [`arsip/tahap-7.md`](arsip/tahap-7.md). Selesai 18 November 2026; F7-01 sampai F7-06 dan tugas sisipan F7-08 sampai F7-33, penutupan F7-07.
- Tahap 8 — Hari lomba dan hasil: [`arsip/tahap-8.md`](arsip/tahap-8.md). Selesai 18 November 2026; F8-01 sampai F8-05 dan F8-07 (#102, #103, #104, #113, #114, #107), penutupan F8-06 bersama F7-06.

## Menunggu pemilik

- Tidak ada. Deploy key diurus pengembang sendiri dan lisensi Flowbite Pro sudah mencakup semua pengembang (K148).
