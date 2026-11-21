<?php

namespace App\Libraries\Hasil;

/**
 * Percobaan nomor lompat/lempar (F9-09, K140 butir 7c). Tanpa basis data; ada uji unit.
 *
 * - Banyak percobaan diatur per nomor (`nomor_lomba.jumlah_percobaan`,
 *   bawaan 3, paling banyak 6).
 * - Tiap percobaan berisi jarak (sentimeter), `X` (gagal), `-` (lewat), atau
 *   kosong (belum diisi). Prestasi = jarak sah terbaik.
 * - Pemecah seri: jarak sah diurutkan dari yang terjauh; prestasi sama
 *   dipisah dengan prestasi terbaik kedua, lalu ketiga, dan seterusnya
 *   (`Peringkat::pemecah()`).
 *
 * Disimpan di `hasil.percobaan` sebagai JSON, misalnya `[345,"X",350]`.
 *
 *     Percobaan::urai(['3,45', 'x', '3,50'], 3);
 *     // ['percobaan' => [345, 'X', 350], 'galat' => null]
 *     Percobaan::terbaik([345, 'X', 350]);     // 350
 *     Percobaan::sah([345, 'X', 350]);         // [350, 345]
 *     Percobaan::teks([345, 'X', null]);       // ['3,45', 'X', '']
 */
final class Percobaan
{
    public const BAWAAN   = 3;
    public const MAKSIMAL = 6;

    public const GAGAL = 'X';
    public const LEWAT = '-';

    /**
     * Banyak percobaan yang berlaku dari isi kolom (kosong atau di luar batas = bawaan).
     */
    public static function jumlah(mixed $nilai): int
    {
        return is_numeric($nilai) && (int) $nilai >= 1 && (int) $nilai <= self::MAKSIMAL ? (int) $nilai : self::BAWAAN;
    }

    /**
     * Urai isian percobaan. Isian di luar `$jumlah` diabaikan. Semua kosong =
     * `percobaan` null (prestasi tidak dicatat).
     *
     * @param array<array-key, mixed> $isian
     *
     * @return array{percobaan: list<int|string|null>|null, galat: string|null}
     */
    public static function urai(array $isian, int $jumlah): array
    {
        $isian     = array_values($isian);
        $percobaan = [];
        $ada       = false;

        for ($i = 0; $i < $jumlah; $i++) {
            $teks = is_scalar($isian[$i] ?? null) ? trim(str_replace(' ', '', (string) $isian[$i])) : '';

            if ($teks === '') {
                $percobaan[] = null;

                continue;
            }

            $ada = true;

            if (in_array(mb_strtoupper($teks), ['X', '×'], true)) {
                $percobaan[] = self::GAGAL;

                continue;
            }

            if (in_array($teks, ['-', '–', '—'], true) || mb_strtoupper($teks) === 'P') {
                $percobaan[] = self::LEWAT;

                continue;
            }

            $nilai = Prestasi::urai($teks, JenisHasil::JARAK);

            if ($nilai['nilai'] === null) {
                return ['percobaan' => null, 'galat' => 'Percobaan ' . ($i + 1) . ': "' . $teks . '" belum benar. Tulis meter dengan koma, misalnya 3,45, atau X bila gagal dan - bila dilewati.'];
            }

            $percobaan[] = $nilai['nilai'];
        }

        return ['percobaan' => $ada ? $percobaan : null, 'galat' => null];
    }

    /**
     * Jarak sah, urut dari yang terjauh.
     *
     * @param list<int|string|null> $percobaan
     *
     * @return list<int>
     */
    public static function sah(array $percobaan): array
    {
        $sah = array_values(array_filter($percobaan, 'is_int'));
        rsort($sah);

        return $sah;
    }

    /**
     * Jarak sah terjauh, atau `null` bila tidak ada lompatan/lemparan sah.
     *
     * @param list<int|string|null> $percobaan
     */
    public static function terbaik(array $percobaan): ?int
    {
        return self::sah($percobaan)[0] ?? null;
    }

    /**
     * Teks tiap percobaan untuk isian dan tampilan, sepanjang `$jumlah`
     * (atau sepanjang isinya bila `$jumlah` null).
     *
     * @param list<int|string|null>|null $percobaan
     *
     * @return list<string>
     */
    public static function teks(?array $percobaan, ?int $jumlah = null): array
    {
        $percobaan ??= [];
        $jumlah    ??= count($percobaan);
        $teks        = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $p      = $percobaan[$i] ?? null;
            $teks[] = is_int($p) ? Prestasi::tulis($p, JenisHasil::JARAK) : (string) $p;
        }

        return $teks;
    }

    /**
     * Baca kolom `hasil.percobaan`; rusak atau kosong = `null`.
     *
     * @return list<int|string|null>|null
     */
    public static function baca(mixed $json): ?array
    {
        if (! is_string($json) || $json === '') {
            return null;
        }

        $data = json_decode($json, true);

        if (! is_array($data) || $data === [] || count($data) > self::MAKSIMAL || ! array_is_list($data)) {
            return null;
        }

        $percobaan = [];

        foreach ($data as $p) {
            if (is_int($p) && $p > 0 && $p < Prestasi::BATAS[JenisHasil::JARAK]) {
                $percobaan[] = $p;
            } elseif ($p === self::GAGAL || $p === self::LEWAT || $p === null) {
                $percobaan[] = $p;
            } else {
                return null;
            }
        }

        return $percobaan;
    }

    /**
     * Isi kolom `hasil.percobaan`; `null` bila tidak ada percobaan.
     *
     * @param list<int|string|null>|null $percobaan
     */
    public static function tulis(?array $percobaan): ?string
    {
        return $percobaan === null || array_filter($percobaan, static fn ($p): bool => $p !== null) === [] ? null : (string) json_encode(array_values($percobaan));
    }
}
