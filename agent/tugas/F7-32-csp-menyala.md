# F7-32 CSP menyala

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 18 November 2026 |

## Tujuan

Peramban hanya menjalankan skrip dan memuat sumber yang memang milik web ini (dan Cloudflare Turnstile), sehingga sisipan skrip dari luar tertahan.

## Hasil

- [x] `App::$CSPEnabled = true` dengan aturan di `app/Config/ContentSecurityPolicy.php`, termasuk `challenges.cloudflare.com` untuk script dan frame (K122)
- [x] Skrip dan gaya sebaris di halaman publik, wizard, Cek Pendaftaran, bukti, panel admin, dan halaman galat dipindah ke berkas atau memakai nonce CodeIgniter (`{csp-script-nonce}`, `{csp-style-nonce}`)
- [x] Atribut `onclick`/`style` sebaris yang ditolak CSP diganti
- [x] Semua halaman dicoba di Chromium 390 dan 1280 piksel tanpa pelanggaran CSP di konsol, termasuk mode produksi dengan minify (`perkecilhtml` tidak merusak nonce)
- [x] `KepalaKeamananTest` memeriksa kepala CSP yang menyala

## Berkas dan uji

`app/Config/App.php`, `app/Config/ContentSecurityPolicy.php`, view di `app/Views/`, CSS/JS di `public/aset/`. Uji: `vendor/bin/phpunit --filter KepalaKeamanan`, `--filter TataLetak`, `--filter PerkecilHtml`, lalu `composer uji`.

## Selesai bila

`composer uji` lulus, CI hijau, dan pemeriksaan peramban tanpa pelanggaran CSP ditulis di deskripsi PR.

## Catatan

K127. Setelah F7-30 dan F8-04/F8-05 masuk `main`, sebelum F7-06.
