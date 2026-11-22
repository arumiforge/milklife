<?php

use App\Libraries\PetaLokasi;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Bagian Lokasi halaman depan (F10-02): peta Google Maps bawaan, iframe
 * baru disisipkan JS (tidak ada di HTML), tautan "Buka di Google Maps",
 * bagian hilang bila alamat embed kosong atau tidak sah, dan letaknya tepat
 * di atas Kontak. Nama tempat karangan.
 *
 * @internal
 */
final class HalamanDepanLokasiTest extends UjiBasisData
{
    use KirimFormulir;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Acara.nama', 'Lomba Lari Karangan');
        setting('Acara.tempat', 'Stadion Karangan, Kota Contoh');
    }

    public function testPetaBawaanTampilTanpaIframeDiHtml(): void
    {
        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $bagian = $xpath->query('//main/section[@id="lokasi"]')->item(0);
        $this->assertNotNull($bagian);
        $this->assertSame('judul-lokasi', $bagian->getAttribute('aria-labelledby'));
        $this->assertSame('Lokasi', trim($xpath->query('//h2[@id="judul-lokasi"]')->item(0)?->textContent ?? ''));
        $this->assertSame('Stadion Karangan, Kota Contoh', trim($xpath->query('//*[@data-lokasi-alamat]')->item(0)?->textContent ?? ''));

        $kotak = $xpath->query('//section[@id="lokasi"]//*[@data-peta-lokasi]')->item(0);
        $this->assertNotNull($kotak);
        $this->assertSame(config('HalamanDepan')->peta_embed, $kotak->getAttribute('data-peta-src'));
        $this->assertSame('Peta Stadion Karangan, Kota Contoh (Google Maps)', $kotak->getAttribute('data-peta-judul'));
        $this->assertStringContainsString('aspect-[4/3]', $kotak->getAttribute('class'));
        $this->assertStringContainsString('lg:aspect-video', $kotak->getAttribute('class'));

        // Tidak ada iframe (dan tidak ada permintaan ke google.com) sebelum JS menyisipkannya.
        $this->assertSame(0, $xpath->query('//iframe')->length);
        $this->assertSame(1, $xpath->query('//script[contains(@src, "/aset/js/peta-lokasi")][@defer]')->length);

        // Tautan buka peta: di kotak pengganti dan di bawah peta, tab baru.
        $tautan = $xpath->query('//section[@id="lokasi"]//a[@href="https://maps.google.com/?q=Supersoccer+Arena+Kudus"]');
        $this->assertSame(2, $tautan->length);
        foreach ($tautan as $a) {
            $this->assertSame('_blank', $a->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $a->getAttribute('rel'));
            $this->assertStringContainsString('Buka di Google Maps', $a->textContent);
        }
    }

    public function testLokasiTepatDiAtasKontak(): void
    {
        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame('kontak', $xpath->query('//main/section[@id="lokasi"]/following-sibling::section[1]')->item(0)?->getAttribute('id'));
    }

    public function testEmbedKosongMenyembunyikanBagian(): void
    {
        setting('HalamanDepan.peta_embed', '');

        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(0, $xpath->query('//section[@id="lokasi"]')->length);
        $this->assertSame(0, $xpath->query('//script[contains(@src, "peta-lokasi")]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="kontak"]')->length);
    }

    public function testEmbedTidakSahTidakDitampilkan(): void
    {
        // Misalnya diubah langsung di basis data: tetap tidak keluar ke HTML.
        setting('HalamanDepan.peta_embed', 'https://jahat.contoh.test/maps/embed?pb=1');

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(0, $xpath->query('//section[@id="lokasi"]')->length);
    }

    public function testTautanKosongTanpaTombolDanTempatKosong(): void
    {
        setting('HalamanDepan.peta_tautan', '');
        setting('Acara.tempat', '');

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(1, $xpath->query('//section[@id="lokasi"]//*[@data-peta-lokasi]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="lokasi"]//a')->length);
        $this->assertSame(0, $xpath->query('//*[@data-lokasi-alamat]')->length);
        $this->assertSame('Peta lokasi lomba (Google Maps)', $xpath->query('//*[@data-peta-lokasi]')->item(0)?->getAttribute('data-peta-judul'));
    }

    public function testAlamatEmbedDiEscape(): void
    {
        $alamat = 'https://www.google.com/maps/embed?pb=!1m2&hl=id';
        setting('HalamanDepan.peta_embed', $alamat);

        $hasil = $this->get('/');
        $hasil->assertSee('data-peta-src="https://www.google.com/maps/embed?pb=!1m2&amp;hl=id"', null, false);
        $this->assertSame($alamat, $this->xpathDari($hasil)->query('//*[@data-peta-lokasi]')->item(0)?->getAttribute('data-peta-src'));
    }

    public function testPemeriksaanAlamat(): void
    {
        $this->assertTrue(PetaLokasi::embedSah(config('HalamanDepan')->peta_embed));
        $this->assertTrue(PetaLokasi::tautanSah(config('HalamanDepan')->peta_tautan));
        $this->assertFalse(PetaLokasi::embedSah('https://www.google.com/maps/embed?pb=1 2'));
        $this->assertFalse(PetaLokasi::embedSah('https://www.google.com/maps/embed?' . str_repeat('a', PetaLokasi::BATAS)));
        $this->assertFalse(PetaLokasi::tautanSah('javascript:alert(1)'));
        $this->assertSame('https://www.google.com/maps/embed?pb=1&hl=id', PetaLokasi::rapikanEmbed("  <iframe width='600' src='https://www.google.com/maps/embed?pb=1&amp;hl=id'></iframe>  "));
        $this->assertSame('https://www.google.com/maps/embed?pb=1', PetaLokasi::rapikanEmbed(' https://www.google.com/maps/embed?pb=1 '));
    }
}
