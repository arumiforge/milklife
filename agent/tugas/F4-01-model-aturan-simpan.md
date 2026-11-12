# F4-01 Model, aturan, dan penyimpanan

| | |
|---|---|
| Status | selesai |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Semua aturan pendaftaran (rancangan bagian 6, K50, K51) bisa diperiksa dan pendaftaran bisa disimpan utuh dalam satu transaksi, sebelum halaman wizard dibuat.

## Hasil

- [x] Model `PendaftaranModel`, `PenanggungJawabModel`, `AtletModel`, `AtletNomorModel`, `PendaftaranBereguModel`
- [x] Aturan murni di `app/Libraries/Pendaftaran/` dengan uji unit: kabupaten, kelompok umur dari tahun lahir dan jenjang, usia tampilan, biaya, ID pendaftaran, kelengkapan tim (baru dan susulan), pemeriksaan seluruh kiriman
- [x] Pemeriksaan NISN terdaftar, satu tim per nomor beregu per sekolah (susulan tanpa biaya tim), dan kuota atlet per nomor individu per sekolah (K51)
- [x] Foto penanggung jawab wajib (K50), diperiksa dan disimpan ulang sebagai JPEG di luar `public/`
- [x] `SimpanPendaftaran`: kunci per NPSN, satu transaksi, ID pendaftaran, foto dihapus bila gagal
- [x] Kunci `Pendaftaran.atlet_per_nomor_maksimal` (nilai awal 2, kosong = tidak dibatasi) dan kotaknya di pengaturan Pendaftaran

## Selesai bila

`composer uji` lokal dan CI lulus, uji unit menutup setiap aturan (lulus dan ditolak), dan uji basis data membuktikan simpan berhasil, transaksi gagal tidak meninggalkan baris atau foto, serta susulan dari NPSN yang sama mengikuti aturan tim dan kuota.

## Catatan

Kesepakatan di `dokumen/draf/rencana-implementasi-tahap-4.md`, bagian "Aturan murni", "Aturan yang diperiksa server", "Penyimpanan", dan "Foto penanggung jawab". Model ini dipakai Tahap 5 hanya untuk membaca.

- Aturan murni di `app/Libraries/Pendaftaran/`: `Kabupaten`, `KelompokUmurAtlet`, `UsiaAtlet`, `IdPendaftaran`, `BiayaPendaftaran`, `KelengkapanTim`, `PemeriksaPendaftaran`, `FotoPenanggungJawab`. Pengumpul data `DataIndukPendaftaran`, penyimpanan `SimpanPendaftaran`.
- `PemeriksaPendaftaran::periksa($kiriman, $periksaFoto)` menghasilkan `galat` (`{langkah, jalur, pesan}`), `data` (siap simpan), dan `rincian` (untuk langkah 4). Bentuk larik ditulis di komentar kelas; dipakai F4-03 dan F4-04.
- Jalur galat: `sekolah.*` (langkah 1), `penanggung_jawab`, `penanggung_jawab.<i>.*` (langkah 2), `atlet`, `atlet.<i>.*`, `beregu.<nomor id>`, `individu.<nomor id>` (langkah 3), `simpan` (langkah 4).
- Kunci per NPSN memakai `GET_LOCK` MySQL (`milklife-pendaftaran-<npsn>`, tunggu 10 detik).
- `DataIndukPendaftaran` tidak `final`, supaya uji bisa menirukan kiriman lain yang tersimpan bersamaan.
- Penanda gagal transaksi CodeIgniter melekat pada sambungan; `SimpanPendaftaran` memanggil `resetTransStatus()` sebelum `transBegin()`.
- Nomor keputusan K50 dan K51 (bukan K49 dan K50 seperti rencana awal): PR Tahap 5 lebih dulu memakai K48 dan K49.
- Di luar berkas model dan pustaka: `.gitignore` ditambah `/writable/unggahan/` supaya foto penanggung jawab tidak pernah masuk git; pengaturan admin Pendaftaran mendapat kotak "Atlet per sekolah pada satu nomor individu, paling banyak" (K51).
- Skema tabel pendaftaran tidak diubah.
