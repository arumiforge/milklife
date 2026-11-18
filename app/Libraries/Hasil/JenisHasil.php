<?php

namespace App\Libraries\Hasil;

/**
 * Jenis hasil nomor lomba (F8-04, K124): menentukan cara prestasi ditulis
 * dan diurutkan.
 *
 * - `waktu`: lari, jalan cepat, estafet, dan lomba beregu berwaktu; makin
 *   kecil makin baik. Disimpan dalam perseratus detik.
 * - `jarak`: lompat, lempar, tolak, Frog Jump, Turbo Throw; makin besar
 *   makin baik. Disimpan dalam sentimeter.
 * - `poin`: Tri Lomba (poin akhir yang sudah dikonversi panitia, K124 butir 3);
 *   makin besar makin baik. Disimpan apa adanya.
 *
 * Kelas murni tanpa basis data; punya uji unit.
 */
final class JenisHasil
{
    public const WAKTU = 'waktu';
    public const JARAK = 'jarak';
    public const POIN  = 'poin';

    /**
     * @var array<string, string> kode => label
     */
    public const LABEL = [
        self::WAKTU => 'Waktu',
        self::JARAK => 'Jarak',
        self::POIN  => 'Poin',
    ];

    /**
     * @var array<string, string> kode => keterangan untuk admin
     */
    public const KETERANGAN = [
        self::WAKTU => 'lari, jalan cepat, estafet; waktu tercepat juara',
        self::JARAK => 'lompat, lempar, tolak; jarak terjauh juara',
        self::POIN  => 'Tri Lomba; poin akhir terbesar juara',
    ];

    public static function sah(mixed $jenis): bool
    {
        return is_string($jenis) && isset(self::LABEL[$jenis]);
    }

    /**
     * Prestasi yang lebih kecil lebih baik?
     */
    public static function makinKecilMakinBaik(string $jenis): bool
    {
        return $jenis === self::WAKTU;
    }

    /**
     * Tebakan jenis hasil dari nama nomor, untuk data awal dan nomor lama
     * (juknis: lari dan estafet = waktu, lompat dan lempar = jarak, Tri Lomba
     * = poin). Nomor beregu yang bukan lompat atau lempar (Kanga's Escape,
     * Formula 1) memakai waktu. Admin bisa mengubahnya di Pengaturan > Nomor lomba.
     */
    public static function dariNama(string $nama): string
    {
        $nama = mb_strtolower($nama);

        if (preg_match('/tri\s*-?\s*lomba/u', $nama) === 1) {
            return self::POIN;
        }

        if (preg_match('/lompat|lempar|tolak|jump|throw/u', $nama) === 1) {
            return self::JARAK;
        }

        return self::WAKTU;
    }
}
