# F7-10 Audit bug menyeluruh dan dokumen sesuai kondisi terbaru

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 15 November 2026 |

## Tujuan

Bug yang tersisa di halaman publik, panel admin, perintah, dan fitur baru ditemukan dan diperbaiki, dan semua dokumen sesuai dengan sistem yang berjalan (K101).

## Hasil

- [x] Audit tiga area (publik dan JS; panel admin; perintah, konfigurasi, dan fitur K97–K99), setiap temuan diperiksa ulang sebelum diperbaiki
- [x] Wizard: total di langkah Biaya dan Pembayaran dihitung ulang setelah atlet, nomor, atau penanggung jawab dihapus, atau data sekolah berubah (sebelumnya total lama bisa tampil dan ditransfer). Akar masalah: parameter `rincian` di `barisAtlet()` membayangi variabel total, jadi tombol Hapus tidak pernah mengosongkannya; dibuktikan di peramban (Rp 75.000 tetap tampil setelah satu nomor dihapus, kini Rp 50.000)
- [x] Wizard: jawaban pencarian NPSN yang datang terlambat tidak menimpa NPSN yang sudah diganti; setelah jawaban SLB atau terlalu sering, Lanjut mencari ulang dan menampilkan pesannya
- [x] Pemilih tanggal: tanggal 29–31 tidak lagi tertinggal nonaktif setelah formulir atlet dikosongkan
- [x] Cek Pendaftaran dikirim dengan `Cache-Control: no-store` (data siswa dan alamat bukti)
- [x] Bukti bayar: foto ponsel diputar sesuai tag EXIF; batas memori dinaikkan sebelum gambar besar diolah
- [x] Masuk admin: data tabel, unduhan, dan berkas tidak lagi menjadi tujuan setelah masuk ulang
- [x] Backup data (K76) membawa usulan nama sekolah (K99) dan sekolah yang disunting admin (K97)
- [x] Rancangan revisi 7, README, progres, dan berkas tugas sesuai kondisi terbaru; PDF dibuat ulang

## Selesai bila

`composer uji` lulus lokal dan di CI.

## Catatan

- Yang diperiksa dan tidak bermasalah: Excel aman dari rumus (semua sel teks ditulis eksplisit), angka rekap layar/Excel/PDF/dasbor sama, kunci baris saat Lunas dan nomor dada, pilih massal, pembatasan laju, escaping, CSRF, cadangan harian (mysqldump seluruh basis data, jadi tabel baru otomatis ikut).
- Belum diperiksa karena di luar repo: batas `client_max_body_size` Nginx di server produksi. Kiriman pendaftaran dengan 5 foto penanggung jawab dan bukti transfer bisa sekitar 5 MB.
