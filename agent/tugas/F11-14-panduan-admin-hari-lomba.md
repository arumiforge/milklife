# F11-14 Panduan admin hari lomba

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 23 November 2026 |

## Tujuan

Panitia punya panduan kerja admin hari lomba yang bisa langsung dipakai, urut dari persiapan sampai akhir hari lomba, dengan nama menu persis seperti di panel.

## Hasil

- [x] Draf `dokumen/draf/panduan-admin-hari-lomba.md` (F11-08) dilengkapi: sebelum hari lomba, pagi hari lomba, selama lomba, akhir hari, daftar periksa tiap tahap, dan bagian "Bila terjadi masalah".
- [x] Panduan dipindah ke `dokumen/final/panduan-admin-hari-lomba.md` dengan status disetujui 23 November 2026 (K178), dan PDF-nya dibuat dengan `php spark dokumen:pdf`.
- [x] Tautan ke draf di `agent/` diperbarui.

## Berkas dan uji

`dokumen/final/panduan-admin-hari-lomba.md`, `dokumen/pdf/panduan-admin-hari-lomba.pdf`, `agent/KEPUTUSAN.md`, `agent/KONTEKS.md`, `agent/PROGRES.md`, `agent/arsip/tahap-11.md`. Uji: `vendor/bin/phpunit --filter Dokumen` dan `--filter MarkdownSederhana`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

PDF panduan ada di `dokumen/pdf/`, uji dokumen lulus, dan tidak ada tautan `agent/` yang masih menunjuk ke draf panduan.

## Catatan

Permintaan pemilik 23 November 2026: "kerjakan Draf panduan admin hari lomba". Tanpa tangkapan layar karena PDF dokumen dibuat dari teks Markdown saja (`MarkdownSederhana` tidak memuat gambar). Kode tidak diubah.
