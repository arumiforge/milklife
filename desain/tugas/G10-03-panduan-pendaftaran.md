# G10-03 Panduan pendaftaran

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F9-04 (sudah terpasang di Bantuan) |
| Menggantikan | G9-03 |

## Tujuan

Panduan bergambar langkah pendaftaran bergaya anime, nama berkas sama dengan G9-03.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`. Guru/ofisial digambar sebagai orang dewasa ramah berpakaian rapi (bukan kaus panitia). Layar ponsel berupa bentuk sederhana (kotak, garis, tombol berwarna) **tanpa tulisan**.

## Keluaran

Folder `public/aset/gambar/panduan/`, **WebP 1200 × 900, paling besar 150 KB**, latar terang `#F3F6FB` dengan garis kecepatan samar.

| Berkas | Adegan |
|---|---|
| `langkah-1-sekolah.webp` | Guru memegang ponsel, di layar kaca pembesar dan gedung sekolah |
| `langkah-2-ofisial.webp` | Guru berswafoto, di layar bingkai foto wajah sederhana |
| `langkah-3-atlet.webp` | Tiga kartu atlet (tokoh A, B, C kecil) dengan centang hijau |
| `langkah-4-biaya.webp` | Kalkulator dan koin bergaya cel-shaded, kuitansi kosong tanpa angka |
| `langkah-5-transfer.webp` | Ponsel memotret struk, panah unggah |
| `langkah-6-cek.webp` | Ponsel menampilkan dokumen dengan pola kotak-kotak abstrak (bukan kode QR asli) dan centang |
| `hari-lomba-qr.webp` | Panitia (tokoh E) di meja daftar ulang memindai ponsel guru, anak-anak antre memakai nomor dada kosong |

## Logo di kaus (K153)

Tokoh yang memakai kaus peserta atau panitia digambar dengan **area dada polos** (tanpa huruf, angka, atau simbol). Setelah gambar jadi, logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** dengan skrip pasca-proses (misalnya Pillow): diperkecil sekitar 18–22% lebar dada, diberi sedikit perspektif/rotasi mengikuti arah badan, tanpa digambar ulang, tanpa diubah warnanya, dan tidak tertutup lengan. Bila dada tidak terlihat jelas (badan menyamping atau terlalu kecil), logo tidak ditempel. Skrip penempelan tidak di-commit; langkahnya ditulis di `desain/catatan/<ID>.md`.

## Selesai bila

Tujuh berkas sesuai tabel dan `desain/catatan/G10-03.md` ada.
