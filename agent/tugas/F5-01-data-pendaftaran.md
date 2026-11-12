# F5-01 Pustaka data pendaftaran

| | |
|---|---|
| Status | rencana |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

Halaman cek dan bukti punya satu tempat baca-saja untuk data pendaftaran, dan mPDF terpasang.

## Hasil

- [ ] `mpdf/mpdf` di `composer.json` dan `composer.lock`
- [ ] `App\Libraries\DataPendaftaran`: `cariId()` dan `cariNpsn()`, bentuk larik di komentar kelas
- [ ] Data uji karangan `tests/_support/CekDataPendaftaran.php` diisi langsung ke tabel
- [ ] `tests/basisdata/DataPendaftaranTest.php`

## Selesai bila

`composer uji` dan CI lulus; uji membuktikan biaya dibaca dari nilai tersimpan dan foto tidak ikut keluar.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.
