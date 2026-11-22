# F10-06 Hero editorial dan slideshow

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Layar pertama halaman depan tampil seperti `desain/referensi/hero-editorial.webp` dengan foto asli acara, dan semua carousel berganti sendiri dengan tenang (permintaan C15, C16, C20).

## Hasil

- [x] Hero: latar biru tua, alis slogan Inggris, judul besar Barlow Condensed huruf besar dengan satu kata oranye, keterangan Indonesia, tombol Daftar Sekarang dan Cek Pendaftaran mengikuti buka/tutup (K81); tahun di judul tetap disembunyikan
- [x] Foto asli carousel (`CarouselHalamanDepan`) memenuhi sisi kanan dengan gradasi biru tua ke kiri; keterangan foto kecil di pojok kanan bawah bila ada; ponsel: foto di atas dengan gradasi ke bawah
- [x] Pita tiga fakta di bawah hero (latar krem): Hari lomba, Lokasi, Batas pendaftaran "30 November 2026", dari pengaturan acara
- [x] Slideshow hero dan semua carousel publik: ganti tiap 5 detik, hanya titik penanda (tombol aksesibel `aria-label` "Foto n dari m"), tanpa tombol maju/mundur; berhenti saat `prefers-reduced-motion`, tab tidak terlihat, dan fokus di dalamnya
- [x] Foto pertama `fetchpriority="high"` dengan `srcset`; foto berikutnya dimuat menjelang tampil; latar warna rata-rata (F9-17) tetap
- [x] Sapuan hero G10-02 (`beranda/hero-sapuan.webp`) lewat `gambar_opsional()`
- [x] Anggaran kinerja rencana diukur sebelum dan sesudah, angkanya di deskripsi PR

## Berkas dan uji

`app/Views/beranda/_kepala.php`, `beranda.php`, `app/Libraries/IsiHalamanDepan.php`, `CarouselHalamanDepan`, `public/aset/js/carousel-beranda.js`, blok F10-06 `masukan.css`. Uji: `HalamanDepanKepalaTest`, `HalamanDepan*`, `KepalaKeamananTest`.

## Selesai bila

Hero sesuai rujukan di 360/390/1280 (minify), slideshow berhenti dengan `prefers-reduced-motion` (emulasi Playwright), anggaran kinerja terpenuhi, alur daftar → cek → bukti tetap, semua uji lulus.

## Catatan

Bergantung pada F10-05. Peta (F10-02) dan menu (F10-07) di berkas lain.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
