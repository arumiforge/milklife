# F2-05 Pengaturan pembayaran dan halaman depan

| | |
|---|---|
| Status | selesai |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengisi rekening dan WA pembayaran beserta pesan konfirmasinya, serta semua isi halaman depan termasuk gambar dan logo.

## Hasil

- [x] `/admin/pengaturan/pembayaran`: bank, nomor rekening, pemilik rekening, nomor WA, logo bank (unggah, ganti, hapus), templat pesan dengan daftar penanda dan contoh hasilnya
- [x] `/admin/pengaturan/halaman-depan`: gambar kepala, logo persegi, logo persegi panjang, logo Bakti Olahraga Djarum Foundation (unggah, ganti, hapus, pratinjau atau kotak tanpa gambar), judul, teks, footer, nomor WA tanya admin, pesan tanya admin, pesan bagikan
- [x] Unggahan lewat `App\Libraries\Unggahan`; berkas lama terhapus saat diganti
- [x] Uji fitur kedua halaman, termasuk unggahan yang ditolak

## Selesai bila

`composer uji` lulus; kedua halaman bisa diisi, gambar tampil sebagai pratinjau.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **Rekening dan nomor WA boleh disimpan kosong.** Bank, nomor rekening, pemilik, dan nomor WA tidak wajib di formulir, supaya admin bisa mengisi bertahap (misalnya logo dulu). Yang kosong menjadi tugas kelengkapan (F2-09, rancangan 6.1) dan pendaftaran belum bisa dibuka. Yang diisi harus benar. Semua isian halaman depan juga boleh kosong.
- **Nomor rekening**: spasi, titik, dan tanda hubung yang ikut diketik dibuang; sisanya harus 5–30 angka (disimpan sebagai teks, jadi nol di depan tetap ada). Nama bank paling banyak 50 huruf, pemilik 100 huruf.
- **Templat pesan wajib diisi** (paling banyak 2.000 huruf). Tombol "Pakai pesan awal" mengisi kotak dengan nilai awal `Config\Pembayaran::$templat_pesan`. Penanda berbentuk `{…}` yang tidak ada di `Pembayaran::PENANDA` ditolak dengan pesan yang menyebut penandanya (huruf besar-kecil dibedakan, `{Total}` ditolak). Akhir baris dibakukan menjadi `\n`.
- **Contoh hasil pesan** memakai data karangan di `PembayaranController::DATA_CONTOH` (SD Negeri Contoh 1, NPSN 12345678, ID `MAC2A0000126`, Rp300.000 + Rp200.000 = Rp500.000; uang lewat `rupiah()`). Contoh dibuat di server dan diperbarui langsung saat mengetik (skrip kecil di halaman). Penanda di daftar bisa ditekan untuk disisipkan di posisi kursor (di akhir pesan bila kotak belum disentuh). Bila nomor WA sudah tersimpan, ada tautan "Coba buka contoh pesan di WA" (`wa.me`), disembunyikan saat isian salah.
- **Gambar**: kotak unggah kosong = gambar tidak berubah; centang "Hapus gambar/logo ini" (hanya tampil bila ada gambar) mengosongkan pengaturan dan menghapus berkasnya; bila berkas baru dipilih sekaligus dicentang hapus, berkas baru yang dipakai. Berkas lama dihapus setelah berkas baru tersimpan dan pengaturan diperbarui. Pratinjau memakai `base_url($jalur)` hanya bila berkasnya ada (`Unggahan::ada()`); jalur yang berkasnya hilang ditampilkan sebagai `tata_letak/_tanpa_gambar`. Rasio kotak: gambar kepala 21×9, logo persegi 1×1, dua logo lain 16×9.
- **Satu isian salah = tidak ada yang disimpan**, termasuk gambar yang sah. Halaman menampilkan peringatan bahwa gambar yang tadi dipilih belum tersimpan dan perlu dipilih lagi (peramban tidak bisa mengisi ulang kotak unggah). Di halaman depan, bila satu dari beberapa gambar gagal disimpan ke disk, gambar yang sudah tersimpan pada kiriman itu dihapus lagi dan pengaturan tidak berubah. Pesan galat gambar di halaman depan diawali nama gambarnya ("Logo persegi: Jenis gambar tidak bisa dipakai. …").
- **Pratinjau sebelum disimpan**: memilih berkas langsung menampilkan gambarnya di kotak pratinjau (skrip kecil di halaman, `URL.createObjectURL`). `aset/js/konfirmasi.js` dimuat di kedua halaman untuk pemeriksaan `data-ukuran-maksimal`.
- **Tanpa CSS baru**: `admin.css` milik F2-01, jadi tampilan memakai kelas Bootstrap dan beberapa atribut `style` kecil (lebar pratinjau, `object-fit`, `white-space: pre-wrap` pada contoh pesan). Skrip halaman ditulis di bagian `skrip` tampilannya sendiri (CSP tidak aktif).
- **Batas teks halaman depan**: judul 150, teks 2.000, footer 500, pesan tanya dan pesan bagikan masing-masing 1.000 huruf. Teks dan pesan disimpan apa adanya (akhir baris `\n`); cara menampilkannya (paragraf, tautan `wa.me`, alamat web pada pesan bagikan) diputuskan Tahap 3.
- **Uji**: `tests/fitur/PengaturanPembayaranTest.php` (17 uji) dan `tests/fitur/PengaturanHalamanDepanTest.php` (12 uji): terbuka untuk admin dan tertutup untuk yang belum masuk, nilai tersimpan tampil, simpan benar, isian salah ditampilkan ulang, token CSRF wajib, unggah/ganti/hapus gambar dengan berkas lama terbuang, bukan gambar, berkas kosong, dan berkas melebihi batas PHP ditolak tanpa menyimpan apa pun.
- Diperiksa di Chromium pada lebar 390 dan 1440 piksel: tanpa gulir mendatar, pratinjau dan kotak tanpa gambar tampil.

### Untuk koordinator

- Tidak ada berkas di luar bagian F2-05 yang diubah.
- Untuk F2-09 dan Tahap 3–4: nomor rekening dan nomor WA disimpan `''` bila kosong; logo dan gambar `''` bila tidak ada.
- Digabung lewat PR #17 pada 12 November 2026.
