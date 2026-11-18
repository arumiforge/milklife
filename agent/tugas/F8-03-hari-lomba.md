# F8-03 Fitur hari lomba: daftar ulang, BIB massal, daftar peserta

| | |
|---|---|
| Tahap | 8 — Hari lomba dan hasil |
| Dibuat | 17 November 2026 |

## Tujuan

Panitia bisa mendaftar ulang sekolah dengan memindai kode QR bukti, mencetak nomor atlet (BIB) massal, dan mencetak daftar peserta per nomor untuk pemanggilan atlet dan juri (S7).

## Hasil

- [x] Halaman `admin/daftar-ulang`: pindai QR (fitur bawaan peramban) atau ketik ID; status, nomor atlet, daftar atlet, tandai hadir per atlet dan semua, batal hadir
- [x] Kode QR gabungan (K109) diarahkan ke pendaftaran gabungan; tautan daftar ulang dari halaman bukti bila admin sedang masuk
- [x] Cetak BIB massal per kelompok umur (PDF)
- [x] Daftar peserta per nomor lomba (PDF dan Excel), nomor beregu per regu
- [x] Uji fitur

## Selesai bila

Uji lulus, `composer uji` lulus, halaman dicoba di Chromium 390 piksel.

## Catatan

K124. Harus siap jauh sebelum pertemuan teknik 6 Desember 2026.

Pelaksanaan:

- Kolom `atlet.hadir_pada` dan `atlet.hadir_oleh` (migrasi `TambahHadirAtlet`). Penggabungan (K109) memindah baris atlet, jadi tanda hadir ikut tanpa perubahan `GabungPendaftaran`; ekspor/impor pindah server (K76) membawa kedua kolom.
- Tandai hadir hanya untuk pendaftaran Lunas; pendaftaran Menunggu tampil dengan peringatan dan tautan ke rinciannya.
- Halaman bukti: admin yang sedang masuk (kuki sesi ada dan sesi sah) mendapat halaman pilihan "Buka daftar ulang" atau "Buka bukti pendaftaran (PDF)" (`?pdf=1`). Pengunjung lain tetap langsung mendapat PDF dan tidak dibuatkan sesi.
- Menu admin: satu butir "Hari lomba" (ponsel: "Lomba") dengan tiga tab: Daftar ulang, Cetak BIB, Daftar peserta.
- Di server: `php spark migrate --all` dan `php spark aset:minify`.
