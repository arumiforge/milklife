<?php

use App\Libraries\Panel\StatusPembayaran;
use App\Libraries\Panel\UrutkanNomorDada;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\Mock\MockInputOutput;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Urutkan ulang nomor dada per sekolah (K111, `UrutkanNomorDada`).
 *
 * Awalan uji: KU 10 → 2, KU 12 → 3, KU 15 → 4 (`PanelDataContoh`).
 *
 * @internal
 */
final class PanelUrutkanNomorDadaTest extends UjiBasisData
{
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testNomorSatuSekolahBerurutanMenurutSekolahYangMendaftarDulu(): void
    {
        $status = new StatusPembayaran();
        $x      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel()]]);                                   // sekolah X, atlet 1–2
        $y      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel()]]); // sekolah Y, atlet 3
        $x2     = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);                                                        // susulan X, atlet 4
        $tanpa  = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD TANPA NPSN'], 'atlet' => [$this->atletPanel()]]);  // atlet 5

        // Ditandai Lunas tidak berurutan: Y dan tanpa NPSN dulu, lalu X dan susulannya.
        $status->tandaiLunas($y['id']);
        $status->tandaiLunas($tanpa['id']);
        $status->tandaiLunas($x['id']);
        $status->tandaiLunas($x2['id']);   // digabung ke X (K109)

        $this->assertSame(['2003', '2004', '2001', '2002', '2005'], $this->nomor([$x['atlet'][0], $x['atlet'][1], $y['atlet'][0], $tanpa['atlet'][0], $x2['atlet'][0]]));

        $hasil = (new UrutkanNomorDada())->urutkan('Admin Uji');

        $this->assertSame(['berhasil' => true, 'pesan' => null, 'atlet' => 5, 'sekolah' => 3, 'dikosongkan' => 0], $hasil);
        // X (mendaftar pertama) 2001–2003 termasuk susulannya, lalu Y, lalu sekolah tanpa NPSN.
        $this->assertSame(
            ['2001', '2002', '2003', '2004', '2005'],
            $this->nomor([$x['atlet'][0], $x['atlet'][1], $x2['atlet'][0], $y['atlet'][0], $tanpa['atlet'][0]]),
        );
        $this->assertSame(5, (int) $this->db->table('awalan_nomor_dada')->where('awalan', '2')->get()->getRow()->nomor_terakhir);
        $this->assertNotNull(UrutkanNomorDada::waktuTerakhir());
        $this->assertSame('Admin Uji', setting('NomorDada.diurutkan_oleh'));

        // Pendaftaran Lunas berikutnya melanjutkan nomor terakhir.
        $baru = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN BARU'], 'atlet' => [$this->atletPanel()]]);
        $status->tandaiLunas($baru['id']);
        $this->assertSame(['2006'], $this->nomor($baru['atlet']));
    }

    public function testSetiapKelompokUmurMulaiDariAwalanSendiriDanYangMenungguDikosongkan(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel(['atlet' => [
            $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-03', 'nomor' => ['lari60putraKu12']]),
            $this->atletPanel(),
        ]]);
        $b = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel()]]);
        $status->tandaiLunas($b['id']);
        $status->tandaiLunas($a['id']);
        $status->kembaliMenunggu($b['id']);   // B memegang 2001 tetapi kembali Menunggu

        $hasil = (new UrutkanNomorDada())->urutkan('Admin Uji');

        $this->assertSame(1, $hasil['dikosongkan']);
        $this->assertSame(['3001', '2001'], $this->nomor($a['atlet']));
        $this->assertSame([null], $this->nomor($b['atlet']));

        // B Lunas lagi: nomor baru melanjutkan.
        $status->tandaiLunas($b['id']);
        $this->assertSame(['2002'], $this->nomor($b['atlet']));
    }

    public function testAwalanTidakCukupTidakMengubahApaPun(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel()]]);
        $status->tandaiLunas($a['id']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->delete();

        $hasil = (new UrutkanNomorDada())->urutkan('Admin Uji');

        $this->assertFalse($hasil['berhasil']);
        $this->assertStringContainsString('KU 10 belum punya awalan nomor atlet (BIB)', (string) $hasil['pesan']);
        $this->assertSame(['2001', '2002'], $this->nomor($a['atlet']));
        $this->assertNull(UrutkanNomorDada::waktuTerakhir());
    }

    public function testRingkasanDanKeterangan(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-03', 'nomor' => ['lari60putraKu12']])]]);
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel()]]);
        $status->tandaiLunas($a['id']);

        $ringkasan = (new UrutkanNomorDada())->ringkasan();

        $this->assertSame(2, $ringkasan['atlet']);
        $this->assertSame(1, $ringkasan['sekolah']);
        $this->assertSame(0, $ringkasan['menunggu_bernomor']);
        $this->assertSame([['nama' => 'KU 10', 'atlet' => 1, 'awalan' => '2', 'cukup' => true], ['nama' => 'KU 12', 'atlet' => 1, 'awalan' => '3', 'cukup' => true]], $ringkasan['per_kelompok_umur']);
        $this->assertNull($ringkasan['diurutkan_pada']);

        $this->assertNull(UrutkanNomorDada::keterangan('lunas'));
        setting('NomorDada.diurutkan_pada', '2026-12-05 10:15:00');
        $this->assertStringStartsWith('Nomor atlet (BIB) sudah diurutkan ulang per sekolah oleh panitia pada 5 Desember 2026, supaya', (string) UrutkanNomorDada::keterangan('lunas'));
        $this->assertNull(UrutkanNomorDada::keterangan('menunggu'));
    }

    public function testResetNomorDadaMenghapusKeterangan(): void
    {
        $a = $this->buatPendaftaranPanel();
        (new StatusPembayaran())->tandaiLunas($a['id']);
        (new UrutkanNomorDada())->urutkan('Admin Uji');
        $this->assertNotNull(UrutkanNomorDada::waktuTerakhir());

        CLI::setInputOutput(new MockInputOutput());
        command('nomor-dada:reset --ya');

        $this->assertNull(UrutkanNomorDada::waktuTerakhir());
    }

    /**
     * @param list<int> $atlet
     *
     * @return list<string|null> nomor dada, urut seperti `$atlet`
     */
    private function nomor(array $atlet): array
    {
        $ada = array_column($this->db->table('atlet')->select('id, nomor_dada')->whereIn('id', $atlet)->get()->getResultArray(), 'nomor_dada', 'id');

        return array_map(static fn (int $id): ?string => $ada[$id] ?? null, $atlet);
    }
}
