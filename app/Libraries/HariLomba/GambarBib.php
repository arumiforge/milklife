<?php

namespace App\Libraries\HariLomba;

use Mpdf\Mpdf;
use RuntimeException;

/**
 * Gambar JPG satu nomor atlet (BIB) (K137): 2480 × 1754 piksel (210 × 148,5 mm
 * pada 300 dpi), tata letak sama dengan PDF `admin/hari_lomba/bib_pdf`:
 * kepala (logo, nama acara, seri dan tahun, kotak kelompok umur), nomor atlet
 * besar, nama atlet berhuruf besar, dan nama sekolah, di atas latar dari
 * `TampilanBib`. Tulisan yang terlalu panjang dikecilkan supaya muat.
 *
 * Huruf DejaVu Sans dari paket mPDF, jadi tidak ada berkas huruf tambahan.
 *
 *     $gambar = new GambarBib(TampilanBib::dariPengaturan(), 'MILKLIFE …', 'Seri 2 Tahun 2026', $logoPng);
 *     $jpg    = $gambar->jpg(['nomor_dada' => '2001', 'nama' => 'Siti', 'sekolah' => 'SD …', 'kelompok_umur' => 'KU 10']);
 */
final class GambarBib
{
    /**
     * Piksel per milimeter pada 300 dpi.
     */
    private const MM = 300 / 25.4;

    /**
     * Ukuran huruf GD (titik pada 96 dpi) per titik cetak pada 300 dpi.
     */
    private const PT = 300 / 96;

    private const MUTU = 90;

    /**
     * Gambar dasar yang sama untuk semua BIB: latar, logo, nama acara, garis kepala.
     */
    private \GdImage $dasar;

    private string $huruf;

    private string $hurufTebal;

    public function __construct(
        private readonly TampilanBib $tampilan,
        string $namaAcara,
        string $seriTahun,
        ?string $logoPng,
        ?string $latarJpg = null,
    ) {
        $folder           = dirname((string) (new \ReflectionClass(Mpdf::class))->getFileName(), 2) . '/ttfonts/';
        $this->huruf      = $folder . 'DejaVuSans.ttf';
        $this->hurufTebal = $folder . 'DejaVuSans-Bold.ttf';

        if (! is_file($this->hurufTebal)) {
            throw new RuntimeException('Huruf DejaVu Sans tidak ditemukan di paket mPDF. Jalankan composer install di server, lalu coba lagi.');
        }

        $this->dasar = $this->buatDasar($namaAcara, $seriTahun, $logoPng, $latarJpg ?? $tampilan->latarJpg());
    }

    public function __destruct()
    {
        imagedestroy($this->dasar);
    }

    /**
     * @param array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string} $atlet
     */
    public function jpg(array $atlet, int $mutu = self::MUTU): string
    {
        $gambar = imagecreatetruecolor(TampilanBib::LEBAR, TampilanBib::TINGGI);
        imagecopy($gambar, $this->dasar, 0, 0, 0, 0, TampilanBib::LEBAR, TampilanBib::TINGGI);
        $warna = $this->tampilan->warna;

        if ($this->tampilan->kepala) {
            // Kotak kelompok umur di kanan kepala, 40 × 17 mm.
            $x1 = self::px(160);
            $x2 = self::px(200);
            imagefilledrectangle($gambar, $x1, self::px(10), $x2, self::px(27), self::warna($gambar, '#021F7B'));
            $this->tulis($gambar, $atlet['kelompok_umur'], true, 20, '#FFFFFF', $x1, $x2, self::px(10), self::px(27));
        }

        $this->tulis($gambar, $atlet['nomor_dada'], true, 180, $warna['nomor'], self::px(10), self::px(200), self::px(37), self::px(101));
        $this->tulis($gambar, mb_strtoupper($atlet['nama']), true, 24, $warna['nama'], self::px(10), self::px(200), self::px(105), self::px(116));
        $this->tulis($gambar, $atlet['sekolah'], false, 15, $warna['sekolah'], self::px(10), self::px(200), self::px(117), self::px(125));

        ob_start();
        imagejpeg($gambar, null, $mutu);
        imagedestroy($gambar);

        return (string) ob_get_clean();
    }

    private function buatDasar(string $namaAcara, string $seriTahun, ?string $logoPng, ?string $latarJpg): \GdImage
    {
        $dasar = imagecreatetruecolor(TampilanBib::LEBAR, TampilanBib::TINGGI);
        imagefill($dasar, 0, 0, self::warna($dasar, '#FFFFFF'));
        $latar = $latarJpg === null ? false : @imagecreatefromstring($latarJpg);

        if ($latar !== false) {
            imagecopyresampled($dasar, $latar, 0, 0, 0, 0, TampilanBib::LEBAR, TampilanBib::TINGGI, imagesx($latar), imagesy($latar));
            imagedestroy($latar);
        }

        if (! $this->tampilan->kepala) {
            return $dasar;
        }

        $warna  = $this->tampilan->warna;
        $kiri   = self::px(10);
        $logo   = $logoPng === null ? false : @imagecreatefromstring($logoPng);

        if ($logo !== false) {
            // Logo setinggi 15 mm, lebar menurut perbandingannya (paling lebar 16 mm).
            $tinggi = self::px(15);
            $lebar  = min(self::px(16), (int) round(imagesx($logo) * $tinggi / max(1, imagesy($logo))));
            imagealphablending($dasar, true);
            imagecopyresampled($dasar, $logo, $kiri, self::px(10), 0, 0, $lebar, $tinggi, imagesx($logo), imagesy($logo));
            imagedestroy($logo);
            $kiri = self::px(28);
        }

        $kanan = self::px(158);

        if ($seriTahun === '') {
            $this->tulis($dasar, $namaAcara, true, 13, $warna['acara'], $kiri, $kanan, self::px(10), self::px(25), false);
        } else {
            $this->tulis($dasar, $namaAcara, true, 13, $warna['acara'], $kiri, $kanan, self::px(11), self::px(18), false);
            $this->tulis($dasar, $seriTahun, false, 10, $warna['seri'], $kiri, $kanan, self::px(18), self::px(24), false);
        }

        imagefilledrectangle($dasar, self::px(10), self::px(27), self::px(200), self::px(27.6), self::warna($dasar, $warna['garis']));

        return $dasar;
    }

    /**
     * Tulis satu baris di tengah pita `$atas`–`$bawah`, rata tengah antara
     * `$kiri`–`$kanan` (atau rata kiri). Ukuran `$pt` titik cetak; dikecilkan
     * sampai muat, paling kecil 40%.
     */
    private function tulis(\GdImage $gambar, string $teks, bool $tebal, float $pt, string $warna, int $kiri, int $kanan, int $atas, int $bawah, bool $tengah = true): void
    {
        $teks = trim($teks);

        if ($teks === '') {
            return;
        }

        $huruf  = $tebal ? $this->hurufTebal : $this->huruf;
        $ukuran = $pt * self::PT;
        $lebarMaks = $kanan - $kiri;
        $kotak  = imagettfbbox($ukuran, 0, $huruf, $teks);
        $lebar  = $kotak === false ? 0 : $kotak[2] - $kotak[0];

        if ($lebar > $lebarMaks) {
            $ukuran = max($ukuran * 0.4, $ukuran * $lebarMaks / $lebar);
            $kotak  = imagettfbbox($ukuran, 0, $huruf, $teks);
            $lebar  = $kotak === false ? 0 : $kotak[2] - $kotak[0];
        }

        // Tinggi baris dari huruf acuan supaya garis dasar tulisan tetap sama.
        $acuan  = imagettfbbox($ukuran, 0, $huruf, 'H');
        $tinggi = $acuan === false ? 0 : $acuan[1] - $acuan[7];
        $dasarY = (int) round($atas + ($bawah - $atas + $tinggi) / 2);
        $x      = $tengah ? (int) round($kiri + ($lebarMaks - $lebar) / 2) : $kiri;

        imagettftext($gambar, $ukuran, 0, $x - ($kotak === false ? 0 : $kotak[0]), $dasarY, self::warna($gambar, $warna), $huruf, $teks);
    }

    private static function px(float $mm): int
    {
        return (int) round($mm * self::MM);
    }

    private static function warna(\GdImage $gambar, string $hex): int
    {
        return (int) imagecolorallocate($gambar, (int) hexdec(substr($hex, 1, 2)), (int) hexdec(substr($hex, 3, 2)), (int) hexdec(substr($hex, 5, 2)));
    }
}
