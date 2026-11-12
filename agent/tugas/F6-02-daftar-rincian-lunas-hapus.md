# F6-02 Daftar, rincian, lunas, dan hapus pendaftaran

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin melihat semua pendaftaran, membuka rinciannya beserta foto penanggung jawab, menandai Lunas sehingga nomor dada dibuat, dan menghapus pendaftaran yang batal.

## Hasil

- [x] `/admin/pendaftaran`: tabel JSON (ID, tanggal, sekolah, NPSN, jenjang, kabupaten, jumlah atlet, total, status) dengan saringan status, jenjang, kabupaten, dan Excel
- [x] Rincian pendaftaran: sekolah, penanggung jawab dan fotonya, atlet (NISN, tanggal lahir, kelompok umur, nomor lomba, nomor dada), tim beregu dan anggotanya, biaya, status, waktu lunas
- [x] Foto penanggung jawab disajikan hanya ke admin lewat `FotoPenanggungJawab::jalurPenuh()`; foto yang tidak ada tampil sebagai kotak "tanpa gambar"
- [x] Aturan murni `NomorDada` dengan uji unit; layanan `StatusPembayaran` (`tandaiLunas`, `kembaliMenunggu`, `beriNomorDada`) dengan kunci baris awalan
- [x] Tandai Lunas dan kembali ke Menunggu lewat jendela konfirmasi; awalan penuh membatalkan seluruh penandaan dan menyebut kelompok umur yang perlu ditambah awalannya
- [x] Hapus pendaftaran lewat jendela konfirmasi; foto penanggung jawab dan bukti bayar ikut terhapus

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: nomor dada berurutan dan berlanjut ke awalan berikutnya, pembatalan bila penuh, nomor tidak berubah saat kembali ke Menunggu lalu Lunas lagi, tidak ada nomor ganda dari dua penandaan, hapus menghapus baris dan berkas, foto ditolak bagi yang belum masuk, CSRF.

## Catatan

Bergantung pada F6-01. `rincian.php` milik tugas ini; F6-03 menambah satu baris `include` bukti bayar dan F6-04 menambah tombol Ubah setelah tugas ini digabung. Asumsi 1–3 rencana Tahap 6.

## Pelaksanaan

- Pengendali `App\Controllers\Admin\PendaftaranController` (`index`, `data`, `excel`, `rincian`, `foto`, `lunas`, `menunggu`, `hapus`); tanpa `ubah`/`perbarui` (F6-04). Tidak ada rute baru.
- Pustaka di `app/Libraries/Panel/`: `NomorDada` (aturan murni), `StatusPembayaran`, `HapusPendaftaran`, `DataPanelPendaftaran` (definisi tabel, kolom Excel, rincian, jalur foto).
- Tampilan `admin/pendaftaran/index.php` dan `rincian.php`; bentuk kolom tabel di `public/aset/js/admin-pendaftaran.js`. Jendela konfirmasi memakai `aset/js/konfirmasi.js` yang sudah ada.
- Tempat untuk tugas lain di `rincian.php`, ditandai komentar: tombol Ubah (F6-04) di deretan tombol tindakan, `include` `_bukti_bayar` (F6-03) setelah kartu biaya.
- `StatusPembayaran::beriNomorDada()` tidak melempar galat bila awalan penuh; hasilnya `['nomor' => …, 'penuh' => [nama KU], 'pesan' => …]` dan tidak ada nomor yang ditulis. Pemanggil yang punya transaksi sendiri (F6-04, K59) membatalkan transaksinya bila `penuh` tidak kosong. Transaksi bersarang CodeIgniter hanya menambah kedalaman, jadi kunci bertahan sampai transaksi luar selesai.
- Pengaman tambahan: nomor baru selalu di atas nomor tertinggi yang tersimpan di `atlet.nomor_dada` dengan awalan yang sama, walau `nomor_terakhir` tertinggal.
- Hapus mengikuti rencana: baris anak lewat `CASCADE`, daftar berkas dibaca setelah baris pendaftaran dikunci, berkas dihapus setelah transaksi berhasil (`BerkasBuktiBayar::hapusBerkas()`, bukan `hapusMilik()` yang menghapus berkas sebelum transaksi selesai).
- Uji: `tests/unit/PanelNomorDadaTest.php`, `tests/basisdata/PanelPendaftaranStatusTest.php`, `tests/basisdata/PanelPendaftaranHapusTest.php`, `tests/fitur/PanelPendaftaranHalamanTest.php`, `tests/fitur/PanelPendaftaranTindakanTest.php`. Dua penandaan bersamaan diuji dengan sambungan basis data kedua yang memegang kunci awalan.
