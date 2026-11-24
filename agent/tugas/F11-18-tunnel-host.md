# F11-18 Tunnel Cloudflare di host VPS

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 24 November 2026 |

## Tujuan

Staging dan produksi MilkLife di VPS memakai cloudflared yang sudah berjalan di host untuk aplikasi SPMB, satu tunnel untuk semuanya (K184).

## Hasil

- [x] `docker/compose.yaml`: app membuka port hanya di `127.0.0.1:${PORT_LOKAL}`; cloudflared hanya menyala dengan profil `tunnel`, dan `TUNNEL_TOKEN` tidak lagi wajib.
- [x] `docker/.env.example`: `PORT_LOKAL`, `COMPOSE_PROFILES`, dan `TUNNEL_TOKEN` dengan penjelasan dua cara.
- [x] Panduan pemasangan produksi revisi 14: bagian 6 untuk tunnel di host (diatur dari dasbor atau dari `config.yml`) dan "Tunnel sebagai container"; bagian 8 mematikan cloudflared Laragon sebelum cadangan dan memindahkan nama alamat ke tunnel di host.
- [x] Rancangan revisi 41, K184, ATURAN, KONTEKS, PETA-KODE, README, `.env.example`, `.env.staging.example`, dan komentar `App::$proxyIPs`.
- [x] CI "Image Docker" memeriksa bahwa tanpa profil hanya ada app dan db, dan dengan profil `tunnel` ada cloudflared.

## Berkas dan uji

`docker/compose.yaml`, `docker/.env.example`, `.github/workflows/uji.yml`, dokumen di atas. Uji `vendor/bin/phpunit --filter 'DokumenAgen|EnvContoh|EnvProduksi|HttpsTerowongan'`.

## Selesai bila

Di container: permintaan ke `127.0.0.1:<PORT_LOKAL>` tanpa `X-Forwarded-Proto: https` dialihkan ke `https://` (307), dan dengan kepala itu 200. Artinya gateway jaringan compose dipercaya sebagai proxy. Job CI hijau.

## Catatan

- Dengan proxy userland Docker maupun NAT, permintaan dari `127.0.0.1` host tiba di container dari alamat gateway jaringan compose (`SUBNET` .1), jadi tidak ada alamat tambahan yang perlu dipercaya.
- Pemasangan di VPS dikerjakan pemilik mengikuti panduan; langkah yang disesuaikan dengan server spmb-prod ada di Claude Doc "Pemasangan MilkLife di VPS spmb-prod".
