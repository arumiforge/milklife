<?php

use App\Libraries\IlustrasiBeranda;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Ilustrasi Codex halaman depan (F9-03): hanya tampil bila berkasnya ada (K41),
 * dan nama nomor lomba dipetakan ke gambar G9-05.
 *
 * @internal
 */
final class IlustrasiBerandaTest extends CIUnitTestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = FCPATH . 'aset/gambar/nomor-uji-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->folder . '/*') ?: [] as $berkas) {
            unlink($berkas);
        }

        if (is_dir($this->folder)) {
            rmdir($this->folder);
        }

        parent::tearDown();
    }

    public function testBerkasYangBelumAdaTidakTampil(): void
    {
        $this->assertNull(IlustrasiBeranda::alamat('gambar/beranda/tidak-ada-' . bin2hex(random_bytes(4)) . '.webp'));

        mkdir($this->folder);
        file_put_contents($this->folder . '/contoh.webp', 'isi');
        $alamat = IlustrasiBeranda::alamat('/gambar/' . basename($this->folder) . '/contoh.webp');

        $this->assertNotNull($alamat);
        $this->assertStringContainsString('aset/gambar/' . basename($this->folder) . '/contoh.webp', $alamat);
    }

    public function testNamaNomorKeGambar(): void
    {
        $peta = new ReflectionClassConstant(IlustrasiBeranda::class, 'NOMOR');
        $this->assertContains('lompat-jauh', $peta->getValue());

        // Tanpa berkas G9-05, tidak ada gambar walaupun namanya cocok.
        foreach (['Lari 60 m', 'Lari 800 m', 'Lompat Jauh', 'Estafet 4 × 50 m', 'Kanga\'s Escape', 'Nomor Karangan'] as $nama) {
            $alamat = IlustrasiBeranda::nomor($nama);

            if ($alamat !== null) {
                $this->assertMatchesRegularExpression('#aset/gambar/nomor/[a-z0-9-]+\.webp#', $alamat, $nama);
            }
        }

        $this->assertNull(IlustrasiBeranda::nomor('Nomor Karangan'));
    }
}
