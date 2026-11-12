<?php

use App\Controllers\Admin\AkunController;
use App\Models\AdminModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman Akun (`/admin/akun`, K26): admin yang sedang masuk mengganti nama,
 * nama pengguna, dan kata sandinya sendiri. Data uji memakai nama karangan
 * dan kata sandi uji yang bukan rahasia.
 *
 * @internal
 */
final class AkunAdminTest extends UjiBasisData
{
    use KirimFormulir;

    private const SANDI_UJI = 'kata-sandi-uji-1';
    private const SANDI_BARU = 'sandi-baru-uji-2';

    private AdminModel $model;
    private int $idAdmin;
    private int $idAdminLain;

    protected function setUp(): void
    {
        parent::setUp();

        // Pembatas laju mulai dari nol di setiap uji (cache tiruan milik uji).
        Services::resetSingle('throttler');
        service('cache')->clean();

        $this->model       = model(AdminModel::class, false);
        $this->idAdmin     = $this->buatAdmin('Admin Karangan', 'admin.uji');
        $this->idAdminLain = $this->buatAdmin('Admin Karangan Lain', 'admin.lain');
    }

    // --- Halaman -------------------------------------------------------------

    public function testHalamanTerbukaDanBerisiDataAkunSendiri(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/akun', $this->sesi());

        $hasil->assertOK();
        $hasil->assertSee('Nama dan nama pengguna');
        $hasil->assertSee('Ganti kata sandi');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Admin Karangan', $xpath->query('//input[@name="nama"]/@value')->item(0)?->nodeValue);
        $this->assertSame('admin.uji', $xpath->query('//input[@name="nama_pengguna"]/@value')->item(0)?->nodeValue);
        $this->assertSame(0, $xpath->query('//input[@name="nama_pengguna" and @value="admin.lain"]')->length);

        // Dua formulir ber-CSRF ke alamat yang benar.
        $this->assertSame(1, $xpath->query('//form[@action="' . site_url('admin/akun') . '" and @method="post"]')->length);
        $this->assertSame(1, $xpath->query('//form[@action="' . site_url('admin/akun/sandi') . '" and @method="post"]')->length);
        $this->assertSame(2, $xpath->query('//form[starts-with(@action, "' . site_url('admin/akun') . '")]//input[@name="' . config('Security')->tokenName . '"]')->length);

        // Kotak kata sandi tidak pernah berisi nilai; hash tidak bocor ke halaman.
        $this->assertSame(0, $xpath->query('//input[@type="password" and @value]')->length);
        $this->assertStringNotContainsString('$2y$', $hasil->getBody());
        $hasil->assertSee('autocomplete="new-password"');
    }

    public function testHalamanMenampilkanNavigasiDanMenuPengaturanAktif(): void
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/akun', $this->sesi()));

        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page" and contains(., "Akun")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//a[@aria-current="page" and contains(@href, "admin/pengaturan")]')->length);
    }

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan('admin/akun');
        $this->assertBelumMasukDialihkan('admin/akun', 'post');
        $this->assertBelumMasukDialihkan('admin/akun/sandi', 'post');
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib('admin/akun', ['nama' => 'Nama Baru', 'nama_pengguna' => 'admin.uji']);
        $this->assertTokenCsrfWajib('admin/akun/sandi', [
            'sandi_saat_ini' => self::SANDI_UJI,
            'sandi_baru'     => self::SANDI_BARU,
            'ulangi_sandi'   => self::SANDI_BARU,
        ]);

        $baris = $this->baris($this->idAdmin);
        $this->assertSame('Admin Karangan', $baris['nama']);
        $this->assertTrue(password_verify(self::SANDI_UJI, $baris['sandi']));
    }

    public function testAkunYangSudahDihapusDikeluarkan(): void
    {
        $this->db->table('admin')->where('id', $this->idAdmin)->delete();

        foreach ([['get', 'admin/akun'], ['post', 'admin/akun'], ['post', 'admin/akun/sandi']] as [$metode, $alamat]) {
            $hasil = $metode === 'get'
                ? $this->bukaSebagaiAdmin($alamat, $this->sesi())
                : $this->kirimSebagaiAdmin($alamat, ['nama' => 'Nama Baru', 'nama_pengguna' => 'admin.uji'], $this->sesi());

            $hasil->assertRedirectTo(site_url('admin/masuk'));
            $hasil->assertSessionMissing('admin_id');
            $hasil->assertSessionHas('pesan_masuk');
        }

        // Akun lain tidak tersentuh.
        $this->assertSame('Admin Karangan Lain', $this->baris($this->idAdminLain)['nama']);
    }

    // --- Nama dan nama pengguna ---------------------------------------------

    public function testGantiNamaSajaTanpaKataSandi(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', ['nama' => '  Bu Karangan Baru ', 'nama_pengguna' => 'admin.uji'], $this->sesi());

        $this->assertBerhasilDialihkan($hasil, 'admin/akun', 'Data akun tersimpan.');
        $hasil->assertSessionHas('admin_nama', 'Bu Karangan Baru');
        $this->assertSame('Bu Karangan Baru', $this->baris($this->idAdmin)['nama']);
        $this->assertSame('Admin Karangan Lain', $this->baris($this->idAdminLain)['nama']);
    }

    public function testNamaBaruTampilDiKepalaSetelahSimpan(): void
    {
        $this->kirimSebagaiAdmin('admin/akun', ['nama' => 'Bu Karangan Baru', 'nama_pengguna' => 'admin.uji'], $this->sesi());

        $this->bukaSebagaiAdmin('admin/akun', $_SESSION)->assertSee('Bu Karangan Baru');
    }

    public function testGantiNamaPenggunaDenganKataSandiBenar(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', [
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => ' Panitia.Kudus ',
            'sandi_akun'    => self::SANDI_UJI,
        ], $this->sesi());

        $this->assertBerhasilDialihkan($hasil, 'admin/akun', 'Data akun tersimpan.');
        $this->assertSame('panitia.kudus', $this->baris($this->idAdmin)['nama_pengguna']);
        $this->assertSame((int) $this->idAdmin, (int) $this->model->cariNamaPengguna('panitia.kudus')['id']);
        $this->assertNull($this->model->cariNamaPengguna('admin.uji'));
    }

    public function testGantiNamaPenggunaTanpaKataSandiDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', ['nama' => 'Nama Baru', 'nama_pengguna' => 'panitia.kudus'], $this->sesi());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_akun']);
        $hasil->assertSee('Kata sandi saat ini belum diisi.');

        // Isian yang diketik dipertahankan, tetapi tidak ada yang tersimpan.
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('panitia.kudus', $xpath->query('//input[@name="nama_pengguna"]/@value')->item(0)?->nodeValue);
        $this->assertSame('Nama Baru', $xpath->query('//input[@name="nama"]/@value')->item(0)?->nodeValue);
        $this->assertSame('admin.uji', $this->baris($this->idAdmin)['nama_pengguna']);
        $this->assertSame('Admin Karangan', $this->baris($this->idAdmin)['nama']);
        $this->assertSame('Admin Uji', $_SESSION['admin_nama']);
    }

    public function testGantiNamaPenggunaDenganKataSandiSalahDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', [
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => 'panitia.kudus',
            'sandi_akun'    => 'bukan-sandinya',
        ], $this->sesi());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_akun']);
        $hasil->assertSee(AkunController::PESAN_SANDI_SALAH);
        $this->assertSame('admin.uji', $this->baris($this->idAdmin)['nama_pengguna']);

        // Kata sandi yang diketik tidak dikembalikan ke halaman.
        $hasil->assertDontSee('bukan-sandinya');
    }

    public function testNamaPenggunaSendiriBolehDisimpanUlang(): void
    {
        // Ditulis berbeda huruf besar-kecilnya: setelah dinormalkan sama dengan
        // miliknya sendiri, jadi tidak dianggap diganti dan tidak perlu kata sandi.
        $hasil = $this->kirimSebagaiAdmin('admin/akun', ['nama' => 'Admin Karangan', 'nama_pengguna' => 'ADMIN.Uji'], $this->sesi());

        $this->assertBerhasilDialihkan($hasil, 'admin/akun', 'Data akun tersimpan.');
        $this->assertSame('admin.uji', $this->baris($this->idAdmin)['nama_pengguna']);
    }

    public function testNamaPenggunaMilikAdminLainDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', [
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => 'Admin.Lain',
            'sandi_akun'    => self::SANDI_UJI,
        ], $this->sesi());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama_pengguna']);
        $hasil->assertSee('Nama pengguna ini sudah dipakai. Pilih nama pengguna lain.');
        $this->assertSame('admin.uji', $this->baris($this->idAdmin)['nama_pengguna']);
    }

    public function testIsianNamaSalahDitolakDenganPesan(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', [
            'nama'          => '   ',
            'nama_pengguna' => 'nama pakai spasi',
            'sandi_akun'    => self::SANDI_UJI,
        ], $this->sesi());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama', 'nama_pengguna']);
        $hasil->assertSee('Nama belum diisi.');
        $hasil->assertSee('Nama pengguna hanya boleh berisi huruf, angka, titik, dan garis bawah, tanpa spasi.');
        $this->assertSame('Admin Karangan', $this->baris($this->idAdmin)['nama']);
    }

    public function testNamaTerlaluPanjangDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/akun', ['nama' => str_repeat('a', 101), 'nama_pengguna' => 'admin.uji'], $this->sesi());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama']);
        $hasil->assertSee('Nama terlalu panjang.');
    }

    // --- Kata sandi -----------------------------------------------------------

    public function testGantiKataSandiLaluMasukDenganSandiBaru(): void
    {
        $hasil = $this->kirimSandi(self::SANDI_UJI, self::SANDI_BARU, self::SANDI_BARU);

        $this->assertBerhasilDialihkan($hasil, 'admin/akun', AkunController::PESAN_SANDI_DIGANTI);

        // Sesi dibuat ulang, admin tetap masuk.
        $this->assertTrue(service('session')->didRegenerate);
        $hasil->assertSessionHas('admin_id', $this->idAdmin);
        $hasil->assertSessionHas('admin_nama', 'Admin Uji');

        $hash = $this->baris($this->idAdmin)['sandi'];
        $this->assertTrue(password_verify(self::SANDI_BARU, $hash));
        $this->assertFalse(password_verify(self::SANDI_UJI, $hash));

        // Keluar, lalu masuk lagi dengan kata sandi baru; yang lama ditolak.
        $this->assertSame(site_url('admin/dasbor'), $this->kirimMasuk('admin.uji', self::SANDI_BARU)->getRedirectUrl());
        $this->kirimMasuk('admin.uji', self::SANDI_UJI)->assertSessionMissing('admin_id');

        // Akun lain tidak berubah.
        $this->assertTrue(password_verify(self::SANDI_UJI, $this->baris($this->idAdminLain)['sandi']));
    }

    public function testKataSandiSaatIniSalahDitolak(): void
    {
        $hasil = $this->kirimSandi('bukan-sandinya', self::SANDI_BARU, self::SANDI_BARU);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_saat_ini']);
        $hasil->assertSee(AkunController::PESAN_SANDI_SALAH);
        $hasil->assertDontSee(self::SANDI_BARU);
        $this->assertFalse(service('session')->didRegenerate);
        $this->assertTrue(password_verify(self::SANDI_UJI, $this->baris($this->idAdmin)['sandi']));
    }

    public function testIsianKataSandiKosongDitolakDenganPesan(): void
    {
        $hasil = $this->kirimSandi('', '', '');

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_saat_ini', 'sandi_baru', 'ulangi_sandi']);
        $hasil->assertSee('Kata sandi saat ini belum diisi.');
        $hasil->assertSee('Kata sandi baru belum diisi.');
        $hasil->assertSee('Ulangi kata sandi baru belum diisi.');
    }

    public function testKataSandiBaruTerlaluPendekDitolak(): void
    {
        $hasil = $this->kirimSandi(self::SANDI_UJI, '1234567', '1234567');

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_baru']);
        $hasil->assertSee('Kata sandi terlalu pendek. Buat kata sandi paling sedikit 8 karakter.');
        $this->assertTrue(password_verify(self::SANDI_UJI, $this->baris($this->idAdmin)['sandi']));
    }

    public function testKataSandiBaruTerlaluPanjangDitolak(): void
    {
        $panjang = str_repeat('a', AdminModel::SANDI_MAKSIMAL + 1);
        $hasil   = $this->kirimSandi(self::SANDI_UJI, $panjang, $panjang);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_baru']);
        $hasil->assertSee('Kata sandi terlalu panjang.');
    }

    public function testUlanganTidakSamaDitolak(): void
    {
        $hasil = $this->kirimSandi(self::SANDI_UJI, self::SANDI_BARU, self::SANDI_BARU . 'x');

        $this->assertIsianSalahDitampilkanUlang($hasil, ['ulangi_sandi']);
        $hasil->assertSee('Ulangan kata sandi tidak sama dengan kata sandi baru.');
        $this->assertTrue(password_verify(self::SANDI_UJI, $this->baris($this->idAdmin)['sandi']));
    }

    public function testKataSandiBaruSamaDenganYangLamaDitolak(): void
    {
        $hasil = $this->kirimSandi(self::SANDI_UJI, self::SANDI_UJI, self::SANDI_UJI);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi_baru']);
        $hasil->assertSee('Kata sandi baru sama dengan kata sandi saat ini.');
    }

    // --- Pembatasan percobaan -------------------------------------------------

    public function testPercobaanKataSandiKeenamDitolak(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= AkunController::BATAS_PERCOBAAN; $ke++) {
            $this->kirimSandi('bukan-sandinya', self::SANDI_BARU, self::SANDI_BARU)->assertSee(AkunController::PESAN_SANDI_SALAH);
        }

        // Percobaan ke-6 ditolak walau kata sandinya benar.
        $hasil = $this->kirimSandi(self::SANDI_UJI, self::SANDI_BARU, self::SANDI_BARU);

        $hasil->assertStatus(429);
        $hasil->assertHeader('Retry-After', '12');
        $hasil->assertSee('Terlalu banyak percobaan kata sandi. Tunggu 12 detik lagi');
        $this->assertSame(1, $this->xpathDari($hasil)->query('//*[@name="sandi_saat_ini" and contains(@class, "is-invalid")]')->length);
        $this->assertTrue(password_verify(self::SANDI_UJI, $this->baris($this->idAdmin)['sandi']));
    }

    public function testPembatasanBerlakuJugaUntukGantiNamaPengguna(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= AkunController::BATAS_PERCOBAAN; $ke++) {
            $this->kirimSandi('bukan-sandinya', self::SANDI_BARU, self::SANDI_BARU);
        }

        $hasil = $this->kirimSebagaiAdmin('admin/akun', [
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => 'panitia.kudus',
            'sandi_akun'    => self::SANDI_UJI,
        ], $this->sesi());

        $hasil->assertStatus(429);
        $hasil->assertSee('Terlalu banyak percobaan kata sandi.');
        $this->assertSame('admin.uji', $this->baris($this->idAdmin)['nama_pengguna']);

        // Mengganti nama saja tidak memeriksa kata sandi, jadi tidak tertahan.
        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin('admin/akun', ['nama' => 'Nama Baru', 'nama_pengguna' => 'admin.uji'], $this->sesi()),
            'admin/akun',
        );
    }

    public function testPembatasanPerAkun(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= AkunController::BATAS_PERCOBAAN; $ke++) {
            $this->kirimSandi('bukan-sandinya', self::SANDI_BARU, self::SANDI_BARU);
        }

        // Admin lain tidak ikut tertahan.
        $hasil = $this->kirimSebagaiAdmin('admin/akun/sandi', [
            'sandi_saat_ini' => self::SANDI_UJI,
            'sandi_baru'     => self::SANDI_BARU,
            'ulangi_sandi'   => self::SANDI_BARU,
        ], ['admin_id' => $this->idAdminLain, 'admin_nama' => 'Admin Karangan Lain']);

        $this->assertBerhasilDialihkan($hasil, 'admin/akun');
    }

    // --- Bantuan --------------------------------------------------------------

    /**
     * @return array{admin_id: int, admin_nama: string}
     */
    private function sesi(): array
    {
        return ['admin_id' => $this->idAdmin, 'admin_nama' => 'Admin Uji'];
    }

    private function kirimSandi(string $saatIni, string $baru, string $ulangi): TestResponse
    {
        return $this->kirimSebagaiAdmin('admin/akun/sandi', [
            'sandi_saat_ini' => $saatIni,
            'sandi_baru'     => $baru,
            'ulangi_sandi'   => $ulangi,
        ], $this->sesi());
    }

    /**
     * Masuk lewat halaman masuk (sesi kosong, token CSRF sah).
     */
    private function kirimMasuk(string $namaPengguna, string $sandi): TestResponse
    {
        Services::resetSingle('throttler');
        service('cache')->clean();

        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));
        Services::resetSingle('security');

        return $this->withSession([$namaToken => $token])->post('admin/masuk', [
            $namaToken      => $token,
            'nama_pengguna' => $namaPengguna,
            'sandi'         => $sandi,
        ]);
    }

    private function buatAdmin(string $nama, string $namaPengguna): int
    {
        $id = $this->model->insert(['nama' => $nama, 'nama_pengguna' => $namaPengguna, 'sandi' => self::SANDI_UJI]);
        $this->assertIsInt($id);

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(int $id): array
    {
        return $this->db->table('admin')->where('id', $id)->get()->getRowArray();
    }
}
