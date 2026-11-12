# F4-02 Pencarian NPSN

| | |
|---|---|
| Status | selesai |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Official yang mengetik NPSN langsung melihat nama sekolahnya, dan SLB ditolak dengan pesan jelas.

## Hasil

- [x] Blok rute `/pendaftaran` berkomentar sendiri di `app/Config/Routes.php`
- [x] `App\Controllers\PendaftaranController::sekolah` (`GET pendaftaran/sekolah?npsn=…`): nama, kabupaten, jenjang dari data referensi; SLB ditolak (K28); NPSN tidak ditemukan dijawab ramah supaya nama diisi manual
- [x] Data susulan untuk NPSN itu: tim beregu yang sudah ada (jumlah anggota) dan jumlah atlet per nomor individu yang sudah terpakai
- [x] Pembatasan laju per alamat IP, kunci `pendaftaran-sekolah-…`, jawaban 429 dengan lama menunggu

## Selesai bila

`composer uji` lokal dan CI lulus; `tests/fitur/PendaftaranSekolahTest.php` menguji ditemukan, tidak ditemukan, NPSN tidak sah, SLB, data susulan, dan 429.

## Catatan

Jawaban hanya berisi data sekolah referensi dan jumlah, tanpa data siswa.

- Bentuk jawaban ditulis di komentar `PendaftaranController::sekolah()`: selalu `status` dan `pesan`; `ditemukan` (200) dengan `sekolah` dan `susulan`, `tidak_ditemukan` (404) dengan `susulan`, `tidak_sah` atau `slb` (422), `terlalu_sering` (429, kepala `Retry-After`). Setiap jawaban JSON membawa token CSRF baru di `csrf` (dipakai F4-04).
- `susulan.tim` dan `susulan.individu` berupa objek id nomor → jumlah, juga untuk NPSN yang tidak ada di data referensi (pendaftaran sebelumnya dengan nama manual).
- Pembatas laju bersama di `PendaftaranController::batasiLaju()` (kunci `pendaftaran-<jenis>-<hash alamat IP>`), dipakai lagi F4-04.
- Rute berada di blok berkomentar "Pendaftaran (Tahap 4)" di `app/Config/Routes.php`.
