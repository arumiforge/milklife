# F6-06 Rekap dan dasbor

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Panitia mendapat rekap siap pakai tanpa mengolah ulang data, dan dasbor menampilkan angka ringkas.

## Hasil

- [ ] `/admin/rekap` dengan lima rekap: peserta per nomor lomba, tim beregu, per sekolah, per kelompok umur, keuangan
- [ ] Saringan status: semua (bawaan), Lunas, Menunggu (K60); rekap keuangan selalu memuat keduanya
- [ ] Excel tiap rekap lewat `EksporExcel`, mengikuti saringan
- [ ] PDF tiap rekap lewat mPDF dengan pola `BuktiPendaftaran` (A4 mendatar, judul dan logo dari pengaturan)
- [ ] Dasbor: angka pendaftaran, atlet, tim, sekolah, tagihan, lunas, dan belum, di atas daftar kelengkapan yang sudah ada

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: angka tiap rekap untuk data karangan, saringan status, Excel terbaca ulang, PDF dimulai dengan `%PDF`, keadaan kosong, angka dasbor.

## Catatan

Bergantung pada F6-01. Daftar kelengkapan dasbor tidak diubah. Asumsi 6 rencana Tahap 6.
