# F9-05 Wizard, cek, dan bukti ke Tailwind

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Wizard pendaftaran, Cek Pendaftaran, dan unggah bukti bayar memakai tampilan baru yang konsisten dengan halaman depan.

## Hasil

- [ ] Lima langkah wizard pindah ke `publik_tw.php` dengan perilaku sama persis (validasi, draf, regu, Turnstile, idempoten K118)
- [ ] Langkah wizard memakai komponen langkah, ilustrasi G9-03 di kepala tiap langkah bila ada, laci bantuan F9-04
- [ ] Cek Pendaftaran, kartu, atlet ditolak, dan unggah bukti bayar ikut pindah
- [ ] Layar berhasil memakai ilustrasi G9-04 `berhasil.webp` bila ada

## Berkas dan uji

`app/Views/pendaftaran*`, `app/Views/cek_pendaftaran*`, `public/aset/js/pendaftaran/` (hanya pemilih kelas/elemen), `public/aset/css/pendaftaran.css` (dilepas bila tidak dipakai). Uji: pola wizard dan cek/bukti di `agent/PETA-KODE.md`, `AlurMenyeluruhTest`.

## Selesai bila

Alur daftar → cek → unggah bukti berhasil di peramban (390 dan 1280 piksel, minify menyala) tanpa galat konsol/CSP; semua uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. **Dikerjakan setelah pendaftaran tutup 30 November 23.59** supaya tidak mengganggu pendaftar. Cek Pendaftaran tetap dipakai sampai hari lomba (kode QR).
