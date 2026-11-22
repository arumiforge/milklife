<?php

namespace App\Libraries;

/**
 * Peta lokasi lomba di halaman depan (F10-02, permintaan B14).
 *
 * Dua pengaturan, bawaan dari `Config\HalamanDepan`:
 * - `HalamanDepan.peta_embed`: alamat embed Google Maps, hanya
 *   `https://www.google.com/maps/embed?…` (satu-satunya asal iframe yang
 *   diizinkan CSP `frame-src` selain Turnstile). Kosong = bagian Lokasi tidak tampil.
 * - `HalamanDepan.peta_tautan`: tautan "Buka di Google Maps" (https). Kosong = tanpa tautan.
 *
 * Admin boleh menempelkan seluruh kode `<iframe …>` dari Google Maps;
 * `rapikanEmbed()` mengambil isi atribut `src`-nya.
 */
final class PetaLokasi
{
    public const AWALAN_EMBED = 'https://www.google.com/maps/embed?';

    public const BATAS = 2000;

    public const GALAT_EMBED = 'Alamat peta harus alamat embed Google Maps yang diawali https://www.google.com/maps/embed?. Di Google Maps pilih Bagikan > Sematkan peta, lalu salin kodenya ke sini.';

    public const GALAT_TAUTAN = 'Tautan buka peta harus alamat web lengkap yang diawali https://, misalnya https://maps.google.com/?q=Supersoccer+Arena+Kudus.';

    /**
     * Alamat embed tersimpan yang sah, atau '' bila kosong atau tidak sah.
     */
    public static function embed(): string
    {
        $alamat = trim((string) setting('HalamanDepan.peta_embed'));

        return self::embedSah($alamat) ? $alamat : '';
    }

    /**
     * Tautan buka peta tersimpan yang sah, atau ''.
     */
    public static function tautan(): string
    {
        $alamat = trim((string) setting('HalamanDepan.peta_tautan'));

        return self::tautanSah($alamat) ? $alamat : '';
    }

    /**
     * Isian admin dibersihkan: kode `<iframe>` utuh diambil `src`-nya,
     * entitas HTML (`&amp;`) dikembalikan, spasi di tepi dibuang.
     */
    public static function rapikanEmbed(string $isian): string
    {
        $isian = trim($isian);

        if (stripos($isian, '<iframe') !== false && preg_match('/\ssrc\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $isian, $cocok) === 1) {
            $isian = $cocok[2] !== '' ? $cocok[2] : ($cocok[3] ?? '');
        }

        return trim(html_entity_decode($isian, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function embedSah(string $alamat): bool
    {
        return $alamat !== ''
            && strlen($alamat) <= self::BATAS
            && str_starts_with($alamat, self::AWALAN_EMBED)
            && preg_match('/[\s"\'<>\\\\]/', $alamat) !== 1
            && filter_var($alamat, FILTER_VALIDATE_URL) !== false;
    }

    public static function tautanSah(string $alamat): bool
    {
        return $alamat !== ''
            && strlen($alamat) <= self::BATAS
            && str_starts_with(strtolower($alamat), 'https://')
            && preg_match('/[\s"\'<>\\\\]/', $alamat) !== 1
            && filter_var($alamat, FILTER_VALIDATE_URL) !== false;
    }
}
