# G9-01 Latar nomor atlet (BIB), empat pilihan

| | |
|---|---|
| Tahap | 9 — Rombak tampilan (K140) |
| Dibuat | 21 November 2026 |
| Dipakai oleh | F9-16: pilihan "latar bawaan" di Hari lomba > Cetak BIB (K137) |

## Tujuan

Panitia bisa memilih latar BIB yang rapi tanpa harus mendesain sendiri. Tulisan BIB (nomor, nama, sekolah, kepala) dicetak web di atas latar ini.

## Keluaran

Folder `public/aset/gambar/bib/`, **JPG kualitas 90, 2480 × 1754 piksel** (setengah A4 mendatar 210 × 148,5 mm pada 300 dpi), RGB, masing-masing **paling besar 2 MB**.

| Berkas | Untuk tulisan | Suasana |
|---|---|---|
| `latar-terang-1.jpg` | gelap (`#021F7B`) | putih kebiruan, pita miring biru-oranye di sudut kiri bawah dan kanan atas |
| `latar-terang-2.jpg` | gelap | gradien `#F3F6FB` ke `#E6F2FB`, garis lintasan melengkung tipis di bawah |
| `latar-gelap-1.jpg` | terang (putih) | biru tua `#021F7B` ke biru `#234987`, sorot cahaya, pita oranye-magenta di tepi |
| `latar-gelap-2.jpg` | terang | biru dengan pola lintasan diagonal samar dan aksen oranye di sudut |

## Zona aman (wajib)

Ukuran dalam milimeter dari kiri atas; 1 mm = 11,81 piksel.

- **Area nomor dan nama**, x 10–200 mm, y 30–127 mm (118–2362 × 354–1500 piksel): **tenang dan rata**, tanpa objek, garis tegas, atau perubahan terang-gelap yang kuat. Kontras dengan warna tulisan sangat tinggi.
- **Kepala**, y 8–28 mm: tetap tenang (logo dan nama acara bisa dicetak di sini).
- **Hiasan hanya** di pita tepi: atas 0–8 mm, bawah 127–148,5 mm, kiri dan kanan 0–10 mm, dan sudut.
- **Lubang peniti** di empat sudut (lingkaran 12 mm dari tiap sudut): jangan taruh detail penting di sana.
- Hiasan boleh sampai tepi (*bleed*), karena web memotong pas ukuran.

## Larangan

Tanpa teks, angka, logo, merek, dan tokoh. Tidak ada objek 3D besar di tengah. Tidak memakai warna merah murni untuk area luas (boros tinta, tulisan sulit dibaca).

## Selesai bila

Empat berkas ada dengan ukuran dan besar yang benar, zona aman tenang, dan `desain/catatan/G9-01.md` berisi ringkasan prompt.
