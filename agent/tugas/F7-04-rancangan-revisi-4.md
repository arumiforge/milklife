# F7-04 Rancangan revisi 4

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Rancangan final kembali sesuai dengan yang dibangun: keputusan K49–K68 masuk ke bagian yang terdampak, lalu PDF untuk pengembang dibuat ulang.

## Hasil

- [x] `dokumen/draf/rancangan-web-pendaftaran.md`: salinan final dengan perubahan bagian 6.3, 6 (kuota), 6.6, 6.8, 8, 9, 13, 14, 15, 16, 17 sesuai rencana Tahap 7
- [x] Baris "Revisi 4" di tabel kepala menyebut keputusan dan bagian yang berubah
- [x] Ringkasan perubahan per bagian di deskripsi PR untuk ditinjau pemilik
- [ ] Setelah disetujui: draf dipindahkan ke `dokumen/final/` dan `php spark dokumen:pdf` dijalankan di PR yang sama

## Selesai bila

Pemilik menyetujui di PR, `dokumen/final/rancangan-web-pendaftaran.md` dan PDF-nya diperbarui, dan `composer uji` tetap lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Rancangan revisi 4". Gelombang 1. Hanya dokumen; tidak menyentuh kode. Keputusan persetujuan dicatat koordinator.

Keputusan kecil saat mengerjakan:

- Kuota (K51, K53) ditulis di 6.5 Nomor lomba, di sebelah batas nomor individu per atlet, karena keduanya diatur di pengaturan Pendaftaran yang sama.
- Selain bagian di rencana, ikut disesuaikan seperlunya: 5 (baris `/admin/pendaftaran` menyebut bukti bayar dan K58, baris `/admin/rekap` menyebut tim beregu yang memang sudah ada), 6.9 (K59, karena aturan nomor dada ada di sana; bagian 9 merujuk ke 6.9), dan 17 (catatan K55 bahwa juknis tidak ditunggu).
- Bagian 13 dan 15 menyebut hasil Tahap 7 yang sedang dibangun (F7-02, F7-03, F7-05) sesuai kesepakatan teknis rencana Tahap 7; bila hasil akhirnya berbeda, disesuaikan sebelum draf dipindah ke final.
- Rancangan tetap menjelaskan *apa*: nama kunci pengaturan, nama kelas, dan ukuran piksel pengecilan gambar admin tidak ditulis.
