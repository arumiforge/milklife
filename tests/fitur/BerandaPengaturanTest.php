<?php

use App\Controllers\Admin\Pengaturan\BerandaPengaturanController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiHalaman;

/**
 * Halaman daftar pengaturan, menu Pengaturan, tautan Akun, potongan tampilan
 * bersama (`admin/_pesan`, `admin/_galat_isian`, `admin/pengaturan/_navigasi`),
 * dan rute Tahap 2 (F2-01).
 *
 * @internal
 */
final class BerandaPengaturanTest extends UjiHalaman
{
    use KirimFormulir;

    /**
     * Semua rute Tahap 2: [metode, alamat, pengendali::metode].
     */
    private const RUTE = [
        ['GET', 'admin/pengaturan', 'Pengaturan\BerandaPengaturanController::index'],
        ['GET', 'admin/pengaturan/acara', 'Pengaturan\AcaraController::index'],
        ['POST', 'admin/pengaturan/acara', 'Pengaturan\AcaraController::simpan'],
        ['GET', 'admin/pengaturan/pendaftaran', 'Pengaturan\PendaftaranController::index'],
        ['POST', 'admin/pengaturan/pendaftaran', 'Pengaturan\PendaftaranController::simpan'],
        ['GET', 'admin/pengaturan/pembayaran', 'Pengaturan\PembayaranController::index'],
        ['POST', 'admin/pengaturan/pembayaran', 'Pengaturan\PembayaranController::simpan'],
        ['GET', 'admin/pengaturan/halaman-depan', 'Pengaturan\HalamanDepanController::index'],
        ['POST', 'admin/pengaturan/halaman-depan', 'Pengaturan\HalamanDepanController::simpan'],
        ['GET', 'admin/akun', 'AkunController::index'],
        ['POST', 'admin/akun', 'AkunController::simpan'],
        ['POST', 'admin/akun/sandi', 'AkunController::simpanSandi'],
    ];

    /**
     * Halaman daftar data: dasar alamat => pengendali.
     */
    private const DAFTAR_DATA = [
        'admin/pengaturan/kelompok-umur' => 'Pengaturan\KelompokUmurController',
        'admin/pengaturan/nomor-lomba'   => 'Pengaturan\NomorLombaController',
        'admin/pengaturan/jadwal'        => 'Pengaturan\JadwalController',
        'admin/pengaturan/kontak'        => 'Pengaturan\KontakController',
        'admin/pengaturan/unduhan'       => 'Pengaturan\UnduhanController',
    ];

    // ---------- Halaman daftar pengaturan ----------

    public function testDaftarPengaturanTerbukaUntukAdmin(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/pengaturan');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Pengaturan', $this->teks($xpath->query('//header//h1')->item(0)));
        $this->assertSame('Pengaturan – Admin MilkLife', $this->teks($xpath->query('//title')->item(0)));

        // Sepuluh kartu: sembilan halaman pengaturan dan Akun, berurutan, bertautan.
        $kartu = [];

        foreach ($xpath->query('//main//a[contains(@class, "kartu-pengaturan")]') as $tautan) {
            $kartu[] = [
                $tautan->getAttribute('data-kunci'),
                $this->teks($xpath->query('.//*[contains(@class, "kartu-pengaturan-judul")]', $tautan)->item(0)),
                $tautan->getAttribute('href'),
            ];
        }

        $this->assertSame([
            ['acara', 'Data Acara', site_url('admin/pengaturan/acara')],
            ['pendaftaran', 'Pendaftaran', site_url('admin/pengaturan/pendaftaran')],
            ['kelompok-umur', 'Kelompok Umur', site_url('admin/pengaturan/kelompok-umur')],
            ['nomor-lomba', 'Nomor Lomba', site_url('admin/pengaturan/nomor-lomba')],
            ['pembayaran', 'Pembayaran', site_url('admin/pengaturan/pembayaran')],
            ['halaman-depan', 'Halaman Depan', site_url('admin/pengaturan/halaman-depan')],
            ['jadwal', 'Jadwal', site_url('admin/pengaturan/jadwal')],
            ['unduhan', 'Unduhan', site_url('admin/pengaturan/unduhan')],
            ['kontak', 'Kontak', site_url('admin/pengaturan/kontak')],
            ['akun', 'Akun', site_url('admin/akun')],
        ], $kartu);

        // Dikelompokkan dengan judul h2; tiap kartu berketerangan.
        $judul = [];

        foreach ($xpath->query('//main//section/h2') as $h2) {
            $judul[] = $this->teks($h2);
        }

        $this->assertSame(['Wajib diisi sebelum pendaftaran dibuka', 'Isi halaman depan', 'Akun Anda'], $judul);
        $hasil->assertSee('Batas usia, jenjang sekolah yang boleh ikut, biaya, dan awalan nomor dada.');

        // Belum ada tanda lengkap/belum sampai F2-09 mengisinya.
        $this->assertSame(0, $xpath->query('//*[contains(@class, "tanda-kelengkapan")]')->length);
    }

    public function testSetiapKartuPunyaIkonDanKeterangan(): void
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan'));

        foreach ($xpath->query('//main//a[contains(@class, "kartu-pengaturan")]') as $tautan) {
            $this->assertSame(1, $xpath->query('.//*[contains(@class, "kartu-pengaturan-ikon")]/i[@aria-hidden="true"]', $tautan)->length);
            $this->assertNotSame('', $this->teks($xpath->query('.//*[contains(@class, "text-body-secondary")]', $tautan)->item(0)));
        }
    }

    public function testDaftarPengaturanTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan('admin/pengaturan');
    }

    public function testTandaKelengkapanTampilBilaDiisi(): void
    {
        $halaman                              = BerandaPengaturanController::daftarHalaman();
        $halaman[0]['lengkap']                = true;
        $halaman[2]['lengkap']                = false;
        $halaman[2]['keterangan_kelengkapan'] = 'KU 10 belum punya jenjang <sekolah>.';
        $kelompok                             = [];

        foreach ($halaman as $butir) {
            $kelompok[$butir['kelompok']][] = $butir;
        }

        $_SESSION = self::SESI_ADMIN;
        $html     = view('admin/pengaturan/index', ['kelompok' => $kelompok]);

        $this->assertSame(1, substr_count($html, '> Lengkap'));
        $this->assertSame(1, substr_count($html, '> Belum lengkap'));
        $this->assertStringContainsString('KU 10 belum punya jenjang &lt;sekolah&gt;.', $html);
    }

    public function testDaftarHalamanSamaUntukKartuDanNavigasi(): void
    {
        $halaman = BerandaPengaturanController::daftarHalaman();

        $this->assertCount(10, $halaman);
        $this->assertSame(array_unique(array_column($halaman, 'kunci')), array_column($halaman, 'kunci'));

        foreach ($halaman as $butir) {
            $this->assertNull($butir['lengkap']);
            $this->assertSame('', $butir['keterangan_kelengkapan']);
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $butir['ikon']);
            $this->assertStringStartsWith('admin/', $butir['alamat']);
        }
    }

    // ---------- Menu dan kepala admin ----------

    public function testMenuPengaturanAktifDiHalamanPengaturanDanAkun(): void
    {
        $_SESSION = self::SESI_ADMIN;

        foreach (['admin/pengaturan', 'admin/pengaturan/acara', 'admin/pengaturan/kelompok-umur/3/ubah', 'admin/akun'] as $alamat) {
            $xpath = $this->xpathHtml($this->renderDiAlamat($alamat, 'Tests\Support\Views\contoh_admin'));
            $aktif = [];

            foreach ($xpath->query('//a[contains(@class, "menu-admin-tautan")][@aria-current="page"]') as $tautan) {
                $aktif[] = $this->teks($tautan);
            }

            // Menu samping dan menu bawah.
            $this->assertSame(['Pengaturan', 'Pengaturan'], $aktif, $alamat);
        }

        $xpath = $this->xpathHtml($this->renderDiAlamat('admin/pengaturanlain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//*[@aria-current]')->length);
    }

    public function testNamaAdminDiKepalaMenautkeAkun(): void
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));
        $nama  = $xpath->query('//header[contains(@class, "kepala-admin")]//a[contains(@class, "kepala-admin-nama")]')->item(0);

        $this->assertNotNull($nama);
        $this->assertSame(site_url('admin/akun'), $nama->getAttribute('href'));
        $this->assertStringContainsString('Admin Uji', $this->teks($nama));
    }

    // ---------- Potongan tampilan bersama ----------

    public function testPesanKilatDitampilkanTataLetak(): void
    {
        $_SESSION = self::SESI_ADMIN;
        session()->setFlashdata('pesan_sukses', 'Data <acara> tersimpan.');
        session()->setFlashdata('pesan_galat', 'Tidak bisa dihapus. Matikan saja.');

        $xpath  = $this->xpathHtml(view('Tests\Support\Views\contoh_admin'));
        $sukses = $xpath->query('//main[@id="isi"]//*[@data-pesan="pesan_sukses"]')->item(0);
        $galat  = $xpath->query('//main[@id="isi"]//*[@data-pesan="pesan_galat"]')->item(0);

        $this->assertNotNull($sukses);
        $this->assertNotNull($galat);
        $this->assertSame('Data <acara> tersimpan.', $this->teks($sukses));
        $this->assertSame('status', $sukses->getAttribute('role'));
        $this->assertSame('alert', $galat->getAttribute('role'));
        $this->assertStringContainsString('alert-danger', $galat->getAttribute('class'));
        $this->assertSame(1, $xpath->query('.//button[@data-bs-dismiss="alert"][@aria-label="Tutup pesan"]', $sukses)->length);

        // Pesan berada di atas isi halaman.
        $this->assertSame(1, $xpath->query('//main[@id="isi"]/*[1][contains(@class, "pesan-kilat")]')->length);
    }

    public function testTanpaPesanKilatTidakAdaKotakPesan(): void
    {
        $_SESSION = self::SESI_ADMIN;

        $this->assertStringNotContainsString('pesan-kilat', view('Tests\Support\Views\contoh_admin'));
        $this->assertStringNotContainsString('pesan-kilat', $this->bukaSebagaiAdmin('admin/pengaturan')->getBody());
    }

    public function testRingkasanGalatIsian(): void
    {
        $html  = view('admin/_galat_isian', ['galat' => ['nama' => 'Nama belum diisi <b>.', 'jenjang.*' => 'Pilih jenjang.', 'kosong' => '']], ['saveData' => false]);
        $xpath = $this->xpathHtml($html);

        $this->assertSame(1, $xpath->query('//*[@id="galat-isian"][@role="alert"]')->length);
        $this->assertStringContainsString('Ada 2 isian yang perlu diperbaiki:', $html);
        $this->assertStringContainsString('Nama belum diisi &lt;b&gt;.', $html);
        $this->assertSame(1, $xpath->query('//a[@href="#nama"]')->length);
        $this->assertSame(0, $xpath->query('//a[contains(@href, "jenjang")]')->length, 'Kunci yang bukan id kotak tidak menjadi tautan.');
        $this->assertSame(2, $xpath->query('//li')->length);

        $this->assertStringContainsString('Ada 1 isian yang perlu diperbaiki:', view('admin/_galat_isian', ['galat' => ['nama' => 'X']], ['saveData' => false]));
        $this->assertStringContainsString('Periksa lagi:', view('admin/_galat_isian', ['galat' => ['nama' => 'X'], 'judul' => 'Periksa lagi:'], ['saveData' => false]));
        $this->assertStringNotContainsString('galat-isian', view('admin/_galat_isian', ['galat' => []], ['saveData' => false]));
        $this->assertStringNotContainsString('galat-isian', view('admin/_galat_isian', [], ['saveData' => false]));
    }

    public function testNavigasiPengaturan(): void
    {
        $_SESSION = self::SESI_ADMIN;
        $xpath    = $this->xpathHtml($this->renderDiAlamat('admin/pengaturan/kelompok-umur/3/ubah', 'admin/pengaturan/_navigasi'));

        $nav = $xpath->query('//nav[@aria-label="Navigasi pengaturan"]')->item(0);
        $this->assertNotNull($nav);
        $this->assertSame(site_url('admin/pengaturan'), $xpath->query('.//a[contains(@class, "navigasi-pengaturan-kembali")]', $nav)->item(0)?->getAttribute('href'));

        $tautan = [];

        foreach ($xpath->query('.//a[contains(@class, "navigasi-pengaturan-tautan")]', $nav) as $a) {
            $tautan[$a->getAttribute('href')] = $a->getAttribute('aria-current');
        }

        $this->assertSame(array_map(static fn (array $b): string => site_url($b['alamat']), BerandaPengaturanController::daftarHalaman()), array_keys($tautan));
        $this->assertSame(['page'], array_values(array_filter($tautan)));
        $this->assertSame('page', $tautan[site_url('admin/pengaturan/kelompok-umur')]);
    }

    // ---------- Rute ----------

    public function testSemuaRuteTahapDuaTerdaftarDanDijagaFilterAdmin(): void
    {
        $rute    = service('routes')->loadRoutes();
        $harapan = self::RUTE;

        foreach (self::DAFTAR_DATA as $dasar => $pengendali) {
            $harapan[] = ['GET', $dasar, $pengendali . '::index'];
            $harapan[] = ['GET', $dasar . '/tambah', $pengendali . '::tambah'];
            $harapan[] = ['POST', $dasar, $pengendali . '::buat'];
            $harapan[] = ['GET', $dasar . '/([0-9]+)/ubah', $pengendali . '::ubah/$1'];
            $harapan[] = ['POST', $dasar . '/([0-9]+)', $pengendali . '::perbarui/$1'];
            $harapan[] = ['POST', $dasar . '/([0-9]+)/hapus', $pengendali . '::hapus/$1'];
        }

        foreach ($harapan as [$metode, $alamat, $penangan]) {
            $daftar = $rute->getRoutes($metode);

            $this->assertArrayHasKey($alamat, $daftar, "{$metode} {$alamat} belum terdaftar.");
            $this->assertSame('\App\Controllers\Admin\\' . $penangan, $daftar[$alamat], "{$metode} {$alamat}");
            $this->assertContains('admin', $rute->getFiltersForRoute($alamat, $metode), "{$metode} {$alamat} harus dijaga filter admin.");
        }
    }

    public function testRuteTanpaPengendaliMenampilkanHalamanTidakDitemukan(): void
    {
        // Selama pengendalinya belum dibuat tugasnya (F2-02 sampai F2-08),
        // alamatnya 404, bukan galat. Yang sudah ada dilewati.
        $halaman = array_merge(
            array_filter(self::RUTE, static fn (array $r): bool => $r[0] === 'GET'),
            array_map(static fn (string $dasar, string $p): array => ['GET', $dasar, $p . '::index'], array_keys(self::DAFTAR_DATA), self::DAFTAR_DATA),
        );
        $sudahAda  = 0;
        $diperiksa = 0;

        foreach ($halaman as [, $alamat, $penangan]) {
            if (class_exists('App\Controllers\Admin\\' . explode('::', $penangan)[0])) {
                $sudahAda++;

                continue;
            }

            $diperiksa++;

            try {
                $this->bukaSebagaiAdmin($alamat);
                $this->fail("{$alamat} seharusnya tidak ditemukan selama pengendalinya belum ada.");
            } catch (PageNotFoundException $e) {
                $this->assertSame(404, $e->getCode());
            }
        }

        $this->assertSame(count($halaman), $sudahAda + $diperiksa);
    }

    // ---------- Pengaturan tidak terbawa antar-uji (tanpa basis data) ----------

    public function testPengaturanDiMemoriMenulis(): void
    {
        setting('Acara.nama', 'Nilai Uji Sebelumnya');

        $this->assertSame('Nilai Uji Sebelumnya', setting('Acara.nama'));
        $this->assertSame(['array'], config('Settings')->handlers, 'Uji halaman tidak memakai basis data untuk pengaturan.');
    }

    public function testPengaturanDiMemoriTidakTerbawa(): void
    {
        $this->assertSame('', setting('Acara.nama'));
    }

    private function renderDiAlamat(string $alamat, string $namaView): string
    {
        $permintaan = new IncomingRequest(config('App'), new SiteURI(config('App'), $alamat), null, new UserAgent());
        Services::injectMock('request', $permintaan);

        try {
            return view($namaView);
        } finally {
            Services::resetSingle('request');
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

    private function teks(?DOMNode $simpul): string
    {
        $this->assertNotNull($simpul);

        return trim((string) preg_replace('/\s+/u', ' ', $simpul->textContent));
    }
}
