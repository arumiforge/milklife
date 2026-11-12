<?php

use App\Controllers\Admin\MasukController;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UjiBasisData;

/**
 * Alamat IP pengunjung saat web dijangkau lewat Cloudflare Tunnel (K31, F3-04).
 *
 * `cloudflared` berjalan di komputer server dan meneruskan pengunjung ke Nginx
 * lokal, jadi permintaannya datang dari `127.0.0.1` atau `::1`. Kepala
 * `CF-Connecting-IP` hanya dipercaya dari kedua alamat itu
 * (`Config\App::$proxyIPs`).
 *
 * Perilaku `IncomingRequest::getIPAddress()` CodeIgniter 4.7 yang diuji di sini:
 * - permintaan dari alamat tepercaya dengan kepala berisi alamat sah: alamat
 *   dari kepala (bila berisi daftar, alamat pertama);
 * - kepala hilang atau berisi alamat tidak sah: alamat komputer itu sendiri;
 * - permintaan dari alamat lain: kepala diabaikan, tetap alamat pengirimnya;
 * - kepala lain (`X-Forwarded-For`) tidak dipercaya.
 *
 * @internal
 */
final class AlamatPengunjungTest extends UjiBasisData
{
    use FeatureTestTrait;

    private const KEPALA = 'CF-Connecting-IP';

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('security');
        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        service('superglobals')->unsetServer('REMOTE_ADDR');

        parent::tearDown();
    }

    public function testHanyaKomputerSendiriYangDipercaya(): void
    {
        $this->assertSame(
            ['127.0.0.1' => self::KEPALA, '::1' => self::KEPALA],
            config('App')->proxyIPs,
        );
    }

    /**
     * @return array<string, array{string, array<string, string>, string}>
     */
    public static function keadaan(): array
    {
        return [
            'lewat tunnel IPv4'                    => ['127.0.0.1', [self::KEPALA => '203.0.113.7'], '203.0.113.7'],
            'lewat tunnel, pengunjung IPv6'        => ['127.0.0.1', [self::KEPALA => '2001:db8::5'], '2001:db8::5'],
            'lewat tunnel dari ::1'                => ['::1', [self::KEPALA => '203.0.113.7'], '203.0.113.7'],
            'kepala berisi daftar alamat'          => ['127.0.0.1', [self::KEPALA => '203.0.113.7, 198.51.100.1'], '203.0.113.7'],
            'kepala hilang'                        => ['127.0.0.1', [], '127.0.0.1'],
            'kepala hilang dari ::1'               => ['::1', [], '::1'],
            'kepala kosong'                        => ['127.0.0.1', [self::KEPALA => ''], '127.0.0.1'],
            'kepala bukan alamat'                  => ['127.0.0.1', [self::KEPALA => 'bukan-alamat'], '127.0.0.1'],
            'kepala alamat rusak'                  => ['127.0.0.1', [self::KEPALA => '999.1.1.1'], '127.0.0.1'],
            'kepala berisi skrip'                  => ['127.0.0.1', [self::KEPALA => '<script>'], '127.0.0.1'],
            'pemalsu dari internet'                => ['198.51.100.9', [self::KEPALA => '203.0.113.7'], '198.51.100.9'],
            'pemalsu dari jaringan lokal'          => ['192.168.1.20', [self::KEPALA => '203.0.113.7'], '192.168.1.20'],
            'pemalsu dari 127.0.0.2'               => ['127.0.0.2', [self::KEPALA => '203.0.113.7'], '127.0.0.2'],
            'pemalsu IPv6'                         => ['2001:db8::9', [self::KEPALA => '203.0.113.7'], '2001:db8::9'],
            'langsung tanpa kepala'                => ['198.51.100.9', [], '198.51.100.9'],
            'X-Forwarded-For tidak dipercaya'      => ['127.0.0.1', ['X-Forwarded-For' => '203.0.113.7'], '127.0.0.1'],
            'X-Real-IP tidak dipercaya'            => ['127.0.0.1', ['X-Real-IP' => '203.0.113.7'], '127.0.0.1'],
        ];
    }

    /**
     * @param array<string, string> $kepala
     */
    #[DataProvider('keadaan')]
    public function testAlamatPengunjung(string $pengirim, array $kepala, string $harapan): void
    {
        $config     = config('App');
        $permintaan = new IncomingRequest($config, new SiteURI($config, 'admin/masuk'), null, new UserAgent());
        $permintaan->setGlobal('server', ['REMOTE_ADDR' => $pengirim]);

        foreach ($kepala as $nama => $nilai) {
            $permintaan->setHeader($nama, $nilai);
        }

        $this->assertSame($harapan, $permintaan->getIPAddress());
    }

    // ---------- Pembatasan percobaan masuk admin ----------

    public function testPembatasanMasukDihitungPerPengunjungTunnel(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= MasukController::BATAS_PERCOBAAN; $ke++) {
            $this->kirimMasuk('127.0.0.1', '203.0.113.7')->assertSee(MasukController::PESAN_GAGAL);
        }

        $this->kirimMasuk('127.0.0.1', '203.0.113.7')->assertStatus(429);

        // Pengunjung lain lewat tunnel yang sama tidak ikut tertahan.
        $hasil = $this->kirimMasuk('127.0.0.1', '203.0.113.8');
        $hasil->assertStatus(200);
        $hasil->assertSee(MasukController::PESAN_GAGAL);

        $hasil = $this->kirimMasuk('::1', '2001:db8::5');
        $hasil->assertStatus(200);
    }

    public function testKepalaPalsuTidakMelewatiPembatasan(): void
    {
        service('throttler')->setTestTime(time());

        // Pengirim langsung mengganti isi kepala setiap kali; semuanya tetap
        // dihitung atas alamatnya sendiri.
        for ($ke = 1; $ke <= MasukController::BATAS_PERCOBAAN; $ke++) {
            $this->kirimMasuk('198.51.100.9', '203.0.113.' . $ke)->assertSee(MasukController::PESAN_GAGAL);
        }

        $this->kirimMasuk('198.51.100.9', '203.0.113.99')->assertStatus(429);

        // Pengunjung tunnel yang alamatnya dipakai pemalsu tidak tertahan.
        $this->kirimMasuk('127.0.0.1', '203.0.113.1')->assertStatus(200);
    }

    /**
     * Kirim formulir masuk dengan sandi salah, dengan token CSRF yang sah.
     */
    private function kirimMasuk(string $pengirim, string $alamatKepala): TestResponse
    {
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));

        Services::resetSingle('security');
        service('superglobals')->setServer('REMOTE_ADDR', $pengirim);

        return $this->withSession([$namaToken => $token])
            ->withHeaders([self::KEPALA => $alamatKepala])
            ->post('admin/masuk', [
                $namaToken      => $token,
                'nama_pengguna' => 'pengguna.karangan',
                'sandi'         => 'bukan-sandinya',
            ]);
    }
}
