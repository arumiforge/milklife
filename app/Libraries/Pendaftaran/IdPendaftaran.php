<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\Jenjang;
use InvalidArgumentException;

/**
 * ID pendaftaran (rancangan 6.8): `MAC` + seri + kode jenjang + nomor urut 5
 * digit + 2 digit tahun.
 *
 *     IdPendaftaran::buat(seri: 2, jenjang: 'A', urut: 1, tahun: 2026); // 'MAC2A0000126'
 *
 * Nomor urut diambil dari `pendaftaran.id`, berlaku untuk seluruh pendaftaran
 * (K32). Bila melewati 99999, nomor urut menjadi lebih panjang dari 5 digit.
 */
final class IdPendaftaran
{
    public const AWALAN = 'MAC';

    public static function buat(int $seri, string $jenjang, int $urut, int $tahun): string
    {
        if ($seri < 0 || $urut < 1 || $tahun < 0 || ! Jenjang::sah($jenjang)) {
            throw new InvalidArgumentException('Bahan ID pendaftaran tidak sah.');
        }

        return self::AWALAN . $seri . $jenjang . str_pad((string) $urut, 5, '0', STR_PAD_LEFT)
            . str_pad((string) ($tahun % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Apakah teks berbentuk ID pendaftaran (huruf besar-kecil diabaikan).
     */
    public static function bentukSah(mixed $teks): bool
    {
        return is_string($teks) && preg_match('/^MAC\d+[ABC]\d{7,}$/iD', trim($teks)) === 1;
    }
}
