# F2-04 Pengaturan nomor lomba

| | |
|---|---|
| Status | rencana |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengelola nomor lomba per kelompok umur, termasuk nomor beregu dan mix.

## Hasil

- [ ] Daftar nomor lomba dikelompokkan atau bisa disaring per kelompok umur, dengan jenis, kategori, biaya beregu, dan aktif
- [ ] Tambah dan ubah: kelompok umur, nama, jenis (individu/beregu), kategori (putra/putri/mix); untuk beregu: jumlah inti, batas anggota (tidak kurang dari inti), biaya beregu; untuk mix: jumlah putra dan putri minimal (jumlahnya tidak lebih dari inti); urutan, aktif
- [ ] Nomor individu tidak boleh berkategori mix; isian tim dikosongkan untuk nomor individu
- [ ] Kelompok umur nomor lomba yang sudah dipakai tidak bisa dipindah; hapus sesuai aturan rencana
- [ ] `NomorLombaModel`, uji fitur dan uji basis data

## Selesai bila

`composer uji` lulus; nomor lomba dari `data:awal` tampil rapi dan bisa dilengkapi.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
