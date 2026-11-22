# F11-09 Daftar tampilan videotron

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Operator videotron dari vendor membuka satu alamat publik `/layar`, memilih tampilan yang dibutuhkan saat itu (per seri, per sesi, klasemen, per kelompok umur, atau per nomor lomba), lalu tampilan itu tetap di layar dan tidak berganti sendiri (K173).

## Hasil

- [x] `/layar` menjadi halaman daftar tautan tanpa masuk: per hari dan sesi (Pagi, Siang, Sore menurut jam mulai babak), klasemen, kelompok umur dan nomor lomba, serta papan putar. Alamat lengkap tertulis dan bisa disalin.
- [x] Tampilan tetap layar penuh: `layar/seri/<babak>/<seri>` (daftar start setelah daftar seri dikunci, lalu hasil seri setelah terbit), `layar/sesi/<tanggal>/<bagian>`, `layar/klasemen/<kunci>`, `layar/kelompok-umur/<id>`, `layar/nomor/<id>` (Final di atas).
- [x] Tampilan tetap tidak memutar. Data diperbarui diam-diam setiap 30 detik; tabel panjang dibagi halaman (`?halaman=`, panah kiri/kanan), dua halaman berdampingan di layar lebar. Latar, skala cair (K141), dan `?skala=` tetap.
- [x] Masukan lanjutan pemilik (K173 butir 6): tanpa jam, "Diperbarui pukul", dan "Halaman x dari y"; baris minimal dua baris teks; tanpa teks sesi di layar; latar bisa diganti lewat unggah gambar di Pengaturan > Videotron.
- [x] Papan putar lama pindah ke `/layar/putar`; isi dan lamanya tetap diatur di Pengaturan > Videotron.
- [x] Hanya data terbit; sakelar `Videotron.tampil` berlaku untuk semua alamat (404 bila mati); JSON bertembolok 10 detik dengan `ETag` dan batas permintaan yang sama.
- [x] Baris hasil dan klasemen diambil dari `PapanVideotron::panelHasil()` dan `PapanVideotron::juaraUmum()` (di atas `HasilLomba`), jadi F11-02, F11-04, dan F11-05 ikut otomatis.

## Berkas dan uji

- `app/Libraries/Hasil/TampilanVideotron.php` (baru), `app/Libraries/Hasil/PapanVideotron.php`, `app/Controllers/LayarController.php`, `app/Config/Routes.php`, `app/Views/layar/daftar.php` (baru), `app/Views/layar/index.php`, `public/aset/js/layar.js`, `public/aset/js/layar-daftar.js` (baru), Pengaturan > Videotron (gambar latar, folder unggahan `videotron`, `Config\Videotron::$latar`).
- `vendor/bin/phpunit --filter 'TampilanVideotronTest|PapanVideotronTest|UnggahanTest'`, `npm run uji:layar` (`alat/uji-layar.mjs`: tampilan tetap di delapan ukuran, tidak berganti sendiri, `?halaman=`).

## Selesai bila

Uji di atas dan `composer uji` lulus; job CI "Tampilan videotron" hijau.

## Catatan

K173. Sesi = tanggal dan bagian hari dari jam mulai babak (sebelum 11.00 Pagi, sebelum 15.00 Siang, selebihnya Sore; tanpa jam "Jam menyusul").
