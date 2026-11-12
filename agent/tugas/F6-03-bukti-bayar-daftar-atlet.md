# F6-03 Bukti bayar dan daftar atlet

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin menyimpan bukti transfer yang dikirim official lewat WA ke pendaftaran yang bersangkutan (K34), dan melihat semua atlet dalam satu tabel.

## Hasil

- [x] Unggah bukti bayar dari rincian pendaftaran, paling banyak 5 gambar per pendaftaran; gambar dikecilkan lewat `BerkasBuktiBayar`
- [x] Lihat (gambar kecil yang bisa diperbesar), unduh, dan hapus tiap gambar dengan konfirmasi
- [x] Gambar hanya disajikan lewat rute admin
- [x] `/admin/atlet`: tabel JSON (nomor dada, nama, NISN, jenis kelamin, tanggal lahir, kelompok umur, nomor lomba, sekolah, ID pendaftaran, status) dengan saringan kelompok umur, jenis kelamin, nomor lomba, status, dan Excel; tautan ke rincian pendaftaran

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: gambar keenam ditolak dengan pesan ramah, berkas bukan gambar ditolak, gambar dikecilkan, hapus menghapus berkas, yang belum masuk tidak bisa membuka gambar, saringan dan Excel atlet.

## Catatan

PR: [#49](https://github.com/arumiforge/milklife/pull/49). Bergantung pada F6-01. Potongan `app/Views/admin/pendaftaran/_bukti_bayar.php` milik tugas ini; satu baris `include` di `rincian.php` ditambahkan setelah F6-02 digabung (rebase). Asumsi 7 rencana Tahap 6.

### Yang dibuat

- **`BuktiBayarController`** (`unggah`, `lihat`, `unduh`, `hapus`): setelah unggah atau hapus, admin kembali ke `admin/pendaftaran/<id>` dengan pesan kilat. Batas 5 diperiksa sebelum menyimpan berkas dan sekali lagi di dalam transaksi yang mengunci baris pendaftaran (`SELECT … FOR UPDATE`), jadi dua unggahan bersamaan tidak bisa melewati batas. Bukti milik pendaftaran lain, pendaftaran yang tidak ada, dan berkas yang hilang dari disk menampilkan halaman tidak ditemukan. Gambar diunduh bernama `BuktiBayar_<ID pendaftaran>_<urutan>.jpg`.
- **Potongan `admin/pendaftaran/_bukti_bayar.php`**: berdiri sendiri. Butuh `$pendaftaran['id']` (atau `$pendaftaranId`) dan membaca daftar bukti sendiri dari `BuktiBayarModel`. Gambar kecil membuka jendela Bootstrap berisi gambar besar; hapus memakai jendela konfirmasi `konfirmasi.js`. Halaman yang memuat potongan ini wajib memuat `aset/js/konfirmasi.js` di bagian `skrip` (potongan tidak memuatnya sendiri supaya skrip tidak dimuat dua kali). Tanpa bukti: kotak "Belum ada gambar". Setelah 5 gambar formulir unggah diganti keterangan batas.
- **`AtletController`** (`index`, `data`, `excel`) dan `admin/atlet/index.php`: satu baris per atlet; nomor lomba atlet digabung dalam satu kolom. Saringan nomor lomba memilih atlet yang ikut nomor itu, individu atau beregu. Urutan bawaan: kelompok umur, nomor dada, pendaftaran. Excel `Atlet_<tanggal>.xlsx` memakai saringan, pencarian, dan urutan yang sama; tanggal lahir ditulis seperti `12 April 2017`.
- Uji: `tests/fitur/PanelBuktiBayarPengendaliTest.php`, `tests/fitur/PanelAtletTest.php`.

### Penyimpangan dari rencana

- Unggah satu gambar per kiriman (kotak `bukti`), bukan beberapa sekaligus, sesuai contoh pemakaian `BerkasBuktiBayar`.
- Baris `include` di `rincian.php` menunggu F6-02 (lihat deskripsi PR).
