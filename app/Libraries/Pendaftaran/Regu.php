<?php

namespace App\Libraries\Pendaftaran;

/**
 * Regu pada nomor beregu (K105).
 *
 * Satu sekolah boleh punya beberapa regu di satu nomor beregu, berlabel huruf
 * A, B, C, dan seterusnya. Label yang boleh dipakai adalah huruf pertama
 * sebanyak `regu_maksimal` nomor itu (kosong = A sampai Z). Setiap anggota
 * regu berperan inti atau cadangan.
 *
 *     Regu::dibolehkan(3);          // ['A', 'B', 'C']
 *     Regu::kunci(40, 'B');         // '40:B'
 *     Regu::pecah('40:B');          // [40, 'B']
 *     Regu::nama("Kanga's Escape putra", 'B'); // "Kanga's Escape putra Regu B"
 */
final class Regu
{
    public const INTI     = 'inti';
    public const CADANGAN = 'cadangan';

    /**
     * @var array<string, string>
     */
    public const PERAN = [self::INTI => 'Inti', self::CADANGAN => 'Cadangan'];

    /**
     * Regu paling banyak bila nomor lomba tidak membatasi.
     */
    public const PALING_BANYAK = 26;

    /**
     * Label regu yang boleh dipakai sekolah baru di nomor dengan batas ini.
     *
     * @return list<string>
     */
    public static function dibolehkan(?int $maksimal): array
    {
        $jumlah = $maksimal === null || $maksimal < 1 ? self::PALING_BANYAK : min($maksimal, self::PALING_BANYAK);

        return array_map(static fn (int $i): string => chr(65 + $i), range(0, $jumlah - 1));
    }

    public static function sah(mixed $label): bool
    {
        return is_string($label) && preg_match('/^[A-Z]$/D', $label) === 1;
    }

    public static function peranSah(mixed $peran): bool
    {
        return is_string($peran) && isset(self::PERAN[$peran]);
    }

    public static function kunci(int $nomorId, string $regu): string
    {
        return $nomorId . ':' . $regu;
    }

    /**
     * @return array{0: int, 1: string}|null
     */
    public static function pecah(mixed $kunci): ?array
    {
        if (! is_string($kunci) || preg_match('/^([1-9]\d*):([A-Z])$/D', $kunci, $m) !== 1) {
            return null;
        }

        return [(int) $m[1], $m[2]];
    }

    /**
     * Nama regu untuk pesan dan tampilan, contoh "Kanga's Escape putra Regu A".
     */
    public static function nama(string $namaNomor, string $regu): string
    {
        return trim($namaNomor . ' Regu ' . $regu);
    }

    public static function labelPeran(?string $peran): string
    {
        return self::PERAN[(string) $peran] ?? '';
    }
}
