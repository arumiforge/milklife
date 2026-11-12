# F5-02 Halaman cek pendaftaran

| | |
|---|---|
| Status | rencana |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

Official bisa memeriksa pendaftarannya lewat NPSN atau ID pendaftaran di `/cek-pendaftaran`.

## Hasil

- [ ] Rute, pengendali, dan tampilan `/cek-pendaftaran` (NPSN: per pendaftaran berurutan, K49)
- [ ] Pesan untuk masukan kosong, keliru, dan tidak ditemukan
- [ ] Hanya bisa dipakai bila pengaturan lengkap
- [ ] Pembatasan laju (kunci `cek-`)
- [ ] Tombol Cek di halaman depan dan menu Cek aktif
- [ ] `tests/fitur/CekPendaftaranTest.php`

## Selesai bila

`composer uji` dan CI lulus; halaman diperiksa di peramban pada 390 dan 1440 piksel dengan data karangan.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.
