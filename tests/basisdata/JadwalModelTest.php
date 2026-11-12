<?php

use App\Models\JadwalModel;
use Tests\Support\UjiBasisData;

/**
 * `JadwalModel` (F2-06) terhadap tabel `jadwal` sungguhan.
 *
 * @internal
 */
final class JadwalModelTest extends UjiBasisData
{
    private JadwalModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new JadwalModel();
    }

    public function testSimpanMengisiWaktuDibuatDanDiubah(): void
    {
        $id = $this->model->insert(['judul' => 'Technical Meeting', 'urutan' => 1]);

        $jadwal = $this->model->find($id);
        $this->assertSame('Technical Meeting', $jadwal['judul']);
        $this->assertNull($jadwal['waktu']);
        $this->assertNull($jadwal['tautan']);
        $this->assertSame('1', (string) $jadwal['tampil'], 'Jadwal baru tampil bila tidak disebut lain.');
        $this->assertNotNull($jadwal['dibuat_pada']);
        $this->assertNotNull($jadwal['diubah_pada']);
    }

    public function testKolomDiLuarDaftarTidakIkutTersimpan(): void
    {
        $id = $this->model->insert(['judul' => 'Sosialisasi', 'id' => 99, 'dibuat_pada' => '2000-01-01 00:00:00']);

        $this->assertNotSame(99, (int) $id);
        $this->assertNotSame('2000-01-01 00:00:00', $this->model->find($id)['dibuat_pada']);
    }

    public function testDaftarMenurutUrutanLaluWaktuYangKosongDiBelakang(): void
    {
        $this->model->insert(['judul' => 'C tanpa waktu', 'urutan' => 1, 'waktu' => null]);
        $this->model->insert(['judul' => 'B lebih siang', 'urutan' => 1, 'waktu' => '2026-11-20 13:00:00']);
        $this->model->insert(['judul' => 'A lebih pagi', 'urutan' => 1, 'waktu' => '2026-11-20 09:00:00']);
        $this->model->insert(['judul' => 'E urutan 2', 'urutan' => 2, 'waktu' => '2026-11-01 08:00:00']);
        $this->model->insert(['judul' => 'D urutan 0', 'urutan' => 0, 'waktu' => '2026-12-01 08:00:00', 'tampil' => 0]);
        $this->model->insert(['judul' => 'F tanpa waktu, dibuat belakangan', 'urutan' => 1, 'waktu' => null]);

        $this->assertSame(
            ['D urutan 0', 'A lebih pagi', 'B lebih siang', 'C tanpa waktu', 'F tanpa waktu, dibuat belakangan', 'E urutan 2'],
            array_column($this->model->daftar(), 'judul'),
        );
        $this->assertSame(
            ['A lebih pagi', 'B lebih siang', 'C tanpa waktu', 'F tanpa waktu, dibuat belakangan', 'E urutan 2'],
            array_column($this->model->daftar(true), 'judul'),
            'Hanya jadwal yang tampil.',
        );
    }

    public function testUrutanBerikutnya(): void
    {
        $this->assertSame(1, $this->model->urutanBerikutnya(), 'Daftar kosong mulai dari 1.');

        $this->model->insert(['judul' => 'Satu', 'urutan' => 4]);
        $dua = $this->model->insert(['judul' => 'Dua', 'urutan' => 7]);

        $this->assertSame(8, $this->model->urutanBerikutnya());
        $this->assertSame(5, $this->model->urutanBerikutnya((int) $dua), 'Jadwal yang sedang diubah tidak ikut dihitung.');

        $this->model->insert(['judul' => 'Tiga', 'urutan' => JadwalModel::URUTAN_MAKSIMAL]);
        $this->assertSame(JadwalModel::URUTAN_MAKSIMAL, $this->model->urutanBerikutnya(), 'Tidak melewati batas.');
    }
}
