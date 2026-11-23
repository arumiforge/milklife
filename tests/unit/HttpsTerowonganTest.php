<?php

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Superglobals;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PulihkanServer;

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
    use PulihkanServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpanServer();
    }

    protected function tearDown(): void
    {
        $this->pulihkanServer();
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
     * @return array<string, array{string, string|null}>
     */
    public static function isianSubnet(): array
    {
        return [
            'IPv4 /24'            => ['10.201.0.0/24', '10.201.0.0/24'],
            'IPv4 dengan spasi'   => [' 10.201.1.0/24 ', '10.201.1.0/24'],
            'IPv6 /64'            => ['fd00:201::/64', 'fd00:201::/64'],
            'kosong'              => ['', null],
            'tanpa awalan'        => ['10.201.0.0', null],
            'bukan alamat'        => ['jaringan/24', null],
            'semua alamat'        => ['0.0.0.0/0', null],
            'awalan terlalu luas' => ['10.0.0.0/4', null],
            'awalan lewat batas'  => ['10.201.0.0/33', null],
        ];
    }

    #[DataProvider('isianSubnet')]
    public function testSubnetProxy(string $isian, ?string $hasil): void
    {
        $this->assertSame($hasil, App::subnetProxy($isian));
    }

    /**
     * Docker (K182): cloudflared di container lain, permintaan datang dari
     * subnet jaringan compose yang diisi lewat MILKLIFE_SUBNET_PROXY.
     */
    public function testSubnetDockerDipercaya(): void
    {
        putenv('MILKLIFE_SUBNET_PROXY=10.201.0.0/24');

        try {
            $konfigurasi = new App();
            $this->assertSame('CF-Connecting-IP', $konfigurasi->proxyIPs['10.201.0.0/24'] ?? null);

            $kepala = ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_CF_CONNECTING_IP' => '203.0.113.7'];

            $dariTunnel = $this->permintaanHttp('10.201.0.3', $kepala, $konfigurasi);
            $this->assertTrue($dariTunnel->isSecure());
            $this->assertSame('203.0.113.7', $dariTunnel->getIPAddress());

            $dariLuar = $this->permintaanHttp('10.202.0.3', $kepala, $konfigurasi);
            $this->assertFalse($dariLuar->isSecure());
            $this->assertSame('10.202.0.3', $dariLuar->getIPAddress());
        } finally {
            putenv('MILKLIFE_SUBNET_PROXY');
        }

        $this->assertSame(['127.0.0.1', '::1'], array_keys((new App())->proxyIPs));
    }

    /**
     * @param array<string, string> $kepala
     */
    private function permintaanHttp(string $alamatPengirim, array $kepala, ?App $konfigurasi = null): IncomingRequest
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

        $konfigurasi ??= config(App::class);

        return new IncomingRequest($konfigurasi, new SiteURI($konfigurasi), null, new UserAgent());
    }
}
