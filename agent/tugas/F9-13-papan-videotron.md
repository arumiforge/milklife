# F9-13 Papan videotron

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Penonton di arena melihat hasil terbaru, juara umum, dan jadwal berikutnya di videotron tanpa operator harus berpindah halaman.

## Hasil

- [ ] `/layar` tanpa masuk, hanya data terbit; berganti otomatis antara hasil terbaru, juara umum per jenjang, jadwal berikutnya, sedang berlangsung
- [ ] Data lewat `/layar/data` (JSON, bisa ditembolok), segar 30 detik, tetap menampilkan data terakhir bila jaringan putus
- [ ] Tata letak cair untuk ukuran apa pun (K141): huruf `clamp()`, baris per halaman dari tinggi layar, susunan otomatis mendatar lebar (≥ 2:1), mendatar, tegak, dan sangat kecil; parameter `?skala=`, `?susunan=`, `?baris=`; tombol layar penuh; latar Three.js dengan cadangan G9-06 yang disusun dari potongan sehingga pas di rasio apa pun
- [ ] Uji tampilan otomatis (Playwright) di 3840×2160, 1920×1080, 1280×720, 1024×768, 3840×1080, 1920×480, 1080×1920, 512×256 tanpa teks terpotong atau tumpang tindih
- [ ] Pengaturan > Videotron: isi yang tampil dan lama tiap tampilan

## Berkas dan uji

`LayarController`, `app/Libraries/Hasil/` (data layar), views `layar/`, `public/aset/js/layar.js`. Uji baru `PapanVideotronTest`.

## Selesai bila

Semua ukuran uji tampil rapi, halaman berjalan satu jam tanpa kebocoran memori yang terlihat, uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K141 butir 2 (menggantikan K140 butir 13): ukuran layar tidak perlu diketahui lebih dulu.
