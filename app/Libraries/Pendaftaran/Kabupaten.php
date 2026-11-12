<?php

namespace App\Libraries\Pendaftaran;

/**
 * Lima kabupaten peserta (K01). Satu-satunya tempat daftarnya ditulis.
 *
 *     Kabupaten::semua();               // ['Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara']
 *     Kabupaten::baku('kab. kudus');    // 'Kudus'
 *     Kabupaten::baku('Kabupaten Pati'); // 'Pati'
 *     Kabupaten::baku('Demak');         // null (bukan peserta)
 */
final class Kabupaten
{
    private const DAFTAR = ['Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara'];

    /**
     * @return list<string>
     */
    public static function semua(): array
    {
        return self::DAFTAR;
    }

    /**
     * Nama baku kabupaten dari isian atau data referensi, atau `null` bila
     * bukan salah satu dari lima kabupaten. Awalan "Kab.", "Kabupaten", atau
     * "Kota" dibuang; huruf besar-kecil diabaikan.
     */
    public static function baku(mixed $isian): ?string
    {
        if (! is_string($isian)) {
            return null;
        }

        $nama = trim((string) preg_replace('/^(kabupaten\b|kab\.|kab\b|kota\b)\s*/i', '', trim($isian)));

        foreach (self::DAFTAR as $kabupaten) {
            if (strcasecmp($kabupaten, $nama) === 0) {
                return $kabupaten;
            }
        }

        return null;
    }
}
