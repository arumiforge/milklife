# F11-24 Foto hero baru, slideshow 3 detik, dan logo di kanan judul ponsel

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 26 November 2026 |

## Tujuan

Hero halaman depan memakai sembilan foto acara terbaru dari pemilik dan berganti lebih cepat, dan di ponsel logo MilkLife tampil di kanan judul (K191).

## Hasil

- [x] Sembilan foto `sumber/foto-acara/mlac*.webp` dikecilkan ke sisi 1600 piksel di `data/halaman-depan/carousel/` dan menjadi `CarouselHalamanDepan::BAWAAN`; sebelas foto bawaan lama dihapus dari `data/`.
- [x] `php spark halaman-depan:carousel` di server yang carousel-nya sudah diatur mengganti salinan foto bawaan lama (sidik SHA-1) dengan foto baru, tanpa mengubah foto unggahan admin dan urutannya; `pembaruan:periksa` memintanya selama foto lama masih ada.
- [x] Sampul album galeri bawaan untuk pemasangan baru memakai foto baru.
- [x] Slideshow hero berganti tiap 3 detik.
- [x] Di bawah layar lg logo acara (unggahan admin, salinan 400 piksel, atau logo bawaan) tampil di kanan judul hero, lapisan teratas, tidak menabrak judul mulai layar 320 piksel.
- [x] K191, ATURAN, rancangan revisi 48 beserta PDF-nya, `sumber/README.md`.

## Berkas dan uji

`data/halaman-depan/carousel/`, `sumber/foto-acara/mlac*.webp`, `app/Libraries/CarouselHalamanDepan.php`, `app/Commands/PasangCarousel.php`, `app/Libraries/PemeriksaPembaruan.php`, `app/Libraries/Galeri/AlbumBawaan.php`, `app/Views/beranda/_kepala.php`, `public/aset/js/carousel-beranda.js`, `public/aset/css/tw.css`. Uji `vendor/bin/phpunit --filter 'HalamanDepanCarouselTest|HalamanDepanKepalaTest|GambarOpsionalTest|PeriksaPembaruanTest|GaleriTest'`.

## Selesai bila

Pemasangan baru menampilkan sembilan foto baru, server lama mendapatkannya lewat `halaman-depan:carousel`, slideshow 3 detik, dan logo tampil di kanan judul di ponsel. Uji terkait lulus.

## Catatan

- Foto baru sudah berwatermark logo di pojok kanan atas. Di ponsel foto dipotong 4:3, jadi watermark bisa sedikit terpotong di tepi kanan.
- Logo juga tampil di tablet (sm dan md) karena susunan hero di sana sama dengan ponsel; mulai lg foto pindah ke kanan dan logo disembunyikan.
