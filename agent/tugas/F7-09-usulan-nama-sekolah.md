# F7-09 Usulan nama sekolah, nomor nonaktif saat ubah, dan revisi dokumen final

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 14 November 2026 |

## Tujuan

Pendaftar bisa mengusulkan nama sekolah yang lebih lengkap untuk sekolah bernama umum, admin tinggal menyetujui atau menolak (K99). Admin bisa mengubah pendaftaran lama walau kelompok umur atau nomor lombanya sudah dimatikan (K98). Dokumen final sesuai dengan sistem (K100).

## Hasil

- [x] Wizard langkah 1: kotak usulan nama, ditonjolkan bila nama sekolah kembar; tautan "Nama sekolah kurang lengkap?" untuk sekolah lain; rincian langkah 4 menyebut nama usulan
- [x] `PemeriksaPendaftaran`: `sekolah.nama_usulan` diperiksa (panjang, SLB) dan dipakai sebagai nama pendaftaran; `nama_resmi` ikut di data dan rincian
- [x] Tabel `usulan_nama_sekolah`; `SimpanPendaftaran` mencatat usulan dalam transaksi yang sama
- [x] `App\Libraries\Panel\UsulanNamaSekolah`: setujui (data sekolah + semua pendaftaran NPSN itu, usulan lain ditutup) dan tolak (pendaftaran pengusul kembali ke nama data sekolah)
- [x] Halaman `admin/sekolah/usulan`, tombol di menu Sekolah, peringatan di dasbor, tanda di rincian pendaftaran
- [x] Ubah pendaftaran mempertahankan nama tersimpan selama NPSN tidak diubah
- [x] K98: `DataIndukPendaftaran::nonaktif()`, `PemeriksaPendaftaran` menerima simpanan atlet; halaman ubah menampilkan nomor "Sudah ditutup" dengan namanya
- [x] Rancangan revisi 6 dan panduan pemasangan produksi revisi 1, PDF dibuat ulang

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji baru: `tests/fitur/PanelUsulanNamaSekolahTest.php`, butir usulan di `PendaftaranPemeriksaTest` dan `PendaftaranSimpanTest`, butir K98 di `PanelUbahPendaftaranTest` dan `PanelUbahPendaftaranHalamanTest`.

## Catatan

- Dikerjakan di PR yang sama dengan F7-08 ([#81](https://github.com/arumiforge/milklife/pull/81)) karena dibangun di atas menu Sekolah yang belum digabung.
- Pendaftaran susulan dari NPSN yang usulannya belum diputuskan kembali melihat nama data sekolah dan boleh mengusulkan lagi; admin melihat semua usulannya.
- Backup data untuk pindah server (K76) belum membawa usulan nama; usulan yang menunggu sebaiknya diputuskan sebelum pindah server.
