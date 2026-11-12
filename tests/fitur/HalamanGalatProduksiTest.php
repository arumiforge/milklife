<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Halaman galat di `CI_ENVIRONMENT = production` (K67, F7-02).
 *
 * Konstanta `ENVIRONMENT` di proses PHPUnit selalu `testing`, jadi uji ini
 * menjalankan aplikasi sungguhan di server bawaan PHP (`php -S`, proses
 * terpisah) dengan `CI_ENVIRONMENT = production`, lalu membuka:
 *
 * - alamat yang tidak ada (404);
 * - alamat dengan karakter terlarang (400, `BadRequestException`);
 * - beranda dengan basis data yang tidak ada, sehingga terjadi pengecualian
 *   yang tidak tertangkap (galat umum, 500).
 *
 * Ketiganya harus ramah, berbahasa Indonesia, dan tanpa jejak teknis.
 * Nilai pengaturan diisikan router `tests/_support/server/router-produksi.php`
 * sebelum CodeIgniter memuat `.env`, jadi didahulukan daripada isi `.env`.
 *
 * @internal
 */
final class HalamanGalatProduksiTest extends CIUnitTestCase
{
    /**
     * Nama basis data yang sengaja tidak ada (bukan rahasia).
     */
    private const BASIS_DATA_TIDAK_ADA = 'milklife_tidak_ada_uji_galat';

    /**
     * Penanda jejak teknis yang tidak boleh terlihat pengunjung.
     */
    private const JEJAK_TEKNIS = [
        'Exception', 'Stack', 'Trace', 'vendor', 'SYSTEMPATH', 'APPPATH', 'ROOTPATH', '.php',
        'CodeIgniter', 'mysqli', 'MySQL', 'SQL', 'Unknown database', 'disallowed', 'Whoops',
        self::BASIS_DATA_TIDAK_ADA, 'Keterangan untuk pengembang', 'galat-teknis">', 'debug-bar',
    ];

    /**
     * @var resource|null
     */
    private static $server;

    private static string $alamat = '';

    /**
     * @var list<string>
     */
    private static array $berkasSesiAwal = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$berkasSesiAwal = glob(WRITEPATH . 'session/*') ?: [];

        $port         = self::portKosong();
        self::$alamat = 'http://127.0.0.1:' . $port;

        $pengaturan = [
            'CI_ENVIRONMENT'                => 'production',
            'app.baseURL'                   => self::$alamat . '/',
            'app.forceGlobalSecureRequests' => 'false',
            'database.default.hostname'     => '127.0.0.1',
            'database.default.database'     => self::BASIS_DATA_TIDAK_ADA,
            'logger.threshold'              => '1',
        ];

        $lingkungan = array_merge(getenv(), [
            'MILKLIFE_UJI_PENGATURAN' => json_encode($pengaturan, JSON_THROW_ON_ERROR),
            'MILKLIFE_UJI_REWRITE'    => SYSTEMPATH . 'rewrite.php',
        ]);

        $perintah = [
            PHP_BINARY,
            '-S', '127.0.0.1:' . $port,
            '-t', ROOTPATH . 'public',
            SUPPORTPATH . 'server/router-produksi.php',
        ];

        // Catatan permintaan dari server bawaan PHP dibuang.
        $buang = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';

        self::$server = proc_open($perintah, [
            0 => ['pipe', 'r'],
            1 => ['file', $buang, 'w'],
            2 => ['file', $buang, 'w'],
        ], $pipa, ROOTPATH, $lingkungan) ?: null;

        // Tunggu server siap, paling lama 10 detik.
        for ($ke = 0; $ke < 100; $ke++) {
            $soket = @fsockopen('127.0.0.1', $port, $kode, $pesan, 0.1);

            if (is_resource($soket)) {
                fclose($soket);

                return;
            }

            usleep(100_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }

        self::$server = null;

        // Berkas sesi yang dibuat server uji dibuang lagi.
        foreach (array_diff(glob(WRITEPATH . 'session/ci_session*') ?: [], self::$berkasSesiAwal) as $berkas) {
            @unlink($berkas);
        }

        parent::tearDownAfterClass();
    }

    public function testServerProduksiBerjalan(): void
    {
        $this->assertIsResource(self::$server, 'Server bawaan PHP tidak bisa dijalankan.');
    }

    public function testHalamanTidakDitemukan(): void
    {
        [$kode, $html] = $this->buka('/halaman-yang-tidak-ada');

        $this->assertSame(404, $kode);
        $this->assertHalamanGalatRamah($html, 'Halaman tidak ditemukan');
        $this->assertStringContainsString('<p class="galat-kode"><span>404</span></p>', $html);
        $this->assertStringContainsString('Alamat yang Anda buka tidak ada atau sudah dipindahkan.', $html);
    }

    public function testPermintaanTidakBisaDiproses(): void
    {
        // `<` tidak termasuk karakter alamat yang diizinkan (`App::$permittedURIChars`).
        [$kode, $html] = $this->buka('/cek%3Cpendaftaran');

        $this->assertSame(400, $kode);
        $this->assertHalamanGalatRamah($html, 'Permintaan tidak bisa diproses');
        $this->assertStringContainsString('<p class="galat-kode"><span>400</span></p>', $html);
    }

    public function testGalatUmumDariPengecualianTakTertangkap(): void
    {
        [$kode, $html] = $this->buka('/');

        $this->assertSame(500, $kode);
        $this->assertHalamanGalatRamah($html, 'Maaf, ada kendala');
        $this->assertStringContainsString('Coba muat ulang halaman ini beberapa menit lagi.', $html);
    }

    public function testGalatAdminJugaRamah(): void
    {
        [$kode, $html] = $this->buka('/admin/pengaturan/tidak-ada');

        $this->assertContains($kode, [302, 303, 404]);

        if ($kode === 404) {
            $this->assertHalamanGalatRamah($html, 'Halaman tidak ditemukan');
        }
    }

    private function assertHalamanGalatRamah(string $html, string $judul): void
    {
        $this->assertStringContainsString('<html lang="id">', $html);
        $this->assertStringContainsString('<h1>' . $judul . '</h1>', $html);
        $this->assertStringContainsString('<a class="galat-tombol" href="' . self::$alamat . '/">Kembali ke beranda</a>', $html);

        foreach (self::JEJAK_TEKNIS as $jejak) {
            $this->assertStringNotContainsStringIgnoringCase($jejak, $html, 'Jejak teknis terlihat: ' . $jejak);
        }
    }

    /**
     * @return array{int, string} kode status dan isi
     */
    private function buka(string $jalur): array
    {
        $this->assertIsResource(self::$server, 'Server bawaan PHP tidak bisa dijalankan.');

        $konteks = stream_context_create(['http' => [
            'method'          => 'GET',
            'ignore_errors'   => true,
            'follow_location' => 0,
            'timeout'         => 30,
            'header'          => "Accept: text/html\r\n",
        ]]);

        $isi   = @file_get_contents(self::$alamat . $jalur, false, $konteks);
        $kepala = $http_response_header ?? [];

        $this->assertNotFalse($isi, 'Server uji tidak menjawab ' . $jalur);
        $this->assertMatchesRegularExpression('#^HTTP/\S+ \d{3}#', $kepala[0] ?? '');
        preg_match('#^HTTP/\S+ (\d{3})#', $kepala[0], $cocok);

        return [(int) $cocok[1], (string) $isi];
    }

    private static function portKosong(): int
    {
        $soket = stream_socket_server('tcp://127.0.0.1:0', $kode, $pesan);
        $nama  = $soket === false ? '' : (string) stream_socket_get_name($soket, false);

        if ($soket !== false) {
            fclose($soket);
        }

        return (int) substr(strrchr($nama, ':') ?: ':0', 1);
    }
}
