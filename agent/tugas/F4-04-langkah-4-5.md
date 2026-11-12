# F4-04 Langkah 4–5: biaya, simpan, pembayaran, dan WA

| | |
|---|---|
| Status | rencana |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Official melihat rincian biaya yang dihitung server, menyimpan pendaftaran, lalu mendapat data rekening, tombol konfirmasi WA, dan tombol cetak bukti.

## Hasil

- [ ] `POST pendaftaran/periksa`: galat per langkah, atau rincian biaya; pembatasan laju
- [ ] Langkah 4: tabel individu dan beregu, subtotal, total, selalu tampil (K45); jendela konfirmasi
- [ ] `POST pendaftaran/simpan`: simpan lewat `SimpanPendaftaran` (F4-01); ID pendaftaran; pembatasan laju; pesan bila pendaftaran sudah ditutup
- [ ] Langkah 5: logo bank, rekening, pemilik, total; "Kirim Konfirmasi Pembayaran" membuka WA dengan `Pembayaran.templat_pesan`; "Pendaftaran Selesai" mengosongkan draf dan menampilkan tombol cetak bukti `/bukti/<ID>`
- [ ] Draf terkunci di langkah 5 setelah tersimpan, supaya tidak ada pendaftaran ganda

## Selesai bila

`composer uji` lokal dan CI lulus, dan satu pendaftaran lengkap disimpan lewat peramban (Chromium, 390 piksel) dengan data karangan, termasuk galat NISN terdaftar yang membuka kembali langkah 3.

## Catatan

Tautan `/bukti/<ID>` boleh 404 sampai Tahap 5 masuk `main` (kesepakatan dua sesi, butir 3).
