<?php

use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\Mock\MockInputOutput;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Perintah `php spark nomor-dada:reset` (K94).
 *
 * @internal
 */
final class ResetNomorDadaTest extends UjiBasisData
{
    use PanelDataContoh;

    private MockInputOutput $io;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->io = new MockInputOutput();
        CLI::setInputOutput($this->io);
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    /**
     * @param list<int> $atlet
     *
     * @return array<int, string|null> id atlet => nomor dada
     */
    private function nomorDada(array $atlet): array
    {
        return array_column($this->db->table('atlet')->select('id, nomor_dada')->whereIn('id', $atlet)->get()->getResultArray(), 'nomor_dada', 'id');
    }

    /**
     * @return array<string, mixed>
     */
    private function pendaftaran(int $id): array
    {
        return $this->db->table('pendaftaran')->where('id', $id)->get()->getRowArray();
    }

    /**
     * @return list<int>
     */
    private function nomorTerakhir(): array
    {
        return array_map('intval', array_column($this->db->table('awalan_nomor_dada')->get()->getResultArray(), 'nomor_terakhir'));
    }

    public function testMengosongkanNomorDanMulaiLagiDariAwal(): void
    {
        $p = $this->buatPendaftaranPanel();
        (new StatusPembayaran())->tandaiLunas($p['id']);
        $this->assertSame('2001', $this->nomorDada($p['atlet'])[$p['atlet'][0]]);

        $this->io->setInputs(['RESET']);
        command('nomor-dada:reset');

        $this->assertSame([null], array_values(array_unique($this->nomorDada($p['atlet']))));
        $this->assertSame('menunggu', $this->pendaftaran($p['id'])['status']);
        $this->assertNull($this->pendaftaran($p['id'])['waktu_lunas']);
        $this->assertSame([0], array_values(array_unique($this->nomorTerakhir())));

        // Ditandai Lunas lagi: nomor mulai dari awal.
        (new StatusPembayaran())->tandaiLunas($p['id']);
        $this->assertSame('2001', $this->nomorDada($p['atlet'])[$p['atlet'][0]]);
    }

    public function testTanpaKetikResetTidakAdaYangBerubah(): void
    {
        $p = $this->buatPendaftaranPanel();
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->io->setInputs(['ya']);
        command('nomor-dada:reset');

        $this->assertSame('lunas', $this->pendaftaran($p['id'])['status']);
        $this->assertSame('2001', $this->nomorDada($p['atlet'])[$p['atlet'][0]]);
    }
}
