<?php

namespace App\Libraries;

use Throwable;

/**
 * Warna rata-rata sebuah foto (`#rrggbb`), untuk latar kotak foto selama
 * fotonya dimuat (skeleton carousel halaman depan, F9-17). Foto dikecilkan GD
 * ke 1 × 1 piksel, jadi hasilnya rata-rata seluruh foto.
 *
 * Hasil disimpan di tembolok per berkas (nama, ukuran, dan waktu ubah), jadi
 * foto hanya dibaca sekali; foto yang diganti mendapat warna baru.
 *
 *     WarnaRataRata::dari('/…/foto.webp'); // '#5a6b7c', atau null bila bukan gambar
 */
final class WarnaRataRata
{
    /** Lama simpan di tembolok (detik): 30 hari. */
    private const UMUR = 2_592_000;

    public static function dari(string $berkas): ?string
    {
        if (! is_file($berkas) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $kunci = 'warna_rata_' . md5($berkas . '|' . (int) @filesize($berkas) . '|' . (int) @filemtime($berkas));

        try {
            $tersimpan = cache($kunci);
        } catch (Throwable) {
            $tersimpan = null;
        }

        if (is_string($tersimpan) && preg_match('/^(#[0-9a-f]{6}|-)$/D', $tersimpan) === 1) {
            return $tersimpan === '-' ? null : $tersimpan;
        }

        $warna = self::hitung($berkas);

        try {
            cache()->save($kunci, $warna ?? '-', self::UMUR);
        } catch (Throwable) {
            // Tembolok gagal: warna tetap dipakai, dihitung lagi lain kali.
        }

        return $warna;
    }

    /**
     * Hitung tanpa tembolok. `null` bila berkas tidak terbaca sebagai gambar.
     */
    public static function hitung(string $berkas): ?string
    {
        $isi    = @file_get_contents($berkas);
        $gambar = $isi === false || $isi === '' ? false : @imagecreatefromstring($isi);

        if ($gambar === false) {
            return null;
        }

        $titik = imagecreatetruecolor(1, 1);
        imagecopyresampled($titik, $gambar, 0, 0, 0, 0, 1, 1, imagesx($gambar), imagesy($gambar));
        $rgb = imagecolorat($titik, 0, 0);
        imagedestroy($gambar);
        imagedestroy($titik);

        return sprintf('#%02x%02x%02x', ($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
    }
}
