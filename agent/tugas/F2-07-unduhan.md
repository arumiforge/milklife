# F2-07 Pengaturan unduhan

| | |
|---|---|
| Status | rencana |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengunggah dan mengelola berkas unduhan (flyer, juknis, dan lainnya).

## Hasil

- [ ] Daftar, tambah, ubah, hapus; judul, keterangan, berkas (wajib saat tambah, boleh diganti saat ubah), urutan, tampil
- [ ] Unggahan lewat `App\Libraries\Unggahan` folder `unduhan`; berkas lama terhapus saat diganti atau dihapus
- [ ] Daftar menampilkan jenis dan ukuran berkas serta tautan untuk membukanya
- [ ] `UnduhanModel`, uji fitur termasuk berkas yang ditolak

## Selesai bila

`composer uji` lulus; berkas bisa diunggah, dibuka, diganti, dan dihapus.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
