# F8-07 Menu ponsel: sidebar dan 5 butir bawah

| | |
|---|---|
| Tahap | 8 — Hari lomba dan hasil |
| Dibuat | 18 November 2026 |

## Tujuan

Di ponsel admin bisa membuka semua menu panel dari sidebar, sementara menu bawah yang menempel tetap lega dengan 5 butir.

## Hasil

- [ ] Tombol Menu di ponsel membuka sidebar (laci samping, Bootstrap offcanvas) berisi semua butir menu panel, dengan butir aktif ditandai
- [ ] Menu bawah yang menempel di ponsel hanya 5 butir urusan pendaftaran: Dasbor, Pendaftaran, Atlet, Ditolak (Atlet ditolak), Menu (K131, K132)
- [ ] Hari lomba, Hasil, Klasemen, dan butir lain hanya lewat sidebar di ponsel
- [ ] Tampilan komputer tidak berubah
- [ ] Dicoba di Chromium 390 piksel: sidebar terbuka dan tertutup, tanpa gulir mendatar

## Berkas dan uji

Tata letak panel admin (`app/Views/tata_letak/` dan CSS/JS panel). Uji: `vendor/bin/phpunit --filter TataLetak`, lalu `composer uji`.

## Selesai bila

`composer uji` lulus, CI hijau, dan tangkapan layar 390 piksel diperiksa (tidak masuk git).

## Catatan

K131 dan K132, menggantikan K130 untuk menu bawah ponsel. Setelah #104 (F8-03) masuk `main`; urutkan dengan F8-04 karena keduanya menyentuh menu.
