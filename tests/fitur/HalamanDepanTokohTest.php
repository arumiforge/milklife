<?php

use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Pemasangan art G10 di halaman depan (F10-10): bagian "Meet the Athletes" dari
 * tokoh G10-01, garis kecepatan G10-11 di hero, sorot kuas di judul, dan pemisah
 * lintasan G10-11. Semua hanya tampil bila berkasnya ada; berkas tiruan ditaruh
 * di folder gambar uji (`GambarCodexKosong`).
 *
 * @internal
 */
final class HalamanDepanTokohTest extends UjiBasisData
{
    use KirimFormulir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('kelompok_umur')->insert([
            'nama'              => 'KU Karangan',
            'tahun_lahir_awal'  => 2016,
            'tahun_lahir_akhir' => 2018,
            'biaya_individu'    => null,
            'urutan'            => 1,
            'aktif'             => 1,
        ]);
        $this->db->table('nomor_lomba')->insert([
            'kelompok_umur_id' => (int) $this->db->insertID(),
            'nama'             => 'Lari Karangan 60 m',
            'jenis'            => 'individu',
            'kategori'         => 'putra',
            'urutan'           => 1,
            'aktif'            => 1,
        ]);
    }

    public function testTanpaBerkasTanpaBagianTokoh(): void
    {
        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(0, $xpath->query('//*[@id="atlet"]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-garis-kecepatan]')->length);
        $this->assertStringNotContainsString('gambar/tokoh/', (string) $hasil->response()->getBody());
    }

    public function testLembarDanTokohTunggal(): void
    {
        foreach (['lembar-tokoh', 'tokoh-a', 'tokoh-b', 'tokoh-c', 'tokoh-d', 'tokoh-e'] as $berkas) {
            $this->taruhGambarUji('tokoh/' . $berkas . '.webp');
        }

        $xpath  = $this->xpathDari($this->get('/'));
        $bagian = $xpath->query('//section[@id="atlet"][@aria-labelledby="judul-atlet"]')->item(0);
        $this->assertNotNull($bagian);

        // Judul Inggris editorial (hiasan) dengan sorot kuas, judul Indonesia yang dibaca.
        $this->assertSame('Meet the Athletes', trim((string) $xpath->query('.//*[@data-judul-inggris][@lang="en"][@aria-hidden="true"]', $bagian)->item(0)?->textContent));
        $this->assertSame(1, $xpath->query('.//*[@data-judul-inggris]/span[contains(@class, "sorot-kuas")]', $bagian)->length);
        $this->assertSame('Kenalan dengan para atlet', trim((string) $xpath->query('.//h2[@id="judul-atlet"]', $bagian)->item(0)?->textContent));

        // Lembar mulai md, dua tokoh tunggal (A dan C) hanya di ponsel; semuanya lazy dan ber-alt.
        $lembar = $xpath->query('.//img[@data-tokoh-lembar]', $bagian)->item(0);
        $this->assertNotNull($lembar);
        $this->assertStringContainsString('aset/gambar/tokoh/lembar-tokoh.webp', $lembar->getAttribute('src'));
        $this->assertStringContainsString('hidden', $lembar->getAttribute('class'));
        $this->assertStringContainsString('md:block', $lembar->getAttribute('class'));

        $tunggal = $xpath->query('.//*[@data-tokoh-tunggal]', $bagian)->item(0);
        $this->assertNotNull($tunggal);
        $this->assertStringContainsString('md:hidden', $tunggal->getAttribute('class'));
        $gambarTunggal = $xpath->query('.//img', $tunggal);
        $this->assertSame(2, $gambarTunggal->length);
        $this->assertStringContainsString('tokoh/tokoh-a.webp', $gambarTunggal->item(0)->getAttribute('src'));
        $this->assertStringContainsString('tokoh/tokoh-c.webp', $gambarTunggal->item(1)->getAttribute('src'));

        foreach ($xpath->query('.//img', $bagian) as $img) {
            $this->assertSame('lazy', $img->getAttribute('loading'));
            $this->assertNotSame('', $img->getAttribute('alt'), 'Tokoh bermakna, jadi ber-alt.');
            $this->assertFalse($img->hasAttribute('aria-hidden'));
        }

        // Bagian tokoh sesudah kelompok umur dan nomor lomba.
        $this->assertSame(1, $xpath->query('//section[@id="lomba"]/following-sibling::section[@id="atlet"]')->length);
    }

    public function testHanyaLembarTampilDiSemuaUkuran(): void
    {
        $this->taruhGambarUji('tokoh/lembar-tokoh.webp');

        $xpath  = $this->xpathDari($this->get('/'));
        $lembar = $xpath->query('//section[@id="atlet"]//img[@data-tokoh-lembar]')->item(0);

        $this->assertNotNull($lembar);
        $this->assertStringNotContainsString('hidden', $lembar->getAttribute('class'));
        $this->assertSame(0, $xpath->query('//*[@data-tokoh-tunggal]')->length);
    }

    public function testHanyaTokohTunggalYangAdaDipakai(): void
    {
        $this->taruhGambarUji('tokoh/tokoh-b.webp');
        $this->taruhGambarUji('tokoh/tokoh-e.webp');

        $xpath   = $this->xpathDari($this->get('/'));
        $tunggal = $xpath->query('//section[@id="atlet"]//*[@data-tokoh-tunggal]')->item(0);

        $this->assertNotNull($tunggal);
        $this->assertStringNotContainsString('md:hidden', $tunggal->getAttribute('class'));
        $this->assertSame(0, $xpath->query('//img[@data-tokoh-lembar]')->length);

        $src = array_map(static fn (DOMElement $img): string => $img->getAttribute('src'), iterator_to_array($xpath->query('.//img', $tunggal)));
        $this->assertCount(2, $src);
        $this->assertStringContainsString('tokoh/tokoh-b.webp', $src[0]);
        $this->assertStringContainsString('tokoh/tokoh-e.webp', $src[1]);
    }

    public function testGarisKecepatanDanPemisahLintasanG1011(): void
    {
        $this->taruhGambarUji('elemen/garis-kecepatan.webp');
        $this->taruhGambarUji('elemen/kuas-sorot.webp');
        $this->taruhGambarUji('elemen/pemisah-lintasan.webp');
        $this->taruhGambarUji('pola/garis-lintasan.webp');

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);
        $kelas = (string) $xpath->query('//html')->item(0)?->getAttribute('class');

        $this->assertStringContainsString('ada-garis-kecepatan', $kelas);
        $this->assertStringContainsString('ada-kuas-sorot', $kelas);

        // Garis kecepatan di hero: hiasan mulai lg, tanpa <img> (latar CSS di bawah penanda).
        $garis = $xpath->query('//*[@data-pembuka-hero]/div[@data-garis-kecepatan][@aria-hidden="true"]')->item(0);
        $this->assertNotNull($garis);
        $this->assertStringContainsString('garis-kecepatan', $garis->getAttribute('class'));
        $this->assertStringContainsString('lg:block', $garis->getAttribute('class'));

        // Pemisah memakai elemen G10-11 (CSS), bukan gambar pola garis lintasan.
        $this->assertGreaterThanOrEqual(1, $xpath->query('//div[@data-hiasan="pemisah-lintasan"][@aria-hidden="true"]/div[@class="pemisah-lintasan"]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-hiasan="garis-lintasan"]')->length);
    }

    public function testCssElemenBaruHanyaDiBawahPenanda(): void
    {
        $css = (string) file_get_contents(FCPATH . 'aset/css/tw.css');

        $this->assertMatchesRegularExpression('#\.ada-kuas-sorot\s+\.sorot-kuas\s*\{[^}]*kuas-sorot\.webp#', $css);
        $this->assertMatchesRegularExpression('#\.ada-garis-kecepatan\s+\.garis-kecepatan\s*\{[^}]*garis-kecepatan\.webp#', $css);
    }
}
