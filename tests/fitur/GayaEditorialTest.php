<?php

use App\Libraries\GambarOpsional;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Fondasi gaya editorial (F10-05): huruf judul Barlow Condensed lokal, kelas
 * penanda `ada-<nama>` untuk elemen berart G10-11 yang hanya ditulis bila
 * berkasnya ada, dan CSS yang hanya merujuk berkas elemen di bawah penandanya
 * (tanpa permintaan 404). Berkas tiruan ditaruh di folder gambar uji
 * (`GambarCodexKosong`).
 *
 * @internal
 */
final class GayaEditorialTest extends UjiBasisData
{
    use KirimFormulir;

    public function testTanpaBerkasElemenTidakAdaPenanda(): void
    {
        $this->assertSame('', GambarOpsional::penandaElemen());

        $hasil = $this->get('/');
        $hasil->assertOK();
        $html = (string) $hasil->response()->getBody();

        $this->assertStringNotContainsString('ada-', (string) $this->xpathDari($hasil)->query('//html')->item(0)?->getAttribute('class'));
        $this->assertStringNotContainsString('gambar/elemen/', $html, 'Tanpa berkas, halaman tidak merujuk gambar elemen.');
        $this->assertStringContainsString('tombol-utama-berart', $html, 'Tombol utama tetap memakai kelas berart (warna padat).');
    }

    public function testDenganBerkasPenandaHanyaUntukYangAda(): void
    {
        $this->taruhGambarUji('elemen/kuas-tombol-oranye.webp');
        $this->taruhGambarUji('elemen/tekstur-kertas.webp');

        $this->assertSame('ada-kuas-tombol-oranye ada-tekstur-kertas', GambarOpsional::penandaElemen());

        foreach (['/', 'pendaftaran', 'cek-pendaftaran'] as $alamat) {
            $hasil = $this->get($alamat);
            $hasil->assertOK();
            $kelas = (string) $this->xpathDari($hasil)->query('//html')->item(0)?->getAttribute('class');

            $this->assertSame('ada-kuas-tombol-oranye ada-tekstur-kertas', $kelas, $alamat);
        }
    }

    public function testHalamanMasukPanelTanpaPenanda(): void
    {
        $this->taruhGambarUji('elemen/kuas-tombol-biru.webp');

        $kelas = (string) $this->xpathDari($this->get('admin/masuk'))->query('//html')->item(0)?->getAttribute('class');

        $this->assertStringNotContainsString('ada-', $kelas, 'Penanda hanya di tata letak publik.');
    }

    public function testCssElemenHanyaDiBawahPenanda(): void
    {
        $css = (string) file_get_contents(FCPATH . 'aset/css/tw.css');

        preg_match_all('#([^{}]+)\{[^{}]*url\(["\']?\.\./gambar/elemen/([a-z-]+)\.webp#', $css, $cocok, PREG_SET_ORDER);
        $this->assertCount(count(GambarOpsional::ELEMEN), $cocok, 'Setiap elemen G10-11 dirujuk tepat satu kali.');

        foreach ($cocok as [, $pemilih, $nama]) {
            $this->assertContains($nama, GambarOpsional::ELEMEN);

            foreach (explode(',', $pemilih) as $satu) {
                $this->assertStringContainsString('.ada-' . $nama . ' ', $satu, 'Berkas ' . $nama . ' hanya dipakai di bawah .ada-' . $nama);
            }
        }

        foreach (['judul-tampilan', 'alis', 'tombol-utama-berart', 'pemisah-lintasan', 'ornamen-judul', 'bingkai-foto', 'tekstur-kertas'] as $kelas) {
            $this->assertMatchesRegularExpression('#\.' . $kelas . '\b[^{]*\{#', $css, $kelas);
        }

        $this->assertStringContainsString('--color-krem: #F5EFE6', $css);
        $this->assertStringContainsString("--font-tampilan: 'Barlow Condensed'", $css);
    }

    public function testHurufJudulLokalDanPreloadHalamanDepan(): void
    {
        $css = (string) file_get_contents(FCPATH . 'aset/css/tw.css');

        preg_match_all('#url\(["\']?\.\./vendor/barlow-condensed/([a-z0-9-]+\.woff2)#', $css, $cocok);
        $berkas = array_unique($cocok[1]);
        sort($berkas);

        $harapan = [];
        foreach (['latin', 'latin-ext'] as $subset) {
            foreach (['600', '800'] as $berat) {
                foreach (['italic', 'normal'] as $gaya) {
                    $harapan[] = "barlow-condensed-{$subset}-{$berat}-{$gaya}.woff2";
                }
            }
        }
        sort($harapan);
        $this->assertSame($harapan, $berkas, 'Hanya berat 600 dan 800, tegak dan miring, latin dan latin-ext.');

        foreach ($berkas as $nama) {
            $this->assertFileExists(FCPATH . 'aset/vendor/barlow-condensed/' . $nama);
        }
        $this->assertFileExists(FCPATH . 'aset/vendor/barlow-condensed/LICENSE');

        // Halaman depan: Inter dan Barlow Condensed 800 miring (judul pembuka) di-preload, paling banyak tiga berkas huruf.
        $xpath   = $this->xpathDari($this->get('/'));
        $preload = array_map(
            static fn (DOMElement $simpul): string => $simpul->getAttribute('href'),
            iterator_to_array($xpath->query('//head/link[@rel="preload"][@as="font"]')),
        );
        $this->assertLessThanOrEqual(3, count($preload));
        $this->assertContains(base_url('aset/vendor/barlow-condensed/barlow-condensed-latin-800-italic.woff2'), $preload);
        $this->assertSame(0, $xpath->query('//head/link[@rel="preload"][@as="font"][not(@crossorigin)]')->length);

        // Halaman lain tidak me-preload huruf judul.
        $this->assertStringNotContainsString('barlow-condensed-latin-800-italic.woff2', (string) $this->get('cek-pendaftaran')->response()->getBody());
    }
}
