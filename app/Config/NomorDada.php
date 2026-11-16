<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Catatan pengurutan ulang nomor dada per sekolah (K111,
 * `App\Libraries\Panel\UrutkanNomorDada`).
 *
 * Diisi oleh aplikasi, bukan formulir pengaturan:
 *
 *     setting('NomorDada.diurutkan_pada');   // '2026-12-05 10:00:00' atau null
 */
class NomorDada extends BaseConfig
{
    /**
     * Waktu terakhir nomor dada diurutkan ulang, `Y-m-d H:i:s` WIB; `null` bila belum pernah.
     */
    public ?string $diurutkan_pada = null;

    /**
     * Nama admin yang terakhir mengurutkan ulang.
     */
    public ?string $diurutkan_oleh = null;
}
