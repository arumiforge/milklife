# F11-25 Videotron bergaya baru dan sebutan Heat atau Seri

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 26 November 2026 |

## Tujuan

Videotron mudah dibaca dari tribun seperti slide Seri 1, tanpa kesalahan ketik ulangnya: logo, judul nomor besar, baris kotak terang, latar tenang yang bisa dipilih, dan sebutan "Heat" yang dipakai panitia (K192).

## Hasil

- [x] Kepala layar memuat logo acara di kanan (`data-logo`, `IsiHalamanDepan::logoBesar()`, dipakai juga logo hero ponsel).
- [x] Judul nomor besar huruf tampilan miring dengan kelompok umur; label isi dan babak dalam pil.
- [x] Baris kotak putih berhuruf biru tua kapital, lencana 1–3, lintasan dalam kotak gelap, tanda Q/q dalam kotak, kode tanpa prestasi merah.
- [x] Pengaturan > Videotron: **Latar bawaan** (sapuan kuas diam bawaan, biru polos, animasi lama) dan **Sebutan pembagian babak** (Heat bawaan, Seri).
- [x] Judul dan pil babak tampilan per heat, papan putar, dan daftar `/layar` memakai sebutan itu; tembolok tampilan dibersihkan saat disimpan.
- [x] K192, ATURAN, rancangan revisi 49, panduan admin hari lomba revisi 4 beserta PDF-nya.

## Berkas dan uji

`public/aset/js/layar.js`, `alat/uji-layar.mjs`, `app/Controllers/LayarController.php`, `app/Views/layar/index.php`, `app/Views/layar/daftar.php`, `app/Libraries/Hasil/PapanVideotron.php`, `app/Libraries/Hasil/TampilanVideotron.php`, `app/Config/Videotron.php`, `app/Controllers/Admin/Pengaturan/VideotronController.php`, `app/Views/admin/pengaturan/videotron.php`, `app/Libraries/IsiHalamanDepan.php`, `public/aset/css/tw.css`. Uji `vendor/bin/phpunit --filter 'TampilanVideotronTest|PapanVideotronTest'` dan `npm run uji:layar`.

## Selesai bila

Tampilan videotron di delapan ukuran layar lulus `npm run uji:layar`, sebutan Heat tampil secara bawaan dan Seri bila dipilih, dan uji terkait lulus.

## Catatan

- Halaman panitia, PDF daftar seri, dan halaman publik Seri dan lintasan tetap memakai kata seri; sebutan hanya untuk videotron yang dibaca penonton dan operator vendor.
- Pilihan latar "Foto acara" dari halaman telaah tidak dibuat sebagai bawaan: admin bisa mengunggah foto sebagai gambar latar, dan baris kotak putih tetap terbaca di atasnya.
