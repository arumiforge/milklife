<?php

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Superglobals;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * HTTPS di balik Cloudflare Tunnel (asumsi 3 rencana Tahap 7, F7-02).
 *
 * Di server produksi `cloudflared` berjalan di komputer yang sama dan
 * meneruskan permintaan ke Nginx lewat HTTP biasa, dengan kepala
 * `X-Forwarded-Proto: https` dari Cloudflare. Uji ini mengunci alasan nilai
 * `app.forceGlobalSecureRequests = true` di bagian Produksi `.env.example`:
 * `IncomingRequest::isSecure()` menganggap permintaan itu sudah HTTPS
 * karena datang dari alamat di `App::$proxyIPs`, jadi tidak ada pengalihan
 * berulang. Permintaan HTTP dari alamat lain tetap dialihkan ke `https://`.
 *
 * @internal
 */
final class HttpsTerowonganTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::resetSingle('superglobals');
        parent::tearDown();
    }

    public function testProxyHanyaKomputerSendiri(): void
    {
        $this->assertSame(['127.0.0.1', '::1'], array_keys(config(App::class)->proxyIPs));
    }

    /**
     * @return array<string, array{string, array<string, string>, bool}>
     */
    public static function permintaan(): array
    {
        return [
            'cloudflared IPv4, HTTPS di Cloudflare' => ['127.0.0.1', ['HTTP_X_FORWARDED_PROTO' => 'https'], true],
            'cloudflared IPv6, HTTPS di Cloudflare' => ['::1', ['HTTP_X_FORWARDED_PROTO' => 'https'], true],
            'cloudflared tanpa kepala proto'        => ['127.0.0.1', [], false],
            'cloudflared, proto http'               => ['127.0.0.1', ['HTTP_X_FORWARDED_PROTO' => 'http'], false],
            'jaringan lokal memalsukan kepala'      => ['192.168.1.20', ['HTTP_X_FORWARDED_PROTO' => 'https'], false],
            'jaringan lokal tanpa kepala'           => ['192.168.1.20', [], false],
        ];
    }

    /**
     * @param array<string, string> $kepala
     */
    #[DataProvider('permintaan')]
    public function testIsSecure(string $alamatPengirim, array $kepala, bool $aman): void
    {
        $this->assertSame($aman, $this->permintaanHttp($alamatPengirim, $kepala)->isSecure());
    }

    /**
     * @param array<string, string> $kepala
     */
    private function permintaanHttp(string $alamatPengirim, array $kepala): IncomingRequest
    {
        $server = [
            'REMOTE_ADDR'     => $alamatPengirim,
            'HTTP_HOST'       => 'milklife.test',
            'REQUEST_METHOD'  => 'GET',
            'REQUEST_URI'     => '/',
            'SCRIPT_NAME'     => '/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ] + $kepala;

        Services::injectMock('superglobals', new Superglobals($server, [], [], [], [], []));

        $konfigurasi = config(App::class);

        return new IncomingRequest($konfigurasi, new SiteURI($konfigurasi), null, new UserAgent());
    }
}
