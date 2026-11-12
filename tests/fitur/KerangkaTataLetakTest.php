<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UjiHalaman;

/**
 * Kesepakatan tata letak dari kerangka (F1-01): bagian `judul`, `isi`, `skrip`,
 * aset lokal lewat `aset()`, dan tidak ada yang dimuat dari luar.
 *
 * @internal
 */
final class KerangkaTataLetakTest extends UjiHalaman
{
    /**
     * @return array<string, array{string}>
     */
    public static function tataLetak(): array
    {
        return [
            'publik' => ['publik'],
            'admin'  => ['admin'],
            'polos'  => ['polos'],
        ];
    }

    #[DataProvider('tataLetak')]
    public function testBagianTataLetakTerisi(string $nama): void
    {
        $html = $this->render($nama);

        $this->assertMatchesRegularExpression('/<html lang="id">/', $html);
        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1">', $html);
        // Tata letak admin dan polos menambahkan nama acara di judul tab (F3-04).
        $this->assertMatchesRegularExpression('#<title>Judul Contoh ' . $nama . '( – .+)?</title>#', $html);
        $this->assertStringContainsString('<p id="isi-contoh">Isi contoh ' . $nama . '</p>', $html);

        // Skrip halaman dimuat setelah Bootstrap.
        $posisiBootstrap = strpos($html, aset('vendor/bootstrap/js/bootstrap.bundle.min.js'));
        $posisiSkrip     = strpos($html, '<script id="skrip-contoh">');
        $this->assertNotFalse($posisiBootstrap);
        $this->assertNotFalse($posisiSkrip);
        $this->assertGreaterThan($posisiBootstrap, $posisiSkrip);
    }

    #[DataProvider('tataLetak')]
    public function testAsetLokalDimuat(string $nama): void
    {
        $html = $this->render($nama);

        foreach ([
            'vendor/bootstrap/css/bootstrap.min.css',
            'vendor/bootstrap-icons/bootstrap-icons.min.css',
            'vendor/bootstrap/js/bootstrap.bundle.min.js',
            'css/tema.css',
        ] as $jalur) {
            $this->assertStringContainsString('"' . aset($jalur) . '"', $html);
            $this->assertFileExists(FCPATH . 'aset/' . $jalur);
        }

        if ($nama === 'admin') {
            $this->assertStringContainsString('"' . aset('css/admin.css') . '"', $html);
        } else {
            $this->assertStringNotContainsString('css/admin.css', $html);
        }
    }

    #[DataProvider('tataLetak')]
    public function testTidakAdaAlamatLuar(string $nama): void
    {
        $this->assertTanpaAlamatLuar($this->render($nama));
    }

    public function testHalamanNyataTanpaAlamatLuar(): void
    {
        foreach (['/', 'admin/dasbor'] as $alamat) {
            $hasil = $this->withSession(self::SESI_ADMIN)->get($alamat);

            $hasil->assertOK();
            $this->assertTanpaAlamatLuar($hasil->getBody());
        }
    }

    private function render(string $nama): string
    {
        if ($nama === 'admin') {
            $_SESSION = self::SESI_ADMIN;
        }

        return view('Tests\Support\Views\contoh_' . $nama);
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
