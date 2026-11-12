<?php

use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use Config\Filters;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Kepala keamanan HTTP (K67, F7-02): filter `secureheaders` bawaan
 * CodeIgniter aktif sebagai global *after*, jadi ikut di halaman publik,
 * halaman admin, dan jawaban berkas (PDF, Excel, gambar admin, pratinjau
 * JSON). Berkas tetap terbuka dengan jenis isi yang benar.
 *
 * Bukti pendaftaran PDF untuk umum diperiksa di `BuktiPendaftaranTest`.
 *
 * @internal
 */
final class KepalaKeamananTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    /**
     * Kepala yang diharapkan dari `CodeIgniter\Filters\SecureHeaders`.
     */
    private const KEPALA = [
        'X-Frame-Options'                   => 'SAMEORIGIN',
        'X-Content-Type-Options'            => 'nosniff',
        'X-Download-Options'                => 'noopen',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'Referrer-Policy'                   => 'same-origin',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testSecureheadersAktifSebagaiGlobalAfter(): void
    {
        $this->assertContains('secureheaders', config(Filters::class)->globals['after']);
    }

    public function testHalamanPublik(): void
    {
        foreach (['/', 'unduhan', 'cek-pendaftaran', 'pendaftaran', 'admin/masuk'] as $alamat) {
            $hasil = $this->withSession([])->get($alamat);

            $hasil->assertOK();
            $this->assertKepalaKeamanan($hasil, $alamat);
        }
    }

    public function testHalamanAdmin(): void
    {
        $p = $this->buatPendaftaranPanel();

        foreach (['admin/dasbor', 'admin/pendaftaran', 'admin/pendaftaran/' . $p['id'], 'admin/rekap', 'admin/pengaturan', 'admin/akun'] as $alamat) {
            $hasil = $this->bukaSebagaiAdmin($alamat);

            $hasil->assertOK();
            $this->assertKepalaKeamanan($hasil, $alamat);
            $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'), $alamat);
        }
    }

    public function testExcelTetapTerunduh(): void
    {
        $this->buatPendaftaranPanel();

        foreach (['admin/pendaftaran/excel', 'admin/atlet/excel', 'admin/rekap/sekolah/excel'] as $alamat) {
            $hasil = $this->bukaSebagaiAdmin($alamat);

            $this->assertSame(200, $hasil->response()->getStatusCode(), $alamat);
            $this->assertKepalaKeamanan($hasil, $alamat);
            $this->assertStringStartsWith('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $hasil->response()->getHeaderLine('Content-Type'), $alamat);
            $this->assertStringStartsWith('PK', $this->isi($hasil), $alamat);
        }
    }

    public function testRekapPdfTetapTerunduh(): void
    {
        $this->buatPendaftaranPanel();

        $hasil = $this->bukaSebagaiAdmin('admin/rekap/keuangan/pdf');

        $hasil->assertOK();
        $this->assertKepalaKeamanan($hasil, 'rekap PDF');
        $this->assertSame('application/pdf', explode(';', $hasil->response()->getHeaderLine('Content-Type'))[0]);
        $this->assertStringStartsWith('%PDF', (string) $hasil->response()->getBody());
    }

    public function testGambarAdminTetapTerbuka(): void
    {
        $p  = $this->buatPendaftaranPanel(['foto' => true]);
        $id = $this->buatBuktiBayarPanel($p['id'])[0];

        foreach ([
            'foto penanggung jawab' => 'admin/pendaftaran/' . $p['id'] . '/foto/' . $p['penanggung_jawab'][0],
            'bukti bayar'           => 'admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id,
            'unduh bukti bayar'     => 'admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id . '/unduh',
        ] as $nama => $alamat) {
            $hasil = $this->bukaSebagaiAdmin($alamat);

            $this->assertSame(200, $hasil->response()->getStatusCode(), $nama);
            $this->assertKepalaKeamanan($hasil, $nama);
            $this->assertStringStartsWith('image/jpeg', $hasil->response()->getHeaderLine('Content-Type'), $nama);
            $this->assertStringStartsWith("\xFF\xD8", $this->isi($hasil), $nama);
        }
    }

    public function testPratinjauUbahTetapBerupaJson(): void
    {
        $p = $this->buatPendaftaranPanel();

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], ['pratinjau' => '1']);

        $hasil->assertOK();
        $this->assertKepalaKeamanan($hasil, 'pratinjau');
        $this->assertStringStartsWith('application/json', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertIsArray(json_decode((string) $hasil->response()->getBody(), true));
    }

    /**
     * Isi jawaban, termasuk unduhan (`DownloadResponse` mengirim isinya
     * langsung, tidak lewat `getBody()`).
     */
    private function isi(TestResponse $hasil): string
    {
        $tanggapan = $hasil->response();

        if (! $tanggapan instanceof DownloadResponse) {
            return (string) $tanggapan->getBody();
        }

        ob_start();
        $tanggapan->sendBody();

        return (string) ob_get_clean();
    }

    private function assertKepalaKeamanan(TestResponse $hasil, string $keterangan): void
    {
        foreach (self::KEPALA as $nama => $nilai) {
            $this->assertSame($nilai, $hasil->response()->getHeaderLine($nama), $keterangan . ': ' . $nama);
        }
    }
}
