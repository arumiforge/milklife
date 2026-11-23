# G11-01 Art dan isian angka kartu kelompok umur

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (K175, K179, K180) |
| Dibuat | 22 November 2026, diperbarui 23 November 2026 setelah kartu KU dirombak F11-15 |
| Dipakai oleh | F11-12 dan F11-15 (kartu kelompok umur di bawah hero halaman depan) |
| Menggantikan | — (baru; `beranda/kartu-ku.webp` G10-02 tetap ada) |

## Tujuan

Setiap kartu kelompok umur (KU 8, KU 10, KU 12, KU 15, KU 18) punya **ciri khas sendiri**. Angka KU ditulis HTML dengan huruf raksasa yang **memenuhi kartu**: melewati tepi bawah, digit kedua melewati tepi kanan, lalu terpotong kotak kartu (K179). Isian huruf angka itu **bertekstur gambar** (CSS `background-clip: text`), bukan warna polos. Di belakang angka ada **art latar pudar** yang tampil di sela huruf. Kesannya artistik dan sporty.

Sampai gambar ini ada, kartu memakai gradien warna dan garis miring samar. Setelah berkas digabung, gambar langsung dipakai tanpa perubahan kode.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama". Tanpa teks, huruf, angka, logo, titik hiasan, atau halftone. **Angka KU tidak digambar**; angka selalu ditulis halaman web.

## Keluaran

Folder `public/aset/gambar/ku/`. Dua berkas per KU, `<n>` = angka KU (`8`, `10`, `12`, `15`, `18`), jadi sepuluh berkas. Nama berkas harus persis, karena halaman mencarinya dengan nama itu.

| Berkas | Ukuran | Format | Paling besar | Isi |
|---|---|---|---|---|
| `ku-<n>-isian.webp` | 600 × 800 (tegak 3:4) | WebP **tanpa alfa**, kualitas 80–85 | 90 KB | Tekstur isian huruf angka KU: rapat, berwarna kuat, tanpa ruang kosong. Ciri khas utama tiap KU ada di sini |
| `ku-<n>-art.webp` | 1000 × 800 (5:4) | WebP **tanpa alfa**, kualitas 80 | 80 KB | Art latar pudar seluruh kartu: adegan khas KU berwarna lembut di atas dasar putih. Hanya terlihat di sela huruf, jadi harus tenang |

## Cara dipasang

Kartu dibuat di `app/Views/beranda/_kepala.php`, gayanya di `public/aset/tailwind/masukan.css` bagian F11-12 dan F11-15.

**Kartu.** Kotak putih bersudut bulat, rasio **5:4** (lebar : tinggi = 1,25) di semua layar. Lebarnya 138 px di ponsel 320 px, 173 px di ponsel 390 px, 237 px di tablet, dan 240 px di desktop (lima kartu sebaris). Isinya terpotong tepi kartu (`overflow: hidden`).

**Isian (`ku-<n>-isian.webp`).** Dipasang sebagai latar huruf angka dengan `background-size: cover; background-position: center`, jadi gambar dipotong mengikuti kotak huruf:

| | Ponsel 320 px | Desktop |
|---|---|---|
| Besar huruf | 132 px | 243 px |
| Kotak "8" (satu digit) | 64 × 132 px | 119 × 243 px |
| Kotak "10", "12", "15", "18" | 100 × 132 px | 184 × 243 px |
| Tebal batang huruf | sekitar 22 px | sekitar 41 px |

- Di layar retina kotak terbesar menjadi sekitar 370 × 490 piksel layar, jadi 600 × 800 cukup tajam.
- Dua digit memakai hampir seluruh lebar gambar. Satu digit ("8") hanya memakai **65% tengah** lebar gambar.
- Tekstur hanya tampak **di dalam batang huruf** setebal 22–41 px. Bentuk dalam tekstur (sapuan, kilat, garis) dibuat besar, sekitar 60–150 px di gambar sumber, supaya tetap terbaca di dalam batang itu. Pola halus akan hilang.
- **Bagian yang terlihat**: 15% atas gambar ada di atas puncak huruf (tidak terlihat), dan sekitar 10% bawah terpotong tepi bawah kartu. Warna dan bentuk terkuat ditaruh di 15–90% tinggi gambar.

**Art (`ku-<n>-art.webp`).** Menutupi seluruh kartu di belakang angka: `absolute inset-0 size-full object-cover`. Rasio gambar sama dengan kartu, jadi tidak ada yang terpotong. Letak unsur kartu di atas art, dalam persen lebar dan tinggi kartu (sama di ponsel dan desktop):

| Unsur | Kiri–kanan | Atas–bawah | Akibat untuk art |
|---|---|---|---|
| Tulisan kecil "KU" | 7–30% | 28–57% | Area tenang, tanpa objek berkontras |
| Angka raksasa | 39% sampai lewat tepi kanan | dari atas sampai lewat tepi bawah | Art di sini tertutup huruf dan tampil hanya di sela huruf (lubang "0" dan "8", celah antardigit) |
| Jumlah nomor (pil putih kecil berikon) | 7–53% | 72–96% | Area tenang |
| Sisa yang paling terlihat | 0–39% | 0–28% dan 57–72% | Tempat objek utama art: pojok kiri atas dan pita kiri tengah |

### Tema tiap KU

| KU | Usia | Tema art latar (`ku-<n>-art.webp`) | Tekstur isian (`ku-<n>-isian.webp`) dan warna dasar |
|---|---|---|---|
| 8 | SD kelas bawah | Perlengkapan permainan atletik anak (kerucut, gawang mini, turbo javelin busa) dan sepatu kecil, ceria | Sapuan kuas oranye `#E85F31` ke magenta `#F0005A` dengan garis kecepatan putih tipis |
| 10 | SD kelas tengah | Tokoh D (G10-01) melompat katak atau berlari estafet, garis kecepatan | Oranye `#E85F31` ke kuning hangat, sapuan kuas miring |
| 12 | SD kelas atas | Tokoh A sprint dari balok start, sudut rendah | Biru `#234987` ke biru muda `#9BCCEC`, kilat cahaya miring |
| 15 | SMP | Tokoh B (berjilbab) lompat jauh melayang, pasir berhamburan | Biru tua `#021F7B` ke magenta `#F0005A`, sapuan tinta tegas |
| 18 | SMA | Tokoh C tolak peluru atau lempar lembing, badan memutar | Biru tua `#021F7B` ke oranye `#E85F31`, garis lintasan merah bata dan putih |

Tokoh mengikuti `desain/catatan/G10-01.md`; usia tokoh disesuaikan dengan KU (KU 8 dan 10 lebih kecil, KU 15 dan 18 remaja). Kaus mengikuti palet; logo di kaus tidak perlu (gambar kecil).

## Syarat teknis

- **Isian**: kontras cukup di atas kartu putih. Rata-rata kecerahan tekstur di bawah 60% supaya angka tetap jelas; tanpa bagian putih atau sangat terang yang lebih besar dari batang huruf, karena huruf akan terlihat bolong.
- **Isian**: tidak perlu menyambung bila diulang, dan tanpa objek penting di tengah yang bisa terpotong bentuk huruf.
- **Art**: dasar putih `#FFFFFF` di semua tepi supaya menyatu dengan kartu. Warna lembut dan terang (rata-rata kecerahan di atas 85%), seperti cat air tipis bergaya cel-shaded: garis tinta tipis berwarna biru muda atau abu kebiruan, bukan hitam. Angka berisian tekstur harus tetap paling menonjol.
- **Art**: tanpa bayangan kotak, bingkai, atau latar penuh berwarna kuat.
- Lima art dan lima isian terlihat satu keluarga: garis, cahaya, dan sudut kamera sama; yang membedakan tema dan warna.

## Catatan

- Pratinjau di **deskripsi PR**: tiap pasangan berkas ditempel di kartu putih **240 × 192 px** bersudut bulat 16 px, art menutupi seluruh kartu. Di atasnya angka "8", "10", "12", "15", "18" berhuruf Barlow Condensed 800 italic berukuran 243 px (118% lebar isi kartu setelah jarak tepi 16 px kiri dan kanan), mulai 39% dari kiri dan sedikit di atas tepi atas kartu, berisi tekstur isian (`background-clip: text`, `cover`), terpotong tepi kartu. Tambahkan "KU" kecil (sekitar 53 px) di 7% kiri, 28% atas. Satu pratinjau juga di lebar 138 × 110 px (ponsel kecil).
- Tulis kecerahan rata-rata tiap `ku-<n>-isian.webp` dan `ku-<n>-art.webp` di `desain/catatan/G11-01.md`.

## Selesai bila

Sepuluh berkas sesuai tabel ada di `public/aset/gambar/ku/` dan `desain/catatan/G11-01.md` ada.
