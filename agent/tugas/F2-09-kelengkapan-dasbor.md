# F2-09 Kelengkapan pengaturan dan dasbor

| | |
|---|---|
| Status | dikerjakan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Dasbor menampilkan daftar centang hal yang belum lengkap dan keadaan pendaftaran, dan halaman daftar pengaturan menandai halaman yang belum lengkap.

## Hasil

- [ ] `KelengkapanPengaturan` (murni): butir sesuai rancangan revisi 3 bagian 6.1 (batas tahun lahir K36, tanpa tanggal patokan usia K37), tiap butir punya label, lengkap atau belum, keterangan yang menyebut apa yang kurang (misalnya nama kelompok umur yang belum punya jenjang), dan alamat halaman untuk melengkapinya
- [ ] `BukaTutupPendaftaran` (murni): keadaan `disiapkan`, `belum_dibuka`, `terbuka`, `ditutup` beserta kalimat ramahnya (contoh: "Pendaftaran dibuka 1 Oktober 2026")
- [ ] `DataKelengkapan`: mengumpulkan potret data dari pengaturan dan tabel lewat pembangun kueri
- [ ] Dasbor: daftar centang dengan tautan, keadaan pendaftaran, keadaan kosong pendaftaran tetap ada
- [ ] Halaman daftar pengaturan: tanda lengkap atau belum per halaman
- [ ] Uji unit kedua kelas murni (termasuk batas waktu tepat dan buka manual yang lewat), uji fitur dasbor

## Selesai bila

`composer uji` lulus; pada basis data kosong semua butir belum lengkap; setelah `data:awal` dan pengisian semua halaman, semua butir bercentang dan keadaan pendaftaran sesuai waktu.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
