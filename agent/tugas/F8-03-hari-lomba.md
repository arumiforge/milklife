# F8-03 Fitur hari lomba: daftar ulang, BIB massal, daftar peserta

| | |
|---|---|
| Status | rencana |
| Tahap | 8 — Hari lomba dan hasil |
| Dibuat | 17 November 2026 |

## Tujuan

Panitia bisa mendaftar ulang sekolah dengan memindai kode QR bukti, mencetak nomor atlet (BIB) massal, dan mencetak daftar peserta per nomor untuk pemanggilan atlet dan juri (S7).

## Hasil

- [ ] Halaman `admin/daftar-ulang`: pindai QR (fitur bawaan peramban) atau ketik ID; status, nomor atlet, daftar atlet, tandai hadir per atlet dan semua, batal hadir
- [ ] Kode QR gabungan (K109) diarahkan ke pendaftaran gabungan; tautan daftar ulang dari halaman bukti bila admin sedang masuk
- [ ] Cetak BIB massal per kelompok umur (PDF)
- [ ] Daftar peserta per nomor lomba (PDF dan Excel), nomor beregu per regu
- [ ] Uji fitur

## Selesai bila

Uji lulus, `composer uji` lulus, halaman dicoba di Chromium 390 piksel.

## Catatan

K124. Harus siap jauh sebelum pertemuan teknik 6 Desember 2026.
