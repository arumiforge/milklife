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
- **Tidak boleh ada teks, huruf, angka, logo, atau merek** yang digambar model di dalam gambar, termasuk logo MilkLife, sponsor, dan tulisan di kaus atau papan. Teks selalu ditulis halaman web di atas gambar. **Pengecualian (K153)**: bila brief memintanya, kaus peserta atau panitia digambar dengan area dada polos, lalu logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` ditempel apa adanya saat pasca-proses (misalnya dengan Pillow), diperkecil dan diberi sedikit perspektif, tanpa digambar ulang.
- **Tidak boleh ada wajah realistis** atau gambar yang bisa disangka foto asli acara. Tokoh bergaya anime 2D cel-shaded (K153), anak usia sekolah dengan proporsi wajar (bukan chibi, bukan dewasa), berpakaian olahraga sopan, beragam (berjilbab dan tidak). Pakai tokoh tetap dari `desain/catatan/G10-01.md` bila sudah ada.
- **Tidak meniru karya lain**: inspirasi *100 Meters (Hyakuemu)* hanya arah gaya (cel-shading, garis tegas, sudut rendah, rasa kecepatan); jangan meniru tokoh, wajah, rambut khas, adegan, atau komposisi khas anime itu atau anime lain.
- Tidak memakai foto di `sumber/foto-acara/` sebagai bahan gambar baru; foto itu hanya rujukan suasana dan warna.
- Gambar untuk anak sekolah: tanpa kekerasan, tanpa unsur dewasa, tanpa simbol politik atau agama tertentu selain pakaian sehari-hari.

## Gaya bersama

Berlaku untuk semua tugas **G10** (K153). Brief G9 lama bergaya 3D lembut dan sudah digantikan.

- **Anime olahraga 2D cel-shaded**: garis tinta tegas, bayangan berbentuk tajam dua sampai tiga tingkat (tanpa gradasi halus ala 3D), sorotan cahaya bersih, sudut kamera rendah, pose dinamis, garis kecepatan, langit biru cerah bergaris awan. Mode terang; latar bersih.
- **Prompt dasar** (dari pemilik), selalu dipakai lalu ditambah adegan dari brief:

  > 2D cel-shaded sports anime illustration, school-age track athletes, dynamic sprint poses, bold linework, crisp shadow shapes, low-angle track composition, brand-colored jerseys.

  Tambahan tetap: `clean background, no text, no letters, no numbers, no logos, no watermark, modest sportswear, Indonesian school kids, original characters`.
- **Rujukan**: `desain/referensi/gaya-anime-lintasan.webp` (lintasan sekolah, tiga pelari, salah satunya berjilbab). Ikuti gaya garis, warna, dan cahayanya, bukan menyalin gambarnya.
- **Palet MilkLife**:

  | Warna | Kode | Pemakaian |
  |---|---|---|
  | Biru | `#234987` | kaus utama, warna utama |
  | Biru tua | `#021F7B` | kaus panitia, bayangan, latar gelap |
  | Oranye | `#E85F31` | kaus kedua, aksen, energi |
  | Magenta | `#F0005A` | aksen sekunder, hemat |
  | Biru muda | `#9BCCEC` | cahaya, kaus ketiga |
  | Krem | `#F5EFE6` | kertas, bagian editorial |
  | Latar | `#F3F6FB` | dasar terang |
  | Lintasan | merah bata dengan garis putih | lintasan lari |

- **Larangan bentuk** (permintaan pemilik): tanpa ikon atau benda berlatar kotak/lingkaran, tanpa bingkai penuh berwarna di empat sisi, **tanpa titik hiasan, pola titik, atau halftone**, konfeti berbentuk pita/potongan kertas.
- **Logo di kaus (K153)**: bila brief meminta, kaus digambar dengan area dada polos, lalu logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` ditempel apa adanya saat pasca-proses (Pillow): diperkecil sekitar 18–22% lebar dada, sedikit perspektif mengikuti badan, warna tidak diubah. Logo **tidak pernah** digambar model. Langkahnya dicatat di `desain/catatan/<ID>.md`; skripnya tidak di-commit.
- Gambar transparan berformat **WebP dengan alfa** (atau PNG bila brief menyebut); gambar penuh **WebP** (kualitas 80–85) atau **JPG** bila brief menyebut. Nama berkas G10 pengganti sama dengan G9 supaya langsung terpakai.

## Commit

- Penulis commit **Arumi Studios** `<226232558+arumiforge@users.noreply.github.com>`: `git config user.name "Arumi Studios"` dan `git config user.email "226232558+arumiforge@users.noreply.github.com"`.
- **Tanpa atribusi alat AI** di mana pun: tanpa `Co-Authored-By`, tautan sesi, atau tulisan "Generated with …" di commit, PR, berkas, dan metadata gambar.
- Pesan commit berbahasa Indonesia: `desain(<ID>): <ringkasan>`.
