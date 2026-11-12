<?php

use App\Commands\MinifyAset;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Perintah `php spark aset:minify` (`App\Commands\MinifyAset`).
 *
 * Uji memakai folder aset karangan di folder sementara sistem, jadi
 * `public/aset/` tidak disentuh.
 *
 * @internal
 */
final class MinifyAsetTest extends CIUnitTestCase
{
    private string $folder = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-aset-' . bin2hex(random_bytes(6));

        $this->tulis('css/tema.css', <<<'CSS'
            /* Warna utama tema */
            :root {
                --warna-utama: #0d6efd;
            }

            .kartu-lomba {
                padding: 1rem;
                border-radius: 0.5rem;
                color: var(--warna-utama);
            }

            @media (max-width: 576px) {
                .kartu-lomba {
                    padding: 0.5rem;
                }
            }
            CSS);

        $this->tulis('js/pendaftaran.js', <<<'JS'
            // Hitung jumlah atlet yang dipilih.
            function hitungAtlet(daftar) {
                var jumlah = 0;

                for (var i = 0; i < daftar.length; i++) {
                    if (daftar[i].dipilih) {
                        jumlah = jumlah + 1;
                    }
                }

                return 'Atlet terpilih: ' + jumlah;
            }
            JS);

        $this->tulis('vendor/pustaka/pustaka.css', ".pustaka {\n    color: red;\n}\n");
        $this->tulis('vendor/pustaka/pustaka.js', "function pustaka() {\n    return 1;\n}\n");
        $this->tulis('js/sudah.min.js', 'var sudah=1;');
        $this->tulis('gambar/ikon.svg', <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <!-- Ikon aplikasi -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
              <rect width="32" height="32"   fill="#021F7B"/>
              <text x="4" y="20">M  L</text>
            </svg>
            SVG);
    }

    protected function tearDown(): void
    {
        self::hapusFolder($this->folder);

        parent::tearDown();
    }

    public function testMembuatBerkasMinYangLebihKecilDenganAturanSama(): void
    {
        $hasil = MinifyAset::perkecil($this->folder);

        $this->assertSame(['css/tema.css', 'gambar/ikon.svg', 'js/pendaftaran.js'], array_column($hasil, 'berkas'));

        foreach ($hasil as $baris) {
            $this->assertLessThan($baris['sebelum'], $baris['sesudah'], $baris['berkas']);
        }

        $css = (string) file_get_contents($this->folder . '/css/tema.min.css');
        $this->assertSame(filesize($this->folder . '/css/tema.css'), $hasil[0]['sebelum']);
        $this->assertSame(strlen($css), $hasil[0]['sesudah']);
        $this->assertStringNotContainsString('Warna utama', $css);
        $this->assertStringNotContainsString("\n", $css);

        // Aturan yang sama tetap ada.
        foreach (['--warna-utama:#0d6efd', '.kartu-lomba{', 'padding:1rem', 'border-radius:.5rem', 'color:var(--warna-utama)', '@media (max-width:576px){.kartu-lomba{padding:.5rem}}'] as $aturan) {
            $this->assertStringContainsString($aturan, $css);
        }

        $svg = (string) file_get_contents($this->folder . '/gambar/ikon.min.svg');
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"> <rect width="32" height="32"   fill="#021F7B"/> <text x="4" y="20">M L</text> </svg>',
            $svg,
        );

        $js = (string) file_get_contents($this->folder . '/js/pendaftaran.min.js');
        $this->assertStringNotContainsString('Hitung jumlah', $js);
        $this->assertStringContainsString('function hitungAtlet(daftar)', $js);
        $this->assertStringContainsString("'Atlet terpilih: '", $js);
    }

    public function testVendorDanBerkasMinDilewati(): void
    {
        $sebelum = (string) file_get_contents($this->folder . '/js/sudah.min.js');
        $asli    = (string) file_get_contents($this->folder . '/gambar/ikon.svg');

        MinifyAset::perkecil($this->folder);

        // Berkas asli tidak diubah.
        $this->assertSame($asli, file_get_contents($this->folder . '/gambar/ikon.svg'));

        $this->assertFileDoesNotExist($this->folder . '/vendor/pustaka/pustaka.min.css');
        $this->assertFileDoesNotExist($this->folder . '/vendor/pustaka/pustaka.min.js');
        $this->assertFileDoesNotExist($this->folder . '/js/sudah.min.min.js');
        $this->assertSame($sebelum, file_get_contents($this->folder . '/js/sudah.min.js'));
        $this->assertSame(
            ['css/tema.css', 'css/tema.min.css', 'gambar/ikon.min.svg', 'gambar/ikon.svg', 'js/pendaftaran.js', 'js/pendaftaran.min.js', 'js/sudah.min.js', 'vendor/pustaka/pustaka.css', 'vendor/pustaka/pustaka.js'],
            $this->isiFolder(),
        );
    }

    public function testAmanDijalankanUlang(): void
    {
        $pertama = MinifyAset::perkecil($this->folder);
        $isi     = file_get_contents($this->folder . '/css/tema.min.css');

        $kedua = MinifyAset::perkecil($this->folder);

        $this->assertSame($pertama, $kedua);
        $this->assertSame($isi, file_get_contents($this->folder . '/css/tema.min.css'));
        $this->assertCount(9, $this->isiFolder());

        // Berkas asli yang berubah menghasilkan berkas kecil baru.
        $this->tulis('css/tema.css', ".baru {\n    margin: 0;\n}\n");
        MinifyAset::perkecil($this->folder);
        $this->assertSame('.baru{margin:0}', file_get_contents($this->folder . '/css/tema.min.css'));
    }

    public function testFolderTidakAdaDitolakDenganPesanRamah(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Jalankan perintah ini dari folder proyek.');

        MinifyAset::perkecil($this->folder . '/tidak-ada');
    }

    public function testAsetAplikasiYangAdaBisaDiperkecil(): void
    {
        // Semua CSS/JS/SVG aplikasi sungguhan bisa diolah tanpa galat; hasilnya
        // ditulis ke salinan di folder sementara.
        $salinan = $this->folder . '/salinan';

        foreach (['css' => 'css', 'js' => 'js', 'gambar' => 'svg'] as $sub => $ekstensi) {
            foreach (glob(FCPATH . 'aset/' . $sub . '/*.' . $ekstensi) ?: [] as $berkas) {
                if (! str_ends_with($berkas, '.min.' . $ekstensi)) {
                    $this->tulis('salinan/' . $sub . '/' . basename($berkas), (string) file_get_contents($berkas));
                }
            }
        }

        $hasil = MinifyAset::perkecil($salinan);

        $this->assertNotEmpty($hasil);
        $this->assertContains('css/tema.css', array_column($hasil, 'berkas'));
        $this->assertContains('js/pendaftaran.js', array_column($hasil, 'berkas'));
        $this->assertContains('gambar/ikon.svg', array_column($hasil, 'berkas'));

        foreach ($hasil as $baris) {
            $this->assertLessThan($baris['sebelum'], $baris['sesudah'], $baris['berkas']);
        }
    }

    private function tulis(string $relatif, string $isi): void
    {
        $jalur = $this->folder . '/' . $relatif;

        if (! is_dir(dirname($jalur))) {
            mkdir(dirname($jalur), 0777, true);
        }

        file_put_contents($jalur, $isi);
    }

    /**
     * @return list<string>
     */
    private function isiFolder(): array
    {
        $hasil    = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->folder, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $berkas) {
            $hasil[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($berkas->getPathname(), strlen($this->folder) + 1));
        }

        sort($hasil);

        return $hasil;
    }

    private static function hapusFolder(string $folder): void
    {
        if ($folder === '' || ! is_dir($folder)) {
            return;
        }

        foreach (scandir($folder) ?: [] as $nama) {
            if ($nama === '.' || $nama === '..') {
                continue;
            }

            $jalur = $folder . DIRECTORY_SEPARATOR . $nama;
            is_dir($jalur) ? self::hapusFolder($jalur) : unlink($jalur);
        }

        rmdir($folder);
    }
}
