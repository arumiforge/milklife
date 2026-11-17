# F7-27 Cloudflare Turnstile di wizard, Cek Pendaftaran, dan masuk admin

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Bot tidak bisa mengirim pendaftaran, mencari di Cek Pendaftaran, atau menebak sandi admin tanpa lolos Turnstile; tanpa kunci di `.env` web tetap jalan seperti sekarang.

## Hasil

- [ ] Pustaka pemeriksa token lewat `CURLRequest` (tanpa pustaka PHP baru): tolak token kosong, bukan teks, atau lebih dari 2048 huruf; siteverify dengan secret, token, dan `getIPAddress()`; batas waktu 10 detik; terima hanya bila `success`, `action` cocok, dan `hostname` diizinkan
- [ ] Kunci `turnstile.kunciSitus`, `turnstile.kunciRahasia`, `turnstile.namaHost` di `.env`; `.env.example` berisi kunci situs `0x4AAAAAAGFuKUT0k64YcOX5` dan kunci rahasia kosong; kunci kosong = Turnstile mati
- [ ] Peramban: `https://challenges.cloudflare.com/turnstile/v0/api.js` (`async defer`), `cf-turnstile` dengan `data-sitekey` dan `data-action` per tempat; wizard mengirim token di badan JSON dan memanggil `turnstile.reset(widgetId)` sebelum kirim ulang
- [ ] Dipasang di kirim pendaftaran, Cek Pendaftaran, dan masuk admin
- [ ] Gagal = 403 dengan pesan berbahasa Indonesia yang menyebut cara memperbaikinya
- [ ] CSP mengizinkan `challenges.cloudflare.com` untuk script dan frame; `KepalaKeamananTest` diperbarui
- [ ] Uji memakai kunci uji resmi Cloudflare dan tiruan HTTP, tanpa panggilan jaringan
- [ ] Panduan produksi (diubah langsung sesuai K101, PDF dibuat ulang): memasang kunci rahasia dari dasbor Cloudflare, daftar nama host, cara mematikan sementara; tanpa langkah `wrangler`

## Selesai bila

Uji lulus, `composer uji` lulus lokal dan di CI, dan tidak ada kunci rahasia di diff.

## Catatan

K122. Kunci rahasia tidak pernah masuk git, chat, log, atau diff. Dikerjakan setelah F7-26 di sub-sesi yang sama.
