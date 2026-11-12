<?php

use App\Libraries\Cadangan\MasaSimpan;
use App\Libraries\Cadangan\PembuatCadangan;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Nama berkas cadangan, masa simpan 14 hari (K66), dan pemeriksaan folder
 * di dalam proyek. Tanpa berkas dan basis data.
 *
 * @internal
 */
final class CadanganMasaSimpanTest extends CIUnitTestCase
{
    public function testNamaBerkasMemuatTanggalDanJam(): void
    {
        $waktu = new DateTimeImmutable('2026-11-12 02:05');

        $this->assertSame('cadangan-milklife-2026-11-12-0205.zip', MasaSimpan::namaBerkas($waktu));
    }

    public function testCadanganHarianTersisaEmpatBelasBerkas(): void
    {
        $sekarang = new DateTimeImmutable('2026-12-20 02:00');
        $nama     = [];

        // Cadangan tiap pukul 02.00 selama 20 hari terakhir, termasuk hari ini.
        for ($hari = 0; $hari < 20; $hari++) {
            $nama[] = MasaSimpan::namaBerkas($sekarang->modify("-{$hari} days"));
        }

        $hapus = MasaSimpan::kedaluwarsa($nama, $sekarang, 14);

        $this->assertCount(6, $hapus);
        $this->assertSame('cadangan-milklife-2026-12-01-0200.zip', $hapus[0]);
        $this->assertSame('cadangan-milklife-2026-12-06-0200.zip', $hapus[5]);
        $this->assertCount(14, array_diff($nama, $hapus));
        $this->assertNotContains('cadangan-milklife-2026-12-07-0200.zip', $hapus);
    }

    public function testBatasEmpatBelasHariTepat(): void
    {
        $sekarang = new DateTimeImmutable('2026-12-20 02:00');

        $this->assertSame(
            ['cadangan-milklife-2026-12-06-0200.zip'],
            MasaSimpan::kedaluwarsa([
                'cadangan-milklife-2026-12-06-0200.zip', // tepat 14 hari
                'cadangan-milklife-2026-12-06-0201.zip', // kurang semenit
            ], $sekarang, 14),
        );
    }

    public function testBerkasLainTidakPernahDihapus(): void
    {
        $sekarang = new DateTimeImmutable('2026-12-20 02:00');

        $this->assertSame([], MasaSimpan::kedaluwarsa([
            'catatan.txt',
            'cadangan-milklife-2020-01-01-0200.zip.bak',
            'cadangan-lain-2020-01-01-0200.zip',
            'cadangan-milklife-2020-01-01.zip',
        ], $sekarang, 14));
    }

    public function testMasaSimpanKurangDariSatuHariDitolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MasaSimpan::kedaluwarsa([], new DateTimeImmutable(), 0);
    }

    public function testFolderDiDalamProyek(): void
    {
        $this->assertTrue(PembuatCadangan::diDalam('/srv/milklife', '/srv/milklife'));
        $this->assertTrue(PembuatCadangan::diDalam('/srv/milklife/writable/cadangan', '/srv/milklife/'));
        $this->assertFalse(PembuatCadangan::diDalam('/srv/milklife-cadangan', '/srv/milklife'));
        $this->assertFalse(PembuatCadangan::diDalam('/srv', '/srv/milklife'));
        $this->assertTrue(PembuatCadangan::diDalam('C:\laragon\www\milklife\cadangan', 'C:\laragon\www\milklife'));
        $this->assertFalse(PembuatCadangan::diDalam('D:\cadangan-milklife', 'C:\laragon\www\milklife'));
    }
}
