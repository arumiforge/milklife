# F6-02 Daftar, rincian, lunas, dan hapus pendaftaran

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin melihat semua pendaftaran, membuka rinciannya beserta foto penanggung jawab, menandai Lunas sehingga nomor dada dibuat, dan menghapus pendaftaran yang batal.

## Hasil

- [ ] `/admin/pendaftaran`: tabel JSON (ID, tanggal, sekolah, NPSN, jenjang, kabupaten, jumlah atlet, total, status) dengan saringan status, jenjang, kabupaten, dan Excel
- [ ] Rincian pendaftaran: sekolah, penanggung jawab dan fotonya, atlet (NISN, tanggal lahir, kelompok umur, nomor lomba, nomor dada), tim beregu dan anggotanya, biaya, status, waktu lunas
- [ ] Foto penanggung jawab disajikan hanya ke admin lewat `FotoPenanggungJawab::jalurPenuh()`; foto yang tidak ada tampil sebagai kotak "tanpa gambar"
- [ ] Aturan murni `NomorDada` dengan uji unit; layanan `StatusPembayaran` (`tandaiLunas`, `kembaliMenunggu`, `beriNomorDada`) dengan kunci baris awalan
- [ ] Tandai Lunas dan kembali ke Menunggu lewat jendela konfirmasi; awalan penuh membatalkan seluruh penandaan dan menyebut kelompok umur yang perlu ditambah awalannya
- [ ] Hapus pendaftaran lewat jendela konfirmasi; foto penanggung jawab dan bukti bayar ikut terhapus

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: nomor dada berurutan dan berlanjut ke awalan berikutnya, pembatalan bila penuh, nomor tidak berubah saat kembali ke Menunggu lalu Lunas lagi, tidak ada nomor ganda dari dua penandaan, hapus menghapus baris dan berkas, foto ditolak bagi yang belum masuk, CSRF.

## Catatan

Bergantung pada F6-01. `rincian.php` milik tugas ini; F6-03 menambah satu baris `include` bukti bayar dan F6-04 menambah tombol Ubah setelah tugas ini digabung. Asumsi 1–3 rencana Tahap 6.
