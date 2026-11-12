# F3-01 Fondasi halaman depan

| | |
|---|---|
| Status | rencana |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Halaman depan memakai isi pengaturan: gambar dan logo, judul dan teks, data acara, keadaan pendaftaran yang sebenarnya, tombol Pendaftaran dan Cek Pendaftaran, dan footer. Tugas lain tinggal mengisi bagian lomba, jadwal, unduhan, dan kontak.

## Hasil

- [ ] `App\Libraries\IsiHalamanDepan`: pengumpul data publik sesuai kesepakatan rencana Tahap 3 (semua metode, termasuk untuk bagian milik F3-02 dan F3-03), hanya data yang boleh dilihat pengunjung, bentuk larik ditulis di komentar kelas
- [ ] `BerandaController::index` dan `beranda.php` menyusun bagian dengan urutan K42; potongan `app/Views/beranda/_kepala.php`, dan potongan kosong `_lomba.php`, `_jadwal.php`, `_unduhan.php`, `_kontak.php` yang sudah menerima datanya
- [ ] Kepala: gambar kepala selebar layar, logo persegi, logo persegi panjang, logo Bakti Olahraga Djarum Foundation, judul (atau nama acara bila kosong), teks berparagraf, seri–tahun, tanggal, tempat; gambar yang belum diunggah disembunyikan (K41)
- [ ] Keadaan pendaftaran dari `BukaTutupPendaftaran` (empat keadaan, kalimat dan tanda yang berbeda); teks statis "Pendaftaran sedang disiapkan" hilang
- [ ] Tombol Pendaftaran dan Cek Pendaftaran tampil nonaktif "Segera" (K39); alamatnya di satu tempat untuk Tahap 4–5
- [ ] Footer dari `HalamanDepan.footer`, dengan cadangan "nama acara tahun"
- [ ] Rute publik `GET unduhan` → `UnduhanController::index` (pengendalinya dibuat F3-03)
- [ ] Wilayah CSS kosong untuk F3-02 dan F3-03 di akhir `tema.css`
- [ ] Uji `tests/basisdata/IsiHalamanDepanTest.php` dan `tests/fitur/HalamanDepanKepalaTest.php`; uji Tahap 1–2 yang terdampak disesuaikan

## Selesai bila

`composer uji` lulus; halaman depan pada lebar 390 dan 1440 piksel menampilkan kepala dengan dan tanpa gambar, keempat keadaan pendaftaran, tanpa gulir mendatar.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
- F3-04 dikerjakan bersamaan; berkasnya tidak bersinggungan (F3-04 memegang `tata_letak/admin.php` dan `tata_letak/polos.php`).
