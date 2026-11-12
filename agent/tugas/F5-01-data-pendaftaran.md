# F5-01 Pustaka data pendaftaran

| | |
|---|---|
| Status | selesai |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

Halaman cek dan bukti punya satu tempat baca-saja untuk data pendaftaran, dan mPDF terpasang.

## Hasil

- [x] `mpdf/mpdf` di `composer.json` dan `composer.lock`
- [x] `App\Libraries\DataPendaftaran`: `cariId()` dan `cariNpsn()`, bentuk larik di komentar kelas
- [x] Data uji karangan `tests/_support/CekDataPendaftaran.php` diisi langsung ke tabel
- [x] `tests/basisdata/DataPendaftaranTest.php`

## Selesai bila

`composer uji` dan CI lulus; uji membuktikan biaya dibaca dari nilai tersimpan dan foto tidak ikut keluar.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.

- `mpdf/mpdf` 8.3.1 (ikut terpasang: `setasign/fpdi`, `psr/http-message`, `paragonie/random_compat`, `mpdf/psr-*`; `myclabs/deep-copy` pindah dari dev ke utama).
- Model Tahap 4 belum ada di `main`; pustaka membaca tabel langsung. Empat kueri per pencarian, untuk semua pendaftaran sekaligus.
- Tim dari pendaftaran susulan yang hanya menambah anggota (tanpa baris `pendaftaran_beregu`) keluar dengan `biaya_tim` `null`.
