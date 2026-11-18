<?php

namespace App\Libraries\Hasil;

/**
 * Penguraian dan penulisan prestasi (F8-04, asumsi 6 rencana Tahap 8).
 *
 * Disimpan sebagai bilangan bulat:
 * - waktu dalam perseratus detik: `1:05,32` → 6532, `8,45` → 845, `12,3` → 1230;
 * - jarak dalam sentimeter: `3,45` → 345, `3,5` → 350, `15` → 1500;
 * - poin apa adanya: `1250` → 1250.
 *
 * Koma atau titik boleh dipakai sebagai pemisah desimal; spasi diabaikan,
 * begitu pula akhiran `m` pada jarak. Pesan galat menyebut contoh yang benar.
 *
 *     Prestasi::urai('1:05,32', 'waktu'); // ['nilai' => 6532, 'galat' => null]
 *     Prestasi::tulis(6532, 'waktu');     // '1:05,32'
 *
 * Kelas murni tanpa basis data; punya uji unit.
 */
final class Prestasi
{
    /**
     * Batas atas supaya angka salah ketik (misalnya 120000) tidak tersimpan:
     * waktu 99:59,99, jarak 999,99 m, poin 99999.
     */
    public const MAKSIMAL = [
        JenisHasil::WAKTU => 599999,
        JenisHasil::JARAK => 99999,
        JenisHasil::POIN  => 99999,
    ];

    /**
     * Contoh format benar untuk bantuan isian dan pesan galat.
     *
     * @var array<string, string>
     */
    public const CONTOH = [
        JenisHasil::WAKTU => 'menit:detik,perseratus seperti 1:05,32 atau detik,perseratus seperti 8,45',
        JenisHasil::JARAK => 'meter,sentimeter seperti 3,45',
        JenisHasil::POIN  => 'angka bulat seperti 1250',
    ];

    /**
     * Satuan untuk judul kolom.
     *
     * @var array<string, string>
     */
    public const JUDUL = [
        JenisHasil::WAKTU => 'Waktu',
        JenisHasil::JARAK => 'Jarak (m)',
        JenisHasil::POIN  => 'Poin',
    ];

    /**
     * Urai teks prestasi. Teks kosong menghasilkan nilai `null` tanpa galat.
     *
     * @return array{nilai: int|null, galat: string|null}
     */
    public static function urai(string $teks, string $jenis): array
    {
        $teks = (string) preg_replace('/\s+/u', '', $teks);

        if ($teks === '') {
            return ['nilai' => null, 'galat' => null];
        }

        $nilai = match ($jenis) {
            JenisHasil::WAKTU => self::uraiWaktu($teks),
            JenisHasil::JARAK => self::uraiJarak($teks),
            JenisHasil::POIN  => preg_match('/^\d{1,5}$/D', $teks) === 1 ? (int) $teks : null,
            default           => null,
        };

        if ($nilai === null || $nilai > (self::MAKSIMAL[$jenis] ?? 0) || ($jenis !== JenisHasil::POIN && $nilai < 1)) {
            $label = JenisHasil::LABEL[$jenis] ?? 'Prestasi';

            return ['nilai' => null, 'galat' => "{$label} \"{$teks}\" belum benar. Tulis " . (self::CONTOH[$jenis] ?? 'angka') . '.'];
        }

        return ['nilai' => $nilai, 'galat' => null];
    }

    /**
     * Tulis prestasi untuk tampilan: `1:05,32`, `8,45`, `3,45`, `1250`.
     */
    public static function tulis(?int $nilai, string $jenis): string
    {
        if ($nilai === null) {
            return '';
        }

        if ($jenis === JenisHasil::WAKTU) {
            $menit  = intdiv($nilai, 6000);
            $detik  = intdiv($nilai % 6000, 100);
            $ratus  = $nilai % 100;

            return $menit > 0
                ? sprintf('%d:%02d,%02d', $menit, $detik, $ratus)
                : sprintf('%d,%02d', $detik, $ratus);
        }

        if ($jenis === JenisHasil::JARAK) {
            return sprintf('%d,%02d', intdiv($nilai, 100), $nilai % 100);
        }

        return (string) $nilai;
    }

    private static function uraiWaktu(string $teks): ?int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})(?:[,.](\d{1,2}))?$/D', $teks, $c) === 1) {
            if ((int) $c[2] > 59) {
                return null;
            }

            return ((int) $c[1] * 60 + (int) $c[2]) * 100 + self::perseratus($c[3] ?? '');
        }

        if (preg_match('/^(\d{1,4})(?:[,.](\d{1,2}))?$/D', $teks, $c) === 1) {
            return (int) $c[1] * 100 + self::perseratus($c[2] ?? '');
        }

        return null;
    }

    private static function uraiJarak(string $teks): ?int
    {
        $teks = (string) preg_replace('/m$/iu', '', $teks);

        if (preg_match('/^(\d{1,3})(?:[,.](\d{1,2}))?$/D', $teks, $c) === 1) {
            return (int) $c[1] * 100 + self::perseratus($c[2] ?? '');
        }

        return null;
    }

    /**
     * Angka di belakang koma menjadi perseratus: "3" → 30, "32" → 32, "" → 0.
     */
    private static function perseratus(string $angka): int
    {
        return $angka === '' ? 0 : (int) str_pad($angka, 2, '0');
    }
}
