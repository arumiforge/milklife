<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use DateTimeImmutable;

/**
 * Pembaca nilai kiriman wizard (bagian dari `PemeriksaPendaftaran`, F7-30):
 * nilai yang bentuknya salah dibaca sebagai kosong, tidak pernah melempar.
 */
final class MasukanPeriksa
{
    public static function teks(mixed $nilai): string
    {
        return is_string($nilai) || is_int($nilai) ? (string) $nilai : '';
    }

    public static function rapikanSpasi(string $teks): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }

    /**
     * @return list<mixed>
     */
    public static function daftar(mixed $nilai): array
    {
        return is_array($nilai) ? array_values($nilai) : [];
    }

    public static function tanggal(string $teks): ?DateTimeImmutable
    {
        $teks = trim($teks);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $teks) !== 1) {
            return null;
        }

        $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', $teks);
        $galat   = DateTimeImmutable::getLastErrors();

        if ($tanggal === false || ($galat !== false && ($galat['warning_count'] > 0 || $galat['error_count'] > 0))) {
            return null;
        }

        return $tanggal;
    }
}
