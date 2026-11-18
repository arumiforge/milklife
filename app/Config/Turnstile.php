<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cloudflare Turnstile (K122): pemeriksaan "bukan robot" di kirim
 * pendaftaran, Cek Pendaftaran, dan masuk admin.
 *
 * Diisi dari `.env`:
 *
 *     turnstile.kunciSitus   = 0x4AAAAAAGFuKUT0k64YcOX5   # publik, boleh di git
 *     turnstile.kunciRahasia =                           # rahasia, hanya di .env server
 *     turnstile.namaHost     = 'daftar.contoh.sch.id'    # dipisah koma
 *
 * **Bila salah satu kunci kosong, Turnstile mati**: halaman tidak memuat
 * pemeriksaan dan server tidak meminta token. Mengosongkan kunci rahasia
 * adalah cara mematikannya sementara (misalnya saat Cloudflare bermasalah).
 *
 * Kunci rahasia tidak pernah ditulis di git, log, atau halaman.
 */
class Turnstile extends BaseConfig
{
    /**
     * Kunci situs (site key) dari dasbor Cloudflare. Publik.
     */
    public string $kunciSitus = '';

    /**
     * Kunci rahasia (secret key) dari dasbor Cloudflare. Hanya di `.env` server.
     */
    public string $kunciRahasia = '';

    /**
     * Nama host yang diizinkan, dipisah koma (misalnya
     * `daftar.contoh.sch.id`). Kosong = nama host dari `app.baseURL`.
     * Produksi tanpa `localhost`.
     */
    public string $namaHost = '';

    /**
     * Alamat pemeriksaan token Cloudflare.
     */
    public string $alamatPeriksa = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Skrip widget di peramban.
     */
    public string $alamatSkrip = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    /**
     * Batas waktu pemeriksaan ke Cloudflare, dalam detik.
     */
    public int $batasWaktu = 10;
}
