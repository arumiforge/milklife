<?php

namespace App\Libraries\Hasil;

/**
 * Peringkat satu babak dari prestasi (F8-04, K124). Tanpa basis data; ada uji unit.
 *
 * - Diurutkan menurut jenis hasil: waktu makin kecil makin baik, jarak dan
 *   poin makin besar makin baik.
 * - Prestasi sama = peringkat sama, peringkat berikutnya dilewati
 *   (1, 2, 2, 4).
 * - Peserta tanpa prestasi atau berketerangan DNS, DNF, atau DQ tidak diberi
 *   peringkat.
 * - Peringkat yang dikoreksi admin (`manual`) tidak dihitung ulang; peserta
 *   lain tetap diurutkan dari prestasinya.
 *
 *     Peringkat::hitung([
 *         10 => ['prestasi' => 1234, 'keterangan' => null],
 *         11 => ['prestasi' => 1210, 'keterangan' => null],
 *         12 => ['prestasi' => null, 'keterangan' => 'DNS'],
 *     ], 'waktu');
 *     // [11 => 1, 10 => 2, 12 => null]
 */
final class Peringkat
{
    /**
     * Keterangan yang menghapus peringkat.
     *
     * @var array<string, string> kode => arti
     */
    public const KETERANGAN = [
        'DNS' => 'Tidak start',
        'DNF' => 'Tidak finis',
        'DQ'  => 'Diskualifikasi',
    ];

    /**
     * @param array<int|string, array{prestasi: int|null, keterangan: string|null, peringkat?: int|null, manual?: bool}> $peserta kunci => data
     *
     * @return array<int|string, int|null> kunci => peringkat, urut dari peringkat terbaik; tanpa peringkat di akhir
     */
    public static function hitung(array $peserta, string $jenis): array
    {
        $dinilai = [];
        $hasil   = [];

        foreach ($peserta as $kunci => $p) {
            if (! empty($p['manual'])) {
                $hasil[$kunci] = $p['peringkat'] ?? null;

                continue;
            }

            if ($p['prestasi'] !== null && ! isset(self::KETERANGAN[(string) $p['keterangan']])) {
                $dinilai[$kunci] = (int) $p['prestasi'];
            }
        }

        JenisHasil::besarLebihBaik($jenis) ? arsort($dinilai) : asort($dinilai);

        $urut    = 0;
        $sebelum = null;
        $posisi  = 0;

        foreach ($dinilai as $kunci => $nilai) {
            $urut++;

            if ($nilai !== $sebelum) {
                $posisi  = $urut;
                $sebelum = $nilai;
            }

            $hasil[$kunci] = $posisi;
        }

        foreach ($peserta as $kunci => $p) {
            $hasil[$kunci] ??= null;
        }

        uasort($hasil, static fn (?int $a, ?int $b): int => ($a ?? PHP_INT_MAX) <=> ($b ?? PHP_INT_MAX));

        return $hasil;
    }

    /**
     * Keterangan yang sah, atau `null` untuk kosong dan isian lain.
     */
    public static function keterangan(mixed $nilai): ?string
    {
        $nilai = is_string($nilai) ? strtoupper(trim($nilai)) : '';

        return isset(self::KETERANGAN[$nilai]) ? $nilai : null;
    }
}
