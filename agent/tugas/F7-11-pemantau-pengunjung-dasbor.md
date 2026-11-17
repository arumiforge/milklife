# F7-11 Monitoring, catatan pengunjung, Pengecekan, dan dasbor bergrafik

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 15 November 2026 |

## Tujuan

Pemilik bisa memantau keadaan server, basis data, penyimpanan, dan pengunjung dari satu halaman terlindung, dan dasbor admin menampilkan ringkasan data dengan grafik (K102–K104).

## Hasil

- [x] `public/arumi.php` (judul Monitoring, tema gelap, K104): kotak masuk tanpa teks berlatar kabur, kata sandi sebagai hash bcrypt (bisa diganti `arumi.sandiHash` di `.env`), kunci 15 menit setelah 5 kali salah, sesi sendiri 60 menit, token CSRF, `noindex`/`no-store`/`frame-ancestors 'none'`
- [x] Monitoring: kondisi PHP (versi, batas, OPcache, ekstensi), server web dan Nginx (termasuk ukuran log Nginx Laragon), basis data (ukuran, tabel, sambungan), penyimpanan (MB gambar dan bukti per jenis, log, sesi, cache, kode), diska server
- [x] Pengunjung per jam dan per hari, pilih tanggal lain (tombol hari sebelum/sesudah, pemilih tanggal, klik grafik harian), halaman terpopuler
- [x] Pustaka `App\Libraries\Pengunjung`, filter `catatpengunjung`, tabel `kunjungan` (IP tidak disimpan)
- [x] Menu kiri: Pengecekan dan Halaman depan di bagian bawah; halaman `/admin/pengecekan` berisi Keadaan pendaftaran, Kelengkapan pengaturan, Gambar kepala halaman depan
- [x] Dasbor: kartu ringkasan tambahan dan delapan grafik ApexCharts (`aset/js/dasbor.js`)
- [x] ApexCharts 4.7.0 dan CSS Flowbite disimpan di `public/aset/vendor/`
- [x] Uji: `PengunjungAturanTest`, `PengunjungTest`, `CatatPengunjungTest`, `RingkasanDasborTest`, `PemantauArumiTest`, dan uji dasbor/tata letak yang disesuaikan

## Selesai bila

`composer uji` lulus lokal dan di CI; `/arumi.php` dan `/admin/dasbor` diperiksa di peramban (layar lebar dan ponsel).

## Catatan

- Setelah memasang versi ini di server: `php spark migrate` (tabel `kunjungan`), lalu `php spark aset:minify`.
- Rancangan final (bagian dasbor 6.1 dan menu admin) belum diubah; menunggu revisi rancangan berikutnya.
- Nginx Laragon menjalankan semua berkas `.php` di folder publik, jadi `/arumi.php` langsung bisa dibuka tanpa mengubah pengaturan Nginx.
