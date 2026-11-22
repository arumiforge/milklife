# G10-11 Elemen tombol, tekstur, pemisah, dan ornamen

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F10-05 (kelas `tombol-utama-berart`, `pemisah-lintasan`, `ornamen-judul`, `bingkai-foto`, `tekstur-kertas`), F10-10 |
| Menggantikan | — (baru) |

## Tujuan

Art pendukung yang memberi karakter pada elemen halaman lewat CSS. **Teks tombol dan judul tetap HTML**, gambar hanya latar atau hiasan di belakang/samping teks (permintaan D26).

Gaya: sapuan kuas dan garis tinta bergaya anime 2D cel-shaded (garis tegas, tepi kering kuas), palet merek. **Tanpa** teks, titik atau pola titik, halftone, dan bingkai penuh berwarna di empat sisi.

## Keluaran

Folder `public/aset/gambar/elemen/`, WebP alfa kecuali disebut lain.

| Berkas | Ukuran | Paling besar | Isi dan cara pakai |
|---|---|---|---|
| `kuas-tombol-oranye.webp` | 640 × 160 | 30 KB | Sapuan kuas oranye `#C4461B`→`#E85F31` mendatar, tepi kiri-kanan kering, bagian tengah rata dan cukup gelap untuk teks putih (kontras AA); dipakai `background-size: 100% 100%` di belakang tombol utama |
| `kuas-tombol-biru.webp` | 640 × 160 | 30 KB | Sama, biru tua `#021F7B`→`#234987` untuk tombol kedua di latar terang |
| `kuas-sorot.webp` | 800 × 120 | 25 KB | Sapuan oranye tipis miring untuk menyorot satu kata judul (di belakang teks, 40% tinggi teks) |
| `pemisah-lintasan.webp` | 1920 × 96 | 40 KB | Potongan lintasan merah bata dengan dua garis putih tegas dan garis kecepatan, **menyambung bila diulang ke samping** |
| `ornamen-judul-kiri.webp` | 240 × 120 | 15 KB | Tiga garis kecepatan miring oranye dan biru, ditaruh di kiri judul bagian |
| `ornamen-judul-kanan.webp` | 240 × 120 | 15 KB | Pasangan cermin untuk kanan |
| `bingkai-sudut.webp` | 400 × 400 | 25 KB | Sudut bingkai foto bergaya sapuan tinta biru tua dengan aksen oranye (dipasang di dua sudut berlawanan, diputar CSS) |
| `tekstur-kertas.webp` | 1024 × 1024 | 60 KB | Tekstur kertas krem `#F5EFE6` dengan serat halus, **tanpa alfa**, menyambung bila diulang, untuk pita fakta dan bagian editorial |
| `garis-kecepatan.webp` | 1600 × 400 | 40 KB | Garis kecepatan putih tipis mendatar, sebagian besar transparan, untuk latar biru tua |

## Catatan

- Periksa kontras teks putih di atas `kuas-tombol-oranye.webp` dan `kuas-tombol-biru.webp` (rasio ≥ 4,5:1 di bagian tengah) dan tulis hasilnya di catatan.
- Berkas berulang (`pemisah-lintasan`, `tekstur-kertas`) diuji dengan menempelkan dua salinan berdampingan; sambungan tidak terlihat.
- Pratinjau tombol, judul, dan pemisah di atas latar `#F3F6FB` dan `#021F7B` di **deskripsi PR**.

## Selesai bila

Sembilan berkas sesuai tabel dan `desain/catatan/G10-11.md` ada.
