# F7-24 Galat CSRF terlihat dan tidak merusak banyak tab

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Admin dan pengunjung yang mengirim formulir dari tab lama atau halaman yang terlalu lama terbuka mendapat pesan yang jelas dan isiannya tidak hilang, bukan halaman yang dimuat ulang tanpa reaksi (B1).

## Hasil

- [ ] `regenerate = false` di `app/Config/Security.php`; perlindungan CSRF tetap aktif
- [ ] Filter CSRF turunan mengalihkan kembali dengan `pesan_galat` berbahasa Indonesia (misalnya "Halaman ini sudah terlalu lama terbuka atau dibuka di tab lain. Muat ulang halaman, lalu ulangi.") dan `withInput()`; kiriman JSON (wizard) mendapat jawaban JSON dengan pesan yang sama
- [ ] Pesan tampil di panel admin dan halaman publik
- [ ] Uji fitur: kiriman dengan token lama di lingkungan produksi menampilkan pesan itu

## Selesai bila

Uji fitur baru lulus dan `composer uji` lulus lokal dan di CI.

## Catatan

K118. Analisis 14 November bagian B1. Dikerjakan bersama F7-25 di satu sub-sesi (satu tugas, satu PR).
