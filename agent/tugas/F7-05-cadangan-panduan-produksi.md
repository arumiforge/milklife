# F7-05 Cadangan dan panduan produksi

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Pemilik bisa memasang web di komputer produksi Laragon tanpa bantuan, dan data pendaftaran tercadang setiap hari (K66).

## Hasil

- [ ] `php spark cadangan:buat`: zip berisi `basisdata.sql`, `writable/unggahan/`, `public/unggahan/` ke `cadangan.folder`; menghapus cadangan lebih tua dari `cadangan.simpanHari` (14); menolak folder di dalam proyek; kode keluar bukan 0 bila gagal
- [ ] `Config\Cadangan` dan bagian Cadangan di `.env.example`; sandi basis data tidak terlihat di baris perintah
- [ ] `dokumen/draf/panduan-pemasangan-produksi.md`: Laragon dan `php.ini`, `.env` produksi, perintah pemasangan, `aset:minify`, virtual host Nginx ke `public/`, Cloudflare Tunnel, Penjadwal Tugas harian, pemulihan, pembaruan versi, daftar periksa sebelum membuka pendaftaran
- [ ] `README.md`: tautan ke panduan dan baris Status diperbarui
- [ ] Setelah disetujui pemilik: panduan dipindahkan ke `dokumen/final/` dan PDF dibuat dengan `php spark dokumen:pdf` di PR yang sama

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup masa simpan 14 hari (kelas murni), penolakan folder di dalam proyek, dan isi zip dari basis data uji. Panduan disetujui pemilik.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Cadangan" dan "Panduan pemasangan produksi". Asumsi 3, 4, 8. Gelombang 2, setelah F7-01 dan F7-02 digabung (nama perintah minify dan isian `.env` produksi).
