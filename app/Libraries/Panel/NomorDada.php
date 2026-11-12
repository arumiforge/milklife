<?php

namespace App\Libraries\Panel;

use InvalidArgumentException;

/**
 * Aturan nomor dada (rancangan 6.9, K05, K11; rencana Tahap 6, "Nomor dada").
 *
 * Nomor dada 4 digit: 1 digit awalan + 3 digit urut `001`–`999`. Satu
 * kelompok umur boleh punya beberapa awalan yang dipakai berurutan; bila
 * awalan pertama penuh, nomor berlanjut ke awalan berikutnya. Bila semua
 * awalan sebuah kelompok umur tidak cukup untuk atlet yang butuh nomor,
 * tidak ada nomor yang dibagikan sama sekali.
 *
 * Aturan murni tanpa basis data. Penyimpanan, kunci baris, dan pembatalan
 * penandaan Lunas dikerjakan `StatusPembayaran`.
 *
 *     $awalan = [
 *         ['awalan' => '2', 'nomor_terakhir' => 998],
 *         ['awalan' => '6', 'nomor_terakhir' => 0],
 *     ];
 *
 *     NomorDada::bagikan($awalan, 3);
 *     // ['nomor' => ['2999', '6001', '6002'], 'nomor_terakhir' => ['2' => 999, '6' => 2]]
 *
 *     NomorDada::bagikan($awalan, 1000);   // null: awalan penuh
 *     NomorDada::sisa($awalan);            // 1000
 *
 * `nomor_terakhir` di hasil hanya memuat awalan yang berubah.
 */
final class NomorDada
{
    /**
     * Nomor urut paling besar dalam satu awalan.
     */
    public const URUT_MAKSIMAL = 999;

    /**
     * Bagikan `$jumlah` nomor dada dari awalan satu kelompok umur.
     *
     * @param list<array{awalan: int|string, nomor_terakhir: int|string|null}> $awalan Berurutan seperti dipakai
     *        (`urutan`, lalu `id`). `nomor_terakhir` adalah urut terakhir yang sudah dibagikan.
     *
     * @return array{nomor: list<string>, nomor_terakhir: array<string, int>}|null `null` bila awalan tidak cukup.
     *
     * @throws InvalidArgumentException Bila jumlah negatif atau awalan bukan satu angka 1–9.
     */
    public static function bagikan(array $awalan, int $jumlah): ?array
    {
        if ($jumlah < 0) {
            throw new InvalidArgumentException('Jumlah nomor dada tidak boleh negatif.');
        }

        $nomor    = [];
        $terakhir = [];

        foreach (self::rapikan($awalan) as [$kode, $urut]) {
            if (count($nomor) >= $jumlah) {
                break;
            }

            $awal = $urut;

            while (count($nomor) < $jumlah && $urut < self::URUT_MAKSIMAL) {
                $urut++;
                $nomor[] = self::format($kode, $urut);
            }

            if ($urut !== $awal) {
                $terakhir[$kode] = $urut;
            }
        }

        if (count($nomor) < $jumlah) {
            return null;
        }

        return ['nomor' => $nomor, 'nomor_terakhir' => $terakhir];
    }

    /**
     * Jumlah nomor dada yang masih bisa dibagikan dari awalan ini.
     *
     * @param list<array{awalan: int|string, nomor_terakhir: int|string|null}> $awalan
     */
    public static function sisa(array $awalan): int
    {
        $sisa = 0;

        foreach (self::rapikan($awalan) as [, $urut]) {
            $sisa += self::URUT_MAKSIMAL - $urut;
        }

        return $sisa;
    }

    /**
     * Nomor dada dari awalan dan urut: `format('2', 7)` menjadi `'2007'`.
     *
     * @throws InvalidArgumentException Bila awalan atau urut di luar rentang.
     */
    public static function format(int|string $awalan, int $urut): string
    {
        $awalan = self::awalanSah($awalan);

        if ($urut < 1 || $urut > self::URUT_MAKSIMAL) {
            throw new InvalidArgumentException('Nomor urut dada harus 1 sampai ' . self::URUT_MAKSIMAL . '.');
        }

        return $awalan . str_pad((string) $urut, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Awalan sebagai pasangan [kode, urut terakhir] dengan urut dibatasi 0–999.
     *
     * @param list<array{awalan: int|string, nomor_terakhir: int|string|null}> $awalan
     *
     * @return list<array{0: string, 1: int}>
     */
    private static function rapikan(array $awalan): array
    {
        $hasil = [];
        $ada   = [];

        foreach ($awalan as $satu) {
            $kode = self::awalanSah($satu['awalan'] ?? null);

            if (isset($ada[$kode])) {
                throw new InvalidArgumentException("Awalan nomor dada {$kode} tertulis lebih dari sekali.");
            }

            $ada[$kode] = true;
            $urut       = is_numeric($satu['nomor_terakhir'] ?? null) ? (int) $satu['nomor_terakhir'] : 0;
            $hasil[]    = [$kode, max(0, min(self::URUT_MAKSIMAL, $urut))];
        }

        return $hasil;
    }

    private static function awalanSah(mixed $awalan): string
    {
        $kode = is_int($awalan) ? (string) $awalan : $awalan;

        if (! is_string($kode) || preg_match('/^[1-9]$/D', $kode) !== 1) {
            throw new InvalidArgumentException('Awalan nomor dada harus satu angka 1 sampai 9.');
        }

        return $kode;
    }
}
