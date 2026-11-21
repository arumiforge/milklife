<?php

use App\Libraries\GambarOpsional;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Pemasangan gambar Codex G9-08 sampai G9-12 (F9-19): setiap gambar hanya
 * dirender bila berkasnya ada, jadi halaman tetap rapi (tanpa `<img>`, tag,
 * atau tautan ke berkas yang belum ada) sebelum gambarnya diserahkan, dan
 * gambar langsung tampil setelah berkasnya dipasang.
 *
 * Berkas uji dibuat di folder sementara (`GambarOpsional::$folder`), bukan di
 * `public/`, jadi gambar Codex yang sudah terpasang tidak tersentuh.
 *
 * @internal
 */
final class GambarOpsionalTest extends UjiBasisData
{
    use KirimFormulir;
    use PendaftaranContoh;

    /**
     * Semua berkas brief G9-08 sampai G9-12 => [lebar, tinggi].
     */
    private const BERKAS = [
        'admin/masuk.webp'                      => [1200, 1400],
        'admin/dasbor-sapaan.webp'              => [640, 400],
        'admin/kosong-admin.webp'               => [800, 600],
        'admin/daftar-ulang.webp'               => [600, 450],
        'admin/cetak-bib.webp'                  => [600, 450],
        'admin/hasil-lomba.webp'                => [600, 450],
        'judul/jadwal.webp'                     => [960, 640],
        'judul/hasil.webp'                      => [960, 640],
        'judul/klasemen.webp'                   => [960, 640],
        'judul/peserta.webp'                    => [960, 640],
        'judul/galeri.webp'                     => [960, 640],
        'judul/bantuan.webp'                    => [960, 640],
        'judul/unduhan.webp'                    => [960, 640],
        'judul/seri.webp'                       => [960, 640],
        'judul/cek-pendaftaran.webp'            => [960, 640],
        'bagikan/umum.jpg'                      => [1200, 630],
        'bagikan/hasil.jpg'                     => [1200, 630],
        'ikon-aplikasi/ikon-512.png'            => [512, 512],
        'ikon-aplikasi/ikon-192.png'            => [192, 192],
        'ikon-aplikasi/ikon-maskable-512.png'   => [512, 512],
        'ikon-aplikasi/apple-touch-180.png'     => [180, 180],
        'ikon-aplikasi/favicon-32.png'          => [32, 32],
        'ikon-aplikasi/favicon-16.png'          => [16, 16],
        'pola/pita-sudut.webp'                  => [800, 800],
        'pola/konfeti.webp'                     => [1200, 800],
    ];

    /**
     * Halaman publik berpita judul => berkas ilustrasi G9-09.
     */
    private const PITA = [
        'hasil'           => 'judul/hasil.webp',
        'hasil/klasemen'  => 'judul/klasemen.webp',
        'unduhan'         => 'judul/unduhan.webp',
        'galeri'          => 'judul/galeri.webp',
        'cek-pendaftaran' => 'judul/cek-pendaftaran.webp',
        'bantuan'         => 'judul/bantuan.webp',
        'jadwal'          => 'judul/jadwal.webp',
        'peserta'         => 'judul/peserta.webp',
    ];

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder           = WRITEPATH . 'uji-gambar-' . bin2hex(random_bytes(4)) . '/';
        GambarOpsional::$folder = $this->folder;
        mkdir($this->folder, 0777, true);

        // Halaman bersakelar (bawaan mati) dinyalakan supaya bisa diperiksa.
        foreach (['HalamanDepan.tampil_jadwal', 'HalamanDepan.tampil_peserta', 'Galeri.tampil'] as $sakelar) {
            setting()->set($sakelar, true);
        }
    }

    protected function tearDown(): void
    {
        GambarOpsional::$folder = null;
        $this->hapusFolder($this->folder);

        parent::tearDown();
    }

    public function testPembantuTanpaBerkasMenulisTeksKosong(): void
    {
        $this->assertSame('', gambar_opsional('admin/masuk.webp', ['class' => 'h-40']));
        $this->assertFalse(ada_gambar('admin/masuk.webp'));
        $this->assertNull(GambarOpsional::alamat('admin/masuk.webp'));
        $this->assertNull(GambarOpsional::ukuran('admin/masuk.webp'));
    }

    public function testPembantuDenganBerkasMenulisImgLengkap(): void
    {
        $this->buatGambar('admin/masuk.webp', 1200, 1400);

        $img = $this->elemen(gambar_opsional('admin/masuk.webp', ['class' => 'h-40 w-auto', 'data-uji' => true]), 'img');

        $this->assertMatchesRegularExpression('#^https?://[^/]+/.*aset/gambar/admin/masuk\.webp\?v=\d+$#', $img->getAttribute('src'));
        $this->assertSame(['1200', '1400'], [$img->getAttribute('width'), $img->getAttribute('height')]);
        $this->assertSame('', $img->getAttribute('alt'));
        $this->assertSame('true', $img->getAttribute('aria-hidden'));
        $this->assertSame('lazy', $img->getAttribute('loading'));
        $this->assertSame('async', $img->getAttribute('decoding'));
        $this->assertSame('h-40 w-auto', $img->getAttribute('class'));
        $this->assertTrue($img->hasAttribute('data-uji'));
        $this->assertTrue(ada_gambar('admin/masuk.webp'));

        // Gambar di atas lipatan dimuat langsung; gambar bermakna punya teks alt dan tidak disembunyikan.
        $img = $this->elemen(gambar_opsional('admin/masuk.webp', ['loading' => false, 'alt' => 'Panitia di tepi lintasan', 'width' => 300, 'height' => 350, 'style' => 'x', 'onclick' => 'x']), 'img');
        $this->assertFalse($img->hasAttribute('loading'));
        $this->assertFalse($img->hasAttribute('aria-hidden'));
        $this->assertFalse($img->hasAttribute('style'));
        $this->assertFalse($img->hasAttribute('onclick'));
        $this->assertSame('Panitia di tepi lintasan', $img->getAttribute('alt'));
        $this->assertSame(['300', '350'], [$img->getAttribute('width'), $img->getAttribute('height')]);
    }

    public function testJalurDiluarFolderGambarDitolak(): void
    {
        foreach (['../.env', 'admin/../../index.php', 'admin/masuk.php', 'Admin/Masuk.webp', 'admin//masuk.webp'] as $jalur) {
            try {
                gambar_opsional($jalur);
                $this->fail('Jalur diterima: ' . $jalur);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testGambarBagikanPerHalamanDenganCadangan(): void
    {
        $this->assertNull(GambarOpsional::bagikan('hasil/klasemen'));

        $this->buatGambar('bagikan/umum.jpg', 1200, 630);
        $this->assertStringContainsString('aset/gambar/bagikan/umum.jpg', (string) GambarOpsional::bagikan('hasil/klasemen'));
        $this->assertStringContainsString('aset/gambar/bagikan/umum.jpg', (string) GambarOpsional::bagikan(''));

        $this->buatGambar('bagikan/hasil.jpg', 1200, 630);
        $this->assertStringContainsString('aset/gambar/bagikan/hasil.jpg', (string) GambarOpsional::bagikan('hasil/klasemen'));
        $this->assertStringContainsString('aset/gambar/bagikan/umum.jpg', (string) GambarOpsional::bagikan('unduhan'));

        foreach (GambarOpsional::BAGIKAN as $berkas) {
            $this->assertArrayHasKey('bagikan/' . $berkas . '.jpg', $this->briefBagikan());
        }
    }

    public function testHalamanPublikTanpaBerkasTetapUtuh(): void
    {
        foreach (array_keys(self::PITA) as $alamat) {
            $html  = $this->bukaOk($alamat);
            $xpath = $this->xpath($html);

            $this->assertSame(1, $xpath->query('//h1')->length, $alamat);
            $this->assertSame(0, $xpath->query('//*[@data-judul-ilustrasi]')->length, $alamat);
            $this->assertSame(0, $xpath->query('//meta[@property="og:image"]')->length, $alamat);
            $this->assertSame('summary', $this->meta($xpath, 'twitter:card'), $alamat);
            $this->assertNotSame('', $this->meta($xpath, 'og:title'), $alamat);
            $this->assertNotSame('', $this->meta($xpath, 'og:description'), $alamat);
            $this->assertStringNotContainsString('ikon-aplikasi/', $html, $alamat);
            $this->assertSame(0, $xpath->query('//link[@rel="manifest"]')->length, $alamat);
            $this->assertSame(1, $xpath->query('//link[@rel="icon"][@type="image/svg+xml"]')->length, $alamat);
        }

        $xpath = $this->xpath($this->bukaOk('/'));
        $this->assertSame(0, $xpath->query('//*[@data-hiasan]')->length);

        $this->bukaPendaftaranUji();
        $xpath = $this->xpath($this->bukaOk('pendaftaran'));
        $this->assertSame(1, $xpath->query('//div[@id="selesai"]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-hiasan]')->length);
    }

    public function testHalamanPublikDenganBerkasMemasangGambar(): void
    {
        $this->buatSemuaGambar();

        foreach (self::PITA as $alamat => $berkas) {
            $xpath = $this->xpath($this->bukaOk($alamat));
            $img   = $xpath->query('//*[@data-judul-ilustrasi]//img');

            $this->assertSame(1, $img->length, $alamat);
            $this->assertStringContainsString('aset/gambar/' . $berkas, $img->item(0)->getAttribute('src'), $alamat);
            $this->assertSame('', $img->item(0)->getAttribute('alt'), $alamat);
            $this->assertSame('true', $img->item(0)->getAttribute('aria-hidden'), $alamat);
            $this->assertSame(['960', '640'], [$img->item(0)->getAttribute('width'), $img->item(0)->getAttribute('height')], $alamat);
            $this->assertStringContainsString('hidden', $xpath->query('//*[@data-judul-ilustrasi]')->item(0)->getAttribute('class'), $alamat);

            // Open Graph: gambar halaman bila ada, selain itu umum.jpg; selalu alamat mutlak.
            $og = $this->meta($xpath, 'og:image');
            $this->assertMatchesRegularExpression('#^https?://#', $og, $alamat);
            $this->assertStringContainsString(str_starts_with($alamat, 'hasil') ? 'bagikan/hasil.jpg' : 'bagikan/umum.jpg', $og, $alamat);
            $this->assertSame('summary_large_image', $this->meta($xpath, 'twitter:card'), $alamat);

            // Ikon aplikasi G9-11 dan manifest.
            $this->assertSame(2, $xpath->query('//link[@rel="icon"][@type="image/png"]')->length, $alamat);
            $this->assertSame(1, $xpath->query('//link[@rel="apple-touch-icon"][@sizes="180x180"]')->length, $alamat);
            $this->assertSame(1, $xpath->query('//link[@rel="manifest"]')->length, $alamat);
            $this->assertSame(1, $xpath->query('//link[@rel="icon"][@type="image/svg+xml"]')->length, $alamat);
        }

        $xpath = $this->xpath($this->bukaOk('/'));
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]//img[@data-hiasan="pita-sudut"][@aria-hidden="true"]')->length);

        $this->bukaPendaftaranUji();
        $xpath = $this->xpath($this->bukaOk('pendaftaran'));
        $this->assertSame(1, $xpath->query('//div[@id="selesai"]//img[@data-hiasan="konfeti"][@aria-hidden="true"]')->length);
    }

    public function testManifestMenyebutIkonYangDiperiksa(): void
    {
        $manifest = json_decode((string) file_get_contents(FCPATH . 'manifest.webmanifest'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('#234987', $manifest['theme_color']);
        $this->assertNotSame('', $manifest['name']);

        foreach ($manifest['icons'] as $ikon) {
            $jalur = substr($ikon['src'], strlen('aset/gambar/'));
            $this->assertArrayHasKey($jalur, self::BERKAS, $ikon['src']);
            $this->assertSame(implode('x', self::BERKAS[$jalur]), $ikon['sizes']);
            $this->assertStringContainsString("'" . basename($jalur) . "'", (string) file_get_contents(APPPATH . 'Views/tata_letak/_ikon_aplikasi.php'));
        }
    }

    public function testHalamanMasukDenganDanTanpaBerkas(): void
    {
        $xpath = $this->xpath($this->bukaOk('admin/masuk'));
        $this->assertSame(0, $xpath->query('//*[@data-polos-ilustrasi]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-hiasan]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-polos-kartu]//form')->length);

        $this->buatSemuaGambar();

        $xpath = $this->xpath($this->bukaOk('admin/masuk'));
        $img   = $xpath->query('//*[@data-polos-ilustrasi]//img');
        $this->assertSame(1, $img->length);
        $this->assertStringContainsString('aset/gambar/admin/masuk.webp', $img->item(0)->getAttribute('src'));
        $this->assertStringContainsString('lg:block', $xpath->query('//*[@data-polos-ilustrasi]')->item(0)->getAttribute('class'));
        $this->assertSame(1, $xpath->query('//main//img[@data-hiasan="pita-sudut"][@aria-hidden="true"]')->length);
        $this->assertSame(1, $xpath->query('//link[@rel="manifest"]')->length);
    }

    public function testHalamanAdminDenganDanTanpaBerkas(): void
    {
        $halaman = [
            'admin/dasbor'             => 'dasbor-sapaan',
            'admin/daftar-ulang'       => 'daftar-ulang',
            'admin/nomor-atlet/cetak'  => 'cetak-bib',
            'admin/hasil'              => 'hasil-lomba',
        ];

        foreach (array_keys($halaman) as $alamat) {
            $xpath = $this->xpath($this->bukaAdminOk($alamat));
            $this->assertSame(0, $xpath->query('//img[@data-gambar-admin]')->length, $alamat);
        }

        $xpath = $this->xpath($this->bukaAdminOk('admin/seri'));
        $this->assertSame(0, $xpath->query('//img[contains(@src, "kosong-admin")]')->length);

        $this->buatSemuaGambar();

        foreach ($halaman as $alamat => $berkas) {
            $xpath = $this->xpath($this->bukaAdminOk($alamat));
            $img   = $xpath->query('//img[@data-gambar-admin="' . $berkas . '"]');
            $this->assertSame(1, $img->length, $alamat);
            $this->assertStringContainsString('aset/gambar/admin/' . $berkas . '.webp', $img->item(0)->getAttribute('src'), $alamat);
            $this->assertSame('true', $img->item(0)->getAttribute('aria-hidden'), $alamat);
            $this->assertStringContainsString('hidden', $img->item(0)->getAttribute('class'), $alamat);
        }

        // Keadaan kosong admin: belum ada nomor lomba.
        $xpath = $this->xpath($this->bukaAdminOk('admin/seri'));
        $this->assertSame(1, $xpath->query('//img[contains(@src, "aset/gambar/admin/kosong-admin.webp")]')->length);
        $this->assertSame(1, $xpath->query('//link[@rel="apple-touch-icon"]')->length);
    }

    private function bukaOk(string $alamat): string
    {
        $hasil = $this->withSession([])->get($alamat);
        $hasil->assertOK();

        return $this->isi($hasil);
    }

    private function bukaAdminOk(string $alamat): string
    {
        $hasil = $this->bukaSebagaiAdmin($alamat);
        $hasil->assertOK();

        return $this->isi($hasil);
    }

    private function isi(TestResponse $hasil): string
    {
        return (string) $hasil->response()->getBody();
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function elemen(string $html, string $tag): DOMElement
    {
        $this->assertNotSame('', $html);
        $node = $this->xpath($html)->query('//' . $tag)->item(0);
        $this->assertInstanceOf(DOMElement::class, $node);

        return $node;
    }

    private function meta(DOMXPath $xpath, string $nama): string
    {
        $node = $xpath->query('//meta[@property="' . $nama . '" or @name="' . $nama . '"]')->item(0);

        return $node instanceof DOMElement ? $node->getAttribute('content') : '';
    }

    /**
     * @return array<string, true>
     */
    private function briefBagikan(): array
    {
        $isi = (string) file_get_contents(ROOTPATH . 'desain/tugas/G9-10-gambar-bagikan.md');
        preg_match_all('/`([a-z-]+\.jpg)`/', $isi, $cocok);

        return array_fill_keys(array_map(static fn (string $b): string => 'bagikan/' . $b, $cocok[1]), true);
    }

    private function buatSemuaGambar(): void
    {
        foreach (self::BERKAS as $jalur => [$lebar, $tinggi]) {
            $this->buatGambar($jalur, $lebar, $tinggi);
        }
    }

    /**
     * Gambar sungguhan kecil berukuran tertentu (supaya `getimagesize()` terbaca).
     */
    private function buatGambar(string $jalur, int $lebar, int $tinggi): void
    {
        $berkas = $this->folder . $jalur;

        if (! is_dir(dirname($berkas))) {
            mkdir(dirname($berkas), 0777, true);
        }

        $gambar = imagecreatetruecolor($lebar, $tinggi);

        match (pathinfo($berkas, PATHINFO_EXTENSION)) {
            'webp'  => imagewebp($gambar, $berkas, 10),
            'png'   => imagepng($gambar, $berkas, 9),
            default => imagejpeg($gambar, $berkas, 10),
        };
    }

    private function hapusFolder(string $folder): void
    {
        if (! is_dir($folder)) {
            return;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $b) {
            $b->isDir() ? rmdir($b->getPathname()) : unlink($b->getPathname());
        }

        rmdir($folder);
    }
}
