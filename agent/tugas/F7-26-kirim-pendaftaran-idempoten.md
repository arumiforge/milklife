# F7-26 Kirim pendaftaran idempoten

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Ofisial yang menekan Kirim Pendaftaran lagi setelah sinyal putus mendapat ID dan bukti pendaftaran yang sudah tersimpan, bukan galat "NISN sudah terdaftar" (B2).

## Hasil

- [x] Wizard membuat `kunci_kirim` acak, menyimpannya di draf, dan mengirimnya bersama kiriman
- [x] Kolom `pendaftaran.kunci_kirim` (unik, boleh kosong), migrasi baru
- [x] Kunci yang sama menjawab 201 dengan ID, rincian, dan tautan bukti pendaftaran yang sudah ada, tanpa menulis apa pun
- [x] Draf dikosongkan setelah berhasil; kunci baru untuk pendaftaran berikutnya
- [x] Uji fitur kirim ganda

## Selesai bila

Uji lulus dan `composer uji` lulus lokal dan di CI.

## Catatan

K118. Analisis bagian B2. Satu sub-sesi dengan F7-27 (berkas yang sama: `pendaftaran.js`, `PendaftaranController`). F7-26 lebih dulu, PR sendiri.

Hasil:

- Migrasi `2026-11-18-000100_TambahKunciKirimPendaftaran`: kolom `kunci_kirim` VARCHAR(64), unik, boleh kosong.
- Kunci dibuat wizard saat Kirim Pendaftaran pertama ditekan (32 huruf heksadesimal), disimpan di draf, dan dipakai lagi sampai tersimpan. Draf baru (setelah tersimpan atau Mulai dari awal) tidak membawa kunci.
- Server menerima kunci 16–64 huruf `A-Z a-z 0-9 _ -`; selain itu kiriman diperlakukan tanpa kunci (halaman lama).
- Kunci diperiksa sebelum kunci NPSN, di dalamnya, dan sekali lagi bila penulisan gagal karena kunci unik (dua kiriman bersamaan). Kirim ulang tetap dijawab walaupun pendaftaran baru saja ditutup. Pembatas laju tetap menghitung kirim ulang.
- Rincian jawaban ulang dibaca dari basis data (`DataPendaftaran`), bukan dihitung ulang.
- Paket pindah server (K76) membawa `kunci_kirim` (paket lama tanpa kunci tetap bisa diimpor). Penggabungan (K109) tidak menyentuh kolom ini; kunci pendaftaran tujuan tetap.
