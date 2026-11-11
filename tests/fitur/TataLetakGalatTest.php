<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UjiHalaman;

/**
 * Halaman galat HTML (F1-05): 404, 400, dan galat umum mode produksi.
 *
 * Rute yang tidak ada melempar `PageNotFoundException` di uji fitur, jadi
 * view galat dirender langsung seperti yang dilakukan penangan galat
 * CodeIgniter (`include` dengan variabel `$message`, `$code`, `$title`).
 *
 * @internal
 */
final class TataLetakGalatTest extends UjiHalaman
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function viewGalat(): array
    {
        return [
            '404'      => ['error_404.php', 'Halaman tidak ditemukan'],
            '400'      => ['error_400.php', 'Permintaan tidak bisa diproses'],
            'produksi' => ['production.php', 'Maaf, ada kendala'],
        ];
    }

    #[DataProvider('viewGalat')]
    public function testHalamanGalatBerbahasaIndonesia(string $berkas, string $judul): void
    {
        $html = $this->renderGalat($berkas);

        $this->assertStringContainsString('<html lang="id">', $html);
        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1">', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex">', $html);
        $this->assertStringContainsString('<title>' . $judul . ' – MilkLife Athletics Challenge</title>', $html);
        $this->assertStringContainsString('<h1>' . $judul . '</h1>', $html);
        $this->assertStringContainsString('MilkLife <span>Athletics Challenge</span>', $html);

        // Langkah berikutnya: kembali ke beranda.
        $this->assertStringContainsString('<a class="galat-tombol" href="' . site_url('/') . '">Kembali ke beranda</a>', $html);

        // Bahasa bawaan CodeIgniter (Inggris) tidak muncul.
        foreach (['Whoops', 'Sorry', 'not found', 'Page Not Found', 'snag'] as $kata) {
            $this->assertStringNotContainsStringIgnoringCase($kata, $html);
        }
    }

    #[DataProvider('viewGalat')]
    public function testGayaDisisipkanTanpaBerkasLuar(string $berkas): void
    {
        $html = $this->renderGalat($berkas);

        // CSS disisipkan langsung, jadi tetap tampil walau berkas statis atau fungsi bantu bermasalah.
        $this->assertMatchesRegularExpression('#<style>.*--ml-biru-tua: \#021F7B;.*</style>#s', $html);
        $this->assertStringNotContainsString('<link', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('extend(', (string) file_get_contents(APPPATH . 'Views/errors/html/' . $berkas));
    }

    #[DataProvider('viewGalat')]
    public function testHalamanGalatTanpaAlamatLuar(string $berkas): void
    {
        $hostSendiri = parse_url(base_url(), PHP_URL_HOST);

        preg_match_all('#(?:https?:)?//([^/\s"\'<>?\#]+)#i', $this->renderGalat($berkas), $cocok);

        foreach ($cocok[1] as $host) {
            $host = strtolower((string) preg_replace('/:\d+$/', '', $host));
            $this->assertSame($hostSendiri, $host, 'Ada alamat ke host luar: ' . $host);
        }
    }

    public function testPesanTeknisDiEscapeDanHanyaDiLuarProduksi(): void
    {
        $html = $this->renderGalat('error_404.php', ['message' => 'Rute <b>uji</b> tidak ada']);

        // Lingkungan uji bukan produksi: pesan untuk pengembang tampil, aman di-escape.
        $this->assertNotSame('production', ENVIRONMENT);
        $this->assertStringContainsString('Rute &lt;b&gt;uji&lt;/b&gt; tidak ada', $html);
        $this->assertStringNotContainsString('<b>uji</b>', $html);

        $sumber = (string) file_get_contents(APPPATH . 'Views/errors/html/error_404.php');
        $this->assertStringContainsString("ENVIRONMENT !== 'production'", $sumber);
    }

    public function testGalatProduksiTidakMenampilkanRincian(): void
    {
        $html = $this->renderGalat('production.php', ['message' => 'Rahasia basis data']);

        $this->assertStringNotContainsString('Rahasia basis data', $html);
        $this->assertStringNotContainsString('galat-teknis">', $html);
    }

    /**
     * Merender view galat seperti `BaseExceptionHandler::render()`.
     *
     * @param array<string, mixed> $variabel
     */
    private function renderGalat(string $berkas, array $variabel = []): string
    {
        $variabel += ['title' => 'Uji', 'type' => 'Uji', 'code' => 404, 'message' => 'Pesan uji'];

        return (static function (string $__berkas, array $__variabel): string {
            extract($__variabel, EXTR_SKIP);
            ob_start();

            include $__berkas;

            return (string) ob_get_clean();
        })(APPPATH . 'Views/errors/html/' . $berkas, $variabel);
    }
}
