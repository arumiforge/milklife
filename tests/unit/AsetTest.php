<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Fungsi `aset()` di `app/Helpers/aplikasi_helper.php`.
 *
 * Uji membuat berkas sementara di `public/aset/` dan menghapusnya lagi.
 *
 * @internal
 */
final class AsetTest extends CIUnitTestCase
{
    private const FOLDER_UJI = 'uji-sementara-aset';

    /**
     * @var list<string>
     */
    private array $berkasSementara = [];

    protected function tearDown(): void
    {
        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        foreach ([FCPATH . 'aset/vendor/' . self::FOLDER_UJI, FCPATH . 'aset/' . self::FOLDER_UJI] as $folder) {
            if (is_dir($folder)) {
                rmdir($folder);
            }
        }

        parent::tearDown();
    }

    public function testAlamatBiasa(): void
    {
        $this->assertSame(base_url('aset/css/tema.css'), aset('css/tema.css'));
        $this->assertSame(base_url('aset/css/tema.css'), aset('/css/tema.css'));
        $this->assertStringEndsWith('/aset/css/tema.css', aset('css/tema.css'));
    }

    public function testMinifyMatiTetapBerkasAsli(): void
    {
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.css');
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.min.css');
        config('Aplikasi')->minify = false;

        $this->assertSame(base_url('aset/' . self::FOLDER_UJI . '/contoh.css'), aset(self::FOLDER_UJI . '/contoh.css'));
    }

    public function testMinifyAktifMemilihBerkasMin(): void
    {
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.css');
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.min.css');
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.js');
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.min.js');
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.svg');
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.min.svg');
        config('Aplikasi')->minify = true;

        $this->assertSame(base_url('aset/' . self::FOLDER_UJI . '/contoh.min.css'), aset(self::FOLDER_UJI . '/contoh.css'));
        $this->assertSame(base_url('aset/' . self::FOLDER_UJI . '/contoh.min.js'), aset(self::FOLDER_UJI . '/contoh.js'));
        $this->assertSame(base_url('aset/' . self::FOLDER_UJI . '/contoh.min.svg'), aset(self::FOLDER_UJI . '/contoh.svg'));
    }

    public function testMinifyAktifTanpaBerkasMinTidakBerubah(): void
    {
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.css');
        config('Aplikasi')->minify = true;

        $this->assertSame(base_url('aset/' . self::FOLDER_UJI . '/contoh.css'), aset(self::FOLDER_UJI . '/contoh.css'));
    }

    public function testJalurMinTidakDiubah(): void
    {
        $this->buatBerkas(self::FOLDER_UJI . '/contoh.min.css');
        config('Aplikasi')->minify = true;

        $this->assertSame(base_url('aset/' . self::FOLDER_UJI . '/contoh.min.css'), aset(self::FOLDER_UJI . '/contoh.min.css'));
    }

    public function testJalurVendorTidakDiubah(): void
    {
        $jalur = 'vendor/' . self::FOLDER_UJI . '/contoh.css';
        $this->buatBerkas($jalur);
        $this->buatBerkas('vendor/' . self::FOLDER_UJI . '/contoh.min.css');
        config('Aplikasi')->minify = true;

        $this->assertSame(base_url('aset/' . $jalur), aset($jalur));
        $this->assertSame(
            base_url('aset/vendor/bootstrap/css/bootstrap.min.css'),
            aset('vendor/bootstrap/css/bootstrap.min.css'),
        );
    }

    public function testMinifyDibacaDariEnv(): void
    {
        $_ENV['aplikasi.minify'] = 'true';

        try {
            $this->assertTrue((new Config\Aplikasi())->minify);
        } finally {
            unset($_ENV['aplikasi.minify']);
        }

        $_ENV['aplikasi.minify'] = 'false';

        try {
            $this->assertFalse((new Config\Aplikasi())->minify);
        } finally {
            unset($_ENV['aplikasi.minify']);
        }
    }

    private function buatBerkas(string $jalur): void
    {
        $berkas = FCPATH . 'aset/' . $jalur;

        if (! is_dir(dirname($berkas))) {
            mkdir(dirname($berkas), 0777, true);
        }

        file_put_contents($berkas, '/* berkas uji */');
        $this->berkasSementara[] = $berkas;
    }
}
