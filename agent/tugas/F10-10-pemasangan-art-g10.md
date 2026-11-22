# F10-10 Pemasangan art G10

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P2 |

## Tujuan

Semua gambar anime G10 tampil pas di halaman, panel, dan videotron.

## Hasil

- [x] Periksa setiap gambar G10 yang sudah di `main` di halamannya (360/390/1280 dan videotron 1920×1080): potongan, `object-position`, gradasi, ukuran, kontras teks di atasnya
- [x] Sesuaikan kelas atau tata letak bila komposisi anime berbeda dari 3D lama; tidak mengubah berkas gambar
- [x] Pasang gambar G10 yang belum punya tempat: lembar tokoh G10-01 (bagian "Meet the Athletes" atau Tentang acara), elemen G10-11 yang belum dipakai F10-05
- [x] Hapus rujukan ke berkas yang tidak dibuat ulang (`nomor/seneng-playon.webp`, `pola/titik-halus.webp`); kedua berkas G9 itu juga dihapus
- [x] Gambar yang belum datang tetap aman (`gambar_opsional()`); daftar gambar yang belum ada ditulis di PR

## Berkas dan uji

View publik, admin, dan `layar/`, blok F10-10 `masukan.css`. Uji: `GambarOpsionalTest`, `TataLetakGalatTest`, `npm run uji:layar`, `KepalaKeamananTest`.

## Selesai bila

Tangkapan setiap halaman bergambar di 360/1280 dan videotron 1920×1080 dilampirkan di PR; semua uji lulus.

## Catatan

Bergantung pada F10-05, F10-06, dan PR G10 yang sudah digabung. Boleh dikerjakan bertahap (lebih dari satu PR) bila gambar datang terlambat.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
