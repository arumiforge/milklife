# Progres

Diperbarui: 22 November 2026

## Sedang berjalan

**Tahap 10 — Identitas visual anime, panel admin, dan data terbaru** (K153–K155). Permintaan: [`dokumen/draf/permintaan-tahap-10.md`](../dokumen/draf/permintaan-tahap-10.md). Rencana: [`dokumen/draf/rencana-implementasi-tahap-10.md`](../dokumen/draf/rencana-implementasi-tahap-10.md). Semua siap sebelum pertemuan teknik 6 Desember.

| Tugas | Status |
|---|---|
| [F10-01 Data dan pengaturan bawaan](tugas/F10-01-data-pengaturan-bawaan.md) | rencana |
| [F10-02 Peta lokasi](tugas/F10-02-peta-lokasi.md) | rencana |
| [F10-03 Tata letak panel admin](tugas/F10-03-tata-letak-admin.md) | rencana |
| [F10-04 Tabel berpaginasi dan dasbor](tugas/F10-04-tabel-paginasi-dasbor.md) | rencana |
| [F10-05 Fondasi gaya editorial](tugas/F10-05-fondasi-gaya-editorial.md) | rencana |
| [F10-06 Hero editorial dan slideshow](tugas/F10-06-hero-editorial-slideshow.md) | rencana |
| [F10-07 Navigasi publik](tugas/F10-07-navigasi-publik.md) | rencana |
| [F10-08 Kepadatan panel admin](tugas/F10-08-kepadatan-admin.md) | rencana |
| [F10-09 Halaman publik dan unduhan](tugas/F10-09-halaman-publik-unduhan.md) | rencana |
| [F10-10 Pemasangan art G10](tugas/F10-10-pemasangan-art-g10.md) | rencana |
| [F10-11 Penutupan Tahap 10](tugas/F10-11-penutupan-tahap-10.md) | rencana |

### Gambar Codex Tahap 10

Gaya anime 2D cel-shaded (K153); dikerjakan Codex berurutan dari [`desain/README.md`](../desain/README.md), ditinjau dan digabung koordinator. Pengganti G9 memakai nama berkas sama, jadi langsung tampil setelah digabung.

| Tugas gambar | Status |
|---|---|
| [G10-01 Lembar tokoh](../desain/tugas/G10-01-lembar-tokoh.md) | menunggu Codex |
| [G10-11 Elemen tombol dan ornamen](../desain/tugas/G10-11-elemen-tombol-ornamen.md) | menunggu Codex |
| [G10-02 Ilustrasi halaman depan](../desain/tugas/G10-02-ilustrasi-halaman-depan.md) | menunggu Codex |
| [G10-09 Pita judul](../desain/tugas/G10-09-pita-judul.md) | menunggu Codex |
| [G10-05 Nomor lomba](../desain/tugas/G10-05-ikon-nomor-lomba.md) | menunggu Codex |
| [G10-04 Keadaan kosong dan galat](../desain/tugas/G10-04-keadaan-kosong-galat.md) | menunggu Codex |
| [G10-03 Panduan pendaftaran](../desain/tugas/G10-03-panduan-pendaftaran.md) | menunggu Codex |
| [G10-07 Kategori Bantuan](../desain/tugas/G10-07-bantuan-kategori.md) | menunggu Codex |
| [G10-06 Videotron dan podium](../desain/tugas/G10-06-videotron-podium.md) | menunggu Codex |
| [G10-08 Panel admin](../desain/tugas/G10-08-panel-admin.md) | menunggu Codex |
| [G10-12 Pola dan hiasan](../desain/tugas/G10-12-pola-hiasan.md) | menunggu Codex |
| [G10-10 Gambar bagikan](../desain/tugas/G10-10-gambar-bagikan.md) | menunggu Codex |

Gambar Tahap 9 (G9-01 sampai G9-12) sudah di `main` dan akan tertimpa G10; sisa PR pemasangan G9 (#168) diselesaikan sesi pemantau G9 dan digabung koordinator Tahap 10.

## Berikutnya

**Tahap 10 — Identitas visual anime, panel admin, dan data terbaru** (K153). Permintaan dan telaahnya: [`dokumen/draf/permintaan-tahap-10.md`](../dokumen/draf/permintaan-tahap-10.md). Dijalankan koordinator Tahap 10 dengan sub-sesi paralel.

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
