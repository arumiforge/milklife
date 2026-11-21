<?php

namespace App\Libraries;

use InvalidArgumentException;

/**
 * Gambar opsional di `public/aset/gambar/` (F9-19, K41, K150): gambar Codex
 * hanya dirender bila berkasnya sudah ada, sehingga halaman tetap rapi (tanpa
 * ruang kosong, galat, atau permintaan 404) sebelum gambarnya diserahkan.
 * Pembungkus pendeknya `gambar_opsional()` dan `ada_gambar()` di
 * `app/Helpers/tampilan_helper.php`.
 *
 *     GambarOpsional::img('admin/masuk.webp', ['class' => 'h-40 w-auto']);  // '' bila belum ada
 *     GambarOpsional::alamat('bagikan/umum.jpg');                           // URL mutlak ber-?v=, atau null
 *
 * Jalur selalu relatif terhadap `public/aset/gambar/`.
 */
final class GambarOpsional
{
    /**
     * Gambar bagikan G9-10 (`bagikan/<berkas>.jpg`) per segmen pertama alamat halaman publik.
     */
    public const BAGIKAN = [
        'jadwal'  => 'jadwal',
        'hasil'   => 'hasil',
        'peserta' => 'peserta',
        'galeri'  => 'galeri',
        'bantuan' => 'bantuan',
    ];

    /**
     * Folder pengganti `public/aset/gambar/` untuk uji (berakhiran `/`); null = folder asli.
     */
    public static ?string $folder = null;

    /**
     * Jalur penuh berkas, atau `null` bila belum ada.
     */
    public static function berkas(string $jalur): ?string
    {
        $berkas = self::folder() . self::periksa($jalur);

        return is_file($berkas) ? $berkas : null;
    }

    public static function ada(string $jalur): bool
    {
        return self::berkas($jalur) !== null;
    }

    /**
     * Alamat mutlak berkas dengan penanda versi (`?v=<waktu ubah>`), atau `null` bila belum ada.
     */
    public static function alamat(string $jalur): ?string
    {
        $berkas = self::berkas($jalur);

        return $berkas === null ? null : base_url('aset/gambar/' . ltrim($jalur, '/')) . '?v=' . (int) @filemtime($berkas);
    }

    /**
     * Lebar dan tinggi berkas dalam piksel, atau `null` bila belum ada atau tidak terbaca.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function ukuran(string $jalur): ?array
    {
        static $simpan = [];

        $berkas = self::berkas($jalur);

        if ($berkas === null) {
            return null;
        }

        $kunci = $berkas . '|' . (int) @filemtime($berkas);

        if (! array_key_exists($kunci, $simpan)) {
            $info           = @getimagesize($berkas);
            $simpan[$kunci] = is_array($info) && $info[0] > 0 && $info[1] > 0 ? [(int) $info[0], (int) $info[1]] : null;
        }

        return $simpan[$kunci];
    }

    /**
     * Tag `<img>` bila berkasnya ada, selain itu teks kosong.
     *
     * - `alt` bawaan `''` (hiasan) dan ditambah `aria-hidden="true"`; beri teks bila gambarnya bermakna.
     * - `loading` bawaan `lazy`; isi `false` untuk gambar di atas lipatan (dimuat langsung).
     * - `width`/`height` diambil dari berkas bila tidak diberikan, supaya tata letak tidak bergeser.
     * - Atribut lain (`class`, `data-*`) lewat `atribut_html()`; `style` dan `on…` ditolak (CSP, K127).
     *
     * @param array<string, scalar|null> $atribut
     */
    public static function img(string $jalur, array $atribut = []): string
    {
        $alamat = self::alamat($jalur);

        if ($alamat === null) {
            return '';
        }

        $alt    = (string) ($atribut['alt'] ?? '');
        $ukuran = self::ukuran($jalur);
        unset($atribut['alt'], $atribut['src']);

        $atribut += [
            'width'    => $ukuran[0] ?? null,
            'height'   => $ukuran[1] ?? null,
            'loading'  => 'lazy',
            'decoding' => 'async',
        ];

        if ($alt === '') {
            $atribut['aria-hidden'] = 'true';
        }

        return '<img src="' . esc($alamat, 'attr') . '" alt="' . esc($alt, 'attr') . '"' . atribut_html($atribut) . '>';
    }

    /**
     * Alamat mutlak gambar bagikan (Open Graph) untuk alamat halaman publik
     * (misalnya `hasil/klasemen`): gambar halamannya, cadangan `umum.jpg`,
     * atau `null` bila keduanya belum ada (tag `og:image` tidak ditulis).
     */
    public static function bagikan(string $alamatHalaman): ?string
    {
        $segmen = strtolower(explode('/', trim($alamatHalaman, '/'))[0]);
        $berkas = self::BAGIKAN[$segmen] ?? null;

        return ($berkas === null ? null : self::alamat('bagikan/' . $berkas . '.jpg')) ?? self::alamat('bagikan/umum.jpg');
    }

    private static function folder(): string
    {
        return self::$folder ?? FCPATH . 'aset/gambar/';
    }

    /**
     * Tolak jalur yang keluar dari folder gambar atau bukan berkas gambar.
     */
    private static function periksa(string $jalur): string
    {
        $jalur = ltrim($jalur, '/');

        if (preg_match('#^[a-z0-9][a-z0-9_/-]*\.(webp|png|jpe?g|svg)$#', $jalur) !== 1 || str_contains($jalur, '..') || str_contains($jalur, '//')) {
            throw new InvalidArgumentException('Jalur gambar tidak sah: ' . $jalur);
        }

        return $jalur;
    }
}
