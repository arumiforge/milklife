<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan khusus aplikasi ini.
 *
 * Nilai dibaca dari `.env` dengan awalan `aplikasi.`, misalnya
 * `aplikasi.minify = true`.
 */
class Aplikasi extends BaseConfig
{
    /**
     * true = halaman memakai berkas CSS/JS versi kecil (`.min`) bila ada,
     * dan HTML diperkecil saat dikirim (filter `perkecilhtml`).
     * Berkas kecil dibuat dengan `php spark aset:minify`.
     */
    public bool $minify = false;
}
