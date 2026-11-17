# F7-26 Kirim pendaftaran idempoten

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Ofisial yang menekan Kirim Pendaftaran lagi setelah sinyal putus mendapat ID dan bukti pendaftaran yang sudah tersimpan, bukan galat "NISN sudah terdaftar" (B2).

## Hasil

- [ ] Wizard membuat `kunci_kirim` acak, menyimpannya di draf, dan mengirimnya bersama kiriman
- [ ] Kolom `pendaftaran.kunci_kirim` (unik, boleh kosong), migrasi baru
- [ ] Kunci yang sama menjawab 201 dengan ID, rincian, dan tautan bukti pendaftaran yang sudah ada, tanpa menulis apa pun
- [ ] Draf dikosongkan setelah berhasil; kunci baru untuk pendaftaran berikutnya
- [ ] Uji fitur kirim ganda

## Selesai bila

Uji lulus dan `composer uji` lulus lokal dan di CI.

## Catatan

K118. Analisis bagian B2. Satu sub-sesi dengan F7-27 (berkas yang sama: `pendaftaran.js`, `PendaftaranController`). F7-26 lebih dulu, PR sendiri.
