<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UjiHalaman;

/**
 * Tema warna (F1-05; sejak F9-17 tanpa `tema.css`): warna flyer di palet
 * Tailwind (`public/aset/tailwind/masukan.css`) dan di halaman galat
 * (`errors/html/galat.css`, berdiri sendiri), sama nilainya, dan kontras teks
 * minimal 4,5 : 1 untuk pasangan yang dipakai halaman galat.
 *
 * @internal
 */
final class TataLetakTemaTest extends UjiHalaman
{
    private const BATAS_KONTRAS = 4.5;

    private const PUTIH = '#FFFFFF';

    public function testVariabelWarnaFlyerTerdefinisi(): void
    {
        $warna = self::warnaDari(APPPATH . 'Views/errors/html/galat.css');

        foreach (['biru', 'biru-tua', 'oranye', 'oranye-tombol', 'oranye-gelap', 'biru-muda', 'teks', 'teks-redup'] as $nama) {
            $this->assertArrayHasKey($nama, $warna, 'Variabel --ml-' . $nama . ' tidak ada di galat.css.');
        }

        // Warna dari flyer (rencana Tahap 1, bagian "Warna tema").
        $this->assertSame('#234987', $warna['biru']);
        $this->assertSame('#021F7B', $warna['biru-tua']);
        $this->assertSame('#E85F31', $warna['oranye']);
        $this->assertSame('#9BCCEC', $warna['biru-muda']);
    }

    public function testWarnaHalamanGalatSamaDenganPaletTailwind(): void
    {
        $galat = self::warnaDari(APPPATH . 'Views/errors/html/galat.css');
        $css   = (string) file_get_contents(FCPATH . 'aset/tailwind/masukan.css');

        foreach (['biru' => 'primary-700', 'biru-tua' => 'primary-900', 'oranye-tombol' => 'oranye-600'] as $ml => $tw) {
            $this->assertMatchesRegularExpression('/--color-' . $tw . ':\s*' . preg_quote($galat[$ml], '/') . '\s*;/i', $css, '--ml-' . $ml . ' berbeda dengan --color-' . $tw . '.');
        }
    }

    /**
     * Pasangan teks/latar yang dipakai halaman galat.
     *
     * @return array<string, array{string, string}>
     */
    public static function pasanganWarna(): array
    {
        return [
            'tombol utama'         => ['putih', 'oranye-tombol'],
            'tombol utama disorot' => ['putih', 'oranye-gelap'],
            'teks putih di biru'   => ['putih', 'biru'],
            'merek di latar'       => ['putih', 'biru-tua'],
            'merek kedua'          => ['biru-muda', 'biru-tua'],
            'judul di kartu'       => ['biru-tua', 'putih'],
            'teks di kartu'        => ['teks', 'putih'],
            'teks redup di kartu'  => ['teks-redup', 'putih'],
        ];
    }

    #[DataProvider('pasanganWarna')]
    public function testKontrasTeksCukup(string $teks, string $latar): void
    {
        $warna   = self::warnaDari(APPPATH . 'Views/errors/html/galat.css') + ['putih' => self::PUTIH];
        $kontras = self::kontras($warna[$teks], $warna[$latar]);

        $this->assertGreaterThanOrEqual(
            self::BATAS_KONTRAS,
            $kontras,
            sprintf('Kontras --ml-%s di atas --ml-%s hanya %.2f : 1.', $teks, $latar, $kontras),
        );
    }

    public function testOranyeFlyerTidakCukupUntukTeksPutih(): void
    {
        // Alasan --ml-oranye hanya untuk hiasan; tombol memakai oranye yang digelapkan.
        $warna = self::warnaDari(APPPATH . 'Views/errors/html/galat.css');

        $this->assertLessThan(self::BATAS_KONTRAS, self::kontras(self::PUTIH, $warna['oranye']));
    }

    public function testSatuTemaTerang(): void
    {
        foreach ([FCPATH . 'aset/css/tw.css', APPPATH . 'Views/errors/html/galat.css'] as $berkas) {
            $css = (string) file_get_contents($berkas);
            $this->assertStringNotContainsString('prefers-color-scheme: dark', $css);
            $this->assertStringNotContainsString('prefers-color-scheme:dark', $css);
        }
    }

    /**
     * Variabel `--ml-*` bernilai heksadesimal enam digit dari sebuah berkas CSS.
     *
     * @return array<string, string> nama tanpa awalan → `#RRGGBB` huruf besar
     */
    private static function warnaDari(string $berkas): array
    {
        preg_match_all('/--ml-([a-z-]+):\s*(#[0-9A-Fa-f]{6})\s*;/', (string) file_get_contents($berkas), $cocok, PREG_SET_ORDER);

        $warna = [];

        foreach ($cocok as [, $nama, $nilai]) {
            $warna[$nama] = strtoupper($nilai);
        }

        return $warna;
    }

    /**
     * Rasio kontras WCAG 2 antara dua warna `#RRGGBB`.
     */
    private static function kontras(string $a, string $b): float
    {
        $terangA = self::luminans($a);
        $terangB = self::luminans($b);

        return (max($terangA, $terangB) + 0.05) / (min($terangA, $terangB) + 0.05);
    }

    private static function luminans(string $heks): float
    {
        $kanal = [];

        foreach ([1, 3, 5] as $posisi) {
            $nilai   = hexdec(substr($heks, $posisi, 2)) / 255;
            $kanal[] = $nilai <= 0.03928 ? $nilai / 12.92 : (($nilai + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $kanal[0] + 0.7152 * $kanal[1] + 0.0722 * $kanal[2];
    }
}
