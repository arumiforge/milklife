# F6-04 Ubah pendaftaran

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin memperbaiki pendaftaran yang sudah tersimpan: data sekolah, penanggung jawab, atlet, dan nomor lomba, dengan aturan dan biaya yang tetap benar.

## Hasil

- [x] Formulir ubah dari rincian pendaftaran (tombol Ubah), memakai `PemeriksaPendaftaran` dan `KelengkapanTim` dengan atlet dan tim pendaftaran ini dikeluarkan dari data "sudah ada"
- [x] Tambah, ubah, dan hapus atlet dan penanggung jawab (termasuk ganti foto); batas minimal dan maksimal tetap berlaku
- [x] Biaya dihitung ulang: baris yang tidak berubah mempertahankan biaya tersimpan, yang baru memakai tarif saat ini
- [x] K32: pendaftaran Lunas yang totalnya berubah kembali ke Menunggu; jendela konfirmasi menyebutnya sebelum simpan
- [x] K59: pendaftaran Lunas yang totalnya tetap langsung memberi nomor dada kepada atlet baru (`StatusPembayaran::beriNomorDada()`)
- [x] Atlet yang sudah punya nomor dada mempertahankannya
- [x] Simpan dalam satu transaksi dengan kunci per NPSN; foto baru dihapus bila gagal, foto lama dihapus setelah berhasil

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: NISN milik pendaftaran lain ditolak, NISN sendiri diterima, tim susulan tetap tanpa biaya tim, K32 naik dan turun, K59, kuota dan batas nomor individu, nomor dada dipertahankan, transaksi gagal tidak meninggalkan berkas.

## Catatan

Bergantung pada F6-02 (rincian, `StatusPembayaran`) dan F6-05 (parameter konfirmasi di `PemeriksaPendaftaran`). Konfirmasi tim susulan (K57) tidak diminta dari admin. Buka-tutup pendaftaran tidak berlaku untuk admin. Asumsi 4 rencana Tahap 6.

## Pengerjaan

- `App\Libraries\Panel\UbahPendaftaran`: isian formulir, `periksa()` (pratinjau tanpa menulis) dan `simpan()`. Data "sudah ada" untuk `PemeriksaPendaftaran` dihitung tanpa pendaftaran ini (NISN, tim, kuota); kunci `konfirmasi_susulan` dibuang. Baris atlet-nomor dan tim yang tetap memakai biaya tersimpan.
- `PendaftaranController::ubah` dan `::perbarui`. `perbarui` dengan `pratinjau=1` menjawab JSON (galat atau kalimat konfirmasi K32/K59, token CSRF baru); tanpa itu menyimpan lalu kembali ke rincian dengan pesan kilat, atau menampilkan ulang formulir dengan galat.
- `app/Views/admin/pendaftaran/ubah.php` dan `public/aset/js/admin-ubah-pendaftaran.js`: kartu penanggung jawab dan atlet (tambah, hapus, ganti foto diperkecil di peramban), pilihan nomor lomba disaring menurut kelompok umur dan jenis kelamin, jendela konfirmasi dari pratinjau server.
- Tombol Ubah di `rincian.php`.
- Uji: `tests/basisdata/PanelUbahPendaftaranTest.php`, `tests/fitur/PanelUbahPendaftaranHalamanTest.php`.

## Penyimpangan dan catatan

- K32 dijaga juga di server: bila status Lunas dan total berubah, simpan hanya berjalan dengan `setuju_menunggu=1` (dikirim jendela konfirmasi). Tanpa itu formulir ditampilkan ulang dengan penjelasannya.
- ID pendaftaran tidak diubah walau jenjang sekolah diubah, supaya bukti dan cek yang dipegang official tetap berlaku.
- Pendaftaran Lunas yang totalnya tetap: atlet tanpa nomor dada langsung diberi nomor (K59). Bila awalan penuh, seluruh perubahan dibatalkan dengan pesan untuk menambah awalan.
- Nomor lomba yang sudah tidak aktif tampil tercentang dengan keterangan; pemeriksa menolaknya sampai centangnya dihapus.
- `ubah()` menerima dua parameter pilihan (kiriman dan galat) supaya `perbarui()` bisa menampilkan ulang formulir; rute tetap hanya memberi id.
- Formulir butuh JavaScript untuk menambah baris dan mengganti foto, seperti wizard.
