# G11-01 Art dan isian angka kartu kelompok umur

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (K175) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F11-12 (kartu kelompok umur di bawah hero halaman depan) |
| Menggantikan | — (baru; `beranda/kartu-ku.webp` G10-02 tetap ada) |

## Tujuan

Setiap kartu kelompok umur (KU 8, KU 10, KU 12, KU 15, KU 18) punya **art khas masing-masing**. Di kartu, angka KU ditulis HTML dengan huruf sangat besar dan **isian hurufnya bertekstur gambar** (CSS `background-clip: text`), bukan warna polos. Kesannya artistik dan sporty. Sampai gambar ini ada, kartu memakai gradien warna; setelah berkas digabung, gambar langsung dipakai tanpa perubahan kode.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama". Tanpa teks, huruf, angka, logo, titik hiasan, atau halftone. **Angka KU tidak digambar**; angka selalu ditulis halaman web.

## Keluaran

Folder `public/aset/gambar/ku/`. Dua berkas per KU, `<n>` = angka KU (`8`, `10`, `12`, `15`, `18`), jadi sepuluh berkas.

| Berkas | Ukuran | Format | Paling besar | Isi |
|---|---|---|---|---|
| `ku-<n>-art.webp` | 800 × 600 | WebP alfa | 90 KB | Art khas KU di **separuh kanan** kanvas (separuh kiri transparan untuk angka dan teks). Ditaruh di pojok kanan bawah kartu putih, dipotong tepi kartu |
| `ku-<n>-isian.webp` | 600 × 600 | WebP **tanpa alfa** | 70 KB | Tekstur isian huruf angka KU: rapat, berwarna kuat, tanpa ruang kosong. Dipakai `background-size: cover` di dalam huruf tebal setinggi sekitar 72–96 px, jadi pola harus tetap terbaca di ukuran kecil |

### Tema tiap KU

| KU | Usia | Tema art (`ku-<n>-art.webp`) | Tekstur isian (`ku-<n>-isian.webp`) dan warna dasar |
|---|---|---|---|
| 8 | SD kelas bawah | Perlengkapan permainan atletik anak (kerucut, gawang mini, turbo javelin busa) dan sepatu kecil, ceria | Sapuan kuas oranye `#E85F31` ke magenta `#F0005A` dengan garis kecepatan putih tipis |
| 10 | SD kelas tengah | Tokoh D (G10-01) melompat katak atau berlari estafet, garis kecepatan | Oranye `#E85F31` ke kuning hangat, sapuan kuas miring |
| 12 | SD kelas atas | Tokoh A sprint dari balok start, sudut rendah | Biru `#234987` ke biru muda `#9BCCEC`, kilat cahaya miring |
| 15 | SMP | Tokoh B (berjilbab) lompat jauh melayang, pasir berhamburan | Biru tua `#021F7B` ke magenta `#F0005A`, sapuan tinta tegas |
| 18 | SMA | Tokoh C tolak peluru atau lempar lembing, badan memutar | Biru tua `#021F7B` ke oranye `#E85F31`, garis lintasan merah bata dan putih |

Tokoh mengikuti `desain/catatan/G10-01.md`; usia tokoh disesuaikan dengan KU (KU 8 dan 10 lebih kecil, KU 15 dan 18 remaja). Kaus mengikuti palet; logo di kaus tidak perlu (gambar kecil).

## Syarat teknis

- **Isian**: kontras cukup di atas kartu putih. Rata-rata kecerahan tekstur di bawah 60% supaya angka tetap jelas; tanpa bagian putih besar.
- **Isian**: tidak perlu menyambung bila diulang (dipakai `cover`), tetapi tidak boleh ada objek penting di tengah yang terpotong huruf.
- **Art**: objek utama di sepertiga kanan dan bawah; sisi kiri dan atas memudar ke transparan supaya tidak menabrak teks. Tanpa bayangan kotak atau latar penuh.
- Lima art terlihat satu keluarga: garis, cahaya, dan sudut kamera sama; yang membedakan tema dan warna.

## Catatan

- Pratinjau di **deskripsi PR**: tiap pasangan berkas ditempel di kartu putih 260 × 180 px dengan angka "8", "10", "12", "15", "18" berhuruf tebal miring (misalnya Barlow Condensed 800 italic) berisi tekstur isian.
- Tulis kecerahan rata-rata tiap `ku-<n>-isian.webp` di `desain/catatan/G11-01.md`.

## Selesai bila

Sepuluh berkas sesuai tabel ada di `public/aset/gambar/ku/` dan `desain/catatan/G11-01.md` ada.
