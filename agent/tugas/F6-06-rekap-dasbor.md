# F6-06 Rekap dan dasbor

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Panitia mendapat rekap siap pakai tanpa mengolah ulang data, dan dasbor menampilkan angka ringkas.

## Hasil

- [x] `/admin/rekap` dengan lima rekap: peserta per nomor lomba, tim beregu, per sekolah, per kelompok umur, keuangan
- [x] Saringan status: semua (bawaan), Lunas, Menunggu (K60); rekap keuangan selalu memuat keduanya
- [x] Excel tiap rekap lewat `EksporExcel`, mengikuti saringan
- [x] PDF tiap rekap lewat mPDF dengan pola `BuktiPendaftaran` (A4 mendatar, judul dan logo dari pengaturan)
- [x] Dasbor: angka pendaftaran, atlet, tim, sekolah, tagihan, lunas, dan belum, di atas daftar kelengkapan yang sudah ada

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: angka tiap rekap untuk data karangan, saringan status, Excel terbaca ulang, PDF dimulai dengan `%PDF`, keadaan kosong, angka dasbor.

## Catatan

Bergantung pada F6-01. Daftar kelengkapan dasbor tidak diubah. Asumsi 6 rencana Tahap 6.

Kelima rekap disusun satu pustaka, `App\Libraries\Panel\Rekap`, yang dipakai halaman, JSON (`…/data`), Excel, dan PDF (`RekapPdf`), jadi angkanya selalu sama. Rekap berupa tabel biasa tanpa DataTables karena barisnya sedikit dan sudah teringkas. Satu tim beregu dihitung per sekolah (NPSN) per nomor, jadi anggota pendaftaran susulan masuk tim yang sama (K07); sekolah tanpa NPSN dihitung per pendaftaran. Saringan status berlaku per atlet menurut status pendaftarannya, sehingga satu tim bisa tampil sebagian di Lunas dan sebagian di Menunggu. Nama berkas Excel dan PDF menyebut saringannya, misalnya `RekapNomorLombaLunas_<NamaAcara>_<Tahun>.pdf`. Logo PDF dibaca seperti `BuktiPendaftaran` (disalin, karena berkas Tahap 5 hanya dibaca). Kartu "Belum ada pendaftaran" di dasbor kini hanya tampil bila memang belum ada pendaftaran.
