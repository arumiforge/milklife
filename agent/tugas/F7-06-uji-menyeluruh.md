# F7-06 Uji menyeluruh

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Seluruh web terbukti bekerja bersama di mode produksi dengan minify aktif, di ponsel dan komputer, sebelum dipasang.

## Hasil

- [ ] Pemasangan bersih mengikuti panduan F7-05 sejauh berlaku di Linux, `CI_ENVIRONMENT = production`, `aplikasi.minify = true`
- [ ] Semua halaman publik dan admin dicoba di Chromium 390 dan 1280 piksel tanpa galat konsol, termasuk wizard dengan susulan, cek, bukti PDF, panel lengkap, rekap Excel/PDF, pengaturan, ganti sandi dengan dua sesi, halaman galat
- [ ] `tests/fitur/AlurMenyeluruhTest.php`: alur lengkap dari simpan pendaftaran sampai hapus, dengan minify aktif
- [ ] Skenario B1 (token CSRF lama di dua tab), B2 (kirim ulang setelah sambungan putus), Turnstile dengan kunci uji Cloudflare, dan fitur Tahap 8 (daftar ulang, BIB, daftar peserta, hasil, klasemen) ikut dicoba (K118, K119)
- [ ] Perbaikan sambungan seperlunya, disebut satu per satu di PR
- [ ] Hasil (dicoba, diperbaiki, tersisa) ditulis di bagian Catatan

## Selesai bila

`composer uji` lulus lokal dan di CI pada `main` setelah F7-01 sampai F7-05 digabung, dan daftar hasil di berkas ini lengkap.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Uji menyeluruh". Asumsi 7. Gelombang 3. Tangkapan layar tidak masuk git.

Diperluas 17 November 2026 (K119): dijalankan setelah F7-24 sampai F7-30 dan F8-01 sampai F8-05 masuk `main`, bersama F8-06.
