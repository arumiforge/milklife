<?php

namespace App\Libraries;

/**
 * Salinan kecil foto unggahan untuk `srcset` (F10-06), supaya ponsel tidak
 * mengunduh foto 1600 piksel untuk layar 390 piksel.
 *
 * Salinan WebP lebar 640 dan 960 piksel dibuat sekali saat pertama dibutuhkan
 * (halaman depan dibuka) di subfolder `varian/` folder unggahan yang sama:
 *
 *     unggahan/halaman-depan/3f2a….webp
 *     unggahan/halaman-depan/varian/3f2a…-640.webp
 *     unggahan/halaman-depan/varian/3f2a…-960.webp
 *
 * Jadi pemasangan lama tidak butuh langkah server. Salinan yang lebih lebar
 * dari aslinya tidak dibuat; salinan yang gagal dibuat dilewati (foto asli
 * tetap dipakai). Salinan dibuat ulang bila foto aslinya lebih baru.
 *
 *     VarianFoto::srcset('unggahan/halaman-depan/3f2a….webp');
 *     // '…/varian/3f2a…-640.webp 640w, …/varian/3f2a…-960.webp 960w, …/3f2a….webp 1600w', atau null
 *     VarianFoto::hapus('unggahan/halaman-depan/3f2a….webp'); // saat fotonya dihapus admin
 */
final class VarianFoto
{
    /**
     * Lebar salinan dalam piksel, dari kecil ke besar.
     */
    public const LEBAR = [640, 960];

    /**
     * Lebar salinan logo kepala tata letak publik (F10-07): logo persegi tampil
     * sekitar 48 piksel, logo persegi panjang sekitar 130 piksel; salinan ini
     * cukup tajam di layar 3x tanpa mengunduh logo unggahan aslinya.
     */
    public const LEBAR_LOGO = [160, 400];

    /**
     * Subfolder salinan di dalam folder unggahan fotonya.
     */
    public const FOLDER = 'varian';

    /**
     * Mutu WebP salinan (0–100).
     */
    private const MUTU = 80;

    /**
     * Nilai `srcset` (salinan kecil lalu foto asli), atau `null` bila foto
     * tidak ada, bukan gambar, atau tidak ada salinan yang bisa dipakai.
     */
    public static function srcset(string $jalur, ?Unggahan $unggahan = null): ?string
    {
        $penuh = ($unggahan ?? new Unggahan())->jalurPenuh($jalur);
        $info  = $penuh === null || ! is_file($penuh) ? false : @getimagesize($penuh);

        if ($info === false || $info[0] < 1) {
            return null;
        }

        $bagian = [];

        foreach (self::LEBAR as $lebar) {
            if ($lebar < $info[0] && self::buat((string) $penuh, $lebar)) {
                $bagian[] = base_url(self::jalurRelatif($jalur, $lebar)) . ' ' . $lebar . 'w';
            }
        }

        if ($bagian === []) {
            return null;
        }

        $bagian[] = base_url($jalur) . ' ' . $info[0] . 'w';

        return implode(', ', $bagian);
    }

    /**
     * Jalur relatif satu salinan selebar `$lebar` (dibuat bila perlu), atau
     * `null` bila foto tidak ada, tidak lebih lebar dari `$lebar`, atau
     * salinannya gagal dibuat (pakai foto asli). Dipakai logo kepala (F10-07).
     */
    public static function kecil(string $jalur, int $lebar, ?Unggahan $unggahan = null): ?string
    {
        $penuh = ($unggahan ?? new Unggahan())->jalurPenuh($jalur);
        $info  = $penuh === null || ! is_file($penuh) ? false : @getimagesize($penuh);

        if ($info === false || $info[0] <= $lebar || ! self::buat((string) $penuh, $lebar)) {
            return null;
        }

        return self::jalurRelatif($jalur, $lebar);
    }

    /**
     * Hapus semua salinan foto ini. Jalur yang tidak sah diabaikan.
     */
    public static function hapus(string $jalur, ?Unggahan $unggahan = null): void
    {
        $penuh = ($unggahan ?? new Unggahan())->jalurPenuh($jalur);

        if ($penuh === null) {
            return;
        }

        foreach ([...self::LEBAR, ...self::LEBAR_LOGO] as $lebar) {
            $varian = self::jalurPenuhVarian($penuh, $lebar);

            if (is_file($varian)) {
                @unlink($varian);
            }
        }
    }

    /**
     * Jalur relatif salinan: `unggahan/<folder>/varian/<nama>-<lebar>.webp`.
     */
    public static function jalurRelatif(string $jalur, int $lebar): string
    {
        return dirname($jalur) . '/' . self::FOLDER . '/' . pathinfo($jalur, PATHINFO_FILENAME) . '-' . $lebar . '.webp';
    }

    private static function jalurPenuhVarian(string $penuh, int $lebar): string
    {
        return dirname($penuh) . DIRECTORY_SEPARATOR . self::FOLDER . DIRECTORY_SEPARATOR . pathinfo($penuh, PATHINFO_FILENAME) . '-' . $lebar . '.webp';
    }

    /**
     * Pastikan salinan selebar `$lebar` ada dan tidak lebih lama dari aslinya.
     * `true` bila salinannya siap dipakai.
     */
    private static function buat(string $penuh, int $lebar): bool
    {
        $tujuan = self::jalurPenuhVarian($penuh, $lebar);

        if (is_file($tujuan) && (int) @filemtime($tujuan) >= (int) @filemtime($penuh)) {
            return true;
        }

        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return false;
        }

        $folder = dirname($tujuan);

        if (! is_dir($folder) && ! @mkdir($folder, 0755, true) && ! is_dir($folder)) {
            return false;
        }

        $info = @getimagesize($penuh);

        if ($info === false) {
            return false;
        }

        $tinggi = max(1, (int) round($info[1] * $lebar / $info[0]));
        Unggahan::cukupkanMemori(($info[0] * $info[1] + $lebar * $tinggi) * 5);

        $isi  = @file_get_contents($penuh);
        $asli = $isi === false || $isi === '' ? false : @imagecreatefromstring($isi);

        if ($asli === false) {
            return false;
        }

        $baru = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($baru, false);
        imagesavealpha($baru, true);
        imagecopyresampled($baru, $asli, 0, 0, 0, 0, $lebar, $tinggi, $info[0], $info[1]);
        imagedestroy($asli);

        // Nama sementara acak: dua permintaan yang membuat salinan bersamaan tidak saling menimpa setengah jadi.
        $sementara = $tujuan . '.' . bin2hex(random_bytes(4)) . '.sementara';
        $tersimpan = imagewebp($baru, $sementara, self::MUTU);
        imagedestroy($baru);

        if (! $tersimpan || ! @rename($sementara, $tujuan)) {
            @unlink($sementara);

            return false;
        }

        @chmod($tujuan, 0644);
        clearstatcache(true, $tujuan);

        return true;
    }
}
