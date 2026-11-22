# F10-13 Tata bahasa panel admin

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P2 |

## Tujuan

Semua teks panel admin (menu, judul, tombol, label, pesan galat dan berhasil, konfirmasi, keadaan kosong, ekspor) lugas dan mudah dipahami panitia, mengikuti skill `.claude/skills/bahasa-indonesia-plain/SKILL.md` (K159).

## Hasil

- [ ] Audit dan tulis ulang teks di `app/Views/admin/`, `tata_letak/admin_tw.php`, `Panel/MenuAdmin`, pengendali `Admin/*`, pustaka `Panel/*` dan `Hasil/*` yang mengirim pesan ke admin, JS admin, dan PDF/Excel admin (judul kolom bila perlu)
- [ ] Keputusan lama menang bila bertentangan dengan skill: `Rp 150.000` (K80), istilah K113, K90, K116, K117; panel admin seluruhnya Indonesia (K153); konflik lain ditulis di PR dengan rekomendasi
- [ ] Hanya teks yang berubah: atribut, rute, `name`, `data-*`, dan logika tetap
- [ ] Uji yang memeriksa teks diperbarui tanpa melemahkan asersi; `composer uji` lulus

## Berkas dan uji

View dan pengendali admin, `Panel/MenuAdmin`, uji `Panel*`, `Pengaturan*`, `HariLomba*`, `Hasil*`, `TataLetakHalamanTest`, `JejakHalamanAdminTest`.

## Selesai bila

Halaman admin diperiksa di Chromium 360/1280 tanpa galat konsol/CSP dan tanpa teks meluap; semua uji lulus.

## Catatan

Dimulai setelah F10-08 (#190) masuk `main` supaya tidak bentrok. Pasangan publiknya F10-12. Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`.
