<?php

namespace App\Libraries;

/**
 * Pembakuan nomor WA ponsel Indonesia.
 *
 * Semua nomor WA disimpan dalam bentuk baku `628…` (tanpa `+`, spasi, atau
 * tanda hubung), sehingga bisa langsung dipakai di alamat `https://wa.me/<nomor>`.
 *
 *     NomorWa::normalkan('0812-3456-7890');     // '6281234567890'
 *     NomorWa::normalkan('+62 812 3456 7890');  // '6281234567890'
 *     NomorWa::normalkan('6281234567890');      // '6281234567890'
 *     NomorWa::normalkan('812 3456 7890');      // '6281234567890'
 *     NomorWa::normalkan('021-555-1234');       // null (bukan nomor ponsel)
 *
 * Pesan galat yang dianjurkan untuk isian yang ditolak: `NomorWa::PESAN_GALAT`.
 */
final class NomorWa
{
    /**
     * Pesan untuk isian yang bukan nomor WA, menyebut cara memperbaikinya.
     */
    public const PESAN_GALAT = 'Nomor WA belum benar. Tulis nomor ponsel yang dipakai untuk WA, misalnya 0812-3456-7890 atau +62 812 3456 7890.';

    private const PANJANG_MINIMAL = 10;
    private const PANJANG_MAKSIMAL = 15;

    /**
     * Ubah isian menjadi bentuk baku `628…`, atau `null` bila bukan nomor
     * ponsel Indonesia (10–15 angka setelah dibakukan, diawali `628`).
     *
     * Spasi, titik, tanda hubung, garis miring, dan tanda kurung diabaikan.
     * Huruf atau tanda lain membuat isian ditolak.
     */
    public static function normalkan(?string $isian): ?string
    {
        if ($isian === null) {
            return null;
        }

        $isian = trim($isian);

        if ($isian === '' || preg_match('#^\+?[0-9 .\-/()]+$#', $isian) !== 1) {
            return null;
        }

        $angka = preg_replace('/\D/', '', $isian);

        if (str_starts_with($angka, '0')) {
            $angka = '62' . substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62' . $angka;
        }

        $panjang = strlen($angka);

        if (! str_starts_with($angka, '628') || $panjang < self::PANJANG_MINIMAL || $panjang > self::PANJANG_MAKSIMAL) {
            return null;
        }

        return $angka;
    }

    /**
     * Apakah isian bisa dibakukan menjadi nomor WA.
     */
    public static function sah(?string $isian): bool
    {
        return self::normalkan($isian) !== null;
    }

    /**
     * Tampilan nomor baku yang mudah dibaca, contoh `0812-3456-7890`.
     * Isian yang tidak sah dikembalikan apa adanya.
     */
    public static function tampilan(?string $nomor): string
    {
        $baku = self::normalkan($nomor);

        if ($baku === null) {
            return (string) $nomor;
        }

        $lokal = '0' . substr($baku, 2);

        return implode('-', array_filter([substr($lokal, 0, 4), substr($lokal, 4, 4), substr($lokal, 8)], static fn (string $bagian): bool => $bagian !== ''));
    }
}
