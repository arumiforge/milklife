<?php

use App\Filters\CsrfFilter;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UjiHalaman;

/**
 * Galat CSRF terlihat dan tidak merusak banyak tab (F7-24, K118, analisis B1).
 *
 * Konstanta `ENVIRONMENT` di PHPUnit selalu `testing`, jadi lingkungan
 * produksi ditiru dengan `Config\Security::$redirect = true` (nilainya di
 * produksi). Kiriman memakai "token lama": sesi sudah memegang token lain.
 *
 * @internal
 */
final class CsrfPesanGalatTest extends UjiBasisData
{
    use KirimFormulir;

    private const HALAMAN = 'admin/pengaturan/acara';

    private bool $alihkanSemula;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alihkanSemula = config('Security')->redirect;
    }

    protected function tearDown(): void
    {
        config('Security')->redirect = $this->alihkanSemula;
        Services::resetSingle('security');

        parent::tearDown();
    }

    public function testFilterCsrfMemakaiTurunanSendiri(): void
    {
        $this->assertSame(CsrfFilter::class, config('Filters')->aliases['csrf']);
        $this->assertContains('csrf', config('Filters')->globals['before']);
    }

    public function testTokenTidakBergantiSetiapKiriman(): void
    {
        $this->assertFalse(config('Security')->regenerate);

        // Dua tab memakai token yang sama: kiriman kedua tetap diterima.
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));
        $isian     = [
            'nama'            => 'Lomba Lari Karangan',
            'seri'            => '3',
            'tahun'           => '2027',
            'tempat'          => 'Stadion Karangan',
            'tanggal_mulai'   => '2027-01-30',
            'tanggal_selesai' => '2027-02-01',
        ];

        Services::resetSingle('security');
        $pertama = $this->withSession([$namaToken => $token] + UjiHalaman::SESI_ADMIN)->post(self::HALAMAN, [$namaToken => $token] + $isian);
        $this->assertBerhasilDialihkan($pertama, self::HALAMAN);
        $this->assertSame($token, $_SESSION[$namaToken], 'Token di sesi tidak boleh berganti.');

        Services::resetSingle('security');
        $isian['nama'] = 'Lomba Lari Tab Kedua';
        $kedua         = $this->withSession($_SESSION)->post(self::HALAMAN, [$namaToken => $token] + $isian);
        $this->assertBerhasilDialihkan($kedua, self::HALAMAN);
        $this->assertSame('Lomba Lari Tab Kedua', setting('Acara.nama'));
    }

    public function testTokenLamaDiProduksiKembaliDenganPesanDanIsian(): void
    {
        $asal  = site_url(self::HALAMAN);
        $hasil = $this->kirimTokenLama(self::HALAMAN, ['nama' => 'Lomba Lari Karangan', 'sandi' => 'rahasia'], ['Referer' => $asal]);

        $hasil->assertRedirectTo($asal);
        $hasil->assertSessionHas('pesan_galat', CsrfFilter::PESAN);
        $this->assertSame('', setting('Acara.nama'), 'Pengaturan tidak boleh berubah.');

        $lama = $_SESSION['_ci_old_input']['post'] ?? [];
        $this->assertSame('Lomba Lari Karangan', $lama['nama'] ?? null, 'Isian lama harus dikembalikan.');
        $this->assertArrayNotHasKey('sandi', $lama, 'Kata sandi tidak boleh disimpan di sesi.');
        $this->assertArrayNotHasKey(config('Security')->tokenName, $lama);
    }

    public function testPesanTampilDiPanelAdmin(): void
    {
        $this->kirimTokenLama(self::HALAMAN, ['nama' => 'Lomba'], ['Referer' => site_url(self::HALAMAN)]);

        $hasil = $this->bukaSebagaiAdmin(self::HALAMAN, $_SESSION);
        $hasil->assertOK();

        $kotak = $this->xpathDari($hasil)->query('//main//div[@data-pesan="pesan_galat"]')->item(0);
        $this->assertNotNull($kotak, 'Pesan galat CSRF tidak tampil di panel admin.');
        $this->assertStringContainsString(CsrfFilter::PESAN, $kotak->textContent);
    }

    public function testPesanTampilDiHalamanPublik(): void
    {
        $asal  = site_url('cek-pendaftaran');
        $hasil = $this->kirimTokenLama('bukti/' . str_repeat('a', 24) . '/MAC2A0000126/bukti-bayar', [], ['Referer' => $asal], []);

        $hasil->assertRedirectTo($asal);

        $halaman = $this->withSession($_SESSION)->get('cek-pendaftaran');
        $halaman->assertOK();

        $kotak = $this->xpathDari($halaman)->query('//div[@data-pesan="pesan_galat"]')->item(0);
        $this->assertNotNull($kotak, 'Pesan galat CSRF tidak tampil di halaman publik.');
        $this->assertStringContainsString(CsrfFilter::PESAN, $kotak->textContent);
    }

    public function testAlamatAsalDariSitusLainTidakDipakai(): void
    {
        $hasil = $this->kirimTokenLama(self::HALAMAN, [], ['Referer' => 'https://jahat.example/tiruan']);

        $hasil->assertRedirectTo(site_url('admin/dasbor'));
        $hasil->assertSessionHas('pesan_galat', CsrfFilter::PESAN);
    }

    public function testKirimanJsonWizardMendapatJawabanJson(): void
    {
        config('Security')->redirect = true;
        Services::resetSingle('security');

        $namaToken = config('Security')->tokenName;
        $tokenSesi = bin2hex(random_bytes(32));

        $hasil = $this->withSession([$namaToken => $tokenSesi])
            ->withHeaders([
                'X-CSRF-TOKEN'     => str_repeat('b', 64),
                'Content-Type'     => 'application/json',
                'Accept'           => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->withBodyFormat('json')
            ->post('pendaftaran/periksa', ['sekolah' => []]);

        $hasil->assertStatus(403);
        $this->assertFalse($hasil->isRedirect());

        $isi = json_decode($hasil->getJSON() ?: 'null', true);
        $this->assertSame('kedaluwarsa', $isi['status'] ?? null);
        $this->assertSame(CsrfFilter::PESAN, $isi['pesan'] ?? null);
        $this->assertSame($tokenSesi, $isi['csrf'] ?? null, 'Token yang berlaku dikirim supaya wizard bisa mencoba lagi.');
    }

    public function testDiLuarProduksiTetapHalamanGalat(): void
    {
        config('Security')->redirect = false;

        $this->assertTokenCsrfWajib(self::HALAMAN, ['nama' => 'Lomba']);
    }

    /**
     * Kiriman dengan token yang sudah tidak berlaku, di lingkungan produksi.
     *
     * @param array<string, mixed>  $isian
     * @param array<string, string> $kepala
     * @param array<string, mixed>  $sesi
     */
    private function kirimTokenLama(string $alamat, array $isian, array $kepala = [], array $sesi = UjiHalaman::SESI_ADMIN): TestResponse
    {
        config('Security')->redirect = true;
        Services::resetSingle('security');

        $namaToken = config('Security')->tokenName;

        try {
            return $this->withSession([$namaToken => bin2hex(random_bytes(32))] + $sesi)
                ->withHeaders($kepala)
                ->post($alamat, [$namaToken => str_repeat('b', 64)] + $isian);
        } catch (SecurityException $e) {
            $this->fail('Di produksi kiriman token lama seharusnya dialihkan dengan pesan: ' . $e->getMessage());
        }
    }
}
