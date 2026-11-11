# F2-03 Pengaturan kelompok umur

| | |
|---|---|
| Status | rencana |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengelola kelompok umur lengkap dengan batas usia, jenjang yang boleh ikut (K23, K24), biaya individu, dan awalan nomor dada.

## Hasil

- [ ] Daftar kelompok umur: nama, rentang usia, jenjang, biaya, awalan, aktif; keadaan kosong yang ramah
- [ ] Tambah dan ubah: nama, usia minimal dan maksimal (tahun penuh, minimal tidak lebih dari maksimal), centang SD/MI, SMP/MTs, SMA/SMK/MA, biaya individu, awalan nomor dada (angka 1–9, unik di semua kelompok umur, urutan sesuai ketikan), urutan, aktif
- [ ] Rentang usia kelompok umur aktif tidak boleh tumpang tindih
- [ ] Awalan yang sudah dipakai (`nomor_terakhir > 0`) tidak bisa dihapus
- [ ] Hapus sesuai aturan rencana, awalan dan jenjang ikut terhapus dalam satu transaksi
- [ ] `KelompokUmurModel`, `AwalanNomorDadaModel`, `KelompokUmurJenjangModel`, uji fitur dan uji basis data

## Selesai bila

`composer uji` lulus; kelompok umur dari `data:awal` bisa dilengkapi sampai siap dipakai; semua aturan tolak dan hapus teruji.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
