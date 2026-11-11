<?php

use App\Models\SekolahModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Events\Events;
use Tests\Support\UjiBasisData;

/**
 * Tabel `sekolah` dan `SekolahModel`. Semua sekolah di sini karangan.
 *
 * @internal
 */
final class SekolahModelTest extends UjiBasisData
{
    private SekolahModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new SekolahModel($this->db);
    }

    public function testMigrasiMembuatTabelDanKolom(): void
    {
        $this->assertTrue($this->db->tableExists('sekolah'));
        $this->assertSame(
            ['id', 'npsn', 'nama', 'bentuk', 'jenjang', 'status', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten', 'dibuat_pada', 'diubah_pada'],
            $this->db->getFieldNames('sekolah'),
        );

        $tabel = $this->db->query(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['sekolah'],
        )->getRowArray();
        $this->assertSame(['ENGINE' => 'InnoDB', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'], $tabel);

        $indeks = [];

        foreach ($this->db->getIndexData('sekolah') as $nama => $data) {
            $indeks[$nama] = [$data->type, $data->fields];
        }

        $this->assertSame(['UNIQUE', ['npsn']], $indeks['sekolah_npsn_unik']);
        $this->assertSame(['INDEX', ['jenjang', 'kabupaten']], $indeks['sekolah_jenjang_kabupaten']);
    }

    public function testNpsnGandaDitolakBasisData(): void
    {
        $this->model->insert($this->sekolahKarangan());

        $this->expectException(DatabaseException::class);

        $this->model->insert($this->sekolahKarangan(['nama' => 'SD KARANGAN KEMBAR']));
    }

    public function testSimpanMengisiCapWaktu(): void
    {
        $id = $this->model->insert($this->sekolahKarangan());

        $sekolah = $this->model->find($id);
        $this->assertNotNull($sekolah['dibuat_pada']);
        $this->assertNotNull($sekolah['diubah_pada']);
    }

    public function testCariNpsnMenemukanSekolah(): void
    {
        $this->model->insert($this->sekolahKarangan());

        $sekolah = $this->model->cariNpsn('99000001');

        $this->assertIsArray($sekolah);
        $this->assertSame('SD KARANGAN SATU', $sekolah['nama']);
        $this->assertSame('Blora', $sekolah['kabupaten']);
        $this->assertSame($sekolah, $this->model->cariNpsn(' 99000001 '));
    }

    public function testCariNpsnYangTidakAdaMenghasilkanNull(): void
    {
        $this->model->insert($this->sekolahKarangan());

        $this->assertNull($this->model->cariNpsn('99000002'));
    }

    public function testCariNpsnTidakSahDitolakTanpaKueri(): void
    {
        $this->model->insert($this->sekolahKarangan());

        $jumlahKueri = 0;
        $hitung      = static function () use (&$jumlahKueri): void {
            $jumlahKueri++;
        };
        Events::on('DBQuery', $hitung);

        try {
            foreach (['', '9900000', '990000011', '99A00001', "99000001' OR '1'='1", '9900000१'] as $masukan) {
                $this->assertNull($this->model->cariNpsn($masukan), 'Masukan: ' . $masukan);
            }
        } finally {
            Events::removeListener('DBQuery', $hitung);
        }

        $this->assertSame(0, $jumlahKueri);
    }

    /**
     * @param array<string, string> $ganti
     *
     * @return array<string, string>
     */
    private function sekolahKarangan(array $ganti = []): array
    {
        return $ganti + [
            'npsn'           => '99000001',
            'nama'           => 'SD KARANGAN SATU',
            'bentuk'         => 'SD',
            'jenjang'        => 'SD',
            'status'         => 'NEGERI',
            'alamat'         => 'Jl. Contoh No. 1',
            'desa_kelurahan' => 'Sukamaju',
            'kecamatan'      => 'Kecamatan Contoh',
            'kabupaten'      => 'Blora',
        ];
    }
}
