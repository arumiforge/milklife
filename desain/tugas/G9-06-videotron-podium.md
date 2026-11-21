# G9-06 Latar videotron, podium, dan medali

| | |
|---|---|
| Tahap | 9 — Rombak tampilan (K140) |
| Dibuat | 21 November 2026 |
| Dipakai oleh | F9-13 (papan videotron), F9-03 dan F9-11 (juara umum) |

## Tujuan

Papan hasil di videotron terlihat meriah dari kejauhan di **layar ukuran apa pun** (K141): pita LED panjang, 16:9, 4:3, tegak, kecil sampai 4K. Karena rasionya tidak tetap, latar **disusun dari potongan** oleh halaman web (dasar yang dipotong pas, hiasan sudut, pita tepi yang diulang), bukan satu gambar utuh.

## Keluaran

Folder `public/aset/gambar/layar/`.

| Berkas | Ukuran | Format | Paling besar | Isi |
|---|---|---|---|---|
| `latar-dasar.webp` | 2560 × 2560 | WebP | 400 KB | Gradien biru tua `#021F7B` ke biru `#234987` dengan cahaya lembut, **tanpa objek**, bisa dipotong bagian mana pun (tengah, pita sempit, tegak) dan tetap bagus |
| `pita-tepi.webp` | 2048 × 160 | WebP alfa | 120 KB | Pita miring oranye-magenta-biru muda mendatar yang **bisa diulang ke samping tanpa sambungan terlihat** (ujung kiri dan kanan menyambung) |
| `hiasan-kiri-atas.webp` | 1024 × 1024 | WebP alfa | 120 KB | Lengkung pita dan siluet garis lintasan di sudut kiri atas, sisanya transparan |
| `hiasan-kanan-bawah.webp` | 1024 × 1024 | WebP alfa | 120 KB | Pasangan sudut kanan bawah, gaya sama |
| `podium.webp` | 1600 × 900 | WebP alfa | 200 KB | Podium 3D tiga tingkat (tengah tertinggi) kosong, tanpa angka |
| `medali-emas.webp` | 512 × 512 | WebP alfa | 60 KB | Medali emas berpita biru, permukaan polos |
| `medali-perak.webp` | 512 × 512 | WebP alfa | 60 KB | Medali perak berpita biru |
| `medali-perunggu.webp` | 512 × 512 | WebP alfa | 60 KB | Medali perunggu berpita biru |
| `piala.webp` | 512 × 512 | WebP alfa | 60 KB | Piala juara umum 3D, polos |

## Catatan

- Videotron biasanya memperlihatkan warna lebih jenuh: hindari putih bersih area luas dan gradien yang terlalu halus (pita warna). Tulisan putih dan kuning akan ditaruh di atas latar.
- Hiasan sudut akan diperkecil sampai 15% sisi pendek layar; detailnya harus tetap terbaca kecil dan tidak mengganggu tabel.
- Tampilkan pratinjau gabungan untuk 16:9, 4:1, dan 9:16 di **deskripsi PR** (gambar tempelan, tidak di-commit), dan jelaskan susunannya di `desain/catatan/G9-06.md`.

## Selesai bila

Sembilan berkas sesuai tabel, `pita-tepi.webp` menyambung saat diulang, dan `desain/catatan/G9-06.md` ada.
