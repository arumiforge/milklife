<?php

use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman Data Acara (`/admin/pengaturan/acara`, F2-02) dan nama acara di
 * tata letak serta halaman depan yang mengikuti pengaturan.
 *
 * @internal
 */
final class PengaturanAcaraTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/acara';

    /**
     * Isian benar seperti flyer Seri 2.
     *
     * @return array<string, string>
     */
    private static function isianBenar(): array
    {
        return [
            'nama'            => 'MilkLife Athletics Challenge',
            'seri'            => '2',
            'tahun'           => '2026',
            'tempat'          => 'Supersoccer Arena, Kudus',
            'tanggal_mulai'   => '2026-12-09',
            'tanggal_selesai' => '2026-12-12',
        ];
    }

    // ---------- Halaman ----------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
    }

    public function testFormulirKosongSebelumDiisi(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Data Acara', trim($xpath->query('//header//h1')->item(0)?->textContent ?? ''));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Data Acara")]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="galat-isian"]')->length);

        foreach (array_keys(self::isianBenar()) as $kotak) {
            $masukan = $xpath->query('//form[@method="post"]//input[@name="' . $kotak . '"]')->item(0);
            $this->assertNotNull($masukan, "Kotak {$kotak} tidak ada.");
            $this->assertSame('', $masukan->getAttribute('value'), "Kotak {$kotak} seharusnya kosong.");
            $this->assertSame(1, $xpath->query('//label[@for="' . $kotak . '"]')->length, "Label kotak {$kotak} tidak ada.");
        }

        // Tanggal memakai kotak tanggal peramban.
        $this->assertSame('date', $xpath->query('//input[@name="tanggal_mulai"]')->item(0)?->getAttribute('type'));
        $this->assertSame('date', $xpath->query('//input[@name="tanggal_selesai"]')->item(0)?->getAttribute('type'));
    }

    public function testFormulirBerisiNilaiTersimpan(): void
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianBenar());

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));

        foreach (self::isianBenar() as $kotak => $nilai) {
            $this->assertSame($nilai, $xpath->query('//input[@name="' . $kotak . '"]')->item(0)?->getAttribute('value'), $kotak);
        }
    }

    public function testSimpanBerhasil(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'nama'   => '  MilkLife   Athletics Challenge ',
            'tempat' => 'Supersoccer Arena,  Kudus',
        ] + self::isianBenar());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Data acara tersimpan.');

        // Spasi berlebih dirapikan; seri dan tahun disimpan sebagai bilangan bulat.
        $this->assertSame('MilkLife Athletics Challenge', setting('Acara.nama'));
        $this->assertSame(2, setting('Acara.seri'));
        $this->assertSame(2026, setting('Acara.tahun'));
        $this->assertSame('Supersoccer Arena, Kudus', setting('Acara.tempat'));
        $this->assertSame('2026-12-09', setting('Acara.tanggal_mulai'));
        $this->assertSame('2026-12-12', setting('Acara.tanggal_selesai'));
    }

    public function testAcaraSehariBoleh(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['tanggal_selesai' => '2026-12-09'] + self::isianBenar());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Data acara tersimpan.');
        $this->assertSame('2026-12-09', setting('Acara.tanggal_selesai'));
    }

    public function testIsianKosongDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, array_fill_keys(array_keys(self::isianBenar()), ''));

        $this->assertIsianSalahDitampilkanUlang($hasil, array_keys(self::isianBenar()));
        $hasil->assertSee('Ada 6 isian yang perlu diperbaiki:');
        $hasil->assertSee('Nama acara belum diisi. Tulis nama acara, misalnya MilkLife Athletics Challenge.');
        $hasil->assertSee('Seri belum diisi.');
        $hasil->assertSee('Tahun belum diisi.');
        $hasil->assertSee('Tempat belum diisi.');
        $hasil->assertSee('Tanggal mulai belum diisi.');
        $hasil->assertSee('Tanggal selesai belum diisi.');

        $this->assertSame('', setting('Acara.nama'));
        $this->assertNull(setting('Acara.seri'));
    }

    public function testIsianSalahDitampilkanUlangDenganIsianYangDiketik(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'nama'            => 'Lomba <Karangan>',
            'seri'            => 'Seri 2',
            'tahun'           => '26',
            'tanggal_mulai'   => '2026-02-30',
        ] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['seri', 'tahun', 'tanggal_mulai']);
        $hasil->assertSee('Seri harus berupa angka, misalnya 2.');
        $hasil->assertSee('Tahun belum benar. Tulis tahun acara dengan empat angka, misalnya 2026.');
        $hasil->assertSee('Tanggal mulai belum benar. Pilih tanggal dari kalender.');

        // Isian yang tadi diketik tetap ada (dan di-escape), tidak ada yang tersimpan.
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Lomba <Karangan>', $xpath->query('//input[@name="nama"]')->item(0)?->getAttribute('value'));
        $this->assertSame('Seri 2', $xpath->query('//input[@name="seri"]')->item(0)?->getAttribute('value'));
        $hasil->assertDontSee('Lomba <Karangan>');
        $this->assertSame('', setting('Acara.nama'));
    }

    public function testBatasPanjangDanAngka(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'nama'   => str_repeat('a', 101),
            'tempat' => str_repeat('b', 151),
            'seri'   => '0',
            'tahun'  => '2101',
        ] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama', 'tempat', 'seri', 'tahun']);
        $hasil->assertSee('Nama acara terlalu panjang.');
        $hasil->assertSee('Tempat terlalu panjang.');
    }

    public function testTanggalSelesaiSebelumMulaiDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['tanggal_selesai' => '2026-12-08'] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['tanggal_selesai']);
        $hasil->assertSee('Tanggal selesai tidak boleh sebelum tanggal mulai (9 Desember 2026).');
        $this->assertNull(setting('Acara.tanggal_selesai'));
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, self::isianBenar());
        $this->assertSame('', setting('Acara.nama'));
    }

    // ---------- Tata letak mengikuti pengaturan ----------

    public function testNamaAcaraLangsungTerlihatDiBerandaDanKepalaAdmin(): void
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, [
            'nama'            => 'Lomba Lari Karangan',
            'seri'            => '3',
            'tahun'           => '2027',
            'tempat'          => 'Stadion Karangan, Pati',
            'tanggal_mulai'   => '2027-01-30',
            'tanggal_selesai' => '2027-02-01',
        ]);

        // Halaman depan: judul tab, merek di kepala, pembuka, dan kaki.
        $beranda = $this->get('/');
        $beranda->assertOK();
        $xpath = $this->xpathDari($beranda);

        $this->assertSame('Lomba Lari Karangan Seri 3 – 2027', $this->teks($xpath, '//title'));
        $this->assertSame('Lomba Lari Karangan', $this->teks($xpath, '//header//a[contains(@class, "navbar-brand")]'));
        $this->assertSame('Lomba', $this->teks($xpath, '//header//*[contains(@class, "merek-utama")]'));
        $this->assertSame('Lari Karangan', $this->teks($xpath, '//header//*[contains(@class, "merek-sambungan")]'));
        $this->assertSame('Lomba Lari Karangan', $this->teks($xpath, '//h1[@id="judul-acara"]'));
        $this->assertSame('Seri 3 – 2027', $this->teks($xpath, '//*[contains(@class, "pita-miring")]'));
        $this->assertSame('30 Januari – 1 Februari 2027 Stadion Karangan, Pati', $this->teks($xpath, '//*[contains(@class, "pembuka-waktu")]'));
        $this->assertSame('Lomba Lari Karangan 2027', $this->teks($xpath, '//footer'));
        $beranda->assertDontSee('MilkLife');

        // Panel admin: merek di menu samping.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));
        $this->assertSame('Lomba Lari Karangan Panel admin', $this->teks($xpath, '//aside//a[contains(@class, "menu-samping-merek")]'));

        // Halaman masuk (tata letak polos).
        $xpath = $this->xpathDari($this->withSession([])->get('admin/masuk'));
        $this->assertSame('Lomba Lari Karangan', $this->teks($xpath, '//*[contains(@class, "polos-merek")]'));
    }

    public function testTeksPenggantiSebelumDataAcaraDiisi(): void
    {
        $beranda = $this->get('/');
        $beranda->assertOK();
        $xpath = $this->xpathDari($beranda);

        $this->assertSame('Pendaftaran Lomba', $this->teks($xpath, '//title'));
        $this->assertSame('Pendaftaran Lomba', $this->teks($xpath, '//header//a[contains(@class, "navbar-brand")]'));
        $this->assertSame('Pendaftaran Lomba', $this->teks($xpath, '//h1[@id="judul-acara"]'));
        $this->assertSame(0, $xpath->query('//*[contains(@class, "pita-miring")]')->length, 'Seri dan tahun yang kosong tidak ditampilkan.');
        $this->assertSame('Tanggal dan tempat acara segera diumumkan', $this->teks($xpath, '//*[contains(@class, "pembuka-waktu")]'));
        $this->assertSame('Pendaftaran Lomba', $this->teks($xpath, '//footer'));

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));
        $this->assertSame('Pendaftaran Lomba Panel admin', $this->teks($xpath, '//aside//a[contains(@class, "menu-samping-merek")]'));

        $xpath = $this->xpathDari($this->withSession([])->get('admin/masuk'));
        $this->assertSame('Pendaftaran Lomba', $this->teks($xpath, '//*[contains(@class, "polos-merek")]'));
    }

    public function testDataAcaraSebagianDanNamaSatuKata(): void
    {
        setting('Acara.nama', 'Atletik');
        setting('Acara.seri', 4);
        setting('Acara.tempat', 'Stadion <Karangan>');

        $beranda = $this->get('/');
        $xpath   = $this->xpathDari($beranda);

        // Nama satu kata tanpa sambungan; yang kosong tidak ditampilkan; teks di-escape.
        $this->assertSame('Atletik', $this->teks($xpath, '//header//a[contains(@class, "navbar-brand")]'));
        $this->assertSame(0, $xpath->query('//header//*[contains(@class, "merek-sambungan")]')->length);
        $this->assertSame('Seri 4', $this->teks($xpath, '//*[contains(@class, "pita-miring")]'));
        $this->assertSame('Stadion <Karangan>', $this->teks($xpath, '//*[contains(@class, "pembuka-waktu")]'));
        $this->assertSame(0, $xpath->query('//*[contains(@class, "pembuka-waktu")]//i[contains(@class, "bi-calendar-event")]')->length);
        $this->assertSame('Atletik', $this->teks($xpath, '//footer'));
        $beranda->assertDontSee('Stadion <Karangan>');
    }

    // ---------- Bantuan ----------

    private function teks(DOMXPath $xpath, string $kueri): string
    {
        $simpul = $xpath->query($kueri)->item(0);
        $this->assertNotNull($simpul, "Tidak ditemukan: {$kueri}");

        return trim((string) preg_replace('/\s+/u', ' ', $simpul->textContent));
    }
}
