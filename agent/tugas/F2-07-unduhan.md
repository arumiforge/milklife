# F2-07 Pengaturan unduhan

| | |
|---|---|
| Status | selesai |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengunggah dan mengelola berkas unduhan (flyer, juknis, dan lainnya).

## Hasil

- [x] Daftar, tambah, ubah, hapus; judul, keterangan, berkas (wajib saat tambah, boleh diganti saat ubah), urutan, tampil
- [x] Unggahan lewat `App\Libraries\Unggahan` folder `unduhan`; berkas lama terhapus saat diganti atau dihapus
- [x] Daftar menampilkan jenis dan ukuran berkas serta tautan untuk membukanya
- [x] `UnduhanModel`, uji fitur termasuk berkas yang ditolak

## Selesai bila

`composer uji` lulus; berkas bisa diunggah, dibuka, diganti, dan dihapus.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **Urutan langkah supaya tidak ada berkas yatim atau baris tanpa berkas:**
  - Tambah: simpan berkas → simpan baris. Bila baris gagal disimpan (hasil `false` atau galat basis data), berkas baru dihapus lagi; galat basis data tetap dilempar setelah dibersihkan.
  - Ganti berkas: simpan berkas baru → perbarui baris → baru hapus berkas lama. Bila baris gagal diperbarui, berkas baru dihapus dan baris tetap menunjuk berkas lama. Bila berkas lama gagal dihapus, yang tersisa hanya berkas tak terpakai bernama acak (tidak merusak tampilan).
  - Hapus: hapus baris di dalam transaksi → hapus berkas → selesaikan transaksi. Bila berkas ada tetapi tidak bisa dihapus, transaksi dibatalkan sehingga baris kembali, dengan `pesan_galat` yang menyebut folder `public/unggahan` harus boleh ditulisi. Berkas yang sudah tidak ada tidak menghalangi hapus. Satu-satunya celah: transaksi gagal diselesaikan setelah berkas terhapus (sangat jarang); daftar lalu menandai baris itu "Berkas tidak ditemukan. Unggah lagi lewat Ubah."
- **Berkas yang hilang dari disk** ditandai merah di daftar dan di formulir ubah (bukan galat), supaya admin bisa mengunggah penggantinya.
- **Batas isian:** judul wajib, paling banyak 150 huruf (kolom); keterangan boleh kosong (disimpan `NULL`), paling banyak 500 huruf walau kolomnya `TEXT`, karena tampil sebagai kalimat pendek; urutan wajib, angka 0–65535 (`SMALLINT UNSIGNED`), formulir tambah mengusulkan urutan terbesar + 1 (`UnduhanModel::urutanBerikutnya()`). Tampil berupa kotak centang, bawaannya tercentang; tanpa centang = `0`.
- **Berkas sah yang belum tersimpan** karena isian lain salah: peramban tidak bisa mengisi ulang kotak berkas, jadi formulir menampilkan catatan "Pilih berkasnya lagi sebelum menyimpan." Berkas tidak disimpan sebelum semua isian sah, sehingga tidak ada berkas sementara yang tertinggal.
- **Daftar** diurutkan `urutan`, lalu `id`. Kolom: urutan, judul + keterangan (dipotong 120 huruf), berkas (jenis, ukuran, tautan "Buka berkas" di tab baru ke `base_url($berkas)`), tampil/disembunyikan, ubah/hapus. `UnduhanModel::daftar(true)` sudah menyaring yang tampil untuk halaman publik Tahap 3.
- **Id yang tidak ada** pada ubah, perbarui, dan hapus menghasilkan halaman tidak ditemukan (`PageNotFoundException`).
- **Gulir mendatar di ponsel:** teks khusus pembaca layar (`visually-hidden`, posisi absolut) di dalam tabel ikut melebarkan halaman ke 619 piksel karena `.tabel-gulir` tidak berposisi. Diatasi di tampilan ini dengan kelas Bootstrap `position-relative` pada pembungkus tabel. Halaman daftar lain yang memakai `visually-hidden` di dalam tabel akan kena hal yang sama; perbaikan umumnya (`position: relative` pada `.tabel-gulir` di `admin.css`) di luar bagian F2-07 dan dilaporkan ke koordinator.
- **Uji:** `tests/fitur/PengaturanUnduhanTest.php` (akses, CSRF, daftar, tambah, berkas ditolak: skrip PHP bernama `.pdf`, teks, kosong, di atas batas PHP, terputus, di atas 10 MB; isian salah, ubah, ganti berkas, hapus, dan jalur gagal simpan/perbarui/hapus lewat model tiruan `Factories::injectMock`) dan `tests/basisdata/UnduhanModelTest.php`.
- Diperiksa di peramban pada 390 dan 1440 piksel: daftar kosong, daftar berisi, tambah, berkas ditolak (termasuk `.ico`), ubah, dan jendela konfirmasi hapus; berkas terbuka lewat tautannya (200, `application/pdf`).
- Digabung lewat PR #21 pada 12 November 2026.
