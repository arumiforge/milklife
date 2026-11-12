# F4-01 Model, aturan, dan penyimpanan

| | |
|---|---|
| Status | rencana |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Semua aturan pendaftaran (rancangan bagian 6, K50, K51) bisa diperiksa dan pendaftaran bisa disimpan utuh dalam satu transaksi, sebelum halaman wizard dibuat.

## Hasil

- [ ] Model `PendaftaranModel`, `PenanggungJawabModel`, `AtletModel`, `AtletNomorModel`, `PendaftaranBereguModel`
- [ ] Aturan murni di `app/Libraries/Pendaftaran/` dengan uji unit: kabupaten, kelompok umur dari tahun lahir dan jenjang, usia tampilan, biaya, ID pendaftaran, kelengkapan tim (baru dan susulan), pemeriksaan seluruh kiriman
- [ ] Pemeriksaan NISN terdaftar, satu tim per nomor beregu per sekolah (susulan tanpa biaya tim), dan kuota atlet per nomor individu per sekolah (K51)
- [ ] Foto penanggung jawab wajib (K50), diperiksa dan disimpan ulang sebagai JPEG di luar `public/`
- [ ] `SimpanPendaftaran`: kunci per NPSN, satu transaksi, ID pendaftaran, foto dihapus bila gagal
- [ ] Kunci `Pendaftaran.atlet_per_nomor_maksimal` (nilai awal 2, kosong = tidak dibatasi) dan kotaknya di pengaturan Pendaftaran

## Selesai bila

`composer uji` lokal dan CI lulus, uji unit menutup setiap aturan (lulus dan ditolak), dan uji basis data membuktikan simpan berhasil, transaksi gagal tidak meninggalkan baris atau foto, serta susulan dari NPSN yang sama mengikuti aturan tim dan kuota.

## Catatan

Kesepakatan di `dokumen/draf/rencana-implementasi-tahap-4.md`, bagian "Aturan murni", "Aturan yang diperiksa server", "Penyimpanan", dan "Foto penanggung jawab". Model ini dipakai Tahap 5 hanya untuk membaca.
