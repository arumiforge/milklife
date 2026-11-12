<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\UjiHalaman;

/**
 * Rute dan halaman sementara dari kerangka (F1-01).
 *
 * @internal
 */
final class HalamanKerangkaTest extends UjiHalaman
{
    public function testHalamanDepanTerbuka(): void
    {
        $hasil = $this->get('/');

        $hasil->assertOK();
        // Keadaan pendaftaran dari pengaturan (F3-01): belum lengkap = sedang disiapkan.
        $hasil->assertSee('Pendaftaran sedang disiapkan.');
    }

    public function testAdminDialihkanKeDasbor(): void
    {
        $hasil = $this->withSession(self::SESI_ADMIN)->get('admin');

        $hasil->assertRedirectTo(site_url('admin/dasbor'));
    }

    public function testDasborTerbukaDenganSesiAdmin(): void
    {
        $hasil = $this->withSession(self::SESI_ADMIN)->get('admin/dasbor');

        $hasil->assertOK();
        $hasil->assertSee('Admin Uji');
        $this->assertFormulirKeluarBerCsrf($hasil->getBody());
    }

    public function testNamaAdminDiEscape(): void
    {
        $sesi  = ['admin_id' => 1, 'admin_nama' => '<b>Nama Karangan</b>'];
        $hasil = $this->withSession($sesi)->get('admin/dasbor');

        $hasil->assertOK();
        $this->assertStringContainsString('&lt;b&gt;Nama Karangan&lt;/b&gt;', $hasil->getBody());
        $this->assertStringNotContainsString('<b>Nama Karangan</b>', $hasil->getBody());
    }

    public function testRuteTakDikenalTidakDibuka(): void
    {
        // Perutean otomatis mati: nama pengendali tidak bisa dibuka langsung.
        $this->expectException(PageNotFoundException::class);

        $this->get('berandacontroller/index');
    }

    private function assertFormulirKeluarBerCsrf(string $html): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $namaToken = config('Security')->tokenName;
        $ditemukan = false;

        foreach ($dom->getElementsByTagName('form') as $formulir) {
            if (strtolower($formulir->getAttribute('method')) !== 'post') {
                continue;
            }
            if (rtrim((string) parse_url($formulir->getAttribute('action'), PHP_URL_PATH), '/') !== '/admin/keluar') {
                continue;
            }

            foreach ($formulir->getElementsByTagName('input') as $masukan) {
                if ($masukan->getAttribute('name') === $namaToken && $masukan->getAttribute('value') !== '') {
                    $ditemukan = true;
                }
            }
        }

        $this->assertTrue($ditemukan, 'Formulir POST ke /admin/keluar dengan token CSRF tidak ditemukan.');
    }
}
