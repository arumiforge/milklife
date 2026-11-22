<?php

namespace App\Libraries\Hasil;

/**
 * Penguraian dan penulisan prestasi (F8-04, K124). Tanpa basis data; ada uji unit.
 *
 * Disimpan sebagai bilangan bulat:
 * - waktu dalam perseratus detik: `12,34` → 1234, `2:05,1` → 12510, `65` → 6500;
 * - jarak dalam sentimeter: `3,45` → 345, `12,5` → 1250, `4` → 400;
 * - poin apa adanya: `1250` → 1250.
 *
 * Koma atau titik boleh dipakai sebagai tanda desimal; satu atau dua angka
 * di belakangnya. Isian yang salah menghasilkan pesan yang menyebut contoh
 * yang benar.
 *
 *     Prestasi::urai('2:05,10', 'waktu');  // ['nilai' => 12510, 'galat' => null]
 *     Prestasi::urai('2.05', 'waktu');     // ['nilai' => 205, 'galat' => null]  (2,05 detik)
 *     Prestasi::urai('3 m', 'jarak');      // ['nilai' => null, 'galat' => 'Jarak … misalnya 3,45.']
 *     Prestasi::tulis(12510, 'waktu');     // '2:05,10'
 *     Prestasi::tulis(345, 'jarak');       // '3,45'
 */
final class Prestasi
{
    /**
     * Batas atas yang masuk akal: waktu di bawah 100 menit, jarak di bawah
     * 100 meter, poin di bawah 100.000.
     *
     * @var array<string, int>
     */
    public const BATAS = [
        JenisHasil::WAKTU => 600000,
        JenisHasil::JARAK => 10000,
        JenisHasil::POIN  => 100000,
    ];

    /**
     * @return array{nilai: int|null, galat: string|null} `nilai` null dan `galat` null = isian kosong
     */
    public static function urai(string $teks, string $jenis): array
    {
        $teks = trim(str_replace(' ', '', $teks));

        if ($teks === '') {
            return ['nilai' => null, 'galat' => null];
        }

        $nilai = match ($jenis) {
            JenisHasil::WAKTU => self::uraiWaktu($teks),
            JenisHasil::JARAK => self::uraiDesimal($teks),
            default           => preg_match('/^\d{1,6}$/D', $teks) === 1 ? (int) $teks : null,
        };

        $batas = self::BATAS[$jenis] ?? self::BATAS[JenisHasil::POIN];

        if ($nilai === null || $nilai < 1 || $nilai >= $batas) {
            return ['nilai' => null, 'galat' => self::pesanGalat($teks, $jenis)];
        }

        return ['nilai' => $nilai, 'galat' => null];
    }

    /**
     * Teks prestasi untuk ditampilkan dan dicetak; kosong bila belum diisi.
     */
    public static function tulis(?int $nilai, string $jenis): string
    {
        if ($nilai === null) {
            return '';
        }

        if ($jenis === JenisHasil::POIN || $jenis === JenisHasil::MULTI) {
            return (string) $nilai;
        }

        $sisa = sprintf('%02d', $nilai % 100);

        if ($jenis === JenisHasil::JARAK) {
            return intdiv($nilai, 100) . ',' . $sisa;
        }

        $detik = intdiv($nilai, 100);

        if ($detik < 60) {
            return $detik . ',' . $sisa;
        }

        return intdiv($detik, 60) . ':' . sprintf('%02d', $detik % 60) . ',' . $sisa;
    }

    /**
     * Urai waktu lari yang boleh memuat seperseribu detik (F9-09), misalnya
     * dari foto finis: `12,345` → `nilai` 1235 (dibulatkan ke atas ke
     * perseratus, seperti aturan World Athletics) dan `seperseribu` 12345.
     * Tanpa angka ketiga sama dengan `urai()` dan `seperseribu` null.
     *
     *     Prestasi::uraiWaktuRinci('12,341');   // ['nilai' => 1235, 'seperseribu' => 12341, 'galat' => null]
     *     Prestasi::uraiWaktuRinci('2:05,1');   // ['nilai' => 12510, 'seperseribu' => null, 'galat' => null]
     *
     * @return array{nilai: int|null, seperseribu: int|null, galat: string|null}
     */
    public static function uraiWaktuRinci(string $teks): array
    {
        $rapi = trim(str_replace(' ', '', $teks));

        if (preg_match('/^(?:(\d{1,2}):([0-5]\d)|(\d{1,4}))[,.](\d{3})$/D', $rapi, $m) === 1) {
            $detik = $m[3] !== '' ? (int) $m[3] : (int) $m[1] * 60 + (int) $m[2];
            $ribu  = $detik * 1000 + (int) $m[4];
            $nilai = intdiv($ribu + 9, 10);

            if ($ribu < 1 || $nilai >= self::BATAS[JenisHasil::WAKTU]) {
                return ['nilai' => null, 'seperseribu' => null, 'galat' => self::pesanGalat($rapi, JenisHasil::WAKTU)];
            }

            return ['nilai' => $nilai, 'seperseribu' => $ribu, 'galat' => null];
        }

        $hasil = self::urai($teks, JenisHasil::WAKTU);

        return [
            'nilai'       => $hasil['nilai'],
            'seperseribu' => null,
            'galat'       => $hasil['galat'] === null ? null : $hasil['galat'] . ' Seperseribu detik dari foto finis boleh ditulis tiga angka, misalnya 12,345.',
        ];
    }

    /**
     * Teks waktu dengan seperseribu detik: `12345` → `12,345`, `125123` → `2:05,123`.
     */
    public static function tulisSeperseribu(int $nilai): string
    {
        $detik = intdiv($nilai, 1000);
        $sisa  = sprintf('%03d', $nilai % 1000);

        if ($detik < 60) {
            return $detik . ',' . $sisa;
        }

        return intdiv($detik, 60) . ':' . sprintf('%02d', $detik % 60) . ',' . $sisa;
    }

    public static function pesanGalat(string $teks, string $jenis): string
    {
        $contoh = match ($jenis) {
            JenisHasil::WAKTU => 'Tulis detik dan perseratus detik, misalnya 12,34. Untuk lari jauh, tulis menit:detik,perseratus, misalnya 2:05,10.',
            JenisHasil::JARAK => 'Tulis meter dengan koma, misalnya 3,45 untuk 3 meter 45 sentimeter.',
            default           => 'Tulis poin akhir berupa angka bulat tanpa titik, misalnya 1250.',
        };

        return 'Prestasi "' . $teks . '" belum benar. ' . $contoh;
    }

    private static function uraiWaktu(string $teks): ?int
    {
        if (preg_match('/^(\d{1,2}):([0-5]\d)(?:[,.](\d{1,2}))?$/D', $teks, $m) === 1) {
            $detik = (int) $m[1] * 60 + (int) $m[2];
        } elseif (preg_match('/^(\d{1,4})(?:[,.](\d{1,2}))?$/D', $teks, $m) === 1) {
            $detik = (int) $m[1];
            $m[3]  = $m[2] ?? '';
        } else {
            return null;
        }

        return $detik * 100 + (($m[3] ?? '') === '' ? 0 : (int) str_pad($m[3], 2, '0'));
    }

    private static function uraiDesimal(string $teks): ?int
    {
        if (preg_match('/^(\d{1,3})(?:[,.](\d{1,2}))?$/D', $teks, $m) !== 1) {
            return null;
        }

        return (int) $m[1] * 100 + (($m[2] ?? '') === '' ? 0 : (int) str_pad($m[2], 2, '0'));
    }
}
