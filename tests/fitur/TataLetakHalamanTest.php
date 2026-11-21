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
    /**
     * Kelompok menu samping panel admin (rencana Tahap 9, F9-14), sama di
     * tata letak baru dan lama (`App\Libraries\Panel\MenuAdmin`).
     */
    private const MENU_SAMPING = [
        'Pendaftaran' => ['Dasbor', 'Pendaftaran', 'Atlet', 'Atlet ditolak', 'Sekolah', 'Rekap'],
        'Hari lomba'  => ['Daftar ulang', 'Cetak BIB', 'Daftar peserta', 'Jadwal pertandingan', 'Seri dan lintasan', 'Lembar juri'],
        'Hasil'       => ['Hasil lomba', 'Klasemen', 'Videotron'],
        'Konten'      => ['Halaman depan', 'Bantuan', 'Galeri', 'Unduhan'],
        'Pengaturan'  => ['Pengaturan'],
    ];

    // ---------- Publik ----------

    public function testHalamanDepanMemakaiTataLetakPublikBaru(): void
    {
        $this->isiDataAcara();

        $hasil = $this->get('/');
        $hasil->assertOK();

        $html  = $hasil->getBody();
        $xpath = $this->xpath($html);

        // Tata letak baru (F9-03): Tailwind dan Flowbite, tanpa Bootstrap.
        $this->assertSame(1, $xpath->query('//header[@data-kepala-publik]')->length);
        $this->assertSame(1, $xpath->query('//header//nav[@aria-label="Menu utama"]')->length);
        $this->assertSame(1, $xpath->query('//main[@id="isi"]')->length);
        $this->assertSame(1, $xpath->query('//a[@href="#isi"][contains(., "Lompat ke isi")]')->length);
        $this->assertSame(1, $xpath->query('//link[@rel="stylesheet"][contains(@href, "aset/css/tw.css")]')->length);
        $this->assertSame(0, $xpath->query('//link[contains(@href, "bootstrap")] | //script[contains(@src, "bootstrap")]')->length, 'Bootstrap tidak dimuat.');
        $this->assertSame(1, $xpath->query('//script[contains(@src, "vendor/flowbite/flowbite.min.js")]')->length);

        // Kepala memuat nama acara sebagai teks.
        $merek = $xpath->query('//header//a[@data-merek]')->item(0);
        $this->assertNotNull($merek);
        $this->assertSame('MilkLife Athletics Challenge Seri 2 – 2026', $this->teks($merek));

        // Kaki: nama acara dan tahun.
        $this->assertSame('MilkLife Athletics Challenge 2026', $this->teks($xpath->query('//footer//*[@data-kaki-teks]')->item(0)));

        // Kepala halaman depan (rinciannya di HalamanDepanKepalaTest).
        $this->assertSame(1, $xpath->query('//main//h1')->length, 'Halaman depan punya tepat satu h1.');
        $hasil->assertSee('Pendaftaran sedang disiapkan.');
        $hasil->assertSee('Seri 2 – 2026');
        $this->assertSame('9–12 Desember 2026 Supersoccer Arena, Kudus', $this->teks($xpath->query('//*[@data-pembuka-waktu]')->item(0)));
        $this->assertSame(1, $xpath->query('//main/section[@id="pembuka"]')->length);
    }

    /**
     * Sejak F9-05 tidak ada halaman publik yang memakai tata letak lama; tata
     * letak itu tetap diuji lewat halaman contoh sampai dilepas (F9-17).
     */
    public function testTataLetakPublikLama(): void
    {
        $this->isiDataAcara();

        $xpath = $this->xpath($this->renderDiAlamat('cek-pendaftaran', 'Tests\Support\Views\contoh_publik'));

        $this->assertSame(1, $xpath->query('//body[@data-bs-theme="light"]')->length, 'Tema terang harus dipasang.');
        $this->assertSame(1, $xpath->query('//header[contains(@class, "kepala-publik")]')->length);
        $this->assertSame(1, $xpath->query('//header//nav[@aria-label="Menu utama"]')->length);
        $this->assertSame(1, $xpath->query('//main[@id="isi"]')->length);

        // Bilah muat, jendela konfirmasi, dan pemilih tanggal di semua halaman publik lama (K89).
        $this->assertSame(1, $xpath->query('//*[@id="bilah-kemajuan"][@hidden]')->length);
        foreach (['js/kemajuan.js', 'js/konfirmasi.js', 'js/pilih-tanggal.js'] as $skrip) {
            $this->assertSame(1, $xpath->query('//script[@src="' . aset($skrip) . '"]')->length, $skrip);
        }

        $merek = $xpath->query('//header//a[contains(@class, "navbar-brand")]')->item(0);
        $this->assertNotNull($merek);
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($merek));
        $this->assertSame('MilkLife Athletics Challenge 2026', $this->teks($xpath->query('//footer')->item(0)));
    }

    public function testMenuPublikLamaDenganButirNonAktif(): void
    {
        $xpath = $this->xpath($this->renderDiAlamat('cek-pendaftaran', 'Tests\Support\Views\contoh_publik'));
        $butir = [];

        foreach ($xpath->query('//nav[@aria-label="Menu utama"]//*[contains(@class, "nav-link")]') as $tautan) {
            $butir[$this->teks($tautan)] = $tautan;
        }

        // K81: menu mengikuti konsep visual; Pendaftaran dan Cek ada di tombol pembuka.
        $this->assertSame(['Tentang', 'Kategori', 'Agenda', 'Unduhan', 'Bantuan'], array_keys($butir));
        $this->assertSame(site_url('/') . '#pembuka', $butir['Tentang']->getAttribute('href'));
        $this->assertSame(site_url('/') . '#lomba', $butir['Kategori']->getAttribute('href'));
        $this->assertSame(site_url('/') . '#jadwal', $butir['Agenda']->getAttribute('href'));

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

        // Halaman selain halaman depan memakai kepala biru.
        $this->assertSame(0, $xpath->query('//header[contains(@class, "kepala-beranda")]')->length);

        // Menu melipat di ponsel.
        $tombol = $xpath->query('//button[@data-bs-toggle="collapse"][@data-bs-target="#menu-publik"]')->item(0);
        $this->assertNotNull($tombol);
        $this->assertSame('menu-publik', $tombol->getAttribute('aria-controls'));
        $this->assertSame(1, $xpath->query('//*[@id="menu-publik"][contains(@class, "collapse")]')->length);
    }

    public function testMenuPublikBaru(): void
    {
        $butir = function (string $alamat): array {
            $hasil = [];

            foreach ($this->xpath($this->get($alamat)->getBody())->query('//nav[@aria-label="Menu utama"]//ul/li/a') as $tautan) {
                $hasil[$this->teks($tautan)] = $tautan;
            }

            return $hasil;
        };

        // Beranda aktif di halaman depan; Unduhan aktif di halamannya (F9-03).
        $depan = $butir('/');
        // Kategori bergantung pada kelompok umur di basis data (diuji di KatalogTampilanTest).
        $this->assertSame(['Beranda', 'Unduhan', 'Bantuan'], array_values(array_diff(array_keys($depan), ['Kategori'])));
        $this->assertSame('page', $depan['Beranda']->getAttribute('aria-current'));
        $this->assertFalse($depan['Unduhan']->hasAttribute('aria-current'));
        $this->assertSame('page', $butir('unduhan')['Unduhan']->getAttribute('aria-current'));

        // Menu melipat di ponsel (Flowbite).
        $xpath = $this->xpath($this->get('/')->getBody());
        $this->assertSame(1, $xpath->query('//button[@data-collapse-toggle="menu-publik"][@aria-controls="menu-publik"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="menu-publik"]')->length);
    }

    // ---------- Admin ----------

    public function testDasborMemakaiTataLetakAdminBaru(): void
    {
        $hasil = $this->withSession(self::SESI_ADMIN)->get('admin/dasbor');
        $hasil->assertOK();

        $html  = $hasil->getBody();
        $xpath = $this->xpath($html);

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertSame(1, $xpath->query('//body[@data-tata="tw"]')->length, 'Dasbor memakai tata letak admin_tw (F9-14).');
        $this->assertStringContainsString(aset('css/tw.css'), $html);
        $this->assertStringNotContainsString('bootstrap.min.css', $html);

        // Kepala halaman: judul halaman, nama admin (ke Akun), tombol Keluar.
        $kepala = $xpath->query('//header[@data-kepala-admin]')->item(0);
        $this->assertNotNull($kepala);
        $this->assertSame('Dasbor', $this->teks($xpath->query('.//h1', $kepala)->item(0)));
        $this->assertSame(site_url('admin/akun'), $xpath->query('.//a[@data-nama-admin]', $kepala)->item(0)?->getAttribute('href'));
        $this->assertStringContainsString('Admin Uji', $this->teks($kepala));
        $this->assertSame(1, $xpath->query('.//form[@method="post"]//button[@type="submit"][contains(., "Keluar")]', $kepala)->length);
        $this->assertFormulirKeluarBerCsrf($xpath);

        // Di ponsel menu samping menjadi laci yang dibuka tombol menu di kepala
        // halaman dan ditutup tombol di dalamnya (K131, K133).
        $samping = $xpath->query('//nav[@data-menu-admin="samping"][@aria-label]')->item(0);
        $bawah   = $xpath->query('//nav[@data-menu-admin="bawah"][@aria-label]')->item(0);
        $this->assertNotNull($samping, 'Menu samping tidak ada.');
        $this->assertNotNull($bawah, 'Menu bawah tidak ada.');
        $laci = $xpath->query('ancestor::aside[@id="menu-laci"]', $samping)->item(0);
        $this->assertNotNull($laci);
        $tombolMenu = $xpath->query('.//button[@data-drawer-toggle="menu-laci"]', $kepala)->item(0);
        $this->assertNotNull($tombolMenu, 'Tombol menu di kepala halaman tidak ada.');
        $this->assertSame('menu-laci', $tombolMenu->getAttribute('aria-controls'));
        $this->assertSame('Buka menu', $xpath->query('.//*[@aria-label]', $tombolMenu)->item(0)?->getAttribute('aria-label'));
        $this->assertSame(1, $xpath->query('.//button[@data-drawer-hide="menu-laci"]//*[@aria-label="Tutup menu"]', $laci)->length);

        // Menu bawah: 5 butir urusan pendaftaran, label pendek (K117, K132, K133).
        $this->assertSame([
            ['Dasbor', site_url('admin/dasbor'), 'page'],
            ['Pendaftaran', site_url('admin/pendaftaran'), ''],
            ['Atlet', site_url('admin/atlet'), ''],
            ['Ditolak', site_url('admin/atlet-ditolak'), ''],
            ['Rekap', site_url('admin/rekap'), ''],
        ], $this->daftarMenu($xpath, $bawah));
        // Butir aktif cukup ikon oranye (K133).
        $this->assertStringContainsString('text-oranye-500', $xpath->query('.//a[@aria-current="page"]//*[name()="svg"]', $bawah)->item(0)?->getAttribute('class') ?? '');

        // Menu samping berkelompok (rencana Tahap 9), lalu daftar akhir (K102).
        $this->assertSame(self::MENU_SAMPING, $this->kelompokMenu($xpath, $samping));
        $akhir = $xpath->query('.//*[@data-menu-akhir]', $samping)->item(0);
        $this->assertNotNull($akhir);
        $this->assertSame([
            ['Pengecekan', site_url('admin/pengecekan'), ''],
            ['Lihat halaman depan (tab baru)', site_url('/'), ''],
        ], $this->daftarMenu($xpath, $akhir));
        $this->assertSame('_blank', $xpath->query('.//a[@data-menu-tautan="/"]', $akhir)->item(0)?->getAttribute('target'));

        // Ikon dekoratif disembunyikan dari pembaca layar.
        foreach ($xpath->query('//nav[@data-menu-admin]//*[name()="svg"]') as $ikon) {
            $this->assertSame('true', $ikon->getAttribute('aria-hidden'));
        }

        // Tanpa teks footer.
        $this->assertSame(0, $xpath->query('//footer')->length, 'Panel admin tidak boleh punya footer.');

        // Bilah kemajuan: ada, tersembunyi sampai dipanggil, skripnya dimuat.
        $bilah = $xpath->query('//*[@id="bilah-kemajuan"]')->item(0);
        $this->assertNotNull($bilah);
        $this->assertTrue($bilah->hasAttribute('hidden'));
        $this->assertSame(1, $xpath->query('.//*[contains(@class, "bilah-kemajuan-isi")]', $bilah)->length);
        $this->assertStringContainsString('"' . aset('js/kemajuan.js') . '"', $html);

        // Keadaan kosong yang ramah, tanpa angka karangan.
        $isi = $this->teks($xpath->query('//main[@id="isi"]')->item(0));
        $this->assertStringContainsString('Belum ada pendaftaran', $isi);
        $this->assertStringContainsString('Langkah berikutnya', $isi);
        $this->assertSame(1, $xpath->query('//*[@data-dasbor-kosong]')->length);

        // Grafik: setiap wadah berisi kerangka sampai dasbor.js menggambarnya.
        $wadah = $xpath->query('//*[@data-grafik]');
        $this->assertSame(8, $wadah->length);

        foreach ($wadah as $satu) {
            $this->assertSame(1, $xpath->query('.//*[@data-kerangka][@role="status"]', $satu)->length, $satu->getAttribute('id'));
        }

        // Kotak tanpa gambar di Pengecekan (K102), juga bertata letak baru.
        $this->assertSame(0, $xpath->query('//main//*[@data-tanpa-gambar]')->length);
        $pengecekan = $this->xpath($this->withSession(self::SESI_ADMIN)->get('admin/pengecekan')->getBody());
        $this->assertSame(1, $pengecekan->query('//body[@data-tata="tw"]')->length);
        $this->assertSame(1, $pengecekan->query('//main//*[@data-tanpa-gambar][@role="img"]')->length);
        $this->assertSame(1, $pengecekan->query('//nav[@data-menu-admin="samping"]//a[@aria-current="page"][@data-menu-tautan="admin/pengecekan"]')->length);
    }

    public function testTataLetakLamaMemakaiMenuYangSama(): void
    {
        $_SESSION = self::SESI_ADMIN;
        $lama     = $this->xpath($this->renderDiAlamat('admin/dasbor', 'Tests\Support\Views\contoh_admin'));
        $baru     = $this->xpath($this->withSession(self::SESI_ADMIN)->get('admin/dasbor')->getBody());

        $this->assertSame(0, $lama->query('//body[@data-tata="tw"]')->length, 'Contoh memakai tata letak lama.');

        foreach (['samping', 'bawah'] as $tempat) {
            $menuLama = $lama->query('//nav[@data-menu-admin="' . $tempat . '"]')->item(0);
            $menuBaru = $baru->query('//nav[@data-menu-admin="' . $tempat . '"]')->item(0);
            $this->assertNotNull($menuLama, $tempat);
            $this->assertNotNull($menuBaru, $tempat);
            $this->assertSame($this->daftarMenu($baru, $menuBaru), $this->daftarMenu($lama, $menuLama), "Isi menu {$tempat} kedua tata letak harus sama.");
        }

        $this->assertSame(self::MENU_SAMPING, $this->kelompokMenu($lama, $lama->query('//nav[@data-menu-admin="samping"]')->item(0)));

        // Bentuk tata letak lama tetap: sidebar offcanvas Bootstrap, menu bawah d-lg-none (K131, K133).
        $laci = $lama->query('//aside[@id="menu-laci"]')->item(0);
        $this->assertNotNull($laci);
        $this->assertStringContainsString('offcanvas-lg', $laci->getAttribute('class'));
        $this->assertStringContainsString('d-lg-none', $lama->query('//nav[@data-menu-admin="bawah"]')->item(0)?->getAttribute('class') ?? '');
        $tombolMenu = $lama->query('//header[@data-kepala-admin]//button[@data-bs-toggle="offcanvas"]')->item(0);
        $this->assertSame('#menu-laci', $tombolMenu?->getAttribute('data-bs-target'));

        // Ikon Lucide (sprite) juga di tata letak lama, disembunyikan dari pembaca layar.
        $this->assertGreaterThan(0, $lama->query('//nav[@data-menu-admin]//*[name()="svg"]')->length);
        $this->assertSame(0, $lama->query('//nav[@data-menu-admin]//i[contains(@class, "bi")]')->length);
    }

    public function testPenandaMenuAktifMengikutiAlamat(): void
    {
        $_SESSION = self::SESI_ADMIN;

        // Halaman di bawah butir menu ikut menandai butir itu.
        $xpath = $this->xpath($this->renderDiAlamat('admin/dasbor/rincian', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(2, $xpath->query('//a[@data-menu-tautan][@aria-current="page"]')->length);

        // Halaman lain tidak menandai Dasbor.
        $xpath = $this->xpath($this->renderDiAlamat('admin/lain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//*[@aria-current]')->length);
        // Lima butir di menu bawah (K133); dua puluh butir berkelompok dan dua di daftar akhir (K102) di menu samping.
        $this->assertSame(27, $xpath->query('//a[@data-menu-tautan]')->length);

        // Alamat yang hanya berawalan sama tidak dianggap halaman di bawahnya.
        $xpath = $this->xpath($this->renderDiAlamat('admin/dasborlain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//*[@aria-current]')->length);

        // Butir yang alamatnya paling cocok menang: Konten dan Videotron di bawah
        // admin/pengaturan tidak ikut menandai Pengaturan.
        foreach ([
            'admin/pengaturan/galeri/3/ubah'   => 'admin/pengaturan/galeri',
            'admin/pengaturan/bantuan'         => 'admin/pengaturan/bantuan',
            'admin/pengaturan/videotron'       => 'admin/pengaturan/videotron',
            'admin/pengaturan/acara'           => 'admin/pengaturan',
            'admin/nomor-atlet/cetak/contoh'   => 'admin/nomor-atlet/cetak',
            'admin/seri/babak/4'               => 'admin/seri',
        ] as $alamat => $butir) {
            $xpath = $this->xpath($this->renderDiAlamat($alamat, 'Tests\Support\Views\contoh_admin'));
            $aktif = [];

            foreach ($xpath->query('//a[@data-menu-tautan][@aria-current="page"]') as $tautan) {
                $aktif[] = $tautan->getAttribute('data-menu-tautan');
            }

            $this->assertSame([$butir], $aktif, $alamat);
        }
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

    public function testKomponenTanpaGambarTailwind(): void
    {
        $html  = view('tata_letak/_tanpa_gambar_tw', ['rasio' => '1x1', 'label' => 'Logo <persegi>', 'kelas' => 'w-full'], ['saveData' => false]);
        $xpath = $this->xpath($html);

        $kotak = $xpath->query('//div[@data-tanpa-gambar]')->item(0);
        $this->assertNotNull($kotak);
        $this->assertStringContainsString('tanpa-gambar aspect-square', $kotak->getAttribute('class'));
        $this->assertStringContainsString(' w-full', $kotak->getAttribute('class'));
        $this->assertSame('img', $kotak->getAttribute('role'));
        $this->assertSame('Belum ada gambar: Logo <persegi>', $kotak->getAttribute('aria-label'));
        $this->assertStringContainsString('Logo &lt;persegi&gt;', $html);
        $this->assertSame(1, $xpath->query('//svg')->length, 'Ikon Lucide, bukan Bootstrap Icons.');
        $this->assertSame(0, $xpath->query('//img|//i')->length, 'Bukan gambar tiruan.');

        $bawaan = view('tata_letak/_tanpa_gambar_tw', ['rasio' => 'sembarang'], ['saveData' => false]);
        $this->assertStringContainsString('aspect-video', $bawaan);
        $this->assertSame('Belum ada gambar', $this->xpath($bawaan)->query('//div[@role="img"]')->item(0)?->getAttribute('aria-label'));
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

        foreach ($xpath->query('.//a[@data-menu-tautan]', $menu) as $tautan) {
            $daftar[] = [$this->teks($tautan), $tautan->getAttribute('href'), $tautan->getAttribute('aria-current')];
        }

        return $daftar;
    }

    /**
     * @return array<string, list<string>> nama kelompok => label butir
     */
    private function kelompokMenu(DOMXPath $xpath, DOMNode $menu): array
    {
        $hasil = [];

        foreach ($xpath->query('.//*[@data-menu-kelompok]', $menu) as $kelompok) {
            foreach ($xpath->query('.//a[@data-menu-tautan]', $kelompok) as $tautan) {
                $hasil[$kelompok->getAttribute('data-menu-kelompok')][] = $this->teks($tautan);
            }
        }

        return $hasil;
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
