<?php

namespace App\Libraries\Hasil;

/**
 * Jenis hasil nomor lomba (F8-04, K124, asumsi 6 K125).
 *
 * - `waktu`: makin kecil makin baik, disimpan dalam perseratus detik.
 * - `jarak`: makin besar makin baik, disimpan dalam sentimeter.
 * - `poin`: makin besar makin baik, disimpan apa adanya (poin akhir yang
 *   diisi admin).
 * - `multi`: poin multi-lomba (F9-10, K140 butir 7b): admin mengisi prestasi
 *   mentah tiap komponen (misalnya Trilomba: Lari 60 m, Lompat Jauh, Tolak
 *   Peluru), web menghitung poinnya dengan tabel skor (`TabelSkor`). Kolom
 *   `hasil.prestasi` berisi total poin, rincian per komponen di
 *   `hasil.rincian`. Makin besar makin baik.
 *
 * `tebak()` dipakai data awal dan migrasi untuk nomor yang sudah ada:
 * Trilomba = poin; lompat, lempar, tolak, Frog Jump, dan Turbo Throw = jarak;
 * selain itu (lari, jalan cepat, estafet, Kanga's Escape, Formula 1) = waktu.
 * Admin bisa menggantinya di Pengaturan > Nomor Lomba.
 */
final class JenisHasil
{
    public const WAKTU = 'waktu';
    public const JARAK = 'jarak';
    public const POIN  = 'poin';
    public const MULTI = 'multi';

    /**
     * @var array<string, string> kode => label
     */
    public const LABEL = [
        self::WAKTU => 'Waktu',
        self::JARAK => 'Jarak',
        self::POIN  => 'Poin',
        self::MULTI => 'Poin multi-lomba',
    ];

    /**
     * Keterangan singkat untuk formulir dan judul kolom.
     *
     * @var array<string, string>
     */
    public const KETERANGAN = [
        self::WAKTU => 'makin kecil makin baik; ditulis 12,34 atau 2:05,10',
        self::JARAK => 'makin jauh makin baik; ditulis dalam meter, misalnya 3,45',
        self::POIN  => 'makin besar makin baik; poin akhir ditulis langsung, misalnya 1250',
        self::MULTI => 'makin besar makin baik; prestasi tiap komponen diisi, poinnya dihitung dari tabel skor',
    ];

    public static function sah(mixed $jenis): bool
    {
        return is_string($jenis) && isset(self::LABEL[$jenis]);
    }

    /**
     * Jenis hasil menurut nama nomor, untuk data awal dan nomor lama.
     */
    public static function tebak(string $nama): string
    {
        $nama = mb_strtolower($nama);

        if (str_contains($nama, 'trilomba') || str_contains($nama, 'tri lomba')) {
            return self::POIN;
        }

        foreach (['lompat', 'lempar', 'tolak', 'frog', 'turbo', 'throw', 'jump'] as $kata) {
            if (str_contains($nama, $kata)) {
                return self::JARAK;
            }
        }

        return self::WAKTU;
    }

    /**
     * Makin besar makin baik?
     */
    public static function besarLebihBaik(string $jenis): bool
    {
        return $jenis !== self::WAKTU;
    }
}
