<?php

namespace Tests\Support;

use App\Libraries\SesiAdmin;

/**
 * Tiruan `SesiAdmin` untuk uji halaman: setiap akun dianggap punya kata
 * sandi dengan sidik `SIDIK`, tanpa membaca basis data. Dipasang otomatis
 * oleh `SesiAdminUji`; `UjiHalaman::SESI_ADMIN` memakai sidik yang sama.
 */
final class SesiAdminTiruan extends SesiAdmin
{
    public const SIDIK = 'sidik-uji-tetap';

    public function sidikTersimpan(int $idAdmin): ?string
    {
        return $idAdmin > 0 ? self::SIDIK : null;
    }
}
