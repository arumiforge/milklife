<?php

use CodeIgniter\Exceptions\PageNotFoundException;
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

        $kosong = $xpath->query('//main//*[@data-unduhan-kosong]')->item(0);
        $this->assertNotNull($kosong);
        $this->assertStringContainsString('Belum ada berkas unduhan', $this->teksSimpul($kosong));
        $this->assertStringContainsString('jadi buka lagi halaman ini nanti.', $this->teksSimpul($kosong));
        $this->assertSame(1, $xpath->query('.//a[@href="' . site_url('/') . '"]', $kosong)->length, 'Ada jalan kembali ke beranda.');
        $this->assertSame(0, $xpath->query('//main//*[@data-daftar-unduhan]')->length);
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
        $this->assertSame(0, $xpath->query('//main//*[@data-unduhan-kosong]')->length);

        // Judul berkas menjadi h2 di bawah h1 halaman, berurutan.
        $this->assertSame(['Juknis', 'Flyer Seri 2'], array_map(
            fn (DOMNode $h2): string => $this->teksSimpul($h2),
            iterator_to_array($xpath->query('//main//ul[@data-daftar-unduhan]/li//h2')),
        ));
        $hasil->assertDontSee('Berkas hilang');
        $hasil->assertDontSee('Berkas tersembunyi');

        $tombol = [];

        foreach ($xpath->query('//main//a[@data-unduhan-tombol]') as $a) {
            $tombol[] = [$a->getAttribute('href'), $a->getAttribute('download')];
        }

        $this->assertSame([[base_url($juknis), 'Juknis.pdf'], [base_url($flyer), 'Flyer Seri 2.png']], $tombol);
        $this->assertSame(1, $xpath->query('//main//*[@data-unduhan-keterangan]/br')->length);
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
        $this->assertSame('Juknis <script>alert(1)</script>', $this->teks($xpath, '//main//ul[@data-daftar-unduhan]//h2'));
        $this->assertSame(0, $xpath->query('//main//script | //main//i')->length);
    }

    public function testJudulEditorialInggrisDenganH1Indonesia(): void
    {
        $xpath = $this->xpathDari($this->get('unduhan'));

        $inggris = $xpath->query('//main//*[@data-judul-inggris]')->item(0);
        $this->assertNotNull($inggris);
        $this->assertSame('Downloads', $this->teksSimpul($inggris));
        $this->assertSame('en', $inggris->getAttribute('lang'));
        $this->assertSame('true', $inggris->getAttribute('aria-hidden'), 'Judul Inggris hanya hiasan; h1 tetap Indonesia.');
        // Halaman publik tanpa alis di atas judul (K164).
        $this->assertSame(0, $xpath->query('//main//*[@data-judul-alis or contains(concat(" ", @class, " "), " alis ")]')->length);
    }

    public function testTombolLihatHanyaUntukPdfDanGambar(): void
    {
        $juknis = $this->simpanBerkasUji('pdf', self::isiPdf());
        $flyer  = $this->simpanBerkasUji('png', self::isiPng());
        $formulir = $this->simpanBerkasUji('docx', 'PK karangan');

        $this->tambahUnduhan(['judul' => 'Juknis', 'urutan' => 1, 'berkas' => $juknis]);
        $this->tambahUnduhan(['judul' => 'Flyer', 'urutan' => 2, 'berkas' => $flyer]);
        $this->tambahUnduhan(['judul' => 'Formulir', 'urutan' => 3, 'berkas' => $formulir]);

        $hasil = $this->get('unduhan');
        $xpath = $this->xpathDari($hasil);
        $butir = $xpath->query('//main//li[@data-unduhan-butir]');
        $this->assertSame(3, $butir->length);

        $lihat = [];

        foreach ($butir as $li) {
            $a       = $xpath->query('.//a[@data-unduhan-lihat]', $li)->item(0);
            $lihat[] = $a === null ? null : [$a->getAttribute('href'), $a->getAttribute('data-pratinjau'), $a->getAttribute('target'), $this->teksSimpul($a)];
            $this->assertSame(1, $xpath->query('.//a[@data-unduhan-tombol]', $li)->length, 'Semua berkas tetap bisa diunduh.');
        }

        $id = array_map(static fn (DOMElement $li): int => (int) $li->getAttribute('data-id'), iterator_to_array($butir));
        $this->assertSame([
            [site_url('unduhan/' . $id[0] . '/lihat'), 'pdf', '_blank', 'Lihat Juknis'],
            [site_url('unduhan/' . $id[1] . '/lihat'), 'gambar', '_blank', 'Lihat Flyer'],
            null,
        ], $lihat);

        // Jenis dan ukuran berkas tidak tampil (F10-09).
        $this->assertSame(0, $xpath->query('//main//*[@data-unduhan-rincian]')->length);
        $this->assertSame(1, $xpath->query('//main//*[@data-pratinjau-modal]')->length);
        $this->assertSame(1, $xpath->query('//script[contains(@src, "js/unduhan-pratinjau")]')->length);
    }

    public function testTanpaPdfAtauGambarTidakAdaModalPratinjau(): void
    {
        $this->tambahUnduhan(['judul' => 'Formulir', 'berkas' => $this->simpanBerkasUji('xlsx', 'PK karangan')]);

        $xpath = $this->xpathDari($this->get('unduhan'));

        $this->assertSame(0, $xpath->query('//a[@data-unduhan-lihat]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-pratinjau-modal]')->length);
        $this->assertSame(0, $xpath->query('//script[contains(@src, "js/unduhan-pratinjau")]')->length);
    }

    public function testPratinjauPdfDanGambarInline(): void
    {
        $this->tambahUnduhan(['judul' => 'Juknis: Seri/2', 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf())]);
        $idPdf = (int) $this->db->insertID();
        $this->tambahUnduhan(['judul' => 'Flyer', 'berkas' => $this->simpanBerkasUji('png', self::isiPng())]);
        $idPng = (int) $this->db->insertID();

        $hasil = $this->get('unduhan/' . $idPdf . '/lihat');
        $hasil->assertOK();
        $this->assertStringStartsWith('application/pdf', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertSame('inline; filename="Juknis- Seri-2.pdf"; filename*=UTF-8\'\'Juknis-%20Seri-2.pdf', $hasil->response()->getHeaderLine('Content-Disposition'));
        $this->assertSame(self::isiPdf(), $hasil->response()->getBody());

        $hasil = $this->get('unduhan/' . $idPng . '/lihat');
        $hasil->assertOK();
        $this->assertStringStartsWith('image/png', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('inline;', $hasil->response()->getHeaderLine('Content-Disposition'));
    }

    public function testPratinjauDitolakUntukBerkasLainTersembunyiAtauHilang(): void
    {
        $this->tambahUnduhan(['judul' => 'Formulir', 'berkas' => $this->simpanBerkasUji('docx', 'PK karangan')]);
        $idDocx = (int) $this->db->insertID();
        $this->tambahUnduhan(['judul' => 'Tersembunyi', 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf()), 'tampil' => 0]);
        $idSembunyi = (int) $this->db->insertID();
        $this->tambahUnduhan(['judul' => 'Hilang', 'berkas' => 'unggahan/unduhan/' . str_repeat('c', 32) . '.pdf']);
        $idHilang = (int) $this->db->insertID();

        foreach ([$idDocx, $idSembunyi, $idHilang, 999999] as $id) {
            try {
                $this->get('unduhan/' . $id . '/lihat');
                $this->fail('Seharusnya 404: ' . $id);
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testMenuUnduhanAktifDiHalamanUnduhan(): void
    {
        $menu = $this->menu($this->xpathDari($this->get('unduhan')));

        // Unduhan di bilah menu atas (F10-07, K154), bersama Bantuan dan Masuk panitia (F10-08);
        // Galeri dan Tanya Panitia mati di data uji.
        $this->assertSame(['Unduhan', 'Bantuan', 'Masuk panitia'], array_keys($menu));
        $this->assertSame(site_url('unduhan'), $menu['Unduhan']->getAttribute('href'));
        $this->assertSame('page', $menu['Unduhan']->getAttribute('aria-current'));
        $this->assertFalse($menu['Bantuan']->hasAttribute('aria-current'));

        // Di halaman depan, Unduhan bertautan tetapi tidak aktif.
        $menu = $this->menu($this->xpathDari($this->get('/')));
        $this->assertSame(site_url('unduhan'), $menu['Unduhan']->getAttribute('href'));
        $this->assertFalse($menu['Unduhan']->hasAttribute('aria-current'));
    }

    // ---------- Bantuan ----------

    /**
     * @return array<string, DOMElement> label => tautan bilah menu atas
     */
    private function menu(DOMXPath $xpath): array
    {
        $butir = [];

        foreach ($xpath->query('//nav[@aria-label="Menu tambahan"]//ul/li/a') as $tautan) {
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
