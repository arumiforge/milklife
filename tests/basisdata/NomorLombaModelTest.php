<?php

use App\Models\NomorLombaModel;
use Tests\Support\BarisUjiNomorLomba;
use Tests\Support\UjiBasisData;

/**
 * `NomorLombaModel` dengan basis data: daftar berurutan, pemakaian, nama
 * kembar, dan urutan berikutnya.
 *
 * @internal
 */
final class NomorLombaModelTest extends UjiBasisData
{
    use BarisUjiNomorLomba;

    private NomorLombaModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new NomorLombaModel();
    }

    public function testDaftarLengkapBerurutanPerKelompokUmur(): void
    {
        // KU 12 dibuat lebih dulu tetapi urutannya sesudah KU 10.
        $ku12 = $this->buatKelompokUmur('KU 12', ['urutan' => 2]);
        $ku10 = $this->buatKelompokUmur('KU 10', ['urutan' => 1, 'aktif' => 0]);

        $this->buatNomorLomba($ku12, ['nama' => 'Trilomba', 'urutan' => 1]);
        $this->buatNomorLomba($ku10, ['nama' => 'Frog Jump', 'urutan' => 2]);
        $this->buatNomorLomba($ku10, ['nama' => 'Lari 60 m', 'urutan' => 1]);
        $this->buatNomorLomba($ku10, ['nama' => 'Turbo Throw', 'urutan' => 2]);

        $daftar = $this->model->daftarLengkap();

        $this->assertSame(['Lari 60 m', 'Frog Jump', 'Turbo Throw', 'Trilomba'], array_column($daftar, 'nama'));
        $this->assertSame(['KU 10', 'KU 10', 'KU 10', 'KU 12'], array_column($daftar, 'nama_kelompok_umur'));
        $this->assertSame('0', (string) $daftar[0]['kelompok_umur_aktif']);
        $this->assertSame('1', (string) $daftar[3]['kelompok_umur_aktif']);
    }

    public function testDipakaiOlehAtletAtauTim(): void
    {
        $ku     = $this->buatKelompokUmur('KU 10');
        $bebas  = $this->buatNomorLomba($ku);
        $atlet  = $this->buatNomorLomba($ku, ['nama' => 'Frog Jump']);
        $tim    = $this->buatNomorLomba($ku, ['nama' => 'Formula 1', 'jenis' => 'beregu', 'jumlah_inti' => 4, 'biaya_beregu' => 100000]);

        $this->pakaiOlehAtlet($atlet, $ku);
        $this->pakaiOlehTim($tim);

        $this->assertFalse($this->model->dipakai($bebas));
        $this->assertTrue($this->model->dipakai($atlet));
        $this->assertTrue($this->model->dipakai($tim));
        $this->assertSame([$atlet => true, $tim => true], $this->model->idDipakai());
    }

    public function testAdaKembarDiKelompokUmurDanKategoriYangSama(): void
    {
        $ku10 = $this->buatKelompokUmur('KU 10');
        $ku12 = $this->buatKelompokUmur('KU 12');
        $id   = $this->buatNomorLomba($ku10, ['nama' => 'Lari 60 m', 'kategori' => 'putra']);

        $this->assertTrue($this->model->adaKembar($ku10, 'Lari 60 m', 'putra'));
        $this->assertTrue($this->model->adaKembar($ku10, 'LARI 60 M', 'putra'), 'Huruf besar-kecil diabaikan.');
        $this->assertFalse($this->model->adaKembar($ku10, 'Lari 60 m', 'putri'));
        $this->assertFalse($this->model->adaKembar($ku12, 'Lari 60 m', 'putra'));
        $this->assertFalse($this->model->adaKembar($ku10, 'Lari 60 m', 'putra', $id), 'Nomor itu sendiri tidak dihitung.');
    }

    public function testUrutanBerikutnya(): void
    {
        $ku10 = $this->buatKelompokUmur('KU 10');
        $ku12 = $this->buatKelompokUmur('KU 12');

        $this->assertSame(1, $this->model->urutanBerikutnya($ku10));

        $this->buatNomorLomba($ku10, ['urutan' => 4]);
        $this->buatNomorLomba($ku10, ['nama' => 'Frog Jump', 'urutan' => 9]);
        $this->buatNomorLomba($ku12, ['urutan' => 30]);

        $this->assertSame(10, $this->model->urutanBerikutnya($ku10));
        $this->assertSame(31, $this->model->urutanBerikutnya($ku12));

        $this->buatNomorLomba($ku12, ['nama' => 'Trilomba', 'urutan' => NomorLombaModel::URUTAN_MAKSIMAL]);
        $this->assertSame(NomorLombaModel::URUTAN_MAKSIMAL, $this->model->urutanBerikutnya($ku12));
    }

    public function testSimpanMengisiWaktu(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = (int) $this->model->insert(NomorLombaModel::untukDisimpan(NomorLombaModel::rapikanIsian([
            'kelompok_umur_id' => (string) $ku, 'nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putri', 'urutan' => '3', 'aktif' => '1',
        ])));

        $baris = $this->model->find($id);
        $this->assertSame('putri', $baris['kategori']);
        $this->assertNotNull($baris['dibuat_pada']);
        $this->assertNotNull($baris['diubah_pada']);
        $this->assertNull($baris['jumlah_inti']);
    }
}
