# F7-25 Backup data tanpa memuat ke memori

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Unduhan Backup data tetap berhasil walau berisi ratusan foto dan bukti bayar (B4).

## Hasil

- [x] `Admin/PendaftaranController::ekspor()` mengirim zip dengan `$this->response->download($jalur, null)->setFileName(...)`, tanpa `file_get_contents`
- [x] Berkas sementara di `writable/cache/ekspor/`; berkas yang lebih tua dari 1 jam dihapus setiap ekspor baru
- [x] Uji fitur ekspor tetap lulus, ditambah uji pembersihan berkas lama

## Selesai bila

Uji lulus dan `composer uji` lulus lokal dan di CI.

## Catatan

K118. Analisis bagian B4.
