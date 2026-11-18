<?php

namespace App\Libraries\Hasil;

/**
 * Peringkat otomatis satu babak (F8-04, K124 butir 2).
 *
 * - Hanya peserta yang punya prestasi dan tanpa keterangan DNS/DNF/DQ yang
 *   mendapat peringkat.
 * - Urutan menurut jenis hasil: waktu makin kecil makin baik, jarak dan poin
 *   makin besar makin baik.
 * - Prestasi sama = peringkat sama, dan peringkat berikutnya dilompati
 *   (1, 2, 2, 4), seperti hasil lomba atletik.
 *
 *     Peringkat::hitung([
 *         'a1' => ['prestasi' => 845, 'keterangan' => null],
 *         'a2' => ['prestasi' => 832, 'keterangan' => null],
 *         'a3' => ['prestasi' => null, 'keterangan' => 'DNS'],
 *     ], 'waktu');
 *     // ['a1' => 2, 'a2' => 1, 'a3' => null]
 *
 * Kelas murni tanpa basis data; punya uji unit.
 */
final class Peringkat
{
    /**
     * @var array<string, string> kode => arti
     */
    public const KETERANGAN = [
        'DNS' => 'tidak start',
        'DNF' => 'tidak finis',
        'DQ'  => 'diskualifikasi',
    ];

    /**
     * @param array<int|string, array{prestasi: int|null, keterangan: string|null}> $baris
     *
     * @return array<int|string, int|null> kunci => peringkat (null bila tanpa peringkat), urutan kunci sama dengan masukan
     */
    public static function hitung(array $baris, string $jenis): array
    {
        $hasil = array_fill_keys(array_keys($baris), null);
        $nilai = [];

        foreach ($baris as $kunci => $b) {
            if ($b['prestasi'] !== null && ($b['keterangan'] ?? '') === '') {
                $nilai[$kunci] = (int) $b['prestasi'];
            }
        }

        JenisHasil::makinKecilMakinBaik($jenis) ? asort($nilai) : arsort($nilai);

        $ke       = 0;
        $sebelum  = null;
        $tingkat  = 0;

        foreach ($nilai as $kunci => $prestasi) {
            $ke++;

            if ($prestasi !== $sebelum) {
                $tingkat = $ke;
                $sebelum = $prestasi;
            }

            $hasil[$kunci] = $tingkat;
        }

        return $hasil;
    }

    /**
     * Urutan tampil: yang berperingkat (kecil dulu), lalu yang belum diisi,
     * lalu DNF, DQ, DNS. Urutan dalam kelompok yang sama dipertahankan.
     *
     * @template T of array{peringkat: int|null, prestasi: int|null, keterangan: string|null}
     *
     * @param list<T> $baris
     *
     * @return list<T>
     */
    public static function urutkan(array $baris): array
    {
        $kelompok = static function (array $b): array {
            $keterangan = (string) ($b['keterangan'] ?? '');

            return match (true) {
                $b['peringkat'] !== null => [0, (int) $b['peringkat']],
                $keterangan === ''       => [1, 0],
                $keterangan === 'DNF'    => [2, 0],
                $keterangan === 'DQ'     => [3, 0],
                default                  => [4, 0],
            };
        };

        $urut = [];

        foreach (array_values($baris) as $i => $b) {
            $urut[] = [...$kelompok($b), $i, $b];
        }

        usort($urut, static fn (array $x, array $y): int => [$x[0], $x[1], $x[2]] <=> [$y[0], $y[1], $y[2]]);

        return array_map(static fn (array $u): array => $u[3], $urut);
    }
}
