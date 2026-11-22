# G10-04 Keadaan kosong, galat, dan berhasil

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F9-17 (sudah terpasang) |
| Menggantikan | G9-04 |

## Tujuan

Layar kosong, galat, dan berhasil bergaya anime, nama berkas sama dengan G9-04.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`.

## Keluaran

Folder `public/aset/gambar/keadaan/`, **WebP alfa 800 × 600, paling besar 100 KB**, objek di tengah dengan ruang kosong di sekelilingnya.

| Berkas | Adegan |
|---|---|
| `kosong-data.webp` | Kotak kardus kosong dengan kerucut, tetesan keringat gaya anime |
| `kosong-hasil.webp` | Podium kosong dengan stopwatch menunggu (jarum, tanpa angka) |
| `tidak-ketemu.webp` | Tokoh D melihat lewat kaca pembesar di lintasan kosong |
| `galat-404.webp` | Tokoh A berlari keluar jalur, bingung tapi tersenyum, tanda tanya besar (bentuk, bukan huruf) |
| `galat-500.webp` | Gawang mini terjatuh, tokoh C memperbaikinya dengan kunci pas |
| `pendaftaran-tutup.webp` | Gerbang stadion tertutup bergembok, langit senja jingga |
| `berhasil.webp` | Tokoh B melompat gembira, konfeti biru-oranye-magenta berbentuk pita (bukan bulatan) |
| `memuat.webp` | Sepatu lari di garis start dengan garis kecepatan |

## Logo di kaus (K153)

Tokoh yang memakai kaus peserta atau panitia digambar dengan **area dada polos** (tanpa huruf, angka, atau simbol). Setelah gambar jadi, logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** dengan skrip pasca-proses (misalnya Pillow): diperkecil sekitar 18–22% lebar dada, diberi sedikit perspektif/rotasi mengikuti arah badan, tanpa digambar ulang, tanpa diubah warnanya, dan tidak tertutup lengan. Bila dada tidak terlihat jelas (badan menyamping atau terlalu kecil), logo tidak ditempel. Skrip penempelan tidak di-commit; langkahnya ditulis di `desain/catatan/<ID>.md`.

## Selesai bila

Delapan berkas sesuai tabel dan `desain/catatan/G10-04.md` ada.
