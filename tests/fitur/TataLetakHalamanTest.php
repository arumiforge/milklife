<?php

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\Services;
use Tests\Support\UjiHalaman;

/**
 * Tata letak publik, admin, dan polos yang jadi (F1-05), beserta komponen
 * "tanpa gambar" dan bilah kemajuan. Sejak F2-02 nama dan data acara diambil
 * dari pengaturan, jadi uji yang memeriksanya mengisi pengaturan lebih dulu.
 *
 * @internal
 */
final class TataLetakHalamanTest extends UjiHalaman
{
    // ---------- Publik ----------

    public function testHalamanDepanMemakaiTataLetakPublik(): void
    {
        $this->isiDataAcara();

        $hasil = $this->get('/');
        $hasil->assertOK();

        $html  = $hasil->getBody();
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//body[@data-bs-theme="light"]')->length, 'Tema terang harus dipasang.');
        $this->assertSame(1, $xpath->query('//header[contains(@class, "kepala-publik")]')->length);
        $this->assertSame(1, $xpath->query('//header//nav[@aria-label="Menu utama"]')->length);
        $this->assertSame(1, $xpath->query('//main[@id="isi"]')->length);
        $this->assertSame(1, $xpath->query('//a[@href="#isi"][contains(., "Lompat ke isi")]')->length);

        // Kepala memuat nama acara sebagai teks.
        $merek = $xpath->query('//header//a[contains(@class, "navbar-brand")]')->item(0);
        $this->assertNotNull($merek);
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($merek));

        // Kaki: nama acara dan tahun.
        $kaki = $xpath->query('//footer')->item(0);
        $this->assertNotNull($kaki);
        $this->assertSame('MilkLife Athletics Challenge 2026', $this->teks($kaki));

        // Kepala halaman depan (sejak F3-01; rinciannya di HalamanDepanKepalaTest).
        $this->assertSame(1, $xpath->query('//main//h1')->length, 'Halaman depan punya tepat satu h1.');
        $hasil->assertSee('Pendaftaran sedang disiapkan.');
        $hasil->assertSee('Seri 2 – 2026');
        $hasil->assertSee('9–12 Desember 2026');
        $hasil->assertSee('Supersoccer Arena, Kudus');
        $this->assertSame(1, $xpath->query('//main/section[@id="pembuka"]')->length);
    }

    public function testMenuPublikDenganButirNonAktif(): void
    {
        $xpath = $this->xpath($this->get('/')->getBody());
        $butir = [];

        foreach ($xpath->query('//nav[@aria-label="Menu utama"]//*[contains(@class, "nav-link")]') as $tautan) {
            $butir[$this->teks($tautan)] = $tautan;
        }

        // K81: menu mengikuti konsep visual; Pendaftaran dan Cek ada di tombol pembuka.
        $this->assertSame(['Tentang', 'Kategori', 'Jadwal', 'Unduhan'], array_keys($butir));
        $this->assertSame(site_url('/') . '#pembuka', $butir['Tentang']->getAttribute('href'));
        $this->assertSame(site_url('/') . '#lomba', $butir['Kategori']->getAttribute('href'));
        $this->assertSame(site_url('/') . '#jadwal', $butir['Jadwal']->getAttribute('href'));

        // Unduhan punya halaman sendiri (F3-03, K40): bertautan, tidak aktif di halaman depan.
        $this->assertSame(site_url('unduhan'), $butir['Unduhan']->getAttribute('href'));
        $this->assertFalse($butir['Unduhan']->hasAttribute('aria-current'));

        foreach ($butir as $label => $tautan) {
            $this->assertFalse($tautan->hasAttribute('aria-disabled'), $label);
        }

        // Tombol Panitia ke halaman masuk admin, tanpa ikon panah.
        $panitia = $xpath->query('//nav[@aria-label="Menu utama"]//a[contains(@class, "tombol-panitia")]')->item(0);
        $this->assertNotNull($panitia);
        $this->assertSame('Panitia', $this->teks($panitia));
        $this->assertSame(site_url('admin/masuk'), $panitia->getAttribute('href'));
        $this->assertSame(0, $xpath->query('.//i', $panitia)->length);

        // Halaman depan memakai kepala terang; halaman lain tetap biru dan Unduhan aktif di halamannya.
        $this->assertSame(1, $xpath->query('//header[contains(@class, "kepala-beranda")]')->length);
        $unduhan = $this->xpath($this->get('unduhan')->getBody());
        $this->assertSame(0, $unduhan->query('//header[contains(@class, "kepala-beranda")]')->length);
        $this->assertSame('page', $unduhan->query('//nav[@aria-label="Menu utama"]//a[normalize-space()="Unduhan"]')->item(0)?->getAttribute('aria-current'));

        // Menu melipat di ponsel.
        $tombol = $xpath->query('//button[@data-bs-toggle="collapse"][@data-bs-target="#menu-publik"]')->item(0);
        $this->assertNotNull($tombol);
        $this->assertSame('menu-publik', $tombol->getAttribute('aria-controls'));
        $this->assertSame(1, $xpath->query('//*[@id="menu-publik"][contains(@class, "collapse")]')->length);
    }

    // ---------- Admin ----------

    public function testDasborMemakaiTataLetakAdmin(): void
    {
        $hasil = $this->withSession(self::SESI_ADMIN)->get('admin/dasbor');
        $hasil->assertOK();

        $html  = $hasil->getBody();
        $xpath = $this->xpath($html);

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertSame(1, $xpath->query('//body[@data-bs-theme="light"]')->length);

        // Kepala halaman menempel: judul halaman, nama admin, tombol Keluar.
        $kepala = $xpath->query('//header[contains(@class, "kepala-admin")]')->item(0);
        $this->assertNotNull($kepala);
        $this->assertSame('Dasbor', $this->teks($xpath->query('.//h1', $kepala)->item(0)));
        $this->assertStringContainsString('Admin Uji', $this->teks($kepala));
        $this->assertSame(1, $xpath->query('.//form[@method="post"]//button[@type="submit"][contains(., "Keluar")]', $kepala)->length);
        $this->assertFormulirKeluarBerCsrf($xpath);

        // Menu samping dan menu bawah.
        $samping = $xpath->query('//aside[contains(@class, "menu-samping")]//nav[@aria-label]')->item(0);
        $bawah   = $xpath->query('//nav[contains(@class, "menu-bawah")][@aria-label]')->item(0);
        $this->assertNotNull($samping, 'Menu samping tidak ada.');
        $this->assertNotNull($bawah, 'Menu bawah tidak ada.');
        $this->assertStringContainsString('d-lg-none', $bawah->getAttribute('class'));
        $this->assertStringContainsString('d-lg-flex', $samping->parentNode->getAttribute('class'));

        // Kedua menu berasal dari daftar yang sama.
        $this->assertSame($this->daftarMenu($xpath, $samping), $this->daftarMenu($xpath, $bawah));
        $this->assertSame([
            ['Dasbor', site_url('admin/dasbor'), 'page'],
            // Panel data (Tahap 6, F6-01).
            ['Pendaftaran', site_url('admin/pendaftaran'), ''],
            ['Atlet', site_url('admin/atlet'), ''],
            ['Rekap', site_url('admin/rekap'), ''],
            ['Pengaturan', site_url('admin/pengaturan'), ''],
        ], $this->daftarMenu($xpath, $samping));

        // Ikon dekoratif disembunyikan dari pembaca layar.
        foreach ($xpath->query('//nav[contains(@class, "menu-bawah")]//i[contains(@class, "bi")]') as $ikon) {
            $this->assertSame('true', $ikon->getAttribute('aria-hidden'));
        }

        // Tanpa teks footer.
        $this->assertSame(0, $xpath->query('//footer')->length, 'Panel admin tidak boleh punya footer.');

        // Bilah kemajuan: ada, tersembunyi sampai dipanggil, skripnya dimuat.
        $bilah = $xpath->query('//*[@id="bilah-kemajuan"]')->item(0);
        $this->assertNotNull($bilah);
        $this->assertTrue($bilah->hasAttribute('hidden'));
        $this->assertStringContainsString('"' . aset('js/kemajuan.js') . '"', $html);

        // Keadaan kosong yang ramah, tanpa angka karangan.
        $isi = $this->teks($xpath->query('//main[@id="isi"]')->item(0));
        $this->assertStringContainsString('Belum ada pendaftaran', $isi);
        $this->assertStringContainsString('Langkah berikutnya', $isi);

        // Contoh komponen tanpa gambar.
        $this->assertSame(1, $xpath->query('//main//*[contains(@class, "tanpa-gambar")][@role="img"]')->length);
    }

    public function testPenandaMenuAktifMengikutiAlamat(): void
    {
        $_SESSION = self::SESI_ADMIN;

        // Halaman di bawah butir menu ikut menandai butir itu.
        $xpath = $this->xpath($this->renderDiAlamat('admin/dasbor/rincian', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(2, $xpath->query('//a[contains(@class, "menu-admin-tautan")][@aria-current="page"]')->length);

        // Halaman lain tidak menandai Dasbor.
        $xpath = $this->xpath($this->renderDiAlamat('admin/lain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//*[@aria-current]')->length);
        $this->assertSame(10, $xpath->query('//a[contains(@class, "menu-admin-tautan")]')->length);

        // Alamat yang hanya berawalan sama tidak dianggap halaman di bawahnya.
        $xpath = $this->xpath($this->renderDiAlamat('admin/dasborlain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//*[@aria-current]')->length);
    }

    public function testJudulKepalaAdminMemakaiJudulBilaJudulHalamanKosong(): void
    {
        $_SESSION = self::SESI_ADMIN;
        $xpath    = $this->xpath(view('Tests\Support\Views\contoh_admin'));

        $this->assertSame('Judul Contoh admin', $this->teks($xpath->query('//header//h1')->item(0)));
    }

    public function testSkripKemajuanDimuatSebelumSkripHalaman(): void
    {
        $_SESSION = self::SESI_ADMIN;
        $html     = view('Tests\Support\Views\contoh_admin');

        $posisiBootstrap = strpos($html, aset('vendor/bootstrap/js/bootstrap.bundle.min.js'));
        $posisiKemajuan  = strpos($html, aset('js/kemajuan.js'));
        $posisiSkrip     = strpos($html, '<script id="skrip-contoh">');

        $this->assertNotFalse($posisiKemajuan);
        $this->assertGreaterThan($posisiBootstrap, $posisiKemajuan);
        $this->assertGreaterThan($posisiKemajuan, $posisiSkrip);

        $js = (string) file_get_contents(FCPATH . 'aset/js/kemajuan.js');
        $this->assertStringContainsString('window.Kemajuan', $js);
        $this->assertStringContainsString('mulai', $js);
        $this->assertStringContainsString('selesai', $js);
        $this->assertStringContainsString('prefers-reduced-motion', $js);
    }

    // ---------- Polos ----------

    public function testTataLetakPolosMembungkusIsiDalamKartu(): void
    {
        $this->isiDataAcara();

        $xpath = $this->xpath(view('Tests\Support\Views\tata_letak_polos_formulir'));

        $this->assertSame(1, $xpath->query('//body[@data-bs-theme="light"]')->length);

        $kolom = $xpath->query('//main[@id="isi"]//*[contains(@class, "polos-kolom")]')->item(0);
        $this->assertNotNull($kolom);

        // Nama acara di atas kartu, bukan heading.
        $merek = $xpath->query('./*[contains(@class, "polos-merek")]', $kolom)->item(0);
        $this->assertNotNull($merek);
        $this->assertSame('p', $merek->nodeName);
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($merek));

        // Isi halaman (formulir) berada di dalam kartu.
        $this->assertSame(1, $xpath->query('.//*[contains(@class, "card") and contains(@class, "polos-kartu")]/*[contains(@class, "card-body")]//form[@id="formulir-contoh"]', $kolom)->length);
        $this->assertSame(1, $xpath->query('//h1')->length);
    }

    // ---------- Komponen tanpa gambar ----------

    public function testKomponenTanpaGambar(): void
    {
        $html  = view('tata_letak/_tanpa_gambar', ['rasio' => '1x1', 'label' => 'Logo <persegi>'], ['saveData' => false]);
        $xpath = $this->xpath($html);

        $kotak = $xpath->query('//div[contains(@class, "tanpa-gambar")]')->item(0);
        $this->assertNotNull($kotak);
        $this->assertStringContainsString('ratio-1x1', $kotak->getAttribute('class'));
        $this->assertSame('img', $kotak->getAttribute('role'));
        $this->assertSame('Belum ada gambar: Logo <persegi>', $kotak->getAttribute('aria-label'));
        $this->assertStringContainsString('Belum ada gambar', $this->teks($kotak));
        $this->assertStringContainsString('Logo &lt;persegi&gt;', $html);
        $this->assertSame(1, $xpath->query('//i[contains(@class, "bi-image")]')->length);
        $this->assertSame(0, $xpath->query('//img')->length, 'Bukan gambar tiruan.');
    }

    public function testKomponenTanpaGambarNilaiBawaan(): void
    {
        $html = view('tata_letak/_tanpa_gambar', ['rasio' => 'sembarang'], ['saveData' => false]);

        $this->assertStringContainsString('ratio-16x9', $html);
        $this->assertSame('Belum ada gambar', $this->xpath($html)->query('//div[@role="img"]')->item(0)?->getAttribute('aria-label'));
        $this->assertStringNotContainsString('tanpa-gambar-label', $html);
    }

    // ---------- Tanpa alamat luar ----------

    public function testHalamanTanpaAlamatLuar(): void
    {
        $halaman = [
            $this->get('/')->getBody(),
            $this->withSession(self::SESI_ADMIN)->get('admin/dasbor')->getBody(),
            view('Tests\Support\Views\tata_letak_polos_formulir'),
            view('tata_letak/_tanpa_gambar', [], ['saveData' => false]),
        ];

        foreach ($halaman as $html) {
            $this->assertTanpaAlamatLuar($html);
        }
    }

    // ---------- Bantuan ----------

    /**
     * Nama acara dan lainnya diambil dari pengaturan (F2-02); isi seperti
     * flyer Seri 2. Tampilan saat belum diisi diuji di `PengaturanAcaraTest`.
     */
    private function isiDataAcara(): void
    {
        setting('Acara.nama', 'MilkLife Athletics Challenge');
        setting('Acara.seri', 2);
        setting('Acara.tahun', 2026);
        setting('Acara.tempat', 'Supersoccer Arena, Kudus');
        setting('Acara.tanggal_mulai', '2026-12-09');
        setting('Acara.tanggal_selesai', '2026-12-12');
    }

    /**
     * Merender view seolah-olah permintaan sedang membuka `$alamat`.
     */
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

    private function xpath(string $html): DOMXPath
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

    /**
     * @return list<array{string, string, string}> label, alamat, aria-current
     */
    private function daftarMenu(DOMXPath $xpath, DOMNode $menu): array
    {
        $daftar = [];

        foreach ($xpath->query('.//a[contains(@class, "menu-admin-tautan")]', $menu) as $tautan) {
            $daftar[] = [$this->teks($tautan), $tautan->getAttribute('href'), $tautan->getAttribute('aria-current')];
        }

        return $daftar;
    }

    private function assertFormulirKeluarBerCsrf(DOMXPath $xpath): void
    {
        $namaToken = config('Security')->tokenName;
        $ditemukan = false;

        foreach ($xpath->query('//form[@method="post"]') as $formulir) {
            if (rtrim((string) parse_url($formulir->getAttribute('action'), PHP_URL_PATH), '/') !== '/admin/keluar') {
                continue;
            }

            $token = $xpath->query('.//input[@type="hidden"][@name="' . $namaToken . '"]', $formulir)->item(0);
            $ditemukan = $token instanceof DOMElement && $token->getAttribute('value') !== '';
        }

        $this->assertTrue($ditemukan, 'Formulir POST ke /admin/keluar dengan token CSRF tidak ditemukan.');
    }

    /**
     * Setiap aset yang dimuat peramban (`src`, `<link href>`, `url()` di CSS
     * sebaris) harus menuju host aplikasi sendiri: tanpa CDN. Tautan biasa
     * (`<a href>`, misalnya tombol bagikan ke WA sejak F3-03) tidak dihitung.
     */
    private function assertTanpaAlamatLuar(string $html): void
    {
        $hostSendiri = parse_url(base_url(), PHP_URL_HOST);
        $alamat      = '((?:https?:)?//[^\s"\'<>]+)';

        preg_match_all('#\ssrc\s*=\s*["\']?' . $alamat . '#i', $html, $src);
        preg_match_all('#<link\b[^>]*\shref\s*=\s*["\']?' . $alamat . '#i', $html, $link);
        preg_match_all('#url\(\s*["\']?' . $alamat . '#i', $html, $url);

        $aset = array_merge($src[1], $link[1], $url[1]);

        foreach ($aset as $alamatAset) {
            $host = (string) parse_url(str_starts_with($alamatAset, '//') ? 'http:' . $alamatAset : $alamatAset, PHP_URL_HOST);
            $host = strtolower((string) preg_replace('/:\d+$/', '', $host));
            $this->assertSame($hostSendiri, $host, 'Ada alamat ke host luar: ' . $host);
        }
    }
}
