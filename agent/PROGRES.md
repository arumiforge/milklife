# Progres

Diperbarui: 22 November 2026

## Sedang berjalan

Tidak ada tahap yang berjalan. Tahap 10 selesai 22 November 2026; semua siap sebelum pertemuan teknik 6 Desember.

## Berikutnya

**Tahap 11 — Pelajaran hasil Seri 1** (K172). Telaah dan rekomendasi: [`dokumen/draf/telaah-hasil-seri-1.md`](../dokumen/draf/telaah-hasil-seri-1.md). Semua sebelum lomba 9 Desember; P1 (F11-01 sampai F11-05) lebih dulu, sebelum pertemuan teknik 6 Desember.

| Tugas | Status |
|---|---|
| [F11-01 Ganti atlet oleh admin](tugas/F11-01-ganti-atlet.md) | menunggu tinjauan |
| [F11-02 Klasemen per kelompok umur](tugas/F11-02-klasemen-kelompok-umur.md) | rencana |
| [F11-03 Status nomor dan batas peserta](tugas/F11-03-status-nomor-batas-peserta.md) | rencana |
| [F11-04 Cara pencatatan waktu](tugas/F11-04-cara-pencatatan-waktu.md) | rencana |
| [F11-05 Kode NM dan r](tugas/F11-05-kode-nm-r.md) | rencana |
| [F11-06 Rekor kejuaraan](tugas/F11-06-rekor-kejuaraan.md) | rencana |
| [F11-07 Nomor acara](tugas/F11-07-nomor-acara.md) | rencana |
| [F11-08 Penutupan Tahap 11](tugas/F11-08-penutupan-tahap-11.md) | rencana |

Pembaruan server mengikuti bagian 10 panduan produksi. Server yang dipasang sebelum Tahap 9 menjalankan sekali, berurutan: `php spark migrate --all`, `faq:awal`, `tabel-skor:impor --trilomba`, `halaman-depan:carousel`, `galeri:awal`, `data:sesuaikan`, `aset:minify`, lalu `pendaftaran:impor <berkas.zip>` bila memindah data. Server yang sudah menjalankan perintah Tahap 9 cukup `migrate --all`, `data:sesuaikan`, dan `aset:minify`. Tanya-jawab dan pesan WA yang sudah tersimpan tidak berubah otomatis; admin menyuntingnya di Pengaturan > Bantuan dan Pengaturan > Halaman depan. Fitur publik bersakelar (Jadwal, Seri, Peserta, Galeri, Videotron) dinyalakan admin setelah dicoba di staging (https://staging.mac2026.web.id, K139). Kesiapan server (`/sehat`, pemantauan uptime, tembolok halaman depan, `client_max_body_size`, `memory_limit`, uji pulih cadangan) ditangani pemilik (K119). Pendaftaran tutup 30 November, pertemuan teknik 6 Desember, lomba 9–12 Desember.

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

## Menunggu pemilik

- Tidak ada. Deploy key diurus pengembang sendiri dan lisensi Flowbite Pro sudah mencakup semua pengembang (K148).
