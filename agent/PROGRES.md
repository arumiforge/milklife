# Progres

Diperbarui: 22 November 2026

## Sedang berjalan

Belum ada tahap berjalan. Tahap 9 sudah ditutup (F9-18) dan diarsipkan.

### Gambar Codex Tahap 9

Dikerjakan Codex dari [`desain/tugas/`](../desain/tugas/), ditinjau dan digabung koordinator (K150). Kode pemasangan semua gambar sudah di `main` dan halaman aman tanpa berkasnya (K41), jadi status di bawah tidak menahan apa pun.

| Tugas gambar | Status |
|---|---|
| [G9-01 Latar BIB](../desain/tugas/G9-01-latar-bib.md) | selesai (#142) |
| [G9-02 Ilustrasi halaman depan](../desain/tugas/G9-02-ilustrasi-halaman-depan.md) | menunggu Codex, kode pemasangan siap |
| [G9-03 Panduan pendaftaran](../desain/tugas/G9-03-panduan-pendaftaran.md) | menunggu Codex, kode pemasangan siap |
| [G9-04 Keadaan kosong dan galat](../desain/tugas/G9-04-keadaan-kosong-galat.md) | menunggu Codex, kode pemasangan siap |
| [G9-05 Ilustrasi nomor lomba](../desain/tugas/G9-05-ikon-nomor-lomba.md) | menunggu Codex, kode pemasangan siap |
| [G9-06 Videotron dan podium](../desain/tugas/G9-06-videotron-podium.md) | menunggu Codex, kode pemasangan siap |
| [G9-07 Kategori Bantuan](../desain/tugas/G9-07-bantuan-kategori.md) | menunggu Codex, kode pemasangan siap |
| [G9-08 Panel admin](../desain/tugas/G9-08-panel-admin.md) | menunggu Codex, kode pemasangan siap |
| [G9-09 Pita judul halaman publik](../desain/tugas/G9-09-pita-judul.md) | menunggu Codex, kode pemasangan siap |
| [G9-10 Gambar bagikan](../desain/tugas/G9-10-gambar-bagikan.md) | menunggu Codex, kode pemasangan siap |
| [G9-11 Ikon aplikasi](../desain/tugas/G9-11-ikon-aplikasi.md) | menunggu Codex, kode pemasangan siap |
| [G9-12 Pola dan hiasan latar](../desain/tugas/G9-12-pola-hiasan.md) | menunggu Codex, kode pemasangan siap |

## Berikutnya

Pembaruan server mengikuti bagian 10 panduan produksi. Server yang dipasang sebelum Tahap 9 menjalankan sekali: `php spark migrate --all`, `faq:awal`, `tabel-skor:impor --trilomba`, `halaman-depan:carousel`, `galeri:awal`, lalu `aset:minify`. Fitur publik baru (Jadwal, Seri, Peserta, Galeri, Videotron) bersakelar bawaan mati sampai dinyalakan admin setelah dicoba di staging (https://staging.mac2026.web.id, K139). Server yang dipasang sebelum repo privat perlu deploy key sekali, diurus pengembang (K138, K148). Kesiapan server (`/sehat`, pemantauan uptime, tembolok halaman depan, `client_max_body_size`, `memory_limit`, uji pulih cadangan) ditangani pemilik (K119). Pendaftaran tutup 30 November, pertemuan teknik 6 Desember, lomba 9–12 Desember.

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

## Menunggu pemilik

- Tidak ada. Deploy key diurus pengembang sendiri dan lisensi Flowbite Pro sudah mencakup semua pengembang (K148).
