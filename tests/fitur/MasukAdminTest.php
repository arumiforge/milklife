<?php

use App\Controllers\Admin\MasukController;
use App\Filters\AdminFilter;
use App\Models\AdminModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\TiruanSiteverify;
use Tests\Support\TurnstileUji;
use Tests\Support\UjiBasisData;
use Tests\Support\UjiHalaman;

/**
 * Masuk, keluar, dan penjaga halaman admin.
 *
 * CSRF tetap aktif di uji ini (filter global `csrf`). Token disiapkan di sesi
 * lalu dikirim bersama formulir, sama seperti peramban yang membuka halaman
 * masuk lebih dulu.
 *
 * @internal
 */
final class MasukAdminTest extends UjiBasisData
{
    use FeatureTestTrait;
    use TurnstileUji;

    private const NAMA_PENGGUNA = 'admin.uji';
    private const SANDI_UJI     = 'kata-sandi-uji-1';

    private int $idAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Layanan bersama dibuat ulang supaya memakai sesi dan cache tiruan
        // milik uji ini; pembatas laju mulai dari nol di setiap uji.
        Services::resetSingle('security');
        Services::resetSingle('throttler');
        service('cache')->clean();

        $this->idAdmin = model(AdminModel::class, false)->insert([
            'nama'          => 'Admin Karangan',
            'nama_pengguna' => self::NAMA_PENGGUNA,
            'sandi'         => self::SANDI_UJI,
        ]);
    }

    public function testHalamanMasukMemuatFormulirBerCsrf(): void
    {
        $hasil = $this->get('admin/masuk');

        $hasil->assertOK();
        $hasil->assertSee('Masuk admin');
        $hasil->assertSee('autocomplete="username"');
        $hasil->assertSee('autocomplete="current-password"');
        $this->assertNotSame('', $this->tokenDariFormulir($hasil->getBody()));
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));
    }

    public function testHalamanMasukMemakaiKartuTataLetakPolos(): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $this->get('admin/masuk')->getBody());
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Kartu disediakan tata letak `polos_tw`; formulir masuk ada di dalamnya,
        // dan halaman tidak membawa kartu sendiri.
        $this->assertSame(1, $xpath->query('//*[@data-polos-kartu]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-polos-kartu]//form[@method="post"]//input[@name="sandi"][@type="password"]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-polos-kartu]//h1')->length);
    }

    public function testMasukDenganTokenDariHalaman(): void
    {
        $token = $this->tokenDariFormulir($this->get('admin/masuk')->getBody());

        Services::resetSingle('security');
        $hasil = $this->withSession()->post('admin/masuk', [
            config('Security')->tokenName => $token,
            'nama_pengguna'               => self::NAMA_PENGGUNA,
            'sandi'                       => self::SANDI_UJI,
        ]);

        $hasil->assertRedirectTo(site_url('admin/dasbor'));
        $hasil->assertSessionHas('admin_id', $this->idAdmin);
    }

    public function testMasukBerhasilMengisiSesi(): void
    {
        $hasil = $this->kirimMasuk(['nama_pengguna' => '  Admin.Uji ', 'sandi' => self::SANDI_UJI]);

        $hasil->assertRedirectTo(site_url('admin/dasbor'));
        $this->assertSame($this->idAdmin, $_SESSION['admin_id']);
        $this->assertSame('Admin Karangan', $_SESSION['admin_nama']);
        $this->assertTrue(service('session')->didRegenerate, 'ID sesi harus dibuat ulang setelah masuk.');
    }

    public function testKataSandiSalahDanNamaPenggunaTakDikenalPesannyaSama(): void
    {
        $salahSandi = $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => 'bukan-sandinya']);
        $sesiSatu   = $_SESSION;
        $takDikenal = $this->kirimMasuk(['nama_pengguna' => 'tidak.ada', 'sandi' => self::SANDI_UJI]);

        foreach ([$salahSandi, $takDikenal] as $hasil) {
            $hasil->assertOK();
            $hasil->assertSee(MasukController::PESAN_GAGAL);
        }
        $this->assertArrayNotHasKey('admin_id', $sesiSatu);
        $this->assertArrayNotHasKey('admin_id', $_SESSION);

        // Nama pengguna yang diketik dipertahankan, kata sandi tidak.
        $takDikenal->assertSee('value="tidak.ada"');
        $this->assertStringNotContainsString(self::SANDI_UJI, $takDikenal->getBody());
    }

    public function testIsianKosongDitolakDenganPesanRamah(): void
    {
        $hasil = $this->kirimMasuk(['nama_pengguna' => '   ', 'sandi' => '']);

        $hasil->assertOK();
        $hasil->assertSee('Nama pengguna belum diisi.');
        $hasil->assertSee('Kata sandi belum diisi.');
        $hasil->assertSessionMissing('admin_id');
    }

    public function testTanpaTokenCsrfDitolak(): void
    {
        try {
            $this->post('admin/masuk', ['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI]);
            $this->fail('Permintaan tanpa token CSRF seharusnya ditolak.');
        } catch (SecurityException $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->assertArrayNotHasKey('admin_id', $_SESSION);
    }

    public function testTokenCsrfSalahDitolak(): void
    {
        $this->expectException(SecurityException::class);

        $this->withSession([config('Security')->tokenName => str_repeat('a', 64)])
            ->post('admin/masuk', [
                config('Security')->tokenName => str_repeat('b', 64),
                'nama_pengguna'               => self::NAMA_PENGGUNA,
                'sandi'                       => self::SANDI_UJI,
            ]);
    }

    public function testPercobaanKeenamDalamSemenitDitolak(): void
    {
        // Waktu pembatas dibekukan supaya lama tunggu yang disebut pasti.
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= MasukController::BATAS_PERCOBAAN; $ke++) {
            $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => 'bukan-sandinya'])
                ->assertSee(MasukController::PESAN_GAGAL);
        }

        // Percobaan ke-6 ditolak walau kata sandinya benar.
        $hasil = $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI]);

        $hasil->assertStatus(429);
        $hasil->assertSee('Terlalu banyak percobaan masuk. Tunggu 12 detik lagi');
        $hasil->assertHeader('Retry-After', '12');
        $hasil->assertSessionMissing('admin_id');
    }

    public function testHalamanAdminTanpaSesiDialihkanKeMasuk(): void
    {
        foreach (['admin', 'admin/dasbor'] as $alamat) {
            $hasil = $this->get($alamat);

            $hasil->assertRedirectTo(site_url('admin/masuk'));
            $this->assertSame($alamat, $_SESSION[AdminFilter::KUNCI_TUJUAN]);
        }
    }

    public function testSetelahMasukKembaliKeHalamanTujuan(): void
    {
        $this->get('admin/dasbor?halaman=2')->assertRedirectTo(site_url('admin/masuk'));

        $hasil = $this->kirimMasuk(
            ['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI],
            $_SESSION,
        );

        $this->assertSame(site_url('admin/dasbor?halaman=2'), $hasil->getRedirectUrl());
        $hasil->assertSessionMissing(AdminFilter::KUNCI_TUJUAN);
    }

    public function testTujuanLuarDitolak(): void
    {
        foreach (['https://contoh.invalid/admin', '//contoh.invalid', 'admin/../beranda'] as $tujuan) {
            $hasil = $this->kirimMasuk(
                ['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI],
                [AdminFilter::KUNCI_TUJUAN => $tujuan],
            );

            $hasil->assertRedirectTo(site_url('admin/dasbor'));
        }
    }

    public function testSudahMasukMembukaHalamanMasukDialihkanKeDasbor(): void
    {
        $hasil = $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/masuk');

        $hasil->assertRedirectTo(site_url('admin/dasbor'));
    }

    public function testHalamanAdminTidakDisimpanPeramban(): void
    {
        $hasil = $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/dasbor');

        $hasil->assertOK();
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));
    }

    public function testKeluarMenghapusSesi(): void
    {
        $hasil = $this->kirimKe('admin/keluar', [], UjiHalaman::SESI_ADMIN + ['lain' => 'isi lain']);

        $hasil->assertRedirectTo(site_url('admin/masuk'));
        $hasil->assertSessionMissing('admin_id');
        $hasil->assertSessionMissing('admin_nama');
        $hasil->assertSessionMissing('lain');
        $this->assertTrue(service('session')->didRegenerate);

        // Halaman masuk berikutnya menampilkan pesan, dan dasbor tertutup lagi.
        $this->withSession()->get('admin/masuk')->assertSee('Anda sudah keluar.');
        $this->withSession()->get('admin/dasbor')->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testKeluarTanpaSesiDialihkanKeMasuk(): void
    {
        $hasil = $this->kirimKe('admin/keluar', []);

        $hasil->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testHashDiperbaruiBilaPerlu(): void
    {
        // Hash lama dengan biaya rendah, seolah dibuat dengan pengaturan lama.
        $hashLama = password_hash(self::SANDI_UJI, PASSWORD_BCRYPT, ['cost' => 4]);
        $this->db->table('admin')->where('id', $this->idAdmin)->update(['sandi' => $hashLama]);
        $this->assertTrue(password_needs_rehash($hashLama, PASSWORD_DEFAULT));

        $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI])
            ->assertRedirectTo(site_url('admin/dasbor'));

        $hashBaru = $this->db->table('admin')->where('id', $this->idAdmin)->get()->getRow()->sandi;
        $this->assertNotSame($hashLama, $hashBaru);
        $this->assertFalse(password_needs_rehash($hashBaru, PASSWORD_DEFAULT));
        $this->assertTrue(password_verify(self::SANDI_UJI, $hashBaru));
    }

    /**
     * @param array<string, string> $isian
     * @param array<string, mixed>  $sesi
     */
    private function kirimMasuk(array $isian, array $sesi = []): TestResponse
    {
        return $this->kirimKe('admin/masuk', $isian, $sesi);
    }

    /**
     * Kirim formulir POST dengan token CSRF yang sah.
     *
     * @param array<string, string> $isian
     * @param array<string, mixed>  $sesi
     */
    private function kirimKe(string $alamat, array $isian, array $sesi = []): TestResponse
    {
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));

        Services::resetSingle('security');

        return $this->withSession([$namaToken => $token] + $sesi)
            ->post($alamat, [$namaToken => $token] + $isian);
    }

    private function tokenDariFormulir(string $html): string
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('form') as $formulir) {
            if (rtrim((string) parse_url($formulir->getAttribute('action'), PHP_URL_PATH), '/') !== '/admin/masuk') {
                continue;
            }
            $this->assertSame('post', strtolower($formulir->getAttribute('method')));

            foreach ($formulir->getElementsByTagName('input') as $masukan) {
                if ($masukan->getAttribute('name') === config('Security')->tokenName) {
                    return $masukan->getAttribute('value');
                }
            }
        }

        $this->fail('Formulir masuk dengan token CSRF tidak ditemukan.');
    }

    protected function tearDown(): void
    {
        $this->matikanTurnstile();
        parent::tearDown();
    }

    public function testTurnstileMatiTanpaKunci(): void
    {
        $this->get('admin/masuk')->assertDontSee('cf-turnstile');
    }

    public function testTurnstileWajibSaatMasuk(): void
    {
        $tiruan = $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_LOLOS, 'masuk_admin');

        $halaman = $this->get('admin/masuk');
        $halaman->assertSee('class="cf-turnstile" data-sitekey="' . TiruanSiteverify::SITUS_LOLOS . '" data-action="masuk_admin"');
        $halaman->assertSee('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>');

        // Tanpa token: 403, sandi tidak diperiksa, tidak masuk.
        $tanpa = $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI]);
        $tanpa->assertStatus(403);
        $tanpa->assertSee('Pemeriksaan keamanan belum selesai');
        $tanpa->assertSessionMissing('admin_id');
        $this->assertSame([], $tiruan->kiriman);

        $dengan = $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI, 'cf-turnstile-response' => TiruanSiteverify::TOKEN]);
        $dengan->assertRedirectTo(site_url('admin/dasbor'));
        $dengan->assertSessionHas('admin_id', $this->idAdmin);
        $this->assertSame(TiruanSiteverify::TOKEN, $tiruan->kiriman[0]['response']);
    }

    public function testTurnstileGagalSaatMasuk(): void
    {
        $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_GAGAL, 'masuk_admin');

        $hasil = $this->kirimMasuk(['nama_pengguna' => self::NAMA_PENGGUNA, 'sandi' => self::SANDI_UJI, 'cf-turnstile-response' => TiruanSiteverify::TOKEN]);

        $hasil->assertStatus(403);
        $hasil->assertSee('Pemeriksaan keamanan gagal');
        $hasil->assertSessionMissing('admin_id');
    }
}
