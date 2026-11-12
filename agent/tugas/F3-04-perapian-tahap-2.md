# F3-04 Perapian sisa Tahap 2

| | |
|---|---|
| Status | rencana |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Admin yang mengunggah berkas terlalu besar mendapat pesan ramah, judul tab admin mengikuti nama acara, dan pembatasan per alamat IP tetap per pengunjung saat web dijangkau lewat Cloudflare Tunnel.

## Hasil

- [ ] Filter batas unggah sebelum `csrf`: kiriman yang melebihi `post_max_size` dialihkan kembali ke halaman asal dengan `pesan_galat` yang menyebut batasnya dan cara memperbaikinya
- [ ] Judul tab admin dan halaman masuk (judul dan teks "Panel panitia …") memakai nama acara dari pengaturan, bukan "MilkLife" tertulis langsung
- [ ] `Config\App::$proxyIPs` memercayai `CF-Connecting-IP` hanya dari `127.0.0.1` dan `::1` (K31); cara pemasangan di `README.md`
- [ ] Uji `BatasUnggahTest`, `JudulTabAdminTest`, `AlamatPengunjungTest`; uji yang terdampak disesuaikan

## Selesai bila

`composer uji` lulus; mengunggah berkas di atas `post_max_size` di server lokal menampilkan pesan ramah di halaman pengaturan, bukan halaman galat.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Dikerjakan bersamaan dengan F3-01; jangan menyentuh `tata_letak/publik.php`, `_merek.php`, `beranda*`, dan `tema.css`.
