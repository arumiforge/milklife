# Petunjuk untuk Codex

Repo ini web pendaftaran MilkLife Athletics Challenge Seri 2 – 2026. Di repo ini **Codex hanya membuat aset gambar** (K140). Semua kode (PHP, view, CSS, JavaScript, uji, migrasi, dokumen di luar `desain/`) ditulis dan digabung oleh agen lain. Jangan menyentuhnya, walaupun terlihat salah.

## Cara kerja

1. Baca `desain/README.md`, lalu pilih **satu** tugas di `desain/tugas/` yang belum punya PR terbuka atau tergabung. Kerjakan sesuai urutan di README kecuali pemilik menyebut tugas lain.
2. Buat cabang `desain/<ID>-<slug>`, misalnya `desain/G9-01-latar-bib`, dari `main` terbaru.
3. Buat gambar persis seperti brief: ukuran piksel, format, nama berkas, folder keluaran, dan batas besar berkas. Periksa ukuran dan besar berkas sebelum commit.
4. Tulis `desain/catatan/<ID>.md`: daftar berkas, ukuran, besar, dan ringkasan prompt yang dipakai per gambar, supaya gambar bisa dibuat ulang.
5. Buka **satu PR per tugas** ke `main`. Judul dan isi PR berbahasa Indonesia, misalnya `desain(G9-01): latar nomor atlet (BIB) empat pilihan`. Isi PR: daftar berkas dan pratinjau. Jangan menggabung PR sendiri.

## Boleh dan tidak boleh

- **Hanya boleh menulis** ke folder keluaran yang disebut tugas (di bawah `public/aset/gambar/`) dan ke `desain/catatan/<ID>.md`. Tidak ada berkas lain yang boleh berubah, termasuk `desain/tugas/`, `desain/README.md`, `agent/`, dan berkas ini.
- **Tidak boleh ada teks, huruf, angka, logo, atau merek** di dalam gambar, termasuk logo MilkLife, sponsor, dan tulisan di kaus atau papan. Teks selalu ditulis halaman web di atas gambar.
- **Tidak boleh ada wajah realistis** atau gambar yang bisa disangka foto asli acara. Tokoh bergaya 3D lembut dengan wajah sederhana, anak usia sekolah, berpakaian olahraga sopan, beragam (berjilbab dan tidak).
- Tidak memakai foto di `bahan-rombak/` sebagai bahan gambar baru; foto itu hanya rujukan suasana dan warna.
- Gambar untuk anak sekolah: tanpa kekerasan, tanpa unsur dewasa, tanpa simbol politik atau agama tertentu selain pakaian sehari-hari.

## Gaya bersama

- **3D lembut** (*soft 3D*, seperti tanah liat atau plastik doff), pencahayaan terang dan merata, bayangan halus, sudut membulat, latar bersih. Mode terang.
- Palet MilkLife (dari `public/aset/css/tema.css` dan flyer resmi):

  | Warna | Kode | Pemakaian |
  |---|---|---|
  | Biru | `#234987` | utama |
  | Biru tua | `#021F7B` | kontras, bayangan |
  | Oranye | `#E85F31` | aksen, energi |
  | Magenta | `#F0005A` | aksen sekunder (flyer) |
  | Biru muda | `#9BCCEC` | latar, cahaya |
  | Latar | `#F3F6FB` | dasar terang |

- Motif yang dipakai: lintasan lari merah bata dengan garis putih, pita miring biru-oranye-magenta, siluet gerak atlet, kerucut, gawang mini, bola turbo, peluru, lembing.
- Gambar transparan berformat **PNG** atau **WebP dengan alfa**; gambar penuh berformat **WebP** (kualitas 80–85) atau **JPG** bila brief menyebut cetak.

## Commit

- Penulis commit **Arumi Studios** `<226232558+arumiforge@users.noreply.github.com>`: `git config user.name "Arumi Studios"` dan `git config user.email "226232558+arumiforge@users.noreply.github.com"`.
- **Tanpa atribusi alat AI** di mana pun: tanpa `Co-Authored-By`, tautan sesi, atau tulisan "Generated with …" di commit, PR, berkas, dan metadata gambar.
- Pesan commit berbahasa Indonesia: `desain(<ID>): <ringkasan>`.
