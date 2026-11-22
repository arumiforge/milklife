# G10-09 Pita judul halaman publik

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F9-19 (sudah terpasang di `_judul_halaman_tw`) |
| Menggantikan | G9-09 |

## Tujuan

Ilustrasi pita judul halaman publik bergaya anime, nama berkas sama dengan G9-09. Pita judul menjadi bagian editorial (judul Inggris + keterangan Indonesia, F10-09), jadi gambar lebih dinamis dan bersudut rendah.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`.

## Keluaran

Folder `public/aset/gambar/judul/`, **WebP alfa 960 × 640, paling besar 110 KB**, objek di tengah dengan ruang kosong; disembunyikan di layar sempit.

| Berkas | Halaman | Adegan |
|---|---|---|
| `jadwal.webp` | Jadwal | Bendera start dan jam dinding stadion (tanpa angka) di samping lintasan |
| `hasil.webp` | Hasil | Tokoh A menembus pita finis, garis kecepatan |
| `klasemen.webp` | Klasemen | Podium dengan piala dan medali, kilau anime |
| `peserta.webp` | Peserta | Tokoh A–D berjajar melambai |
| `galeri.webp` | Galeri | Kamera dan tiga bingkai foto kosong melayang |
| `bantuan.webp` | Bantuan | Tokoh E mengangkat tangan menawarkan bantuan, peluit |
| `unduhan.webp` | Unduhan | Map dokumen dengan panah bawah besar (bentuk, bukan huruf) |
| `seri.webp` | Seri dan lintasan | Lintasan delapan jalur dari sudut rendah dengan balok start |
| `cek-pendaftaran.webp` | Cek Pendaftaran | Kaca pembesar di atas kartu peserta polos |
| `kalkulator.webp` | Kalkulator poin | Kalkulator tanpa angka di tombol, peluru, sepatu lari |
| `aturan.webp` | Aturan nomor | Buku aturan tertutup, peluit, bendera juri |

## Logo di kaus (K153)

Tokoh yang memakai kaus peserta atau panitia digambar dengan **area dada polos** (tanpa huruf, angka, atau simbol). Setelah gambar jadi, logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** dengan skrip pasca-proses (misalnya Pillow): diperkecil sekitar 18–22% lebar dada, diberi sedikit perspektif/rotasi mengikuti arah badan, tanpa digambar ulang, tanpa diubah warnanya, dan tidak tertutup lengan. Bila dada tidak terlihat jelas (badan menyamping atau terlalu kecil), logo tidak ditempel. Skrip penempelan tidak di-commit; langkahnya ditulis di `desain/catatan/<ID>.md`.

## Selesai bila

Sebelas berkas sesuai tabel dan `desain/catatan/G10-09.md` ada.
