<?php

use App\Models\AdminModel;
use Tests\Support\UjiBasisData;

/**
 * Tabel `admin` dan `AdminModel`. Data uji memakai nama karangan dan kata
 * sandi uji yang bukan rahasia.
 *
 * @internal
 */
final class AdminModelTest extends UjiBasisData
{
    private const SANDI_UJI = 'kata-sandi-uji-1';

    private AdminModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = model(AdminModel::class, false);
    }

    public function testTabelAdminSesuaiKesepakatan(): void
    {
        $kolom = array_column($this->db->getFieldData('admin'), null, 'name');

        $this->assertSame(['id', 'nama', 'nama_pengguna', 'sandi', 'dibuat_pada', 'diubah_pada'], array_keys($kolom));
        $this->assertSame(255, (int) $kolom['sandi']->max_length);

        $tabel = $this->db->query(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['admin'],
        )->getRowArray();
        $this->assertSame('InnoDB', $tabel['ENGINE']);
        $this->assertSame('utf8mb4_unicode_ci', $tabel['TABLE_COLLATION']);
    }

    public function testMenyimpanHashBukanTeksAsli(): void
    {
        $id = $this->model->insert([
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => 'admin.uji',
            'sandi'         => self::SANDI_UJI,
        ]);

        $this->assertIsInt($id);

        $baris = $this->db->table('admin')->where('id', $id)->get()->getRowArray();
        $this->assertNotSame(self::SANDI_UJI, $baris['sandi']);
        $this->assertStringNotContainsString(self::SANDI_UJI, $baris['sandi']);
        $this->assertTrue(password_verify(self::SANDI_UJI, $baris['sandi']));
        $this->assertSame(PASSWORD_DEFAULT, password_get_info($baris['sandi'])['algo']);
        $this->assertNotNull($baris['dibuat_pada']);
        $this->assertNotNull($baris['diubah_pada']);
    }

    public function testSimpanSandiJugaMenyimpanHash(): void
    {
        $id = $this->buatAdmin();

        $this->assertTrue($this->model->simpanSandi($id, 'sandi-uji-baru-2'));

        $hash = $this->db->table('admin')->where('id', $id)->get()->getRow()->sandi;
        $this->assertTrue(password_verify('sandi-uji-baru-2', $hash));
        $this->assertFalse(password_verify(self::SANDI_UJI, $hash));
    }

    public function testNamaPenggunaDinormalkan(): void
    {
        $id = $this->buatAdmin(namaPengguna: '  Admin.Uji ');

        $this->assertSame('admin.uji', $this->model->find($id)['nama_pengguna']);
        $this->assertSame($id, (int) $this->model->cariNamaPengguna(' ADMIN.uji')['id']);
        $this->assertNull($this->model->cariNamaPengguna('tidak.ada'));
        $this->assertNull($this->model->cariNamaPengguna('   '));
    }

    public function testMenolakNamaPenggunaGanda(): void
    {
        $this->buatAdmin();

        $hasil = $this->model->insert([
            'nama'          => 'Admin Karangan Kedua',
            'nama_pengguna' => 'ADMIN.UJI',
            'sandi'         => self::SANDI_UJI,
        ]);

        $this->assertFalse($hasil);
        $this->assertSame('Nama pengguna ini sudah dipakai. Pilih nama pengguna lain.', $this->model->errors()['nama_pengguna']);
        $this->assertSame(1, $this->db->table('admin')->countAllResults());
    }

    public function testMenolakNamaPenggunaTidakSah(): void
    {
        foreach (['ab', 'nama pakai spasi', 'tanda-hubung', str_repeat('a', 31), 'surel@contoh'] as $namaPengguna) {
            $hasil = $this->model->insert([
                'nama'          => 'Admin Karangan',
                'nama_pengguna' => $namaPengguna,
                'sandi'         => self::SANDI_UJI,
            ]);

            $this->assertFalse($hasil, $namaPengguna);
            $this->assertArrayHasKey('nama_pengguna', $this->model->errors(), $namaPengguna);
        }

        $this->assertSame(0, $this->db->table('admin')->countAllResults());
    }

    public function testMenolakKataSandiPendek(): void
    {
        $hasil = $this->model->insert([
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => 'admin.uji',
            'sandi'         => '1234567',
        ]);

        $this->assertFalse($hasil);
        $this->assertSame(
            'Kata sandi terlalu pendek. Buat kata sandi paling sedikit 8 karakter.',
            $this->model->errors()['sandi'],
        );
    }

    public function testPeriksaIsianTanpaMenyimpan(): void
    {
        $this->buatAdmin();

        $this->assertArrayHasKey('nama_pengguna', $this->model->periksaIsian(['nama_pengguna' => 'admin.uji']));
        $this->assertSame([], $this->model->periksaIsian(['nama_pengguna' => 'Admin.Uji'], true));
        $this->assertSame([], $this->model->periksaIsian(['sandi' => '12345678']));
        $this->assertArrayHasKey('sandi', $this->model->periksaIsian(['sandi' => 'pendek']));
        $this->assertSame(1, $this->db->table('admin')->countAllResults());
    }

    private function buatAdmin(string $namaPengguna = 'admin.uji'): int
    {
        $id = $this->model->insert([
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => $namaPengguna,
            'sandi'         => self::SANDI_UJI,
        ]);
        $this->assertIsInt($id);

        return $id;
    }
}
