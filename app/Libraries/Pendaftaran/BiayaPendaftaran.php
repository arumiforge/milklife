<?php

namespace App\Libraries\Pendaftaran;

/**
 * Biaya pendaftaran (rancangan 6.7), bilangan bulat rupiah.
 *
 * - Individu: biaya kelompok umur atlet untuk setiap nomor individu yang
 *   diikuti; dua nomor berarti dua kali biaya.
 * - Beregu: biaya per tim dari nomor lomba; tim susulan (sekolah sudah punya
 *   tim di nomor itu) tanpa biaya (K07).
 *
 *     $biaya = BiayaPendaftaran::hitung(
 *         individu: [150000, 150000, 200000],   // satu angka per atlet per nomor individu
 *         beregu: [100000, 0],                  // satu angka per tim
 *     );
 *     // ['subtotal_individu' => 500000, 'subtotal_beregu' => 100000, 'total' => 600000]
 */
final class BiayaPendaftaran
{
    /**
     * Biaya satu tim: 0 untuk susulan, selain itu biaya beregu nomor lomba.
     */
    public static function tim(int $biayaBeregu, bool $susulan): int
    {
        return $susulan ? 0 : $biayaBeregu;
    }

    /**
     * @param list<int> $individu Biaya tiap atlet per nomor individu.
     * @param list<int> $beregu   Biaya tiap tim.
     *
     * @return array{subtotal_individu: int, subtotal_beregu: int, total: int}
     */
    public static function hitung(array $individu, array $beregu): array
    {
        $subtotalIndividu = array_sum(array_map('intval', $individu));
        $subtotalBeregu   = array_sum(array_map('intval', $beregu));

        return [
            'subtotal_individu' => $subtotalIndividu,
            'subtotal_beregu'   => $subtotalBeregu,
            'total'             => $subtotalIndividu + $subtotalBeregu,
        ];
    }
}
