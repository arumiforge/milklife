<?php

use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Halaman publik `/unduhan` (F3-03, K40): judul tab, satu `<h1>`, daftar
 * berkas yang tampil dan ada di disk, pesan ramah bila kosong, dan menu
 * Unduhan aktif. Judul dan isi karangan.
 *
 * @internal
 */
final class HalamanUnduhanTest extends UjiBasisData
{
    use KirimFormulir;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Acara.nama', 'Lomba Lari Karangan');
    }

    public function testHalamanKosongMenampilkanPesanRamah(): void
    {
        $hasil = $this->get('unduhan');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('Unduhan – Lomba Lari Karangan', $this->teks($xpath, '//title'));
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame('Unduhan', $this->teks($xpath, '//main//h1'));

        $kosong = $xpath->query('//main//*[contains(@class, "unduhan-kosong")]')->item(0);
        $this->assertNotNull($kosong);
        $this->assertStringContainsString('Belum ada berkas unduhan', $this->teksSimpul($kosong));
        $this->assertStringContainsString('Silakan kembali lagi nanti.', $this->teksSimpul($kosong));
        $this->assertSame(1, $xpath->query('.//a[@href="' . site_url('/') . '"]', $kosong)->length, 'Ada jalan kembali ke beranda.');
        $this->assertSame(0, $xpath->query('//main//*[contains(@class, "daftar-unduhan")]')->length);
    }

    public function testHalamanBerisiHanyaBerkasYangTampilDanAda(): void
    {
        $flyer  = $this->simpanBerkasUji('png', self::isiPng());
        $juknis = $this->simpanBerkasUji('pdf', self::isiPdf());

        $this->tambahUnduhan(['judul' => 'Flyer Seri 2', 'urutan' => 2, 'berkas' => $flyer]);
        $this->tambahUnduhan(['judul' => 'Juknis', 'urutan' => 1, 'berkas' => $juknis, 'keterangan' => "Baca sebelum mendaftar.\nVersi 2."]);
        $this->tambahUnduhan(['judul' => 'Berkas hilang', 'urutan' => 3, 'berkas' => 'unggahan/unduhan/' . str_repeat('a', 32) . '.pdf']);
        $this->tambahUnduhan(['judul' => 'Berkas tersembunyi', 'urutan' => 4, 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf()), 'tampil' => 0]);

        $hasil = $this->get('unduhan');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('Unduhan – Lomba Lari Karangan', $this->teks($xpath, '//title'));
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(0, $xpath->query('//main//*[contains(@class, "unduhan-kosong")]')->length);

        // Judul berkas menjadi h2 di bawah h1 halaman, berurutan.
        $this->assertSame(['Juknis', 'Flyer Seri 2'], array_map(
            fn (DOMNode $h2): string => $this->teksSimpul($h2),
            iterator_to_array($xpath->query('//main//ul[contains(@class, "daftar-unduhan")]/li//h2')),
        ));
        $hasil->assertDontSee('Berkas hilang');
        $hasil->assertDontSee('Berkas tersembunyi');

        $tombol = [];

        foreach ($xpath->query('//main//a[contains(@class, "unduhan-tombol")]') as $a) {
            $tombol[] = [$a->getAttribute('href'), $a->getAttribute('download')];
        }

        $this->assertSame([[base_url($juknis), 'Juknis.pdf'], [base_url($flyer), 'Flyer Seri 2.png']], $tombol);
        $this->assertSame(1, $xpath->query('//main//*[contains(@class, "unduhan-keterangan")]/br')->length);
    }

    public function testTeksAdminDiEscape(): void
    {
        setting('Acara.nama', 'Lomba <b>Karangan</b>');
        $this->tambahUnduhan(['judul' => 'Juknis <script>alert(1)</script>', 'keterangan' => 'Isi <i>miring</i>', 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf())]);

        $hasil = $this->get('unduhan');
        $xpath = $this->xpathDari($hasil);

        $hasil->assertDontSee('<script>alert(1)</script>');
        $hasil->assertDontSee('<i>miring</i>');
        $hasil->assertDontSee('<b>Karangan</b>');
        $this->assertSame('Unduhan – Lomba <b>Karangan</b>', $this->teks($xpath, '//title'));
        $this->assertSame('Juknis <script>alert(1)</script>', $this->teks($xpath, '//main//ul[contains(@class, "daftar-unduhan")]//h2'));
        $this->assertSame(0, $xpath->query('//main//script | //main//i[not(contains(@class, "bi"))]')->length);
    }

    public function testMenuUnduhanAktifDiHalamanUnduhan(): void
    {
        $menu = $this->menu($this->xpathDari($this->get('unduhan')));

        $this->assertSame(['Tentang', 'Kategori', 'Jadwal', 'Unduhan'], array_keys($menu));
        $this->assertSame(site_url('unduhan'), $menu['Unduhan']->getAttribute('href'));
        $this->assertSame('page', $menu['Unduhan']->getAttribute('aria-current'));
        $this->assertStringContainsString('active', $menu['Unduhan']->getAttribute('class'));
        $this->assertFalse($menu['Tentang']->hasAttribute('aria-current'));

        // Di halaman depan, Unduhan bertautan tetapi tidak aktif.
        $menu = $this->menu($this->xpathDari($this->get('/')));
        $this->assertSame(site_url('unduhan'), $menu['Unduhan']->getAttribute('href'));
        $this->assertFalse($menu['Unduhan']->hasAttribute('aria-current'));
    }

    // ---------- Bantuan ----------

    /**
     * @return array<string, DOMElement> label => tautan menu utama
     */
    private function menu(DOMXPath $xpath): array
    {
        $butir = [];

        foreach ($xpath->query('//nav[@aria-label="Menu utama"]//*[contains(@class, "nav-link")]') as $tautan) {
            $butir[$this->teksSimpul($tautan)] = $tautan;
        }

        return $butir;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function tambahUnduhan(array $data): void
    {
        $this->db->table('unduhan')->insert($data + ['urutan' => 1, 'tampil' => 1]);
    }

    /**
     * Berkas unduhan karangan di folder unggahan sementara; kembalikan jalurnya.
     */
    private function simpanBerkasUji(string $ekstensi, string $isi): string
    {
        $jalur = 'unggahan/unduhan/' . bin2hex(random_bytes(16)) . '.' . $ekstensi;
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, $isi);

        return $jalur;
    }

    private function teks(DOMXPath $xpath, string $kueri): string
    {
        $simpul = $xpath->query($kueri)->item(0);
        $this->assertNotNull($simpul, "Tidak ditemukan: {$kueri}");

        return $this->teksSimpul($simpul);
    }

    private function teksSimpul(DOMNode $simpul): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $simpul->textContent));
    }
}
