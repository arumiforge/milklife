<?php

use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman ganti atlet di panel (`admin/pendaftaran/<id>/ganti-atlet/<id atlet>`)
 * dan daftar atlet yang diganti di rincian pendaftaran (F11-01, K172).
 *
 * @internal
 */
final class PanelGantiAtletHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function pendaftaranLunas(): array
    {
        return $this->buatPendaftaranPanel([
            'status' => 'lunas',
            'atlet'  => [$this->atletPanel(['nama' => 'Budi Karangan', 'nomor_dada' => '2001'])],
        ]);
    }

    public function testRincianLunasMenautkanGantiAtlet(): void
    {
        $p = $this->pendaftaranLunas();

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);

        $hasil->assertOK();
        $this->assertStringContainsString(site_url('admin/pendaftaran/' . $p['id'] . '/ganti-atlet/' . $p['atlet'][0]), $hasil->getBody());

        // Pendaftaran Menunggu tidak punya tautan Ganti.
        $menunggu = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);
        $this->assertStringNotContainsString('data-ganti-atlet', $this->bukaSebagaiAdmin('admin/pendaftaran/' . $menunggu['id'])->getBody());
    }

    public function testFormulirMenampilkanAtletDanNomorYangDiwariskan(): void
    {
        $p = $this->pendaftaranLunas();

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/ganti-atlet/' . $p['atlet'][0]);

        $hasil->assertOK();
        $hasil->assertSee('Budi Karangan');
        $hasil->assertSee('Lari 60 m putra');
        $hasil->assertSee('Atlet pengganti');
        $this->assertStringContainsString('name="nisn"', $hasil->getBody());
    }

    public function testFormulirAtletTakDikenalTidakDitemukan(): void
    {
        $p = $this->pendaftaranLunas();

        $this->expectException(CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/ganti-atlet/999999');
    }

    public function testGantiLaluRincianMenampilkanCatatan(): void
    {
        $p = $this->pendaftaranLunas();

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/ganti-atlet/' . $p['atlet'][0], [
            'nama'          => 'Dewi Karangan',
            'nisn'          => '9900199001',
            'tanggal_lahir' => '2017-09-09',
            'alasan'        => 'Cedera lutut',
        ]);

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran/' . $p['id'],
            'Budi Karangan sudah diganti DEWI KARANGAN dengan nomor atlet (BIB) baru 2002.'
            . ' Nomor lomba, regu, dan tempatnya di seri ikut pindah. Cetak BIB baru di menu Cetak BIB, dan cetak ulang daftar seri bila sudah dibagikan.',
        );

        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $rincian->assertSee('Atlet diganti');
        $rincian->assertSee('Cedera lutut');
        $rincian->assertSee('BIB 2002');
    }

    public function testGalatIsianTampilDiFormulir(): void
    {
        $p = $this->pendaftaranLunas();

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/ganti-atlet/' . $p['atlet'][0], [
            'nama'          => 'Dewi Karangan',
            'nisn'          => '12345',
            'tanggal_lahir' => '2017-09-09',
            'alasan'        => 'Cedera lutut',
        ]);

        $hasil->assertOK();
        $hasil->assertSee('NISN harus tepat 10 angka');
        $this->assertSame(1, $this->db->table('atlet')->where('id', $p['atlet'][0])->countAllResults());
    }
}
