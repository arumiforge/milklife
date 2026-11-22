# G10-06 Videotron dan podium

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F9-13 (sudah terpasang di `/layar`), F10-10 |
| Menggantikan | G9-06 |

## Tujuan

Papan videotron hari lomba bergaya anime, nama berkas sama dengan G9-06.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`.

## Keluaran

Folder `public/aset/gambar/layar/`.

| Berkas | Ukuran | Format | Paling besar | Isi |
|---|---|---|---|---|
| `latar-dasar.webp` | 2560 × 2560 | WebP | 400 KB | Biru tua `#021F7B` ke biru `#234987` dengan garis kecepatan cel-shaded sangat samar, **tanpa objek**, bisa dipotong bagian mana pun |
| `pita-tepi.webp` | 2048 × 160 | WebP alfa | 120 KB | Sapuan kecepatan oranye-magenta-biru muda mendatar yang **menyambung bila diulang ke samping** |
| `hiasan-kiri-atas.webp` | 1024 × 1024 | WebP alfa | 120 KB | Sapuan kuas dan garis lintasan di sudut kiri atas, sisanya transparan |
| `hiasan-kanan-bawah.webp` | 1024 × 1024 | WebP alfa | 120 KB | Pasangan sudut kanan bawah, gaya sama; boleh siluet tokoh A berlari kecil |
| `podium.webp` | 1600 × 900 | WebP alfa | 200 KB | Podium tiga tingkat kosong bergaya cel-shaded, tanpa angka |
| `medali-emas.webp` | 512 × 512 | WebP alfa | 60 KB | Medali emas berpita biru, kilau bergaya anime, permukaan polos |
| `medali-perak.webp` | 512 × 512 | WebP alfa | 60 KB | Medali perak berpita biru |
| `medali-perunggu.webp` | 512 × 512 | WebP alfa | 60 KB | Medali perunggu berpita biru |
| `piala.webp` | 512 × 512 | WebP alfa | 60 KB | Piala juara umum polos dengan kilau |

## Catatan

- Videotron menampilkan warna lebih jenuh: hindari putih luas dan gradien terlalu halus. Teks putih dan kuning akan ditaruh di atas latar.
- Hiasan sudut diperkecil sampai 15% sisi pendek layar; tetap terbaca dan tidak mengganggu tabel.
- Pratinjau gabungan 16:9, 4:1, dan 9:16 di **deskripsi PR** (tidak di-commit).

## Selesai bila

Sembilan berkas sesuai tabel dan `desain/catatan/G10-06.md` ada.
