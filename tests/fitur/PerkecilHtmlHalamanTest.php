<?php

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Filter `perkecilhtml` pada halaman sungguhan (F7-01): halaman HTML
 * diperkecil hanya bila `aplikasi.minify` aktif, isi dan skripnya tetap sama;
 * JSON, PDF, dan Excel tidak berubah. Semua nama, NISN, dan NPSN karangan.
 *
 * @internal
 */
final class PerkecilHtmlHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    private int $idPendaftaran = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->idPendaftaran = $this->buatPendaftaranPanel([
            'atlet' => [$this->atletPanel(['nama' => 'Budi Karangan', 'nomor' => ['lari60putra']])],
        ])['id'];

        // mPDF membaca SCRIPT_NAME (selalu ada di server web); uji lain bisa menghapusnya.
        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
    }

    protected function tearDown(): void
    {
        config('Aplikasi')->minify = false;
        $this->bersihkanBerkasPanel();

        // Tabel dikosongkan lagi: uji halaman (`UjiHalaman`) sesudahnya
        // memakai basis data yang sama tanpa menyegarkannya.
        $this->regressDatabase();
        $this->migrateDatabase();

        parent::tearDown();
    }

    public function testHalamanDiperkecilHanyaBilaMinifyAktifDenganIsiSama(): void
    {
        $id = $this->idPendaftaran;

        foreach (['/', 'pendaftaran', 'cek-pendaftaran', 'unduhan', 'admin/dasbor', 'admin/pendaftaran', "admin/pendaftaran/{$id}", "admin/pendaftaran/{$id}/ubah", 'admin/rekap/sekolah', 'admin/pengaturan/halaman-depan'] as $alamat) {
            config('Aplikasi')->minify = false;
            $asli = $this->buka($alamat);

            config('Aplikasi')->minify = true;
            $kecil = $this->buka($alamat);

            $this->assertStringStartsWith('text/html', $kecil->response()->getHeaderLine('Content-Type'), $alamat);
            $this->assertLessThan(strlen($asli->getBody()), strlen($kecil->getBody()), $alamat);
            $this->assertStringNotContainsString("\n    <", $kecil->getBody(), $alamat);

            // Teks yang tampil, tautan, isian, dan skrip tetap sama.
            $this->assertSame(self::ringkasan($asli), self::ringkasan($kecil), $alamat);
        }
    }

    public function testJsonPdfDanExcelTidakBerubah(): void
    {
        config('Aplikasi')->minify = false;
        $json = $this->bukaSebagaiAdmin('admin/rekap/sekolah/data')->getBody();
        $excel = $this->isiUnduhan($this->bukaSebagaiAdmin('admin/rekap/sekolah/excel'));

        config('Aplikasi')->minify = true;
        $this->assertSame($json, $this->bukaSebagaiAdmin('admin/rekap/sekolah/data')->getBody());

        $pdf = $this->bukaSebagaiAdmin('admin/rekap/sekolah/pdf')->response();
        $this->assertSame('application/pdf', explode(';', $pdf->getHeaderLine('Content-Type'))[0]);
        $this->assertStringStartsWith('%PDF', (string) $pdf->getBody());
        $this->assertStringContainsString('%%EOF', (string) $pdf->getBody());

        $excelKecil = $this->isiUnduhan($this->bukaSebagaiAdmin('admin/rekap/sekolah/excel'));
        $this->assertStringStartsWith('PK', $excelKecil);
        $this->assertSame(strlen($excel), strlen($excelKecil));
    }

    private function buka(string $alamat): TestResponse
    {
        // Jawaban bersama dari uji sebelumnya bisa masih berjenis JSON.
        Services::resetSingle('response');

        $hasil = str_starts_with($alamat, 'admin') ? $this->bukaSebagaiAdmin($alamat) : $this->get($alamat);
        $hasil->assertOK();

        return $hasil;
    }

    private function isiUnduhan(TestResponse $hasil): string
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();

        return (string) ob_get_clean();
    }

    /**
     * Potongan teks yang tampil (spasi dirapatkan), alamat tautan, nama dan
     * nilai isian dan pilihan, serta isi skrip dan gaya, tanpa token CSRF yang berganti tiap
     * permintaan. Berkas aset `.min` (dipilih `aset()` bila ada) dianggap sama
     * dengan aslinya.
     *
     * @return array<string, list<string>|string>
     */
    private static function ringkasan(TestResponse $hasil): array
    {
        $html = (string) preg_replace('/[a-f0-9]{32}/', 'TOKEN', $hasil->getBody());
        $html = (string) preg_replace('#(/aset/(?:css|js|gambar)/[^"\']+)\.min\.(css|js|svg)#', '$1.$2', $html);
        // Penanda versi `?v=` (K86) mengikuti waktu ubah berkas asli atau `.min`-nya.
        $html = (string) preg_replace('#(/aset/[^"\']+)\?v=\d+#', '$1', $html);
        $dom  = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $daftar = static function (string $kueri, callable $ambil) use ($xpath): array {
            $hasil = [];

            foreach ($xpath->query($kueri) as $simpul) {
                $hasil[] = $ambil($simpul);
            }

            return $hasil;
        };

        return [
            'teks'   => $daftar('//body//text()[normalize-space() != ""][not(ancestor::script)][not(ancestor::style)]', static fn (DOMNode $t): string => trim((string) preg_replace('/\s+/', ' ', $t->textContent))),
            'judul'  => (string) $xpath->query('//title')->item(0)?->textContent,
            'tautan' => $daftar('//a', static fn (DOMElement $a): string => $a->getAttribute('href')),
            'isian'  => $daftar('//input|//select|//textarea', static fn (DOMElement $e): string => $e->getAttribute('name') . '=' . $e->getAttribute('value')),
            'pilihan' => $daftar('//option', static fn (DOMElement $e): string => $e->getAttribute('value') . '=' . trim($e->textContent)),
            'skrip'  => $daftar('//script|//style', static fn (DOMElement $e): string => $e->getAttribute('src') . $e->textContent),
            'gaya'   => $daftar('//link', static fn (DOMElement $e): string => $e->getAttribute('href')),
        ];
    }
}
