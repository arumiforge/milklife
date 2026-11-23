<?php

use App\Filters\AdminFilter;
use App\Libraries\SesiAdmin;
use App\Models\AdminModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\Mock\MockInputOutput;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Peran juri (F11-16, K181): akun juri hanya bisa membuka Lembar juri dan
 * Akun miliknya; admin membuat akun juri di Pengaturan > Akun juri atau
 * dengan `php spark admin:buat --peran juri`.
 *
 * Memakai `SesiAdmin` sungguhan, jadi peran dibaca dari tabel `admin` uji,
 * bukan dari isi sesi. Nama dan kata sandi karangan.
 *
 * @internal
 */
final class PeranJuriTest extends UjiBasisData
{
    use KirimFormulir;

    private const SANDI = 'kata-sandi-uji-1';

    private int $idAdmin;
    private int $idJuri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pakaiSesiAdminAsli();
        Services::resetSingle('throttler');
        service('cache')->clean();

        $model         = model(AdminModel::class, false);
        $this->idAdmin = (int) $model->insert(['nama' => 'Admin Karangan', 'nama_pengguna' => 'admin.uji', 'sandi' => self::SANDI]);
        $this->idJuri  = (int) $model->insert(['nama' => 'Juri Karangan', 'nama_pengguna' => 'juri.uji', 'peran' => AdminModel::PERAN_JURI, 'sandi' => self::SANDI]);
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();
        parent::tearDown();
    }

    public function testAkunLamaDanAkunBaruBerperanAdmin(): void
    {
        $this->assertSame(AdminModel::PERAN_ADMIN, $this->baris($this->idAdmin)['peran']);
        $this->assertSame(AdminModel::PERAN_JURI, $this->baris($this->idJuri)['peran']);
        $this->assertNotSame([], model(AdminModel::class, false)->periksaIsian(['peran' => 'pengawas']));
    }

    public function testJuriMasukLangsungKeLembarJuri(): void
    {
        $this->masuk('juri.uji')->assertRedirectTo(site_url('admin/juri'));
        $this->assertSame(AdminModel::PERAN_JURI, $_SESSION[SesiAdmin::KUNCI_PERAN] ?? null);

        // Alamat tujuan yang tidak boleh dibuka juri diabaikan.
        $this->masuk('juri.uji', [AdminFilter::KUNCI_TUJUAN => 'admin/pendaftaran'])->assertRedirectTo(site_url('admin/juri'));
        $this->masuk('juri.uji', [AdminFilter::KUNCI_TUJUAN => 'admin/juri/babak/3'])->assertRedirectTo(site_url('admin/juri/babak/3'));

        $this->masuk('admin.uji')->assertRedirectTo(site_url('admin/dasbor'));
        $this->assertSame(AdminModel::PERAN_ADMIN, $_SESSION[SesiAdmin::KUNCI_PERAN] ?? null);
    }

    public function testJuriHanyaMembukaLembarJuriDanAkun(): void
    {
        $this->bukaSebagaiJuri('admin/juri')->assertOK();
        $this->bukaSebagaiJuri('admin/akun')->assertOK();

        foreach (['admin', 'admin/dasbor', 'admin/masuk'] as $alamat) {
            $hasil = $this->bukaSebagaiJuri($alamat);
            $hasil->assertRedirectTo(site_url('admin/juri'));
            $this->assertArrayNotHasKey('pesan_galat', $_SESSION ?? [], $alamat);
        }

        foreach (['admin/pendaftaran', 'admin/hasil', 'admin/pengaturan', 'admin/pengaturan/akun-juri', 'admin/pengaturan/cadangan', 'admin/rekap'] as $alamat) {
            $hasil = $this->bukaSebagaiJuri($alamat);
            $hasil->assertRedirectTo(site_url('admin/juri'));
            $hasil->assertSessionHas('pesan_galat', AdminFilter::PESAN_JURI);
        }
    }

    public function testKirimanJuriKeHalamanAdminDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/akun-juri', [
            'nama'          => 'Juri Siluman',
            'nama_pengguna' => 'juri.siluman',
            'sandi'         => self::SANDI,
            'ulangi_sandi'  => self::SANDI,
        ], $this->sesiAdminAsli($this->idJuri, 'Juri Karangan'));

        $hasil->assertStatus(403);
        $this->assertSame(AdminFilter::PESAN_JURI, $hasil->response()->getBody());
        $this->assertNull(model(AdminModel::class, false)->cariNamaPengguna('juri.siluman'));

        // Simpan Lembar juri tetap sampai ke pengendalinya.
        $simpan = $this->kirimSebagaiAdmin('admin/juri/babak/999/1', ['prestasi' => '9,5'], $this->sesiAdminAsli($this->idJuri, 'Juri Karangan'));
        $this->assertNotSame(403, $simpan->response()->getStatusCode());
    }

    public function testPeranMengikutiBasisDataBukanIsiSesi(): void
    {
        // Sesi lama mengaku admin, tetapi akunnya juri.
        $hasil = $this->withSession($this->sesiAdminAsli($this->idJuri) + [SesiAdmin::KUNCI_PERAN => AdminModel::PERAN_ADMIN])->get('admin/pendaftaran');

        $hasil->assertRedirectTo(site_url('admin/juri'));
        $this->assertSame(AdminModel::PERAN_JURI, $_SESSION[SesiAdmin::KUNCI_PERAN] ?? null);
    }

    public function testMenuJuriHanyaLembarJuriDanAkun(): void
    {
        $xpath  = $this->xpathDari($this->bukaSebagaiJuri('admin/juri'));
        $tautan = static fn (string $tempat): array => array_values(array_unique(array_map(
            static fn (DOMElement $a): string => $a->getAttribute('data-menu-tautan'),
            iterator_to_array($xpath->query('//nav[@data-menu-admin="' . $tempat . '"]//a[@data-menu-tautan]')),
        )));

        $this->assertSame(['admin/juri', 'admin/akun', '/'], $tautan('samping'));
        $this->assertSame(['admin/juri', 'admin/akun'], $tautan('bawah'));
        $this->assertSame(0, $xpath->query('//main//a[contains(@href, "admin/hasil")]')->length, 'Tanpa tautan ke Hasil lomba.');
        $this->assertSame('Panel juri', trim($xpath->query('//*[@data-merek-admin]//span[contains(@class, "text-xs")]')->item(0)->textContent));

        // Akun juri: tanpa pil Pengaturan, jejak dimulai Lembar juri.
        $akun  = $this->xpathDari($this->bukaSebagaiJuri('admin/akun'));
        $jejak = array_map(static fn (DOMNode $li): string => trim($li->textContent), iterator_to_array($akun->query('//nav[@data-jejak]//ol/li')));
        $this->assertSame(0, $akun->query('//nav[@aria-label="Navigasi pengaturan"]')->length);
        $this->assertSame(['Lembar juri', 'Akun'], $jejak);

        // Admin tetap melihat menu lengkap.
        $admin = $this->xpathDari($this->withSession($this->sesiAdminAsli($this->idAdmin))->get('admin/juri'));
        $this->assertGreaterThan(10, $admin->query('//nav[@data-menu-admin="samping"]//a[@data-menu-tautan]')->length);
    }

    public function testAdminMembuatAkunJuri(): void
    {
        $this->bukaAdmin('admin/pengaturan/akun-juri')->assertSee('juri.uji');

        $hasil = $this->kirimAdmin('admin/pengaturan/akun-juri', [
            'nama'          => 'Pak Juri Karangan',
            'nama_pengguna' => 'Juri.Lompat',
            'sandi'         => self::SANDI,
            'ulangi_sandi'  => self::SANDI,
        ]);

        $this->assertBerhasilDialihkan($hasil, 'admin/pengaturan/akun-juri', 'Akun juri "juri.lompat" sudah dibuat. Berikan nama pengguna dan kata sandinya kepada Pak Juri Karangan.');
        $baru = model(AdminModel::class, false)->cariNamaPengguna('juri.lompat');
        $this->assertNotNull($baru);
        $this->assertSame(AdminModel::PERAN_JURI, $baru['peran']);
        $this->assertTrue(password_verify(self::SANDI, (string) $baru['sandi']));

        $daftar = $this->bukaAdmin('admin/pengaturan/akun-juri');
        $daftar->assertSee('juri.lompat');
        // Akun admin tidak tampil di daftar juri.
        $daftar->assertDontSee('admin.uji');
    }

    public function testIsianAkunJuriSalahDitampilkanUlang(): void
    {
        $hasil = $this->kirimAdmin('admin/pengaturan/akun-juri', [
            'nama'          => '',
            'nama_pengguna' => 'admin.uji',
            'sandi'         => self::SANDI,
            'ulangi_sandi'  => 'sandi-lain-uji-9',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama', 'nama_pengguna', 'ulangi_sandi']);
        $hasil->assertSee('Nama pengguna ini sudah dipakai.');
        $hasil->assertSee('Kedua kata sandi tidak sama.');
        $this->assertSame(2, $this->db->table('admin')->countAllResults());
    }

    public function testAdminMengubahDanMenghapusAkunJuri(): void
    {
        $hashAwal = (string) $this->baris($this->idJuri)['sandi'];

        $this->bukaAdmin('admin/pengaturan/akun-juri/' . $this->idJuri . '/ubah')->assertOK();

        // Tanpa kata sandi: hanya nama yang berubah.
        $hasil = $this->kirimAdmin('admin/pengaturan/akun-juri/' . $this->idJuri, ['nama' => 'Juri Ganti Nama', 'nama_pengguna' => 'juri.uji', 'sandi' => '', 'ulangi_sandi' => '']);
        $this->assertBerhasilDialihkan($hasil, 'admin/pengaturan/akun-juri', 'Akun juri "juri.uji" sudah diubah.');
        $this->assertSame('Juri Ganti Nama', $this->baris($this->idJuri)['nama']);
        $this->assertSame($hashAwal, $this->baris($this->idJuri)['sandi']);

        // Sesi juri yang sedang masuk dikeluarkan setelah kata sandinya diganti admin.
        $sesiJuri = $this->sesiAdminAsli($this->idJuri);
        $this->kirimAdmin('admin/pengaturan/akun-juri/' . $this->idJuri, ['nama' => 'Juri Ganti Nama', 'nama_pengguna' => 'juri.uji', 'sandi' => 'sandi-baru-uji-2', 'ulangi_sandi' => 'sandi-baru-uji-2']);
        $this->assertTrue(password_verify('sandi-baru-uji-2', (string) $this->baris($this->idJuri)['sandi']));
        $this->withSession($sesiJuri)->get('admin/juri')->assertRedirectTo(site_url('admin/masuk'));

        $hapus = $this->kirimAdmin('admin/pengaturan/akun-juri/' . $this->idJuri . '/hapus');
        $this->assertBerhasilDialihkan($hapus, 'admin/pengaturan/akun-juri', 'Akun juri "juri.uji" sudah dihapus. Prestasi yang sudah diisinya tetap tersimpan.');
        $this->assertSame([], $this->baris($this->idJuri));
        $this->bukaAdmin('admin/pengaturan/akun-juri')->assertSee('Belum ada akun juri.');
    }

    public function testAkunAdminTidakBisaDiubahLewatAkunJuri(): void
    {
        $pesan = 'Akun juri itu tidak ditemukan, mungkin sudah dihapus. Pilih akun dari daftar di bawah.';

        $this->assertGagalDialihkan($this->bukaAdmin('admin/pengaturan/akun-juri/' . $this->idAdmin . '/ubah'), 'admin/pengaturan/akun-juri', $pesan);
        $this->assertGagalDialihkan($this->kirimAdmin('admin/pengaturan/akun-juri/' . $this->idAdmin, ['nama' => 'Dibajak', 'nama_pengguna' => 'admin.uji', 'sandi' => 'sandi-baru-uji-2', 'ulangi_sandi' => 'sandi-baru-uji-2']), 'admin/pengaturan/akun-juri', $pesan);
        $this->assertGagalDialihkan($this->kirimAdmin('admin/pengaturan/akun-juri/' . $this->idAdmin . '/hapus'), 'admin/pengaturan/akun-juri', $pesan);

        $this->assertSame('Admin Karangan', $this->baris($this->idAdmin)['nama']);
        $this->assertTrue(password_verify(self::SANDI, (string) $this->baris($this->idAdmin)['sandi']));
    }

    public function testPerintahBuatAkunJuri(): void
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        $io->setInputs([self::SANDI, self::SANDI]);

        command('admin:buat --nama "Juri Lempar" --pengguna juri.lempar --peran juri');

        $this->assertSame(AdminModel::PERAN_JURI, model(AdminModel::class, false)->cariNamaPengguna('juri.lempar')['peran'] ?? null);
        $this->assertStringContainsString('Akun juri "juri.lempar" sudah dibuat.', $io->getOutput());

        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        command('admin:buat --nama "Pengawas" --pengguna pengawas.uji --peran pengawas');

        $this->assertStringContainsString('Peran "pengawas" tidak dikenal.', $io->getOutput());
        $this->assertNull(model(AdminModel::class, false)->cariNamaPengguna('pengawas.uji'));
    }

    private function bukaSebagaiJuri(string $alamat): TestResponse
    {
        return $this->withSession($this->sesiAdminAsli($this->idJuri, 'Juri Karangan'))->get($alamat);
    }

    private function bukaAdmin(string $alamat): TestResponse
    {
        return $this->withSession($this->sesiAdminAsli($this->idAdmin))->get($alamat);
    }

    /**
     * @param array<string, mixed> $isian
     */
    private function kirimAdmin(string $alamat, array $isian = []): TestResponse
    {
        return $this->kirimSebagaiAdmin($alamat, $isian, $this->sesiAdminAsli($this->idAdmin));
    }

    /**
     * @param array<string, mixed> $sesi
     */
    private function masuk(string $namaPengguna, array $sesi = []): TestResponse
    {
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));
        Services::resetSingle('security');
        Services::resetSingle('throttler');

        return $this->withSession([$namaToken => $token] + $sesi)->post('admin/masuk', [
            $namaToken      => $token,
            'nama_pengguna' => $namaPengguna,
            'sandi'         => self::SANDI,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(int $id): array
    {
        return $this->db->table('admin')->where('id', $id)->get()->getRowArray() ?? [];
    }
}
