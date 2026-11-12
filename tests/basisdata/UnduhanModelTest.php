<?php

use App\Models\UnduhanModel;
use Tests\Support\UjiBasisData;

/**
 * Model berkas unduhan (F2-07).
 *
 * @internal
 */
final class UnduhanModelTest extends UjiBasisData
{
    private UnduhanModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new UnduhanModel();
    }

    public function testDaftarBerurutanMenurutUrutanLaluId(): void
    {
        $c = $this->buat('C', 2);
        $a = $this->buat('A', 1);
        $b = $this->buat('B', 2, 0);
        $d = $this->buat('D', 0);

        $this->assertSame([$d, $a, $c, $b], array_map(static fn (array $baris): int => (int) $baris['id'], $this->model->daftar()));
        $this->assertSame([$d, $a, $c], array_map(static fn (array $baris): int => (int) $baris['id'], $this->model->daftar(true)));
    }

    public function testUrutanBerikutnya(): void
    {
        $this->assertSame(1, $this->model->urutanBerikutnya());

        $this->buat('A', 3);
        $this->buat('B', 1);
        $this->assertSame(4, $this->model->urutanBerikutnya());

        $this->buat('C', UnduhanModel::URUTAN_TERBESAR);
        $this->assertSame(UnduhanModel::URUTAN_TERBESAR, $this->model->urutanBerikutnya());
    }

    public function testCapWaktuDanKolomTerlindung(): void
    {
        $id = $this->model->insert([
            'judul'  => 'Flyer',
            'berkas' => 'unggahan/unduhan/' . str_repeat('a', 32) . '.pdf',
            'id'     => 99,
        ]);

        $baris = $this->model->find($id);
        $this->assertNotSame(99, (int) $baris['id'], 'Kolom id tidak boleh diisi dari luar.');
        $this->assertNotNull($baris['dibuat_pada']);
        $this->assertNotNull($baris['diubah_pada']);
        $this->assertSame(0, (int) $baris['urutan']);
        $this->assertSame(1, (int) $baris['tampil']);
    }

    private function buat(string $judul, int $urutan, int $tampil = 1): int
    {
        return (int) $this->model->insert([
            'judul'  => $judul,
            'berkas' => 'unggahan/unduhan/' . bin2hex(random_bytes(16)) . '.pdf',
            'urutan' => $urutan,
            'tampil' => $tampil,
        ]);
    }
}
