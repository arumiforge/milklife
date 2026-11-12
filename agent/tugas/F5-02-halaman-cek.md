# F5-02 Halaman cek pendaftaran

| | |
|---|---|
| Status | selesai |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

Official bisa memeriksa pendaftarannya lewat NPSN atau ID pendaftaran di `/cek-pendaftaran`.

## Hasil

- [x] Rute, pengendali, dan tampilan `/cek-pendaftaran` (NPSN: per pendaftaran berurutan, K49)
- [x] Pesan untuk masukan kosong, keliru, dan tidak ditemukan
- [x] Hanya bisa dipakai bila pengaturan lengkap
- [x] Pembatasan laju (kunci `cek-`)
- [x] Tombol Cek di halaman depan dan menu Cek aktif
- [x] `tests/fitur/CekPendaftaranTest.php`

## Selesai bila

`composer uji` dan CI lulus; halaman diperiksa di peramban pada 390 dan 1440 piksel dengan data karangan.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.

- Pencarian lewat GET `?kode=`; spasi dibuang, huruf dijadikan besar. Delapan angka = NPSN, diawali `MAC` = ID.
- Batas laju 10 pencarian per menit per alamat IP (kunci `cek-` + hash alamat); membuka formulir tanpa `kode` tidak dihitung.
- Menu Cek selalu menaut; tombol Cek di halaman depan nonaktif selama pengaturan belum lengkap (`tombol()` memakai `bukaTutup()->keadaan()`).
- Uji Tahap 3 yang terdampak langsung (label "Cek Pendaftaran Segera"): `HalamanDepanKepalaTest`, `TataLetakHalamanTest`, `HalamanUnduhanTest`. Bagian Pendaftaran di uji itu tidak diubah.
- Peramban (Chromium, 390 dan 1440 piksel) dengan data karangan: tanpa gulir mendatar; tabel atlet bergulir di dalam kartu pada ponsel.
