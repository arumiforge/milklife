<?php

namespace App\Libraries;

/**
 * Kode dan label jenjang sekolah. Satu-satunya tempat keduanya ditulis.
 *
 * Kode mengikuti ID pendaftaran (rancangan 6.8): `A` = SD/MI, `B` = SMP/MTs,
 * `C` = SMA/SMK/MA. Kode yang sama disimpan di `kelompok_umur_jenjang.jenjang`
 * dan `pendaftaran.jenjang`.
 *
 *     Jenjang::semua();          // ['A' => 'SD/MI', 'B' => 'SMP/MTs', 'C' => 'SMA/SMK/MA']
 *     Jenjang::label('B');       // 'SMP/MTs'
 *     Jenjang::sah('D');         // false
 *     Jenjang::rapikan(['c', 'A', 'x', 'A']); // ['A', 'C']
 */
final class Jenjang
{
    public const SD  = 'A';
    public const SMP = 'B';
    public const SMA = 'C';

    /**
     * Kode => label, berurutan dari jenjang terendah.
     */
    private const DAFTAR = [
        self::SD  => 'SD/MI',
        self::SMP => 'SMP/MTs',
        self::SMA => 'SMA/SMK/MA',
    ];

    /**
     * Semua jenjang, kode => label, berurutan dari jenjang terendah.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        return self::DAFTAR;
    }

    /**
     * Semua kode jenjang, berurutan.
     *
     * @return list<string>
     */
    public static function kode(): array
    {
        return array_keys(self::DAFTAR);
    }

    /**
     * Apakah kode ini kode jenjang yang dikenal. Peka huruf besar-kecil.
     */
    public static function sah(mixed $kode): bool
    {
        return is_string($kode) && isset(self::DAFTAR[$kode]);
    }

    /**
     * Label jenjang, atau `null` bila kodenya tidak dikenal.
     */
    public static function label(mixed $kode): ?string
    {
        return self::sah($kode) ? self::DAFTAR[$kode] : null;
    }

    /**
     * Rapikan daftar kode dari isian (misalnya kotak centang): huruf
     * dijadikan besar, kode yang tidak dikenal dan kode ganda dibuang, hasil
     * diurutkan seperti `semua()`.
     *
     * @param iterable<mixed>|null $daftar
     *
     * @return list<string>
     */
    public static function rapikan(?iterable $daftar): array
    {
        $ada = [];

        foreach ($daftar ?? [] as $kode) {
            if (is_string($kode)) {
                $ada[strtoupper(trim($kode))] = true;
            }
        }

        return array_values(array_filter(self::kode(), static fn (string $kode): bool => isset($ada[$kode])));
    }

    /**
     * Label beberapa jenjang yang digabung, contoh `SD/MI, SMP/MTs`.
     * Kode yang tidak dikenal dilewati; daftar kosong menghasilkan `''`.
     *
     * @param iterable<mixed>|null $daftar
     */
    public static function gabungLabel(?iterable $daftar, string $pemisah = ', '): string
    {
        return implode($pemisah, array_map(static fn (string $kode): string => self::DAFTAR[$kode], self::rapikan($daftar)));
    }
}
