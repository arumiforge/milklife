# F4-04 Langkah 4–5: biaya, simpan, pembayaran, dan WA

| | |
|---|---|
| Status | selesai |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Official melihat rincian biaya yang dihitung server, menyimpan pendaftaran, lalu mendapat data rekening, tombol konfirmasi WA, dan tombol cetak bukti.

## Hasil

- [x] `POST pendaftaran/periksa`: galat per langkah, atau rincian biaya; pembatasan laju
- [x] Langkah 4: tabel individu dan beregu, subtotal, total, selalu tampil (K45); jendela konfirmasi
- [x] `POST pendaftaran/simpan`: simpan lewat `SimpanPendaftaran` (F4-01); ID pendaftaran; pembatasan laju; pesan bila pendaftaran sudah ditutup
- [x] Langkah 5: logo bank, rekening, pemilik, total; "Kirim Konfirmasi Pembayaran" membuka WA dengan `Pembayaran.templat_pesan`; "Pendaftaran Selesai" mengosongkan draf dan menampilkan tombol cetak bukti `/bukti/<ID>`
- [x] Draf terkunci di langkah 5 setelah tersimpan, supaya tidak ada pendaftaran ganda

## Selesai bila

`composer uji` lokal dan CI lulus, dan satu pendaftaran lengkap disimpan lewat peramban (Chromium, 390 piksel) dengan data karangan, termasuk galat NISN terdaftar yang membuka kembali langkah 3.

## Catatan

Tautan `/bukti/<ID>` boleh 404 sampai Tahap 5 masuk `main` (kesepakatan dua sesi, butir 3). Tahap 5 sudah di `main`, jadi tautan langsung membuka PDF.

- Jawaban `periksa` dan `simpan` ditulis di komentar `PendaftaranController`: `sah` (200), `tersimpan` (201, dengan `id_pendaftaran`, `rincian`, `wa`, `bukti`), `galat` (422, daftar `{langkah, jalur, pesan}`), `tidak_sah` (400), `ditutup` (409), `terlalu_sering` (429). Batas laju: periksa 30 per menit, simpan 10 per 10 menit per alamat IP.
- `App\Libraries\Pendaftaran\PesanKonfirmasi`: penanda `Pembayaran.templat_pesan` diganti data pendaftaran (uang `Rp…`, NPSN kosong "-"), alamat `https://wa.me/<nomor>?text=…`.
- Pemeriksaan server dijalankan saat pindah dari langkah 3 ke 4 (asumsi 2 rencana); galat membuka langkah pertama yang salah. Isian yang berubah membuat rincian lama tidak berlaku.
- Setelah tersimpan, foto dihapus dari draf, draf terkunci di langkah 5 (muat ulang tetap di langkah 5, tidak ada pendaftaran ganda). "Pendaftaran Selesai" mengosongkan draf dan menampilkan tombol cetak bukti.
- Tabel langkah 4 bertumpuk berlabel kolom di layar di bawah 576 piksel.
- Diperiksa di Chromium 390 dan 1440 piksel: alur lengkap sampai bukti PDF terunduh (200, `application/pdf`) dan pendaftaran tampil di `/cek-pendaftaran`; tim yang melewati batas anggota pada pendaftaran susulan ditahan di langkah 3. Tanpa gulir mendatar dan tanpa galat skrip.
