<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UjiHalaman;

/**
 * Tema warna (F1-05): variabel warna flyer di `tema.css`, pemetaan ke
 * Bootstrap, dan kontras teks minimal 4,5 : 1 untuk pasangan yang dipakai.
 *
 * @internal
 */
final class TataLetakTemaTest extends UjiHalaman
{
    private const BATAS_KONTRAS = 4.5;

    public function testVariabelWarnaFlyerTerdefinisi(): void
    {
        $warna = self::warnaDari(FCPATH . 'aset/css/tema.css');

        foreach (['biru', 'biru-tua', 'oranye', 'oranye-tombol', 'oranye-gelap', 'oranye-pekat', 'biru-muda', 'biru-muda-pucat', 'putih', 'latar', 'teks', 'teks-redup'] as $nama) {
            $this->assertArrayHasKey($nama, $warna, 'Variabel --ml-' . $nama . ' tidak ada di tema.css.');
        }

        // Warna dari flyer (rencana Tahap 1, bagian "Warna tema").
        $this->assertSame('#234987', $warna['biru']);
        $this->assertSame('#021F7B', $warna['biru-tua']);
        $this->assertSame('#E85F31', $warna['oranye']);
        $this->assertSame('#9BCCEC', $warna['biru-muda']);
        $this->assertSame('#FFFFFF', $warna['putih']);
    }

    public function testVariabelBootstrapDipetakan(): void
    {
        $css = (string) file_get_contents(FCPATH . 'aset/css/tema.css');

        $this->assertMatchesRegularExpression('/--bs-primary:\s*var\(--ml-biru\)/', $css);
        $this->assertMatchesRegularExpression('/--bs-primary-rgb:\s*35,\s*73,\s*135/', $css);
        $this->assertMatchesRegularExpression('/--bs-link-color:\s*var\(--ml-biru\)/', $css);
        $this->assertMatchesRegularExpression('/--bs-link-color-rgb:\s*35,\s*73,\s*135/', $css);

        // Tombol utama oranye (yang sudah digelapkan), bukan oranye asli flyer.
        $this->assertMatchesRegularExpression('/\.btn-primary\s*\{[^}]*--bs-btn-bg:\s*var\(--ml-oranye-tombol\)/', $css);
    }

    /**
     * Pasangan teks/latar yang dipakai di tema.
     *
     * @return array<string, array{string, string}>
     */
    public static function pasanganWarna(): array
    {
        return [
            'tombol utama'              => ['putih', 'oranye-tombol'],
            'tombol utama disorot'      => ['putih', 'oranye-gelap'],
            'tombol utama ditekan'      => ['putih', 'oranye-pekat'],
            'teks putih di biru'        => ['putih', 'biru'],
            'teks putih di biru tua'    => ['putih', 'biru-tua'],
            'bidang informasi'          => ['biru-tua', 'biru-muda'],
            'merek di kepala'           => ['biru-muda', 'biru-tua'],
            'judul pembuka di gradasi'  => ['biru-muda', 'biru'],
            'tautan di latar'           => ['biru', 'latar'],
            'tautan di kartu'           => ['biru', 'putih'],
            'teks di latar'             => ['teks', 'latar'],
            'teks redup di latar'       => ['teks-redup', 'latar'],
            'teks redup di kartu'       => ['teks-redup', 'putih'],
            'teks redup tanpa gambar'   => ['teks-redup', 'biru-muda-pucat'],
            'judul tanpa gambar'        => ['biru-tua', 'biru-muda-pucat'],
        ];
    }

    #[DataProvider('pasanganWarna')]
    public function testKontrasTeksCukup(string $teks, string $latar): void
    {
        $warna   = self::warnaDari(FCPATH . 'aset/css/tema.css');
        $kontras = self::kontras($warna[$teks], $warna[$latar]);

        $this->assertGreaterThanOrEqual(
            self::BATAS_KONTRAS,
            $kontras,
            sprintf('Kontras --ml-%s di atas --ml-%s hanya %.2f : 1.', $teks, $latar, $kontras),
        );
    }

    public function testOranyeFlyerTidakCukupUntukTeksPutih(): void
    {
        // Alasan --ml-oranye hanya untuk hiasan: bila suatu saat cukup, catatan di tema.css perlu diperbarui.
        $warna = self::warnaDari(FCPATH . 'aset/css/tema.css');

        $this->assertLessThan(self::BATAS_KONTRAS, self::kontras($warna['putih'], $warna['oranye']));
    }

    public function testWarnaHalamanGalatSamaDenganTema(): void
    {
        $tema  = self::warnaDari(FCPATH . 'aset/css/tema.css');
        $galat = self::warnaDari(APPPATH . 'Views/errors/html/galat.css');

        $this->assertNotEmpty($galat);

        foreach ($galat as $nama => $nilai) {
            $this->assertArrayHasKey($nama, $tema, 'galat.css memakai --ml-' . $nama . ' yang tidak ada di tema.css.');
            $this->assertSame($tema[$nama], $nilai, 'Warna --ml-' . $nama . ' di galat.css berbeda dengan tema.css.');
        }
    }

    public function testSatuTemaTerang(): void
    {
        foreach (['tema.css', 'admin.css'] as $berkas) {
            $css = (string) file_get_contents(FCPATH . 'aset/css/' . $berkas);
            $this->assertStringNotContainsString('prefers-color-scheme', $css);
            $this->assertStringNotContainsString('data-bs-theme="dark"', $css);
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
