# F5-04 PDF dokumen

| | |
|---|---|
| Status | selesai |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

PDF di `dokumen/pdf/` dibuat dari dokumen final dengan satu perintah, tidak disunting tangan.

## Hasil

- [x] Perintah `php spark dokumen:pdf`
- [x] `App\Libraries\MarkdownSederhana` dan `tests/unit/MarkdownSederhanaTest.php`
- [x] `dokumen/pdf/rancangan-web-pendaftaran.pdf`

## Selesai bila

`composer uji` dan CI lulus; PDF rancangan terbaca dengan tabel dan daftar utuh.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.

- Tanpa paket Markdown: `MarkdownSederhana` hanya mengenal judul, paragraf, daftar, tabel, tebal, miring, kode sebaris, dan tautan (cukup untuk dokumen final sekarang). Daftar bersarang, kutipan, dan blok kode belum didukung.
- PDF A4 tegak, nomor halaman di kaki, judul PDF dari judul `#` dokumen.
- `ALUR-KERJA.md` bagian Dokumen menyebut perintahnya.
- Menunda tugas F0-01 (PDF dokumen ditunda ke Tahap 5) selesai di sini.
