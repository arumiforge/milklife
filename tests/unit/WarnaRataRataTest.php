<?php

use App\Libraries\WarnaRataRata;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Warna rata-rata foto untuk latar carousel selama foto dimuat (F9-17).
 *
 * @internal
 */
final class WarnaRataRataTest extends CIUnitTestCase
{
    /** @var list<string> */
    private array $berkas = [];

    protected function tearDown(): void
    {
        foreach ($this->berkas as $b) {
            if (is_file($b)) {
                unlink($b);
            }
        }

        parent::tearDown();
    }

    /**
     * Gambar PNG sementara: setengah kiri `$kiri`, setengah kanan `$kanan` (RGB).
     *
     * @param array{int, int, int} $kiri
     * @param array{int, int, int} $kanan
     */
    private function gambar(array $kiri, array $kanan): string
    {
        $g = imagecreatetruecolor(20, 10);
        imagefilledrectangle($g, 0, 0, 9, 9, (int) imagecolorallocate($g, ...$kiri));
        imagefilledrectangle($g, 10, 0, 19, 9, (int) imagecolorallocate($g, ...$kanan));
        $berkas = WRITEPATH . 'warna-uji-' . bin2hex(random_bytes(4)) . '.png';
        imagepng($g, $berkas);
        imagedestroy($g);
        $this->berkas[] = $berkas;

        return $berkas;
    }

    public function testWarnaRataRataFoto(): void
    {
        $this->assertSame('#2a4b6c', WarnaRataRata::hitung($this->gambar([42, 75, 108], [42, 75, 108])));

        // Dua warna: hasilnya di antara keduanya.
        $campur = WarnaRataRata::hitung($this->gambar([255, 0, 0], [0, 0, 255]));
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', (string) $campur);
        $this->assertEqualsWithDelta(128, hexdec(substr((string) $campur, 1, 2)), 20);
        $this->assertSame('00', substr((string) $campur, 3, 2));
        $this->assertEqualsWithDelta(128, hexdec(substr((string) $campur, 5, 2)), 20);
    }

    public function testDariMemakaiTembolokPerBerkas(): void
    {
        $berkas = $this->gambar([10, 20, 30], [10, 20, 30]);

        $this->assertSame('#0a141e', WarnaRataRata::dari($berkas));
        // Kedua kali dari tembolok, hasil sama.
        $this->assertSame('#0a141e', WarnaRataRata::dari($berkas));
    }

    public function testBukanGambarAtauTidakAda(): void
    {
        $teks = WRITEPATH . 'warna-uji-' . bin2hex(random_bytes(4)) . '.webp';
        file_put_contents($teks, 'bukan gambar');
        $this->berkas[] = $teks;

        $this->assertNull(WarnaRataRata::hitung($teks));
        $this->assertNull(WarnaRataRata::dari($teks));
        $this->assertNull(WarnaRataRata::dari(WRITEPATH . 'tidak-ada.webp'));
    }
}
