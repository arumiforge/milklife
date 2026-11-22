<?php

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\UjiBasisData;
use Tests\Support\UjiHalaman;

/**
 * Tata letak publik, admin, dan polos yang jadi (F1-05), beserta komponen
 * "tanpa gambar" dan bilah kemajuan. Sejak F2-02 nama dan data acara diambil
 * dari pengaturan, jadi uji yang memeriksanya mengisi pengaturan lebih dulu.
 * Dasbor membaca tabel pendaftaran, jadi kelas ini memakai `UjiBasisData`
 * (basis data dimigrasi ulang tiap uji) supaya keadaan kosongnya tidak
 * bergantung pada sisa data uji lain.
 *
 * @internal
 */
final class TataLetakHalamanTest extends UjiBasisData
{
    use FeatureTestTrait;

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
        $this->assertSame('Hari lomba 9–12 Desember 2026 Lokasi Supersoccer Arena, Kudus', $this->teks($xpath->query('//*[@data-pembuka-waktu]')->item(0)));
        $this->assertSame(1, $xpath->query('//main/section[@id="pembuka"]')->length);
    }

    /**
     * Tata letak lama Bootstrap (publik, admin, polos) dan potongannya dilepas
     * setelah semua halaman pindah ke Tailwind (F9-17); tidak ada view yang
     * masih memakainya.
     */
    public function testTataLetakLamaDilepas(): void
    {
        foreach (['tata_letak/publik', 'tata_letak/admin', 'tata_letak/polos', 'tata_letak/_menu_admin', 'tata_letak/_pesan_publik', 'tata_letak/_tanpa_gambar', 'admin/_pesan', 'admin/_galat_isian', 'admin/_jejak'] as $view) {
            $this->assertFileDoesNotExist(APPPATH . 'Views/' . $view . '.php', $view);
        }

        $berkas = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APPPATH . 'Views', FilesystemIterator::SKIP_DOTS));

        foreach ($berkas as $b) {
            if ($b->getExtension() !== 'php') {
                continue;
            }
            $isi = (string) file_get_contents($b->getPathname());
            $this->assertDoesNotMatchRegularExpression("#extend\\('tata_letak/(publik|admin|polos)'\\)#", $isi, $b->getPathname());
            $this->assertDoesNotMatchRegularExpression('#\\bbi bi-|bootstrap|data-bs-#', $isi, $b->getPathname());
        }
    }

    /**
     * Tata letak publik memuat bilah muat, jendela konfirmasi (K89) lewat
     * halaman contoh, dan skrip halaman paling akhir.
     */
    public function testTataLetakPublikHalamanContoh(): void
    {
        $this->isiDataAcara();

        $html  = $this->renderDiAlamat('unduhan', 'Tests\Support\Views\contoh_publik');
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//main[@id="isi"]//p[@id="isi-contoh"]')->length);
        $this->assertSame(1, $xpath->query('//header//nav')->length);
        $this->assertSame(1, $xpath->query('//footer')->length);
        $this->assertSame(0, $xpath->query('//link[contains(@href, "bootstrap")] | //script[contains(@src, "bootstrap")]')->length);
        $this->assertGreaterThan(strpos($html, aset('js/kemajuan.js')), strpos($html, '<script id="skrip-contoh">'));
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

        // Menu utama F10-07 (K154): Beranda, Jadwal, Hasil, Peserta (yang mati tidak tampil).
        $depan = $butir('/');
        $this->assertSame(['Beranda'], array_keys($depan));
        $this->assertSame('page', $depan['Beranda']->getAttribute('aria-current'));

        setting('HalamanDepan.tampil_jadwal', true);
        setting('HalamanDepan.tampil_peserta', true);
        $jadwal = $butir('jadwal');
        $this->assertSame(['Beranda', 'Jadwal', 'Peserta'], array_keys($jadwal));
        $this->assertSame('page', $jadwal['Jadwal']->getAttribute('aria-current'));
        $this->assertFalse($jadwal['Beranda']->hasAttribute('aria-current'));
    }

    /**
     * Bilah submenu atas (F10-07, K154): butir pilihan admin dan media sosial dari Kontak.
     */
    public function testBilahAtasPublik(): void
    {
        setting('HalamanDepan.nomor_wa_tanya', '6281234567890');
        setting('Galeri.tampil', true);
        db_connect()->table('tautan')->insert(['jenis' => 'media_sosial', 'label' => 'Instagram Karangan', 'nilai' => 'https://instagram.com/lomba_karangan', 'urutan' => 1]);

        $xpath = $this->xpath($this->get('unduhan')->getBody());
        $bilah = $xpath->query('//div[@data-bilah-atas]')->item(0);
        $this->assertNotNull($bilah);
        $this->assertSame(['unduhan', 'galeri', 'bantuan', 'tanya', 'masuk'], array_map(
            static fn (DOMElement $a): string => $a->getAttribute('data-bilah-atas-butir'),
            iterator_to_array($xpath->query('.//a[@data-bilah-atas-butir]', $bilah)),
        ));
        $this->assertSame('page', $xpath->query('.//a[@data-bilah-atas-butir="unduhan"]', $bilah)->item(0)?->getAttribute('aria-current'));
        // Masuk panitia pindah dari kaki ke bilah atas (F10-08).
        $this->assertSame(site_url('admin/masuk'), $xpath->query('.//a[@data-bilah-atas-butir="masuk"]', $bilah)->item(0)?->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//footer//a[@href="' . site_url('admin/masuk') . '"]')->length);
        $tanya = $xpath->query('.//a[@data-bilah-atas-butir="tanya"]', $bilah)->item(0);
        $this->assertStringStartsWith('https://wa.me/6281234567890', (string) $tanya?->getAttribute('href'));
        $this->assertSame('_blank', $tanya?->getAttribute('target'));
        $this->assertSame(1, $xpath->query('.//ul[@data-bilah-atas-sosial]//a[@href="https://instagram.com/lomba_karangan"]', $bilah)->length);
        $this->assertSame(1, $xpath->query('//ul[@data-laci-sosial]//a[@href="https://instagram.com/lomba_karangan"]')->length, 'Media sosial juga di laci.');

        // Butir yang tidak dicentang admin hilang dari bilah atas dan laci.
        setting('HalamanDepan.bilah_atas_unduhan', false);
        setting('HalamanDepan.bilah_atas_tanya', false);
        $xpath = $this->xpath($this->get('/')->getBody());
        $this->assertSame(0, $xpath->query('//a[@data-bilah-atas-butir="unduhan"] | //a[@data-bilah-atas-butir="tanya"]')->length);
        $this->assertSame(1, $xpath->query('//a[@data-bilah-atas-butir="galeri"]')->length);
        $this->assertSame(0, $xpath->query('//ul[@data-laci-tambahan]//a[@href="' . site_url('unduhan') . '"]')->length);
    }

    /**
     * Salin ID pendaftaran sekali klik (F10-08): skrip, templat ikon, dan pesan
     * "tersalin" ada di tata letak publik (contoh ID di keterangan Cek: `CekPendaftaranTest`).
     */
    public function testSalinIdPendaftaranPublik(): void
    {
        $html  = $this->get('cek-pendaftaran')->getBody();
        $xpath = $this->xpath($html);

        $this->assertStringContainsString(aset('js/salin-id.js'), $html);
        $templat = $xpath->query('//template[@id="templat-salin-id"]')->item(0);
        $this->assertNotNull($templat);
        $this->assertStringContainsString('#copy', $templat->ownerDocument->saveHTML($templat));
        $this->assertSame(1, $xpath->query('//*[@data-salin-pesan][@role="status"][@aria-live="polite"][@hidden][@data-tanpa-salin]')->length);
    }

    /**
     * Laci kanan ponsel dan menu bawah (F10-07, K154; F10-08).
     */
    public function testLaciDanMenuBawahPublik(): void
    {
        $html  = $this->get('bantuan')->getBody();
        $xpath = $this->xpath($html);

        // Laci: dialog tertutup, dibuka tombol menu di kanan kepala halaman (F10-08), muncul dari kanan.
        $laci = $xpath->query('//div[@id="laci-publik"][@role="dialog"][@aria-modal="true"][@aria-labelledby="laci-publik-judul"]')->item(0);
        $this->assertNotNull($laci);
        $this->assertStringContainsString('invisible', $laci->getAttribute('class'));
        $this->assertSame(1, $xpath->query('//header//button[@data-buka-laci][@aria-controls="laci-publik"][@aria-expanded="false"]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-buka-laci]')->length);
        $this->assertStringContainsString('end-0', $laci->getAttribute('class'));
        $this->assertSame(1, $xpath->query('//div[@id="laci-publik"]//button[@data-tutup-laci]')->length);
        $this->assertSame(1, $xpath->query('//div[@data-latar-laci]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-collapse-toggle]')->length, 'Menu turun lama diganti laci.');
        $this->assertStringContainsString(aset('js/menu-publik.js'), $html);
        // Laci berisi menu utama, tombol Cek Pendaftaran, dan butir bilah atas.
        $this->assertSame(1, $xpath->query('//ul[@data-laci-utama]//a[@href="' . site_url('/') . '"]')->length);
        $this->assertSame(1, $xpath->query('//div[@id="laci-publik"]//a[@href="' . site_url('cek-pendaftaran') . '"]')->length);
        $this->assertSame('page', $xpath->query('//ul[@data-laci-tambahan]//a[@href="' . site_url('bantuan') . '"]')->item(0)?->getAttribute('aria-current'));

        // Menu bawah: Cek paling kanan, tanpa butir Menu (F10-08); ruang bawah di body.
        $bawah = $xpath->query('//nav[@data-menu-bawah-publik][@aria-label="Menu bawah"]')->item(0);
        $this->assertNotNull($bawah);
        $this->assertSame(['Beranda', 'Bantuan', 'Unduhan', 'Cek'], array_map(
            fn (DOMElement $el): string => $this->teks($el),
            iterator_to_array($xpath->query('.//li/*', $bawah)),
        ), 'Pendaftaran belum dibuka: tanpa Daftar, Cek tetap paling kanan.');
        $this->assertSame('page', $xpath->query('.//a[@href="' . site_url('bantuan') . '"]', $bawah)->item(0)?->getAttribute('aria-current'));
        $this->assertSame(1, $xpath->query('//body[contains(concat(" ", @class, " "), " ada-menu-bawah ")]')->length);
    }

    // ---------- Admin ----------

    public function testDasborMemakaiTataLetakAdminBaru(): void
    {
        $hasil = $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/dasbor');
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
            ['Rekap', site_url('admin/rekap'), ''],
        ], $this->daftarMenu($xpath, $bawah));
        // Butir kelima: Menu, membuka sidebar (F10-03, permintaan A9).
        $tombolBawah = $xpath->query('.//li/button[@data-menu-buka]', $bawah);
        $this->assertSame(1, $tombolBawah->length);
        $this->assertSame('Menu', $this->teks($tombolBawah->item(0)));
        $this->assertSame('menu-laci', $tombolBawah->item(0)->getAttribute('data-drawer-toggle'));
        $this->assertSame(5, $xpath->query('./ul/li', $bawah)->length);
        // Butir aktif: ikon dan label oranye (K133, F10-03).
        $this->assertStringContainsString('text-oranye-500', $xpath->query('.//a[@aria-current="page"]//*[name()="svg"]', $bawah)->item(0)?->getAttribute('class') ?? '');
        $this->assertStringContainsString('text-oranye-600', $xpath->query('.//a[@aria-current="page"]', $bawah)->item(0)?->getAttribute('class') ?? '');

        // Menu samping berkelompok (rencana Tahap 9), lalu daftar akhir (K102).
        $this->assertSame(self::MENU_SAMPING, $this->kelompokMenu($xpath, $samping));

        // Submenu (F10-03): tertutup bila tidak memuat halaman aktif.
        $anak = [];
        foreach ($xpath->query('.//a[@data-menu-anak]', $samping) as $tautan) {
            $anak[] = $tautan->getAttribute('data-menu-tautan');
        }
        $this->assertSame([
            'admin/riwayat', 'admin/atlet/nomor-dada', 'admin/sekolah/usulan', 'admin/sekolah/nama-desa',
            'admin/pengaturan/acara', 'admin/pengaturan/pendaftaran', 'admin/pengaturan/kelompok-umur', 'admin/pengaturan/nomor-lomba',
            'admin/pengaturan/pembayaran', 'admin/pengaturan/jadwal', 'admin/pengaturan/kontak', 'admin/pengaturan/tabel-skor',
            'admin/pengaturan/hasil', 'admin/akun',
        ], $anak);
        foreach ($xpath->query('.//button[@data-lipat]', $samping) as $tombol) {
            $daftar = $xpath->query('//*[@id="' . $tombol->getAttribute('aria-controls') . '"]')->item(0);
            $this->assertNotNull($daftar, $tombol->getAttribute('data-lipat'));
            $this->assertSame($daftar->hasAttribute('hidden') ? 'false' : 'true', $tombol->getAttribute('aria-expanded'));
        }
        $this->assertSame(4, $xpath->query('.//button[starts-with(@data-lipat, "kelompok-")][@aria-expanded="true"]', $samping)->length, 'Kelompok terbuka tanpa JavaScript.');
        $this->assertSame(1, $xpath->query('.//button[@data-lipat="kelompok-pendaftaran"][@data-lipat-aktif]', $samping)->length, 'Kelompok halaman aktif selalu terbuka.');
        $this->assertSame(0, $xpath->query('.//button[starts-with(@data-lipat, "butir-")][@aria-expanded="true"]', $samping)->length);
        $this->assertStringContainsString('"' . aset('js/menu-admin.js') . '"', $html);

        // Warna merek (K154): sidebar biru tua, pojok kiri atas biru berisi logo resmi.
        $this->assertStringContainsString('bg-primary-900', $laci->getAttribute('class'));
        $pojok = $xpath->query('.//*[@data-pojok-admin]', $laci)->item(0);
        $this->assertNotNull($pojok);
        $this->assertStringContainsString('bg-primary-700', $pojok->getAttribute('class'));
        // Logo resmi bila ada (tanpa logo: ikon pengganti), langsung di latar biru tanpa wadah berlatar (F10-08).
        $this->assertSame(1, $xpath->query('.//a[@data-merek-admin]/span[.//img[@data-logo-merek] or .//*[name()="svg"]]', $pojok)->length);
        $this->assertSame(0, $xpath->query('.//a[@data-merek-admin]//*[contains(@class, "bg-white")]', $pojok)->length);

        // Jejak navigasi bawaan dari menu (F10-03).
        $this->assertSame(['Dasbor', 'Dasbor'], $this->labelJejak($xpath));
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
        $pengecekan = $this->xpath($this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/pengecekan')->getBody());
        $this->assertSame(1, $pengecekan->query('//body[@data-tata="tw"]')->length);
        $this->assertSame(1, $pengecekan->query('//main//*[@data-tanpa-gambar][@role="img"]')->length);
        $this->assertSame(1, $pengecekan->query('//nav[@data-menu-admin="samping"]//a[@aria-current="page"][@data-menu-tautan="admin/pengecekan"]')->length);
    }

    public function testPenandaMenuAktifMengikutiAlamat(): void
    {
        $_SESSION = UjiHalaman::SESI_ADMIN;

        // Halaman di bawah butir menu ikut menandai butir itu.
        $xpath = $this->xpath($this->renderDiAlamat('admin/dasbor/rincian', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(2, $xpath->query('//a[@data-menu-tautan][@aria-current="page"]')->length);

        // Halaman lain tidak menandai Dasbor; jejaknya hanya judul halaman.
        $xpath = $this->xpath($this->renderDiAlamat('admin/lain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//nav[@data-menu-admin]//*[@aria-current]')->length);
        $this->assertSame(['Dasbor', 'Judul Contoh admin'], $this->labelJejak($xpath));
        // Empat tautan dan butir Menu di menu bawah (K133, F10-03); di menu samping
        // dua puluh butir berkelompok, empat belas anak submenu, dan dua di daftar akhir (K102).
        $this->assertSame(4 + 20 + 14 + 2, $xpath->query('//a[@data-menu-tautan]')->length);

        // Alamat yang hanya berawalan sama tidak dianggap halaman di bawahnya.
        $xpath = $this->xpath($this->renderDiAlamat('admin/dasborlain', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(0, $xpath->query('//nav[@data-menu-admin]//*[@aria-current]')->length);

        // Butir yang alamatnya paling cocok menang: Konten dan Videotron di bawah
        // admin/pengaturan tidak ikut menandai Pengaturan.
        foreach ([
            'admin/pengaturan/galeri/3/ubah'   => 'admin/pengaturan/galeri',
            'admin/pengaturan/bantuan'         => 'admin/pengaturan/bantuan',
            'admin/pengaturan/videotron'       => 'admin/pengaturan/videotron',
            'admin/pengaturan/acara'           => 'admin/pengaturan/acara',
            'admin/pengaturan/jadwal/3/ubah'   => 'admin/pengaturan/jadwal',
            'admin/sekolah/usulan'             => 'admin/sekolah/usulan',
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

    /**
     * Jejak bawaan (F10-03): induk submenu, butir menu, lalu judul halaman;
     * halaman yang menulis jejak sendiri tidak mendapat jejak kedua.
     */
    public function testJejakBawaanDariMenu(): void
    {
        $_SESSION = UjiHalaman::SESI_ADMIN;

        foreach ([
            'admin/pengaturan/jadwal/3/ubah' => ['Dasbor', 'Pengaturan', 'Agenda', 'Judul Contoh admin'],
            'admin/pengaturan/jadwal'        => ['Dasbor', 'Pengaturan', 'Judul Contoh admin'],
            'admin/rekap/keuangan'           => ['Dasbor', 'Rekap', 'Judul Contoh admin'],
            'admin/seri/babak/4'             => ['Dasbor', 'Seri dan lintasan', 'Judul Contoh admin'],
        ] as $alamat => $harapan) {
            $xpath = $this->xpath($this->renderDiAlamat($alamat, 'Tests\Support\Views\contoh_admin'));
            $this->assertSame($harapan, $this->labelJejak($xpath), $alamat);
            $this->assertSame(1, $xpath->query('//nav[@data-jejak]//*[@aria-current="page"]')->length, $alamat);
        }

        // Tautan butir induk mengarah ke halamannya.
        $xpath = $this->xpath($this->renderDiAlamat('admin/pengaturan/jadwal/3/ubah', 'Tests\Support\Views\contoh_admin'));
        $this->assertSame(site_url('admin/pengaturan/jadwal'), $xpath->query('//nav[@data-jejak]//ol//a[normalize-space()="Agenda"]')->item(0)?->getAttribute('href'));

        // Judul yang sama dengan butir menu tidak diulang.
        $this->assertSame([['label' => 'Pengaturan', 'alamat' => 'admin/pengaturan'], ['label' => 'Data Acara']], $this->jejakDiAlamat('admin/pengaturan/acara', 'Data Acara'));
        $this->assertSame([['label' => 'Sekolah', 'alamat' => 'admin/sekolah'], ['label' => 'Usulan Nama Sekolah']], $this->jejakDiAlamat('admin/sekolah/usulan', 'Usulan Nama Sekolah'));
        $this->assertSame([['label' => 'pendaftaran']], $this->jejakDiAlamat('admin/pendaftaran', 'pendaftaran'));
    }

    /**
     * @return list<array{label: string, alamat?: string}>
     */
    private function jejakDiAlamat(string $alamat, string $judul): array
    {
        Services::injectMock('request', new IncomingRequest(config('App'), new SiteURI(config('App'), $alamat), null, new UserAgent()));

        try {
            return \App\Libraries\Panel\MenuAdmin::jejak($judul);
        } finally {
            Services::resetSingle('request');
        }
    }

    /**
     * Label jejak navigasi lengkap (layar lebar): Dasbor (ikon rumah), lalu butirnya.
     *
     * @return list<string>
     */
    private function labelJejak(DOMXPath $xpath): array
    {
        $label = [];

        foreach ($xpath->query('//nav[@data-jejak]//ol/li') as $butir) {
            $ikon    = $xpath->query('.//*[@aria-label]', $butir)->item(0);
            $teks    = $this->teks($butir);
            $label[] = $teks === '' && $ikon !== null ? $ikon->getAttribute('aria-label') : $teks;
        }

        return $label;
    }

    public function testJudulKepalaAdminMemakaiJudulBilaJudulHalamanKosong(): void
    {
        $_SESSION = UjiHalaman::SESI_ADMIN;
        $xpath    = $this->xpath(view('Tests\Support\Views\contoh_admin'));

        $this->assertSame('Judul Contoh admin', $this->teks($xpath->query('//header//h1')->item(0)));
    }

    public function testSkripKemajuanDimuatSebelumSkripHalaman(): void
    {
        $_SESSION = UjiHalaman::SESI_ADMIN;
        $html     = view('Tests\Support\Views\contoh_admin');

        $posisiFlowbite = strpos($html, aset('vendor/flowbite/flowbite.min.js'));
        $posisiKemajuan = strpos($html, aset('js/kemajuan.js'));
        $posisiSkrip    = strpos($html, '<script id="skrip-contoh">');

        $this->assertNotFalse($posisiFlowbite);
        $this->assertNotFalse($posisiKemajuan);
        $this->assertGreaterThan($posisiKemajuan, $posisiSkrip);
        $this->assertGreaterThan($posisiFlowbite, $posisiSkrip);

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

        // Nama acara di atas kartu, bukan heading.
        $merek = $xpath->query('//main[@id="isi"]//*[@data-polos-merek]')->item(0);
        $this->assertNotNull($merek);
        $this->assertSame('p', $merek->nodeName);
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($merek));

        // Isi halaman (formulir) berada di dalam kartu.
        $this->assertSame(1, $xpath->query('//main[@id="isi"]//*[@data-polos-kartu]//form[@id="formulir-contoh"]')->length);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(0, $xpath->query('//link[contains(@href, "bootstrap")]')->length);
    }

    // ---------- Komponen tanpa gambar ----------

    public function testKomponenTanpaGambarTailwind(): void
    {
        $html  = view('tata_letak/_tanpa_gambar_tw', ['rasio' => '1x1', 'label' => 'Logo <persegi>', 'kelas' => 'w-full'], ['saveData' => false]);
        $xpath = $this->xpath($html);

        $kotak = $xpath->query('//div[@data-tanpa-gambar]')->item(0);
        $this->assertNotNull($kotak);
        $this->assertStringContainsString('aspect-square', $kotak->getAttribute('class'));
        $this->assertSame('1x1', $kotak->getAttribute('data-tanpa-gambar'));
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
            $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/dasbor')->getBody(),
            view('Tests\Support\Views\tata_letak_polos_formulir'),
            view('tata_letak/_tanpa_gambar_tw', [], ['saveData' => false]),
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
            foreach ($xpath->query('.//a[@data-menu-tautan][not(@data-menu-anak)]', $kelompok) as $tautan) {
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
