<?php

use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\Mock\MockInputOutput;
use Tests\Support\UjiBasisData;

/**
 * Perintah `php spark admin:buat`. Jawaban diberikan lewat masukan tiruan,
 * sama seperti bila perintah dipakai dari skrip.
 *
 * @internal
 */
final class BuatAdminTest extends UjiBasisData
{
    private MockInputOutput $io;

    protected function setUp(): void
    {
        parent::setUp();

        $this->io = new MockInputOutput();
        CLI::setInputOutput($this->io);
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();

        parent::tearDown();
    }

    public function testMembuatAdminDenganHash(): void
    {
        $this->io->setInputs(['kata-sandi-uji-1', 'kata-sandi-uji-1']);

        command('admin:buat --nama "Admin Karangan" --pengguna Admin.Uji');

        $admin = $this->db->table('admin')->get()->getRowArray();
        $this->assertSame('Admin Karangan', $admin['nama']);
        $this->assertSame('admin.uji', $admin['nama_pengguna']);
        $this->assertNotSame('kata-sandi-uji-1', $admin['sandi']);
        $this->assertTrue(password_verify('kata-sandi-uji-1', $admin['sandi']));
        $this->assertStringContainsString('Admin "admin.uji" sudah dibuat.', $this->io->getOutput());
    }

    public function testMenanyakanNamaDanNamaPengguna(): void
    {
        $this->io->setInputs(['admin uji', 'admin.uji', 'Admin Karangan', 'kata-sandi-uji-1', 'kata-sandi-uji-1']);

        command('admin:buat');

        $keluaran = $this->io->getOutput();
        $this->assertStringContainsString('Nama pengguna hanya boleh berisi huruf, angka, titik, dan garis bawah', $keluaran);
        $this->assertSame(1, $this->db->table('admin')->where('nama_pengguna', 'admin.uji')->countAllResults());
    }

    public function testMenolakKataSandiPendek(): void
    {
        $this->io->setInputs(['pendek', 'pendek1', '1234567']);

        command('admin:buat --nama "Admin Karangan" --pengguna admin.uji');

        $this->assertStringContainsString('Kata sandi terlalu pendek', $this->io->getOutput());
        $this->assertSame(0, $this->db->table('admin')->countAllResults());
    }

    public function testMenolakKataSandiYangTidakSama(): void
    {
        $this->io->setInputs([
            'kata-sandi-uji-1', 'kata-sandi-uji-2',
            'kata-sandi-uji-1', 'kata-sandi-uji-1',
        ]);

        command('admin:buat --nama "Admin Karangan" --pengguna admin.uji');

        $this->assertStringContainsString('Kedua kata sandi tidak sama', $this->io->getOutput());
        $hash = $this->db->table('admin')->get()->getRow()->sandi;
        $this->assertTrue(password_verify('kata-sandi-uji-1', $hash));
    }

    public function testMenggantiKataSandiAdminYangSudahAda(): void
    {
        $id = $this->buatAdmin();
        $this->io->setInputs(['ya', 'sandi-uji-baru-2', 'sandi-uji-baru-2']);

        command('admin:buat --pengguna ADMIN.UJI');

        $this->assertSame(1, $this->db->table('admin')->countAllResults());
        $admin = $this->db->table('admin')->where('id', $id)->get()->getRowArray();
        $this->assertSame('Admin Karangan', $admin['nama']);
        $this->assertTrue(password_verify('sandi-uji-baru-2', $admin['sandi']));
        $this->assertStringContainsString('Kata sandi admin "admin.uji" sudah diganti.', $this->io->getOutput());
    }

    public function testTidakMenggantiBilaDijawabTidak(): void
    {
        $id = $this->buatAdmin();
        $this->io->setInputs(['tidak']);

        command('admin:buat --pengguna admin.uji');

        $hash = $this->db->table('admin')->where('id', $id)->get()->getRow()->sandi;
        $this->assertTrue(password_verify('kata-sandi-uji-1', $hash));
        $this->assertStringContainsString('Tidak ada yang diubah.', $this->io->getOutput());
    }

    private function buatAdmin(): int
    {
        return model('AdminModel', false)->insert([
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => 'admin.uji',
            'sandi'         => 'kata-sandi-uji-1',
        ]);
    }
}
