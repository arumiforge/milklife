# G10-05 Ilustrasi nomor lomba

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F9-03, F9-12, F9-17 (sudah terpasang lewat `IlustrasiBeranda::nomor()`) |
| Menggantikan | G9-05 |

## Tujuan

Ikon nomor lomba bergaya anime, nama berkas sama dengan G9-05.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`. Ikon harus tetap jelas saat dikecilkan sampai 64 piksel: garis tebal, siluet kuat, latar transparan tanpa lingkaran atau kotak di belakangnya.

## Keluaran

Folder `public/aset/gambar/nomor/`, **WebP alfa 512 × 512, paling besar 60 KB**.

| Berkas | Nomor | Gambar |
|---|---|---|
| `lari-pendek.webp` | Lari 40/60/80/100 m | Pelari keluar dari balok start |
| `lari-menengah.webp` | Lari 400–1500 m | Pelari di tikungan lintasan |
| `jalan-cepat.webp` | Jalan Cepat | Pejalan cepat dengan ayunan lengan |
| `estafet.webp` | Estafet | Dua tangan menyerahkan tongkat |
| `lompat-jauh.webp` | Lompat Jauh | Pelompat melayang di atas bak pasir |
| `lompat-tinggi.webp` | Lompat Tinggi | Pelompat melewati mistar gaya flop |
| `tolak-peluru.webp` | Tolak Peluru | Peluru di tangan siap ditolak |
| `lempar-lembing.webp` | Lempar Lembing | Lembing melesat dengan garis kecepatan |
| `lempar-cakram.webp` | Lempar Cakram | Cakram berputar |
| `frog-jump.webp` | Frog Jump | Anak melompat jongkok |
| `turbo-throw.webp` | Turbo Throw | Turbo busa bersirip melesat |
| `kangas-escape.webp` | Kanga's Escape | Anak melompati gawang rendah busa |
| `formula-1.webp` | Formula 1 | Rangkaian kerucut dan gawang mini berkelok |
| `trilomba.webp` | Trilomba / Pancalomba | Sepatu lari, bak pasir, dan peluru tersusun segitiga |

`seneng-playon.webp` **tidak dibuat** (KU 8 tanpa "Seneng Playon").

## Logo di kaus (K153)

Tokoh yang memakai kaus peserta atau panitia digambar dengan **area dada polos** (tanpa huruf, angka, atau simbol). Setelah gambar jadi, logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** dengan skrip pasca-proses (misalnya Pillow): diperkecil sekitar 18–22% lebar dada, diberi sedikit perspektif/rotasi mengikuti arah badan, tanpa digambar ulang, tanpa diubah warnanya, dan tidak tertutup lengan. Bila dada tidak terlihat jelas (badan menyamping atau terlalu kecil), logo tidak ditempel. Skrip penempelan tidak di-commit; langkahnya ditulis di `desain/catatan/<ID>.md`.

## Selesai bila

Empat belas berkas sesuai tabel dan `desain/catatan/G10-05.md` ada.
