<?php

namespace Tests\Support;

use App\Libraries\SesiAdmin;
use App\Models\AdminModel;

/**
 * Tiruan `SesiAdmin` untuk uji halaman: setiap akun dianggap punya kata
 * sandi dengan sidik `SIDIK`, tanpa membaca basis data. Dipasang otomatis
 * oleh `SesiAdminUji`; `UjiHalaman::SESI_ADMIN` memakai sidik yang sama.
 *
 * Peran akun mengikuti `admin_peran` di sesi uji (bawaan admin), jadi uji
 * akun juri cukup memakai `UjiHalaman::SESI_JURI` (F11-16).
 */
final class SesiAdminTiruan extends SesiAdmin
{
    public const SIDIK = 'sidik-uji-tetap';

    public function akunTersimpan(int $idAdmin): ?array
    {
        if ($idAdmin < 1) {
            return null;
        }

        return [
            'sidik' => self::SIDIK,
            'peran' => session(SesiAdmin::KUNCI_PERAN) === AdminModel::PERAN_JURI ? AdminModel::PERAN_JURI : AdminModel::PERAN_ADMIN,
        ];
    }
}
