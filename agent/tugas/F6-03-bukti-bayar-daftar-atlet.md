# F6-03 Bukti bayar dan daftar atlet

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin menyimpan bukti transfer yang dikirim official lewat WA ke pendaftaran yang bersangkutan (K34), dan melihat semua atlet dalam satu tabel.

## Hasil

- [ ] Unggah bukti bayar dari rincian pendaftaran, paling banyak 5 gambar per pendaftaran; gambar dikecilkan lewat `BerkasBuktiBayar`
- [ ] Lihat (gambar kecil yang bisa diperbesar), unduh, dan hapus tiap gambar dengan konfirmasi
- [ ] Gambar hanya disajikan lewat rute admin
- [ ] `/admin/atlet`: tabel JSON (nomor dada, nama, NISN, jenis kelamin, tanggal lahir, kelompok umur, nomor lomba, sekolah, ID pendaftaran, status) dengan saringan kelompok umur, jenis kelamin, nomor lomba, status, dan Excel; tautan ke rincian pendaftaran

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: gambar keenam ditolak dengan pesan ramah, berkas bukan gambar ditolak, gambar dikecilkan, hapus menghapus berkas, yang belum masuk tidak bisa membuka gambar, saringan dan Excel atlet.

## Catatan

Bergantung pada F6-01. Potongan `app/Views/admin/pendaftaran/_bukti_bayar.php` milik tugas ini; satu baris `include` di `rincian.php` ditambahkan setelah F6-02 digabung (rebase). Asumsi 7 rencana Tahap 6.
