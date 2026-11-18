# F7-27 Cloudflare Turnstile di wizard, Cek Pendaftaran, dan masuk admin

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Bot tidak bisa mengirim pendaftaran, mencari di Cek Pendaftaran, atau menebak sandi admin tanpa lolos Turnstile; tanpa kunci di `.env` web tetap jalan seperti sekarang.

## Hasil

- [x] Pustaka pemeriksa token lewat `CURLRequest` (tanpa pustaka PHP baru): tolak token kosong, bukan teks, atau lebih dari 2048 huruf; siteverify dengan secret, token, dan `getIPAddress()`; batas waktu 10 detik; terima hanya bila `success`, `action` cocok, dan `hostname` diizinkan
- [x] Kunci `turnstile.kunciSitus`, `turnstile.kunciRahasia`, `turnstile.namaHost` di `.env`; `.env.example` berisi kunci situs `0x4AAAAAAGFuKUT0k64YcOX5` dan kunci rahasia kosong; kunci kosong = Turnstile mati
- [x] Peramban: `https://challenges.cloudflare.com/turnstile/v0/api.js` (`async defer`), `cf-turnstile` dengan `data-sitekey` dan `data-action` per tempat; wizard mengirim token di badan JSON dan memanggil `turnstile.reset(widgetId)` sebelum kirim ulang
- [x] Dipasang di kirim pendaftaran, Cek Pendaftaran, dan masuk admin
- [x] Gagal = 403 dengan pesan berbahasa Indonesia yang menyebut cara memperbaikinya
- [x] CSP mengizinkan `challenges.cloudflare.com` untuk script dan frame; `KepalaKeamananTest` diperbarui
- [x] Uji memakai kunci uji resmi Cloudflare dan tiruan HTTP, tanpa panggilan jaringan
- [x] Panduan produksi (diubah langsung sesuai K101, PDF dibuat ulang): memasang kunci rahasia dari dasbor Cloudflare, daftar nama host, cara mematikan sementara; tanpa langkah `wrangler`

## Selesai bila

Uji lulus, `composer uji` lulus lokal dan di CI, dan tidak ada kunci rahasia di diff.

## Catatan

K122. Kunci rahasia tidak pernah masuk git, chat, log, atau diff. Dikerjakan setelah F7-26 di sub-sesi yang sama.

Hasil:

- `Config\Turnstile` (`turnstile.kunciSitus`, `turnstile.kunciRahasia`, `turnstile.namaHost`; nama host kosong = host dari `app.baseURL`) dan `App\Libraries\Turnstile` (satu POST `CURLRequest` ke siteverify, batas waktu 10 detik). Aksi: `kirim_pendaftaran`, `cek_pendaftaran`, `masuk_admin`.
- Wizard: widget dipasang eksplisit saat langkah 5 tampil (`?render=explicit`), token dikirim di badan JSON hanya untuk `simpan` (bukan `periksa`), dan `turnstile.reset(widgetId)` dipanggil setelah setiap kiriman yang tidak berhasil sehingga kirim ulang (termasuk B2) memakai token baru.
- Cek Pendaftaran (formulir GET): token ikut di alamat; setelah lolos, pengunjung tidak diperiksa lagi selama 30 menit (sesi), supaya alamat `?kode=` setelah unggah bukti bayar dan muat ulang hasil tetap terbuka.
- Masuk admin: token diperiksa setelah pembatas laju dan sebelum kata sandi.
- CSP: `script-src`, `script-src-elem`, dan `frame-src` memuat `https://challenges.cloudflare.com`. CSP aplikasi sendiri masih mati (`App::$CSPEnabled = false`), jadi perubahan ini baru berpengaruh bila CSP dinyalakan.
- Diperiksa di peramban dengan kunci uji resmi Cloudflare: widget di Masuk Admin memberi token, server memanggil siteverify sungguhan dan mendapat `success: true`, lalu menolak karena kunci uji tidak mengembalikan `action` (sesuai rancangan; kunci produksi mengembalikannya).

