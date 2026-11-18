<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\Panel\UbahPendaftaran;
use App\Libraries\Pendaftaran\Regu;

/**
 * Pembaca nilai kiriman formulir Ubah pendaftaran (bagian dari
 * `UbahPendaftaran`, F7-30): nilai yang bentuknya salah dibaca sebagai
 * kosong, tidak pernah melempar.
 */
final class MasukanUbah
{
    /**
     * Regu ("nomor:regu") yang dicentang "Bebankan biaya regu" di kiriman.
     *
     * @param array<string, mixed> $kiriman
     *
     * @return list<string>
     */
    public static function reguBebankan(array $kiriman): array
    {
        return array_values(array_filter(self::daftar($kiriman[UbahPendaftaran::KUNCI_BEBANKAN] ?? null), static fn (mixed $k): bool => Regu::pecah($k) !== null));
    }

    /**
     * Pilihan regu atlet dari kiriman, dirapikan untuk ditampilkan ulang.
     *
     * @return array<int, array{regu: string, peran: string}>
     */
    public static function reguIsian(mixed $nilai): array
    {
        $hasil = [];

        foreach (is_array($nilai) ? $nilai : [] as $nomorId => $pilih) {
            if (self::id($nomorId) !== null && is_array($pilih)) {
                $hasil[(int) $nomorId] = ['regu' => self::teks($pilih['regu'] ?? null), 'peran' => self::teks($pilih['peran'] ?? null)];
            }
        }

        return $hasil;
    }

    public static function npsn(mixed $nilai): ?string
    {
        return is_string($nilai) && preg_match('/^\d{8}$/D', $nilai) === 1 ? $nilai : null;
    }

    public static function id(mixed $nilai): ?int
    {
        return (is_int($nilai) || (is_string($nilai) && ctype_digit($nilai))) && (int) $nilai > 0 ? (int) $nilai : null;
    }

    public static function teks(mixed $nilai): string
    {
        return is_string($nilai) || is_int($nilai) ? (string) $nilai : '';
    }

    /**
     * @return list<mixed>
     */
    public static function daftar(mixed $nilai): array
    {
        return is_array($nilai) ? array_values($nilai) : [];
    }
}
