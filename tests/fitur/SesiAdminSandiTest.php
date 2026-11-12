<?php

use App\Controllers\Admin\AkunController;
use App\Libraries\SesiAdmin;
use App\Models\AdminModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\Mock\MockInputOutput;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Ganti kata sandi mengeluarkan sesi lain milik akun yang sama (K67, F7-02).
 *
 * Uji ini memakai `SesiAdmin` sungguhan: sidik di sesi dibandingkan dengan
 * hash di tabel `admin` uji. Setiap "sesi" adalah isi `$_SESSION` yang
 * disimpan, seperti dua peramban yang masuk dengan akun yang sama. Nama dan
 * kata sandi karangan.
 *
 * @internal
 */
final class SesiAdminSandiTest extends UjiBasisData
{
    use KirimFormulir;

    private const SANDI_UJI  = 'kata-sandi-uji-1';
    private const SANDI_BARU = 'sandi-baru-uji-2';

    private int $idAdmin;
    private int $idAdminLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pakaiSesiAdminAsli();
        Services::resetSingle('throttler');
        service('cache')->clean();

        $model             = model(AdminModel::class, false);
        $this->idAdmin     = (int) $model->insert(['nama' => 'Admin Karangan', 'nama_pengguna' => 'admin.uji', 'sandi' => self::SANDI_UJI]);
        $this->idAdminLain = (int) $model->insert(['nama' => 'Admin Karangan Lain', 'nama_pengguna' => 'admin.lain', 'sandi' => self::SANDI_UJI]);
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();
        parent::tearDown();
    }

    public function testMasukMenyimpanSidikPendekBukanHash(): void
    {
        $this->masuk('admin.uji', self::SANDI_UJI)->assertRedirectTo(site_url('admin/dasbor'));

        $hash  = $this->hash($this->idAdmin);
        $sidik = $_SESSION[SesiAdmin::KUNCI_SIDIK] ?? null;

        $this->assertSame(SesiAdmin::sidik($hash), $sidik);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{16}\z/', $sidik);
        $this->assertStringNotContainsString($hash, serialize($_SESSION));

        // Sesi hasil masuk langsung bisa membuka panel.
        $this->withSession($_SESSION)->get('admin/dasbor')->assertOK();
    }

    public function testGantiSandiDiSatuSesiMengeluarkanSesiLain(): void
    {
        $sesiA = $this->sesiAdminAsli($this->idAdmin);
        $sesiB = $this->sesiAdminAsli($this->idAdmin);

        // Keduanya masuk.
        $this->withSession($sesiA)->get('admin/dasbor')->assertOK();
        $this->withSession($sesiB)->get('admin/dasbor')->assertOK();

        // Sesi A mengganti kata sandi dan tetap masuk.
        $hasil = $this->gantiSandi($sesiA);
        $this->assertBerhasilDialihkan($hasil, 'admin/akun', AkunController::PESAN_SANDI_DIGANTI);
        $this->assertSame(SesiAdmin::sidik($this->hash($this->idAdmin)), $_SESSION[SesiAdmin::KUNCI_SIDIK]);
        $sesiA = $_SESSION;

        $this->withSession($sesiA)->get('admin/dasbor')->assertOK();
        $this->withSession($sesiA)->get('admin/akun')->assertOK();

        // Sesi B diminta masuk lagi dengan pesan yang menjelaskan.
        $this->assertDikeluarkan($this->withSession($sesiB)->get('admin/dasbor'), SesiAdmin::PESAN_SANDI_DIGANTI);
        $this->withSession($_SESSION)->get('admin/masuk')->assertSee(SesiAdmin::PESAN_SANDI_DIGANTI);

        // Kiriman formulir dari sesi B juga ditolak, dan tidak mengubah apa pun.
        $this->assertDikeluarkan(
            $this->kirimSebagaiAdmin('admin/akun', ['nama' => 'Nama Sisipan', 'nama_pengguna' => 'admin.uji'], $sesiB),
            SesiAdmin::PESAN_SANDI_DIGANTI,
        );
        $this->assertSame('Admin Karangan', $this->baris($this->idAdmin)['nama']);

        // Sesi B masuk lagi dengan kata sandi baru, lalu terbuka lagi.
        $this->masuk('admin.uji', self::SANDI_BARU)->assertRedirectTo(site_url('admin/dasbor'));
        $this->withSession($_SESSION)->get('admin/dasbor')->assertOK();
    }

    public function testSesiAkunLainTidakIkutKeluar(): void
    {
        $sesiLain = $this->sesiAdminAsli($this->idAdminLain, 'Admin Karangan Lain');

        $this->gantiSandi($this->sesiAdminAsli($this->idAdmin))->assertRedirectTo(site_url('admin/akun'));

        $this->withSession($sesiLain)->get('admin/dasbor')->assertOK();
    }

    public function testGantiNamaTanpaGantiSandiTidakMengeluarkanSesiLain(): void
    {
        $sesiA = $this->sesiAdminAsli($this->idAdmin);
        $sesiB = $this->sesiAdminAsli($this->idAdmin);

        $this->kirimSebagaiAdmin('admin/akun', ['nama' => 'Nama Baru', 'nama_pengguna' => 'admin.uji'], $sesiA)
            ->assertRedirectTo(site_url('admin/akun'));

        $this->withSession($sesiB)->get('admin/dasbor')->assertOK();
    }

    public function testGantiLewatAdminBuatMengeluarkanSemuaSesiAkunItu(): void
    {
        $sesiA    = $this->sesiAdminAsli($this->idAdmin);
        $sesiB    = $this->sesiAdminAsli($this->idAdmin);
        $sesiLain = $this->sesiAdminAsli($this->idAdminLain, 'Admin Karangan Lain');

        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        $io->setInputs(['ya', self::SANDI_BARU, self::SANDI_BARU]);

        command('admin:buat --pengguna admin.uji');

        $this->assertStringContainsString('Semua sesi yang sedang masuk dengan akun ini akan diminta masuk lagi.', $io->getOutput());
        $this->assertTrue(password_verify(self::SANDI_BARU, $this->hash($this->idAdmin)));

        foreach (['sesi A' => $sesiA, 'sesi B' => $sesiB] as $sesi) {
            $this->assertDikeluarkan($this->withSession($sesi)->get('admin/dasbor'), SesiAdmin::PESAN_SANDI_DIGANTI);
        }

        $this->withSession($sesiLain)->get('admin/dasbor')->assertOK();
    }

    public function testSesiTanpaSidikDimintaMasukLagi(): void
    {
        // Sesi yang dibuat sebelum aturan ini berlaku.
        $sesiLama = [SesiAdmin::KUNCI_ID => $this->idAdmin, SesiAdmin::KUNCI_NAMA => 'Admin Karangan'];

        $this->assertDikeluarkan($this->withSession($sesiLama)->get('admin/dasbor'), SesiAdmin::PESAN_TANPA_SIDIK);
    }

    public function testAkunYangDihapusDikeluarkanDariSemuaHalaman(): void
    {
        $sesi = $this->sesiAdminAsli($this->idAdmin);
        $this->db->table('admin')->where('id', $this->idAdmin)->delete();

        $this->assertDikeluarkan($this->withSession($sesi)->get('admin/pendaftaran'), SesiAdmin::PESAN_AKUN_HILANG);
    }

    public function testAlamatTujuanTetapDiingatSetelahDikeluarkan(): void
    {
        $sesiB = $this->sesiAdminAsli($this->idAdmin);
        $this->gantiSandi($this->sesiAdminAsli($this->idAdmin));

        $this->withSession($sesiB)->get('admin/pendaftaran?status=lunas');

        $this->assertSame('admin/pendaftaran?status=lunas', $_SESSION['admin_tujuan'] ?? null);
        $this->assertSame(site_url('admin/pendaftaran?status=lunas'), $this->masuk('admin.uji', self::SANDI_BARU, $_SESSION)->getRedirectUrl());
    }

    public function testHalamanMasukDenganSesiLamaMengosongkanSesi(): void
    {
        $sesiB = $this->sesiAdminAsli($this->idAdmin);
        $this->gantiSandi($this->sesiAdminAsli($this->idAdmin));

        // Bukan dialihkan ke dasbor seperti sesi yang sah.
        $this->assertDikeluarkan($this->withSession($sesiB)->get('admin/masuk'), SesiAdmin::PESAN_SANDI_DIGANTI);

        // Kiriman formulir masuk dari sesi lama tetap diproses.
        $hasil = $this->masuk('admin.uji', self::SANDI_BARU, $sesiB);
        $hasil->assertRedirectTo(site_url('admin/dasbor'));
        $this->assertSame(SesiAdmin::sidik($this->hash($this->idAdmin)), $_SESSION[SesiAdmin::KUNCI_SIDIK]);
    }

    public function testMasukDenganHashLamaMemakaiSidikHashBaru(): void
    {
        $hashLama = password_hash(self::SANDI_UJI, PASSWORD_BCRYPT, ['cost' => 4]);
        $this->db->table('admin')->where('id', $this->idAdmin)->update(['sandi' => $hashLama]);

        $this->masuk('admin.uji', self::SANDI_UJI)->assertRedirectTo(site_url('admin/dasbor'));

        $this->assertNotSame($hashLama, $this->hash($this->idAdmin));
        $this->withSession($_SESSION)->get('admin/dasbor')->assertOK();
    }

    // --- Bantuan --------------------------------------------------------------

    private function assertDikeluarkan(TestResponse $hasil, string $pesan): void
    {
        $hasil->assertRedirectTo(site_url('admin/masuk'));
        $hasil->assertSessionMissing(SesiAdmin::KUNCI_ID);
        $hasil->assertSessionMissing(SesiAdmin::KUNCI_SIDIK);
        $hasil->assertSessionHas('pesan_masuk', $pesan);
        $this->assertTrue(service('session')->didRegenerate, 'ID sesi harus dibuat ulang saat dikeluarkan.');
    }

    /**
     * @param array<string, mixed> $sesi
     */
    private function gantiSandi(array $sesi): TestResponse
    {
        return $this->kirimSebagaiAdmin('admin/akun/sandi', [
            'sandi_saat_ini' => self::SANDI_UJI,
            'sandi_baru'     => self::SANDI_BARU,
            'ulangi_sandi'   => self::SANDI_BARU,
        ], $sesi);
    }

    /**
     * Masuk lewat formulir dengan token CSRF sah.
     *
     * @param array<string, mixed> $sesi
     */
    private function masuk(string $namaPengguna, string $sandi, array $sesi = []): TestResponse
    {
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));
        Services::resetSingle('security');

        return $this->withSession([$namaToken => $token] + $sesi)->post('admin/masuk', [
            $namaToken      => $token,
            'nama_pengguna' => $namaPengguna,
            'sandi'         => $sandi,
        ]);
    }

    private function hash(int $id): string
    {
        return (string) $this->baris($id)['sandi'];
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(int $id): array
    {
        return $this->db->table('admin')->where('id', $id)->get()->getRowArray() ?? [];
    }
}
