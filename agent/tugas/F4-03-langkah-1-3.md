# F4-03 Langkah 1–3, daftar sementara, draf, dan foto

| | |
|---|---|
| Status | rencana |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Official bisa mengisi data sekolah, penanggung jawab, dan atlet di ponsel tanpa kehilangan isian saat halaman dimuat ulang.

## Hasil

- [ ] Halaman `/pendaftaran` dengan lima pil langkah bercentang; keterangan ramah bila pendaftaran belum terbuka
- [ ] Langkah 1: jenjang, kabupaten, NPSN dengan pencarian (F4-02) atau nama manual
- [ ] Langkah 2: penanggung jawab sesuai batas pengaturan, nomor WA, foto wajib diperkecil di peramban (800 piksel, JPEG)
- [ ] Langkah 3: formulir atlet (nama huruf besar, NISN, tanggal lahir, jenis kelamin), kelompok umur dan usia otomatis, pilihan nomor sesuai kelompok umur, jenjang, dan jenis kelamin; daftar sementara individu dan tim beregu dengan ubah dan hapus; kelengkapan tim dan kuota diperiksa sebelum lanjut
- [ ] Draf di `localStorage` pada setiap perubahan
- [ ] `public/aset/js/pendaftaran.js` dan `public/aset/css/pendaftaran.css`

## Selesai bila

`composer uji` lokal dan CI lulus, dan alur langkah 1–3 diperiksa di Chromium pada lebar 390 dan 1440 piksel tanpa gulir mendatar, termasuk muat ulang di tengah isian.

## Catatan

Validasi peramban hanya untuk kenyamanan; aturan yang berlaku tetap di server (F4-01).
