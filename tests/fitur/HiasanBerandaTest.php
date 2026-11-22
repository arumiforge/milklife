<?php

use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Hiasan G9-02 dan G9-12 di halaman depan dan halaman masuk (F9-19): latar
 * pita dan lintasan, perlengkapan di kartu kelompok umur, tokoh bergantian,
 * pemisah lintasan, pola titik di latar merek, dan bokeh di halaman masuk.
 * Semua hanya tampil bila berkasnya ada; berkas tiruan ditaruh di folder
 * gambar uji (`GambarCodexKosong`). Nama dan isi karangan.
 *
 * @internal
 */
final class HiasanBerandaTest extends UjiBasisData
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

    public function testTanpaBerkasTanpaHiasan(): void
    {
        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(1, $xpath->query('//*[@data-pembuka-ku]')->length, 'Prasyarat: kartu kelompok umur tampil.');
        $this->assertSame(0, $xpath->query('//*[@data-hiasan]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="pembuka"]//img')->length);
        $this->assertStringNotContainsString('pola-titik', (string) $xpath->query('//body')->item(0)?->getAttribute('class'));

        $xpath = $this->xpathDari($this->get('admin/masuk'));
        $this->assertSame(0, $xpath->query('//*[@data-hiasan]')->length);
    }

    public function testDenganBerkasHiasanTerpasang(): void
    {
        foreach (['beranda/latar-pita.webp', 'beranda/latar-lintasan.webp', 'beranda/kartu-ku.webp', 'beranda/tokoh-lompat.webp', 'pola/pita-sudut.webp', 'pola/garis-lintasan.webp', 'pola/titik-halus.webp', 'pola/bintik-stadion.webp'] as $berkas) {
            $this->taruhGambarUji($berkas);
        }

        $xpath = $this->xpathDari($this->get('/'));

        // Latar pita di belakang pembuka tanpa foto, hanya mulai md; pita sudut G9-12 hanya di ponsel.
        $pita = $xpath->query('//section[@id="pembuka"]/img[@data-hiasan="latar-pita"][@aria-hidden="true"]')->item(0);
        $this->assertNotNull($pita);
        $this->assertStringContainsString('hidden', $pita->getAttribute('class'));
        $this->assertStringContainsString('md:block', $pita->getAttribute('class'));
        $this->assertStringContainsString('md:hidden', (string) $xpath->query('//img[@data-hiasan="pita-sudut"]')->item(0)?->getAttribute('class'));

        // Tokoh yang berkasnya ada dipakai walaupun bukan giliran harinya.
        $this->assertStringContainsString('beranda/tokoh-lompat.webp', (string) $xpath->query('//section[@id="pembuka"]//img[contains(@src, "tokoh-")]')->item(0)?->getAttribute('src'));

        $this->assertStringContainsString('md:block', (string) $xpath->query('//*[@data-pembuka-ku]/img[@data-hiasan="latar-lintasan"]')->item(0)?->getAttribute('class'));
        $this->assertSame(1, $xpath->query('//article[@data-lomba-ku]//img[@data-hiasan="kartu-ku"]')->length);
        $this->assertSame(1, $xpath->query('//article[@data-lomba-ku]//h3[text()="KU Karangan"]')->length, 'Judul kartu tetap ada.');
        $this->assertGreaterThanOrEqual(1, $xpath->query('//div[@data-hiasan="garis-lintasan"][@aria-hidden="true"]/img[contains(@src, "pola/garis-lintasan.webp")]')->length);
        $this->assertStringContainsString('pola-titik', (string) $xpath->query('//body')->item(0)?->getAttribute('class'));

        // Halaman masuk: bokeh stadion mulai lg di belakang kartu.
        $xpath = $this->xpathDari($this->get('admin/masuk'));
        $this->assertStringContainsString('lg:block', (string) $xpath->query('//main/img[@data-hiasan="bintik-stadion"]')->item(0)?->getAttribute('class'));
    }

    public function testCssPolaTitikHanyaLewatKelasBody(): void
    {
        $css = (string) file_get_contents(FCPATH . 'aset/css/tw.css');

        $this->assertMatchesRegularExpression('#\.pola-titik\s+\.latar-merek\s*\{[^}]*url\(["\']?\.\./gambar/pola/titik-halus\.webp#', $css);
        $this->assertSame(1, substr_count($css, 'titik-halus.webp'), 'Berkas pola hanya dirujuk di bawah .pola-titik, jadi tanpa berkas tidak ada permintaan 404.');
    }
}
