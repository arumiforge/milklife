# F2-05 Pengaturan pembayaran dan halaman depan

| | |
|---|---|
| Status | dikerjakan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengisi rekening dan WA pembayaran beserta pesan konfirmasinya, serta semua isi halaman depan termasuk gambar dan logo.

## Hasil

- [ ] `/admin/pengaturan/pembayaran`: bank, nomor rekening, pemilik rekening, nomor WA, logo bank (unggah, ganti, hapus), templat pesan dengan daftar penanda dan contoh hasilnya
- [ ] `/admin/pengaturan/halaman-depan`: gambar kepala, logo persegi, logo persegi panjang, logo Bakti Olahraga Djarum Foundation (unggah, ganti, hapus, pratinjau atau kotak tanpa gambar), judul, teks, footer, nomor WA tanya admin, pesan tanya admin, pesan bagikan
- [ ] Unggahan lewat `App\Libraries\Unggahan`; berkas lama terhapus saat diganti
- [ ] Uji fitur kedua halaman, termasuk unggahan yang ditolak

## Selesai bila

`composer uji` lulus; kedua halaman bisa diisi, gambar tampil sebagai pratinjau.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
