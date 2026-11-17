# F7-20 Pemeriksaan setelah git pull

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Setelah `git pull` di server, satu perintah memberi tahu perintah lain apa yang masih perlu dijalankan, supaya tidak ada langkah pembaruan yang terlewat (K115).

## Hasil

- [x] Perintah `php spark pembaruan:periksa` (`app/Commands/PeriksaPembaruan.php`), hanya membaca
- [x] Pemeriksaan di `App\Libraries\PemeriksaPembaruan`: Composer (`composer.lock` lawan `vendor/composer/installed.json`, tanpa paket pengembang), migrasi semua namespace, data sekolah (`sekolah.json` lawan tabel, tanpa sekolah disunting admin), pendaftaran Lunas ber-NPSN sama, dan berkas `.min` yang belum ada atau lebih tua
- [x] Hasil `[beres]`/`[PERLU]` per pemeriksaan, lalu daftar perintah berurutan dengan `cadangan:buat` di depan; kode keluar 0 bila beres, 1 bila tidak
- [x] Pemeriksaan yang gagal (MySQL mati, berkas rusak) tidak dianggap beres dan menyebut cara memperbaikinya; tabel yang belum dibuat ditangani tanpa galat
- [x] Panduan produksi revisi 4 (bagian 10), rancangan revisi 16 (bagian 15), README, KEPUTUSAN K115

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji baru: `tests/unit/PemeriksaPembaruanTest.php` dan `tests/basisdata/PeriksaPembaruanTest.php`.

## Catatan

- Dicoba di basis data kosong (menyebut `migrate --all` dan `sekolah:impor`), setelah migrasi (hanya `sekolah:impor`), setelah impor (beres, kode keluar 0), dan dengan MySQL mati.
- `data:awal` dan `dokumen:pdf` tidak diperiksa: yang pertama hanya untuk pemasangan baru, yang kedua untuk pengembang.
- PR [#89](https://github.com/arumiforge/milklife/pull/89).
