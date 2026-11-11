<?php

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Fungsi `rupiah()`, `angka_rupiah()`, `tanggal_id()`, dan
 * `rentang_tanggal_id()` di `app/Helpers/aplikasi_helper.php` (F2-01).
 *
 * @internal
 */
final class FungsiBantuRupiahTanggalTest extends CIUnitTestCase
{
    public function testRupiahTanpaKomaDenganTitikRibuan(): void
    {
        $this->assertSame('Rp150.000', rupiah(150000));
        $this->assertSame('Rp0', rupiah(0));
        $this->assertSame('Rp999', rupiah(999));
        $this->assertSame('Rp1.000', rupiah(1000));
        $this->assertSame('Rp4.294.967.295', rupiah(4294967295));
        $this->assertSame('-Rp5.000', rupiah(-5000));
    }

    public function testRupiahDariTeksBasisData(): void
    {
        $this->assertSame('Rp100.000', rupiah('100000'));
        $this->assertSame('Rp100.000', rupiah(' 100000 '));
        $this->assertSame('', rupiah(null));
        $this->assertSame('', rupiah(''));
        $this->assertSame('', rupiah('12,5'));
        $this->assertSame('', rupiah('abc'));
    }

    public function testAngkaRupiahMembuangRpTitikDanSpasi(): void
    {
        $this->assertSame(150000, angka_rupiah('150000'));
        $this->assertSame(150000, angka_rupiah('150.000'));
        $this->assertSame(150000, angka_rupiah('Rp150.000'));
        $this->assertSame(150000, angka_rupiah('Rp 150.000'));
        $this->assertSame(150000, angka_rupiah('rp. 150 000'));
        $this->assertSame(0, angka_rupiah('0'));
        $this->assertSame(4294967295, angka_rupiah('4294967295'));
    }

    public function testAngkaRupiahMenolakIsianTidakSah(): void
    {
        $this->assertNull(angka_rupiah(''));
        $this->assertNull(angka_rupiah('   '));
        $this->assertNull(angka_rupiah(null));
        $this->assertNull(angka_rupiah('Rp'));
        $this->assertNull(angka_rupiah('150.000,50'), 'Koma desimal tidak diterima.');
        $this->assertNull(angka_rupiah('-5000'));
        $this->assertNull(angka_rupiah('seratus ribu'));
        $this->assertNull(angka_rupiah('4294967296'), 'Melebihi kolom INT UNSIGNED.');
        $this->assertNull(angka_rupiah('99999999999'));
    }

    public function testTanggalId(): void
    {
        $this->assertSame('30 November 2026', tanggal_id('2026-11-30'));
        $this->assertSame('1 Oktober 2026', tanggal_id('2026-10-01'));
        $this->assertSame('9 Desember 2026', tanggal_id('2026-12-09 08:00:00'));
        $this->assertSame('15 Januari 2027', tanggal_id('2027-01-15'));
        $this->assertSame('1 Mei 2026', tanggal_id('2026-05-01'));
        $this->assertSame('17 Agustus 2026', tanggal_id('2026-08-17'));
    }

    public function testTanggalIdDenganJam(): void
    {
        $this->assertSame('30 November 2026 pukul 23.59', tanggal_id('2026-11-30 23:59:00', true));
        $this->assertSame('1 Oktober 2026 pukul 00.00', tanggal_id('2026-10-01 00:00:00', true));
        $this->assertSame('1 Oktober 2026 pukul 07.05', tanggal_id('2026-10-01 07:05', true));
        $this->assertSame('1 Oktober 2026 pukul 07.05', tanggal_id('2026-10-01T07:05', true));
        $this->assertSame('1 Oktober 2026 pukul 00.00', tanggal_id('2026-10-01', true));
    }

    public function testTanggalIdDariObjekWaktu(): void
    {
        $this->assertSame('30 November 2026 pukul 23.59', tanggal_id(new DateTimeImmutable('2026-11-30 23:59:00'), true));
        $this->assertSame('9 Desember 2026', tanggal_id(Time::parse('2026-12-09 10:00:00', 'Asia/Jakarta')));
    }

    public function testTanggalIdKosongAtauTidakSah(): void
    {
        $this->assertSame('', tanggal_id(null));
        $this->assertSame('', tanggal_id(''));
        $this->assertSame('', tanggal_id('bukan tanggal'));
        $this->assertSame('', tanggal_id('2026-02-30'), 'Tanggal yang tidak ada ditolak, bukan digeser.');
        $this->assertSame('', tanggal_id('30-11-2026'));
        $this->assertSame('', tanggal_id('2026-11-30 25:00:00', true));
    }

    public function testRentangTanggalId(): void
    {
        $this->assertSame('9–12 Desember 2026', rentang_tanggal_id('2026-12-09', '2026-12-12'));
        $this->assertSame('30 November – 2 Desember 2026', rentang_tanggal_id('2026-11-30', '2026-12-02'));
        $this->assertSame('31 Desember 2026 – 1 Januari 2027', rentang_tanggal_id('2026-12-31', '2027-01-01'));
        $this->assertSame('9 Desember 2026', rentang_tanggal_id('2026-12-09', '2026-12-09'));
        $this->assertSame('9 Desember 2026', rentang_tanggal_id('2026-12-09', null));
        $this->assertSame('12 Desember 2026', rentang_tanggal_id(null, '2026-12-12'));
        $this->assertSame('', rentang_tanggal_id(null, ''));
    }
}
