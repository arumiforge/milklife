<?php

use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use CodeIgniter\HTTP\ContentSecurityPolicy as CspBawaan;
use CodeIgniter\HTTP\Response;
use Config\App;
use Config\ContentSecurityPolicy;
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
        foreach (['/', 'unduhan', 'bantuan', 'cek-pendaftaran', 'pendaftaran', 'admin/masuk'] as $alamat) {
            $hasil = $this->withSession([])->get($alamat);

            $hasil->assertOK();
            $this->assertKepalaKeamanan($hasil, $alamat);
        }
    }

    /**
     * CSP menyala (K127, F7-32) dan mengizinkan Cloudflare Turnstile (K122):
     * skrip dan iframe dari challenges.cloudflare.com. Tanpa `unsafe-inline`;
     * gambar boleh `data:` dan `blob:` (pratinjau foto dan ikon Bootstrap).
     */
    public function testCspMenyalaDanMengizinkanTurnstile(): void
    {
        $this->assertTrue(config(App::class)->CSPEnabled);

        $csp = config(ContentSecurityPolicy::class);

        foreach (['scriptSrc', 'scriptSrcElem', 'frameSrc'] as $arahan) {
            $this->assertContains('https://challenges.cloudflare.com', (array) $csp->{$arahan}, $arahan);
            $this->assertContains('self', (array) $csp->{$arahan}, $arahan);
        }

        // Peta lokasi halaman depan (F10-02): hanya iframe, bukan skrip dari Google.
        $this->assertContains('https://www.google.com', (array) $csp->frameSrc);
        $this->assertNotContains('https://www.google.com', (array) $csp->scriptSrc);
        $this->assertNotContains('https://www.google.com', (array) $csp->scriptSrcElem);

        $jawaban = new Response(config(App::class));
        $jawaban->setBody('<script {csp-script-nonce}></script><style {csp-style-nonce}></style>');
        (new CspBawaan($csp))->finalize($jawaban);
        $kepala = $jawaban->getHeaderLine('Content-Security-Policy');

        $this->assertMatchesRegularExpression("#script-src 'self' https://challenges\\.cloudflare\\.com 'nonce-[^']+'#", $kepala);
        $this->assertMatchesRegularExpression("#frame-src 'self' https://challenges\\.cloudflare\\.com https://www\\.google\\.com#", $kepala);
        $this->assertMatchesRegularExpression("#img-src 'self' data: blob:#", $kepala);
        $this->assertStringContainsString("object-src 'none'", $kepala);
        $this->assertStringContainsString("manifest-src 'self'", $kepala);
        $this->assertStringNotContainsString('unsafe-inline', $kepala);
        $this->assertStringNotContainsString('unsafe-eval', $kepala);
        $this->assertMatchesRegularExpression('#^<script nonce="[^"]+"></script><style nonce="[^"]+"></style>$#', (string) $jawaban->getBody());
    }

    /**
     * Tampilan untuk peramban tanpa gaya sebaris, atribut `on…`, atau skrip
     * sebaris tanpa nonce, yang ditolak CSP (F7-32). Templat PDF (mPDF) dan
     * blok data JSON (`type="application/json"`) dikecualikan.
     */
    public function testTampilanTanpaSkripDanGayaSebaris(): void
    {
        $salah = [];
        $berkas = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APPPATH . 'Views', FilesystemIterator::SKIP_DOTS));

        foreach ($berkas as $b) {
            $jalur = str_replace('\\', '/', substr($b->getPathname(), strlen(APPPATH . 'Views') + 1));

            if ($b->getExtension() !== 'php' || str_contains($jalur, 'pdf')) {
                continue;
            }

            // Komentar dokumentasi (`/** … */`) boleh menyebut `<script>`.
            $isi = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($b->getPathname()));

            foreach ([
                'style="'           => '/\sstyle="/',
                'atribut on…'       => '/\son[a-z]+="/',
                'skrip tanpa nonce' => '/<script(?![^>]*(\ssrc=|nonce|type="application\/json"))[^>]*>/',
                'gaya tanpa nonce'  => '/<style(?![^>]*(nonce|csp_style_nonce))[^>]*>/',
            ] as $jenis => $pola) {
                if (preg_match($pola, $isi) === 1) {
                    $salah[] = $jalur . ' (' . $jenis . ')';
                }
            }
        }

        $this->assertSame([], $salah);
    }

    public function testHalamanAdmin(): void
    {
        $p = $this->buatPendaftaranPanel();

        foreach (['admin/dasbor', 'admin/pendaftaran', 'admin/pendaftaran/' . $p['id'], 'admin/rekap', 'admin/pengaturan', 'admin/akun', 'admin/gaya'] as $alamat) {
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
            'foto ofisial' => 'admin/pendaftaran/' . $p['id'] . '/foto/' . $p['penanggung_jawab'][0],
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
