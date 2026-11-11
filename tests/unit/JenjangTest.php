<?php

use App\Libraries\Jenjang;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Kode dan label jenjang (`App\Libraries\Jenjang`).
 *
 * @internal
 */
final class JenjangTest extends CIUnitTestCase
{
    public function testKodeDanLabelMengikutiIdPendaftaran(): void
    {
        $this->assertSame(['A' => 'SD/MI', 'B' => 'SMP/MTs', 'C' => 'SMA/SMK/MA'], Jenjang::semua());
        $this->assertSame(['A', 'B', 'C'], Jenjang::kode());
        $this->assertSame('A', Jenjang::SD);
        $this->assertSame('B', Jenjang::SMP);
        $this->assertSame('C', Jenjang::SMA);
    }

    public function testKodeSamaDenganPilihanKolomBasisData(): void
    {
        // ENUM di migrasi `kelompok_umur_jenjang` dan `pendaftaran` memakai kode yang sama.
        $this->assertSame(['A', 'B', 'C'], Jenjang::kode());
    }

    public function testSahDanLabel(): void
    {
        $this->assertTrue(Jenjang::sah('A'));
        $this->assertTrue(Jenjang::sah('C'));
        $this->assertFalse(Jenjang::sah('a'), 'Kode peka huruf besar-kecil.');
        $this->assertFalse(Jenjang::sah('D'));
        $this->assertFalse(Jenjang::sah(''));
        $this->assertFalse(Jenjang::sah(null));
        $this->assertFalse(Jenjang::sah(1));
        $this->assertFalse(Jenjang::sah(['A']));

        $this->assertSame('SMP/MTs', Jenjang::label('B'));
        $this->assertNull(Jenjang::label('X'));
        $this->assertNull(Jenjang::label(null));
    }

    public function testRapikanIsianKotakCentang(): void
    {
        $this->assertSame(['A', 'C'], Jenjang::rapikan(['c', 'A', ' a ', 'x', '', 'A']));
        $this->assertSame(['A', 'B', 'C'], Jenjang::rapikan(['C', 'B', 'A']));
        $this->assertSame([], Jenjang::rapikan([]));
        $this->assertSame([], Jenjang::rapikan(null));
        $this->assertSame(['B'], Jenjang::rapikan([['B'], 2, null, 'B']));
    }

    public function testGabungLabel(): void
    {
        $this->assertSame('SD/MI, SMA/SMK/MA', Jenjang::gabungLabel(['C', 'A']));
        $this->assertSame('SMP/MTs', Jenjang::gabungLabel(['B']));
        $this->assertSame('SD/MI dan SMP/MTs', Jenjang::gabungLabel(['A', 'B'], ' dan '));
        $this->assertSame('', Jenjang::gabungLabel([]));
        $this->assertSame('', Jenjang::gabungLabel(['Z']));
    }
}
