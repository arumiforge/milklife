<?php

use App\Models\TautanModel;
use Tests\Support\UjiBasisData;

/**
 * `TautanModel` (F2-06) terhadap tabel `tautan` sungguhan: narahubung dan
 * media sosial.
 *
 * @internal
 */
final class KontakTautanModelTest extends UjiBasisData
{
    private TautanModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new TautanModel();
    }

    public function testSimpanMengisiWaktuDibuatDanDiubah(): void
    {
        $id = $this->model->insert(['jenis' => TautanModel::NARAHUBUNG, 'label' => 'Bu Rina', 'nilai' => '6281234567890', 'urutan' => 1]);

        $kontak = $this->model->find($id);
        $this->assertSame('narahubung', $kontak['jenis']);
        $this->assertSame('6281234567890', $kontak['nilai']);
        $this->assertNotNull($kontak['dibuat_pada']);
        $this->assertNotNull($kontak['diubah_pada']);
    }

    public function testDaftarPerJenisMenurutUrutan(): void
    {
        $this->model->insert(['jenis' => TautanModel::NARAHUBUNG, 'label' => 'Pak Budi', 'nilai' => '6281200000002', 'urutan' => 2]);
        $this->model->insert(['jenis' => TautanModel::MEDIA_SOSIAL, 'label' => 'Instagram', 'nilai' => 'https://instagram.com/contoh', 'urutan' => 1]);
        $this->model->insert(['jenis' => TautanModel::NARAHUBUNG, 'label' => 'Bu Rina', 'nilai' => '6281200000001', 'urutan' => 1]);
        $this->model->insert(['jenis' => TautanModel::NARAHUBUNG, 'label' => 'Bu Sari', 'nilai' => '6281200000003', 'urutan' => 1]);

        $this->assertSame(['Bu Rina', 'Bu Sari', 'Pak Budi'], array_column($this->model->daftarJenis(TautanModel::NARAHUBUNG), 'label'));
        $this->assertSame(['Instagram'], array_column($this->model->daftarJenis(TautanModel::MEDIA_SOSIAL), 'label'));
    }

    public function testUrutanBerikutnyaPerJenis(): void
    {
        $this->assertSame(1, $this->model->urutanBerikutnya(TautanModel::NARAHUBUNG));

        $id = $this->model->insert(['jenis' => TautanModel::NARAHUBUNG, 'label' => 'Bu Rina', 'nilai' => '6281200000001', 'urutan' => 5]);
        $this->model->insert(['jenis' => TautanModel::MEDIA_SOSIAL, 'label' => 'YouTube', 'nilai' => 'https://youtube.com/@contoh', 'urutan' => 9]);

        $this->assertSame(6, $this->model->urutanBerikutnya(TautanModel::NARAHUBUNG));
        $this->assertSame(10, $this->model->urutanBerikutnya(TautanModel::MEDIA_SOSIAL));
        $this->assertSame(1, $this->model->urutanBerikutnya(TautanModel::NARAHUBUNG, (int) $id), 'Kontak yang sedang diubah tidak ikut dihitung.');
    }

    public function testJenisSamaDenganPilihanDiBasisData(): void
    {
        $baris = $this->db->query("SHOW COLUMNS FROM tautan LIKE 'jenis'")->getRowArray();

        $this->assertSame("enum('" . implode("','", array_keys(TautanModel::JENIS)) . "')", $baris['Type']);
    }
}
