# F4-02 Pencarian NPSN

| | |
|---|---|
| Status | rencana |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Official yang mengetik NPSN langsung melihat nama sekolahnya, dan SLB ditolak dengan pesan jelas.

## Hasil

- [ ] Blok rute `/pendaftaran` berkomentar sendiri di `app/Config/Routes.php`
- [ ] `App\Controllers\PendaftaranController::sekolah` (`GET pendaftaran/sekolah?npsn=…`): nama, kabupaten, jenjang dari data referensi; SLB ditolak (K28); NPSN tidak ditemukan dijawab ramah supaya nama diisi manual
- [ ] Data susulan untuk NPSN itu: tim beregu yang sudah ada (jumlah anggota) dan jumlah atlet per nomor individu yang sudah terpakai
- [ ] Pembatasan laju per alamat IP, kunci `pendaftaran-sekolah-…`, jawaban 429 dengan lama menunggu

## Selesai bila

`composer uji` lokal dan CI lulus; `tests/fitur/PendaftaranSekolahTest.php` menguji ditemukan, tidak ditemukan, NPSN tidak sah, SLB, data susulan, dan 429.

## Catatan

Jawaban hanya berisi data sekolah referensi dan jumlah, tanpa data siswa.
