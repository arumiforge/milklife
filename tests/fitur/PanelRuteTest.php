<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiHalaman;

/**
 * Blok rute Tahap 6 (F6-01) dan butir menu Pendaftaran, Atlet, Rekap.
 *
 * Pengendalinya dibuat F6-02, F6-03, F6-04, dan F6-06. Selama belum ada,
 * alamatnya menampilkan halaman tidak ditemukan; yang sudah ada dilewati.
 *
 * @internal
 */
final class PanelRuteTest extends UjiHalaman
{
    use KirimFormulir;

    private const JENIS_REKAP = 'nomor-lomba|tim-beregu|sekolah|kelompok-umur|keuangan';

    /**
     * [metode, pola rute, penangan, contoh alamat].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private const RUTE = [
        ['GET', 'admin/pendaftaran', 'PendaftaranController::index', 'admin/pendaftaran'],
        ['GET', 'admin/pendaftaran/data', 'PendaftaranController::data', 'admin/pendaftaran/data'],
        ['GET', 'admin/pendaftaran/excel', 'PendaftaranController::excel', 'admin/pendaftaran/excel'],
        ['GET', 'admin/pendaftaran/([0-9]+)', 'PendaftaranController::rincian/$1', 'admin/pendaftaran/5'],
        ['GET', 'admin/pendaftaran/([0-9]+)/foto/([0-9]+)', 'PendaftaranController::foto/$1/$2', 'admin/pendaftaran/5/foto/7'],
        ['POST', 'admin/pendaftaran/([0-9]+)/lunas', 'PendaftaranController::lunas/$1', 'admin/pendaftaran/5/lunas'],
        ['POST', 'admin/pendaftaran/([0-9]+)/menunggu', 'PendaftaranController::menunggu/$1', 'admin/pendaftaran/5/menunggu'],
        ['POST', 'admin/pendaftaran/([0-9]+)/hapus', 'PendaftaranController::hapus/$1', 'admin/pendaftaran/5/hapus'],
        ['GET', 'admin/pendaftaran/([0-9]+)/ubah', 'PendaftaranController::ubah/$1', 'admin/pendaftaran/5/ubah'],
        ['POST', 'admin/pendaftaran/([0-9]+)', 'PendaftaranController::perbarui/$1', 'admin/pendaftaran/5'],
        ['POST', 'admin/pendaftaran/([0-9]+)/bukti-bayar', 'BuktiBayarController::unggah/$1', 'admin/pendaftaran/5/bukti-bayar'],
        ['GET', 'admin/pendaftaran/([0-9]+)/bukti-bayar/([0-9]+)', 'BuktiBayarController::lihat/$1/$2', 'admin/pendaftaran/5/bukti-bayar/2'],
        ['GET', 'admin/pendaftaran/([0-9]+)/bukti-bayar/([0-9]+)/unduh', 'BuktiBayarController::unduh/$1/$2', 'admin/pendaftaran/5/bukti-bayar/2/unduh'],
        ['POST', 'admin/pendaftaran/([0-9]+)/bukti-bayar/([0-9]+)/hapus', 'BuktiBayarController::hapus/$1/$2', 'admin/pendaftaran/5/bukti-bayar/2/hapus'],
        ['GET', 'admin/atlet', 'AtletController::index', 'admin/atlet'],
        ['GET', 'admin/atlet/data', 'AtletController::data', 'admin/atlet/data'],
        ['GET', 'admin/atlet/excel', 'AtletController::excel', 'admin/atlet/excel'],
        ['GET', 'admin/rekap', 'RekapController::index', 'admin/rekap'],
        ['GET', 'admin/rekap/gabungan/excel', 'RekapController::semua', 'admin/rekap/gabungan/excel'],
        ['GET', 'admin/rekap/(' . self::JENIS_REKAP . ')', 'RekapController::index/$1', 'admin/rekap/keuangan'],
        ['GET', 'admin/rekap/(' . self::JENIS_REKAP . ')/data', 'RekapController::data/$1', 'admin/rekap/sekolah/data'],
        ['GET', 'admin/rekap/(' . self::JENIS_REKAP . ')/excel', 'RekapController::excel/$1', 'admin/rekap/tim-beregu/excel'],
        ['GET', 'admin/rekap/(' . self::JENIS_REKAP . ')/pdf', 'RekapController::pdf/$1', 'admin/rekap/kelompok-umur/pdf'],
    ];

    public function testSemuaRuteTahapEnamTerdaftarDanDijagaFilterAdmin(): void
    {
        $rute = service('routes')->loadRoutes();

        foreach (self::RUTE as [$metode, $pola, $penangan]) {
            $daftar = $rute->getRoutes($metode);

            $this->assertArrayHasKey($pola, $daftar, "{$metode} {$pola} belum terdaftar.");
            $this->assertSame('\App\Controllers\Admin\\' . $penangan, $daftar[$pola], "{$metode} {$pola}");
            $this->assertContains('admin', $rute->getFiltersForRoute($pola, $metode), "{$metode} {$pola} harus dijaga filter admin.");
        }
    }

    public function testRuteMengarahKePenanganYangBenar(): void
    {
        foreach (self::RUTE as [$metode, , $penangan, $alamat]) {
            $permintaan = (new IncomingRequest(config('App'), new SiteURI(config('App'), $alamat), null, new UserAgent()))->withMethod($metode);
            $router     = service('router', service('routes', false)->loadRoutes(), $permintaan, false);
            $router->handle($alamat);

            [$pengendali, $metodePengendali] = explode('::', explode('/', $penangan)[0]);
            $this->assertSame('\App\Controllers\Admin\\' . $pengendali, $router->controllerName(), "{$metode} {$alamat}");
            $this->assertSame($metodePengendali, $router->methodName(), "{$metode} {$alamat}");
        }
    }

    public function testSemuaAlamatMenolakYangBelumMasuk(): void
    {
        foreach (self::RUTE as [$metode, , , $alamat]) {
            $this->assertBelumMasukDialihkan($alamat, strtolower($metode));
        }
    }

    public function testJenisRekapLainTidakDitemukan(): void
    {
        foreach (['admin/rekap/lain', 'admin/rekap/lain/excel', 'admin/rekap/../excel', 'admin/pendaftaran/abc', 'admin/pendaftaran/5/foto/x'] as $alamat) {
            try {
                $this->bukaSebagaiAdmin($alamat);
                $this->fail("{$alamat} seharusnya tidak ditemukan.");
            } catch (PageNotFoundException $e) {
                $this->assertSame(404, $e->getCode(), $alamat);
            }
        }
    }

    public function testRuteTanpaPengendaliMenampilkanHalamanTidakDitemukan(): void
    {
        foreach (self::RUTE as [$metode, , $penangan, $alamat]) {
            if ($metode !== 'GET' || class_exists('App\Controllers\Admin\\' . explode('::', $penangan)[0])) {
                continue;
            }

            try {
                $this->bukaSebagaiAdmin($alamat);
                $this->fail("{$alamat} seharusnya tidak ditemukan selama pengendalinya belum ada.");
            } catch (PageNotFoundException $e) {
                $this->assertSame(404, $e->getCode(), $alamat);
            }
        }

        // Setelah semua tugas Tahap 6 digabung, tidak ada lagi yang diperiksa.
        $this->addToAssertionCount(1);
    }

    public function testMenuPanelDataMenandaiHalamannya(): void
    {
        $_SESSION = self::SESI_ADMIN;

        foreach ([
            'admin/pendaftaran'                => 'Pendaftaran',
            'admin/pendaftaran/5'              => 'Pendaftaran',
            'admin/pendaftaran/5/bukti-bayar/2' => 'Pendaftaran',
            'admin/atlet'                      => 'Atlet',
            'admin/rekap'                      => 'Rekap',
            'admin/rekap/keuangan'             => 'Rekap',
            // Pengaturan pendaftaran tetap menandai Pengaturan, bukan Pendaftaran.
            'admin/pengaturan/pendaftaran'     => 'Pengaturan',
        ] as $alamat => $label) {
            $xpath = $this->xpathHtml($this->renderDiAlamat($alamat, 'Tests\Support\Views\contoh_admin'));
            $aktif = [];

            foreach ($xpath->query('//a[contains(@class, "menu-admin-tautan")][@aria-current="page"]') as $tautan) {
                $aktif[] = trim($tautan->textContent);
            }

            // Menu samping dan menu bawah.
            $this->assertSame([$label, $label], $aktif, $alamat);
        }
    }

    private function xpathHtml(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8">' . $html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    /**
     * Render tampilan seolah-olah dibuka di alamat tertentu (untuk `url_is()`).
     */
    private function renderDiAlamat(string $alamat, string $tampilan): string
    {
        $permintaan = new IncomingRequest(config('App'), new SiteURI(config('App'), $alamat), null, new UserAgent());
        Services::injectMock('request', $permintaan);

        try {
            return view($tampilan);
        } finally {
            Services::resetSingle('request');
        }
    }
}
