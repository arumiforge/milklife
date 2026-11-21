# F9-03 Tata letak publik dan halaman depan

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Pengunjung melihat halaman depan baru yang meriah dan jelas: carousel foto acara sebelumnya dengan indikator, menu baru, dan bagian yang lebih tertata.

## Hasil

- [ ] `publik_tw.php` lengkap: kepala, menu (Beranda, Jadwal, Hasil, Peserta, Galeri, Bantuan, Cek Pendaftaran, Daftar; butir yang sakelarnya mati tidak tampil), kaki
- [ ] Carousel pembuka dengan indikator, tombol jeda, dan geser (Flowbite); foto bawaan dari `bahan-rombak/` dikecilkan ke `data/halaman-depan/carousel/`, dipasang `data:awal` dan perintah `halaman-depan:carousel`; admin menambah, mengurutkan, menghapus di Pengaturan > Halaman Depan
- [ ] Judul, keadaan pendaftaran, dan dua tombol besar tetap di layar pertama (K81)
- [ ] Bagian baru: angka acara, hasil dan juara umum terbaru, FAQ teratas (bila F9-04 sudah masuk), ilustrasi G9-02/G9-05 bila ada
- [ ] Halaman Unduhan, Hasil, dan Klasemen publik ikut pindah ke `publik_tw.php`
- [ ] Sentuhan Three.js di pembuka yang menghormati `prefers-reduced-motion`

## Berkas dan uji

`app/Views/tata_letak/publik_tw.php`, `app/Views/beranda*`, `app/Views/unduhan.php`, `app/Views/hasil/`, `app/Libraries/IsiHalamanDepan.php`, `app/Controllers/BerandaController.php`, pengaturan halaman depan, `app/Commands/DataAwal.php` (blok carousel). Uji: `HalamanDepan*`, `HalamanUnduhanTest`, `HasilLombaTest`, `KlasemenSekolahTest`.

## Selesai bila

Halaman depan, Unduhan, Hasil, dan Klasemen tampil dengan tata letak baru tanpa galat konsol/CSP di 360, 390, dan 1280 piksel; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 6; K41 (gambar kosong disembunyikan), K42, K44, K81 tetap.
