# F4-05 Tombol, menu, pesan kilat, dan penutupan Tahap 4

| | |
|---|---|
| Status | rencana |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Pengunjung hanya bisa masuk ke wizard saat pendaftaran terbuka, pesan kilat terlihat di halaman publik, dan Tahap 4 ditutup.

## Hasil

- [ ] `IsiHalamanDepan::ALAMAT_PENDAFTARAN` diisi; tombol Pendaftaran aktif hanya bila `bukaTutup()->terbuka()`, selain itu nonaktif dengan label keadaan
- [ ] Menu Pendaftaran di tata letak publik mengikuti aturan yang sama
- [ ] Pesan kilat `pesan` dan `pesan_galat` di tata letak publik
- [ ] Uji gabungan pada salinan baru `main`; bila Tahap 5 sudah ditutup lebih dulu, uji wizard → cek → bukti dan perbaiki sambungannya (kesepakatan dua sesi, butir 7)
- [ ] "Perubahan saat pengerjaan" di rencana, arsip `agent/arsip/tahap-4.md`, `PROGRES.md`

## Selesai bila

PR penutupan digabung setelah `composer uji` lokal dan CI lulus pada salinan `main` terbaru.

## Catatan

Bila Tahap 4 ditutup lebih dulu, hal yang menunggu Tahap 5 dicatat di arsip.
