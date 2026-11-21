<?php

namespace App\Libraries\Hasil;

/**
 * Pengaturan metode klasemen juara umum (F9-11, K140 butir 7a): baca dan simpan
 * `Klasemen.metode` dan `Klasemen.poin`, serta periksa isian Pengaturan > Hasil.
 *
 *     MetodeKlasemen::metode(); // 'medali' atau 'poin'
 *     MetodeKlasemen::poin();   // [7, 5, 4, 3, 2, 1, 0, 0]
 *     $galat = MetodeKlasemen::periksa('poin', ['10', '8', '6', '5', '4', '3', '2', '1']); // [] = sah
 */
final class MetodeKlasemen
{
    /**
     * Poin satu peringkat paling besar.
     */
    public const POIN_MAKSIMAL = 999;

    public static function metode(): string
    {
        $metode = (string) setting('Klasemen.metode');

        return array_key_exists($metode, Klasemen::METODE) ? $metode : Klasemen::MEDALI;
    }

    /**
     * Poin peringkat 1–8 tersimpan; bawaan bila tersimpan rusak.
     *
     * @return list<int>
     */
    public static function poin(): array
    {
        return self::urai((string) setting('Klasemen.poin')) ?? Klasemen::POIN_BAWAAN;
    }

    /**
     * Periksa isian formulir. Kunci galat: `metode`, `poin`, atau `poin_<n>`
     * (peringkat n, mulai 1; sama dengan id kotak isian).
     *
     * @param array<int|string, mixed> $poin isian poin peringkat 1–8
     *
     * @return array<string, string>
     */
    public static function periksa(string $metode, array $poin): array
    {
        $galat = [];

        if (! array_key_exists($metode, Klasemen::METODE)) {
            $galat['metode'] = 'Pilih metode klasemen: Medali atau Poin per peringkat.';
        }

        $poin = array_values($poin);

        if (count($poin) !== Klasemen::PERINGKAT_POIN) {
            $galat['poin'] = 'Isi poin untuk peringkat 1 sampai ' . Klasemen::PERINGKAT_POIN . '.';

            return $galat;
        }

        foreach ($poin as $i => $nilai) {
            $teks = is_scalar($nilai) ? trim((string) $nilai) : '';

            if (preg_match('/^\d{1,3}$/', $teks) !== 1) {
                $galat['poin_' . ($i + 1)] = 'Poin peringkat ' . ($i + 1) . ' harus angka bulat 0 sampai ' . self::POIN_MAKSIMAL . '. Tulis 0 bila peringkat ini tidak diberi poin.';
            }
        }

        if ($galat !== []) {
            return $galat;
        }

        $angka = array_map('intval', $poin);

        for ($i = 1; $i < count($angka); $i++) {
            if ($angka[$i] > $angka[$i - 1]) {
                $galat['poin_' . ($i + 1)] = 'Poin peringkat ' . ($i + 1) . ' tidak boleh lebih besar dari poin peringkat ' . $i . '.';

                return $galat;
            }
        }

        if ($angka[0] === 0) {
            $galat['poin_1'] = 'Poin peringkat 1 harus lebih dari 0.';
        }

        return $galat;
    }

    /**
     * Simpan isian yang sudah lolos `periksa()`.
     *
     * @param array<int|string, mixed> $poin
     */
    public static function simpan(string $metode, array $poin): void
    {
        setting('Klasemen.metode', $metode);
        setting('Klasemen.poin', implode(',', array_map(static fn (mixed $p): int => (int) $p, array_values($poin))));
    }

    /**
     * "7,5,4,3,2,1,0,0" → [7, 5, 4, 3, 2, 1, 0, 0]; `null` bila bukan 8 angka.
     *
     * @return list<int>|null
     */
    public static function urai(string $teks): ?array
    {
        $bagian = array_map('trim', explode(',', $teks));

        if (count($bagian) !== Klasemen::PERINGKAT_POIN) {
            return null;
        }

        foreach ($bagian as $b) {
            if (preg_match('/^\d{1,3}$/', $b) !== 1) {
                return null;
            }
        }

        return array_map('intval', $bagian);
    }
}
