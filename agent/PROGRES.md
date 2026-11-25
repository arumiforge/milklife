# Progres

Diperbarui: 25 November 2026

## Sedang berjalan

- F11-22 Ganti atlet seperti Seri 1 dengan dua sakelar (K188): menunggu tinjauan.
- F11-21 Salinan cadangan Cloudflare R2 diatur di panel admin (K187): menunggu tinjauan.

## Berikutnya

- Pembaruan server produksi Tahap 11 berlangsung sekaligus saat produksi pindah ke Docker (F11-17, panduan produksi bagian 8): `migrate --all` dan perintah yang diminta `pembaruan:periksa`, misalnya `data:sesuaikan`. Sakelar Rekor dan Videotron dinyalakan admin setelah dicoba di staging (https://staging.mac2026.web.id, K139), dan alamat `/layar` diberikan ke operator videotron vendor. Panduan admin hari lomba (`dokumen/final/panduan-admin-hari-lomba.md`, K178, revisi 1 K183) siap dibagikan ke admin panitia dan juri sebelum pertemuan teknik; setelah pembaruan server, admin membuat akun juri di Pengaturan > Akun juri dan menyunting jawaban ganti atlet di Bantuan.
- Pendaftaran tutup 30 November, pertemuan teknik daring 5 Desember, pengambilan nomor atlet 8 Desember, lomba 9–12 Desember 2026 (K186).

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
- Tahap 9 — Rombak tampilan dan fitur publik hari lomba: [`arsip/tahap-9.md`](arsip/tahap-9.md). Selesai 22 November 2026; F9-01 sampai F9-17 dan F9-19 (#121–#150), penutupan F9-18 (#151).
- Tahap 10 — Identitas visual anime, panel admin, dan data terbaru: [`arsip/tahap-10.md`](arsip/tahap-10.md). Selesai 22 November 2026; F10-01 sampai F10-10, F10-12, F10-13 (#172–#203) dan gambar G10-01 sampai G10-13, penutupan F10-11.
- Tahap 11 — Pelajaran hasil Seri 1: [`arsip/tahap-11.md`](arsip/tahap-11.md). Selesai 23 November 2026; F11-01 sampai F11-07 dan F11-09 sampai F11-12 (#211–#224), penutupan F11-08. Gambar G11-01 (kartu kelompok umur, #236) dan G11-02 (latar hero kiri, #237) dari Codex dipasang 24 November 2026; PR #230 dan #231 dari brief lama ditutup.
- Tugas sisipan Tahap 11 setelah penutupan: F11-15 kartu KU dan pita fakta halaman depan (K179), F11-16 peran juri (K181, #234), F11-17 migrasi server ke Docker (K182, #235), F11-18 tunnel Cloudflare di host VPS (K184, #241), F11-19 pemeriksaan pembaruan untuk data pindahan (K185, #242), F11-20 data awal sesuai isian panitia di produksi (K186, #244).

## Menunggu pemilik

- Pemasangan di VPS Ubuntu 24.04 (F11-17, K182), dikerjakan pemilik karena agen tidak punya akses ke VPS: staging dipasang dulu (panduan produksi bagian 1–7, `COMPOSE_PROJECT_NAME=milklife-staging`), lalu produksi dipindah dari Laragon pada malam hari sebelum 30 November 2026 (bagian 8), dan salinan R2 diatur admin di Pengaturan > Cadangan lalu dicoba dengan `cadangan:buat` (bagian 7). Langkah yang disesuaikan dengan server spmb-prod ada di Claude Doc "Pemasangan MilkLife di VPS spmb-prod". Deploy key diurus pengembang sendiri dan lisensi Flowbite Pro sudah mencakup semua pengembang (K148).
