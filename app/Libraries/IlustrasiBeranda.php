<?php

namespace App\Libraries;

use InvalidArgumentException;

/**
 * Ilustrasi Codex untuk halaman depan (F9-03): G9-02 di
 * `public/aset/gambar/beranda/` dan G9-05 di `public/aset/gambar/nomor/`.
 *
 * Ilustrasi hanya tampil bila berkasnya sudah ada (K41), jadi halaman
 * tetap rapi sebelum Codex menyerahkan gambarnya.
 *
 *     IlustrasiBeranda::alamat('gambar/beranda/tokoh-lari.webp'); // alamat aset, atau null bila belum ada
 *     IlustrasiBeranda::nomor('Lompat Jauh');                     // alamat gambar/nomor/lompat-jauh.webp, atau null
 */
final class IlustrasiBeranda
{
    /**
     * Kata kunci nama nomor lomba (huruf kecil) => berkas G9-05, dicocokkan berurutan.
     *
     * @var array<string, string>
     */
    private const NOMOR = [
        'estafet'       => 'estafet',
        'lompat jauh'   => 'lompat-jauh',
        'lompat tinggi' => 'lompat-tinggi',
        'tolak peluru'  => 'tolak-peluru',
        'lembing'       => 'lempar-lembing',
        'cakram'        => 'lempar-cakram',
        'frog'          => 'frog-jump',
        'turbo'         => 'turbo-throw',
        'kanga'         => 'kangas-escape',
        'formula'       => 'formula-1',
        'trilomba'      => 'trilomba',
        'pancalomba'    => 'trilomba',
        'jalan cepat'   => 'jalan-cepat',
    ];

    /**
     * Alamat berkas di `public/aset/`, atau `null` bila berkasnya belum ada.
     * Jalur `gambar/…` dibaca lewat `GambarOpsional` (folder penggantinya ikut berlaku di uji).
     */
    public static function alamat(string $jalur): ?string
    {
        $jalur = ltrim($jalur, '/');

        if (str_starts_with($jalur, 'gambar/')) {
            try {
                return GambarOpsional::alamat(substr($jalur, strlen('gambar/')));
            } catch (InvalidArgumentException) {
                return null;
            }
        }

        return is_file(FCPATH . 'aset/' . $jalur) ? aset($jalur) : null;
    }

    /**
     * Ilustrasi G9-05 untuk nomor lomba, atau `null` bila tidak cocok atau belum ada.
     */
    public static function nomor(string $nama): ?string
    {
        $kecil = mb_strtolower($nama);
        $nama  = null;

        foreach (self::NOMOR as $kunci => $berkas) {
            if (str_contains($kecil, $kunci)) {
                $nama = $berkas;

                break;
            }
        }

        // Lari: pendek sampai 100 m, selain itu menengah.
        if ($nama === null && str_contains($kecil, 'lari')) {
            $nama = preg_match('/(\d+)\s*m\b/u', $kecil, $c) === 1 && (int) $c[1] <= 100 ? 'lari-pendek' : 'lari-menengah';
        }

        return $nama === null ? null : self::alamat('gambar/nomor/' . $nama . '.webp');
    }
}
