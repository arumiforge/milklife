# F4-05 Tombol, menu, pesan kilat, dan penutupan Tahap 4

| | |
|---|---|
| Status | selesai |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Pengunjung hanya bisa masuk ke wizard saat pendaftaran terbuka, pesan kilat terlihat di halaman publik, dan Tahap 4 ditutup.

## Hasil

- [x] `IsiHalamanDepan::ALAMAT_PENDAFTARAN` diisi; tombol Pendaftaran aktif hanya bila `bukaTutup()->terbuka()`, selain itu nonaktif dengan label keadaan
- [x] Menu Pendaftaran di tata letak publik mengikuti aturan yang sama
- [x] Pesan kilat `pesan` dan `pesan_galat` di tata letak publik
- [x] Uji gabungan pada salinan baru `main`; bila Tahap 5 sudah ditutup lebih dulu, uji wizard → cek → bukti dan perbaiki sambungannya (kesepakatan dua sesi, butir 7)
- [x] "Perubahan saat pengerjaan" di rencana, arsip `agent/arsip/tahap-4.md`, `PROGRES.md`

## Selesai bila

PR penutupan digabung setelah `composer uji` lokal dan CI lulus pada salinan `main` terbaru.

## Catatan

Tahap 5 ditutup lebih dulu (arsip `agent/arsip/tahap-5.md`, "Menunggu Tahap 4"), jadi uji gabungan wizard → cek → bukti dijalankan di sini.

- Uji gabungan otomatis `tests/fitur/PendaftaranGabunganTest.php`: dua pendaftaran dari NPSN yang sama lewat `POST pendaftaran/simpan` (tim baru, lalu susulan tanpa biaya tim), cek lewat ID (satu kartu, tanpa foto) dan NPSN (dua kartu, total keseluruhan), bukti PDF dari tautan langkah 5; menu Pendaftaran terbuka, ditutup, dan belum dibuka; menu aktif di halaman wizard; pesan kilat.
- Chromium 390 dan 1440 piksel dengan data karangan: alur lengkap sampai bukti PDF (200, `application/pdf`) dan pendaftaran tampil di cek; tim susulan yang melebihi batas ditahan di langkah 3; tanpa gulir mendatar.
- Sambungan yang dirapikan (berkas Tahap 5, setelah Tahap 5 ditutup): `DataPendaftaran` menulis nama nomor dengan kategori dan nomor WA bentuk tampilan; asersi `DataPendaftaranTest` dan `BuktiPendaftaranTest` disesuaikan. Tim susulan tampil "Rp0" di cek dan bukti, sesuai catatan Tahap 5.
- `composer uji` pada salinan `main` terbaru (4b40a54, semua tugas Tahap 4 dan 5) bersama cabang penutupan: 929 uji, 5.992 asersi, lulus.
- `HalamanDepanKepalaTest` dan `IsiHalamanDepanTest` (Tahap 3): uji tombol nonaktif "Segera" diganti uji tombol Pendaftaran yang mengikuti keadaan (terbuka, belum dibuka, ditutup, belum lengkap).
