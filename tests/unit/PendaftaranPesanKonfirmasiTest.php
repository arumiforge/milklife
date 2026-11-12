<?php

use App\Libraries\Pendaftaran\PesanKonfirmasi;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Pembayaran;

/**
 * Pesan WA konfirmasi pembayaran (F4-04, rancangan 7 langkah 5).
 *
 * @internal
 */
final class PendaftaranPesanKonfirmasiTest extends CIUnitTestCase
{
    private const DATA = [
        'nama_sekolah'      => 'SD Karangan Maju',
        'npsn'              => '99999911',
        'id_pendaftaran'    => 'MAC2A0000126',
        'subtotal_individu' => 450000,
        'subtotal_beregu'   => 100000,
        'total'             => 550000,
    ];

    public function testTemplatBawaan(): void
    {
        $teks = PesanKonfirmasi::teks((new Pembayaran())->templat_pesan, self::DATA);

        $this->assertStringContainsString("Nama sekolah: SD Karangan Maju\n", $teks);
        $this->assertStringContainsString("NPSN: 99999911\n", $teks);
        $this->assertStringContainsString("ID pendaftaran: MAC2A0000126\n", $teks);
        $this->assertStringContainsString("Subtotal individu: Rp450.000\n", $teks);
        $this->assertStringContainsString("Subtotal beregu: Rp100.000\n", $teks);
        $this->assertStringContainsString("Total: Rp550.000\n", $teks);
        $this->assertStringNotContainsString('{', $teks);
    }

    public function testNpsnKosongDanPenandaTidakDikenal(): void
    {
        $this->assertSame('NPSN - {lain}', PesanKonfirmasi::teks('NPSN {npsn} {lain}', ['npsn' => null] + self::DATA));
    }

    public function testAlamatWa(): void
    {
        $this->assertSame('https://wa.me/6281200000099?text=Halo%20Admin%0ATotal%3A%20Rp1.000', PesanKonfirmasi::alamat('0812-0000-0099', "Halo Admin\nTotal: Rp1.000"));
        $this->assertSame('https://wa.me/6281200000099', PesanKonfirmasi::alamat('6281200000099', ''));
        $this->assertNull(PesanKonfirmasi::alamat('', 'Halo'));
        $this->assertNull(PesanKonfirmasi::alamat(null, 'Halo'));
    }
}
