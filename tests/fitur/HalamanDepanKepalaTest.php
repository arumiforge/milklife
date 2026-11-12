<?php

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Kepala halaman depan (F3-01): gambar dan logo, judul dan teks, data acara,
 * keadaan pendaftaran, tombol nonaktif "Segera", footer, susunan bagian, dan
 * rute `/unduhan`. Nama dan isi karangan.
 *
 * @internal
 */
final class HalamanDepanKepalaTest extends UjiBasisData
{
    use KirimFormulir;
    use StreamFilterTrait;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-11-11 10:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    // ---------- Gambar dan logo ----------

    public function testTanpaGambarTidakAdaKotakKosong(): void
    {
        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(0, $xpath->query('//section[@id="pembuka"]//img')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class, "tanpa-gambar")]')->length, 'Kotak "tanpa gambar" hanya untuk panel admin (K41).');
        $this->assertSame(0, $xpath->query('//*[contains(@class, "pembuka-gambar") or contains(@class, "pembuka-logo")]')->length);
        $hasil->assertDontSee('Belum ada gambar');
    }

    public function testGambarKepalaDanTigaLogo(): void
    {
        setting('Acara.nama', 'Lomba Lari Karangan');

        $jalur = [];

        foreach (['gambar_kepala', 'logo_persegi', 'logo_persegi_panjang', 'logo_djarum'] as $kunci) {
            $jalur[$kunci] = $this->simpanGambarUji();
            setting('HalamanDepan.' . $kunci, $jalur[$kunci]);
        }

        $xpath = $this->xpathDari($this->get('/'));

        // Gambar kepala menjadi latar di belakang judul (K44), di dalam bagian pembuka.
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"][contains(@class, "pembuka-dengan-gambar")]')->length);
        $gambar = $xpath->query('//section[@id="pembuka"]//*[contains(@class, "pembuka-gambar")]/img')->item(0);
        $this->assertNotNull($gambar);
        $this->assertSame(base_url($jalur['gambar_kepala']), $gambar->getAttribute('src'));
        $this->assertSame('Gambar kepala Lomba Lari Karangan', $gambar->getAttribute('alt'));
        $this->assertSame('1', $gambar->getAttribute('width'));
        $this->assertSame('1', $gambar->getAttribute('height'));

        $logo = [];

        foreach ($xpath->query('//ul[contains(@class, "pembuka-logo")]/li/img') as $img) {
            $logo[] = [$img->getAttribute('src'), $img->getAttribute('alt'), $img->getAttribute('width')];
        }

        $this->assertSame([
            [base_url($jalur['logo_persegi']), 'Logo Lomba Lari Karangan', '1'],
            [base_url($jalur['logo_persegi_panjang']), 'Logo Lomba Lari Karangan', '1'],
            [base_url($jalur['logo_djarum']), 'Logo Bakti Olahraga Djarum Foundation', '1'],
        ], $logo);
    }

    public function testGambarYangBerkasnyaHilangDisembunyikan(): void
    {
        $ada = $this->simpanGambarUji();
        setting('HalamanDepan.logo_djarum', $ada);
        setting('HalamanDepan.gambar_kepala', 'unggahan/halaman-depan/' . str_repeat('c', 32) . '.png');
        setting('HalamanDepan.logo_persegi', 'unggahan/halaman-depan/' . str_repeat('d', 32) . '.webp');

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(0, $xpath->query('//*[contains(@class, "pembuka-gambar")]')->length);
        $this->assertSame([base_url($ada)], array_map(
            static fn (DOMElement $img): string => $img->getAttribute('src'),
            iterator_to_array($xpath->query('//section[@id="pembuka"]//img')),
        ));
    }

    // ---------- Judul, teks, dan data acara ----------

    public function testJudulKosongMemakaiNamaAcara(): void
    {
        $this->isiDataAcara();

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(1, $xpath->query('//h1')->length, 'Tepat satu h1.');
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($xpath, '//h1[@id="judul-acara"]'));
        $this->assertSame(0, $xpath->query('//*[contains(@class, "pembuka-nama")]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class, "pembuka-teks")]')->length, 'Teks kosong tidak ditampilkan.');
        $this->assertSame('Seri 2 – 2026', $this->teks($xpath, '//*[contains(@class, "pita-miring")]'));
        $this->assertSame('9–12 Desember 2026 Supersoccer Arena, Kudus', $this->teks($xpath, '//*[contains(@class, "pembuka-waktu")]'));
    }

    public function testJudulDanTeksDariPengaturanDiEscape(): void
    {
        $this->isiDataAcara();
        setting('HalamanDepan.judul', 'Ayo <b>ikut</b> lomba!');
        setting('HalamanDepan.teks', "Paragraf <script>alert(1)</script> satu\nbaris kedua\n\nParagraf dua");

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame('Ayo <b>ikut</b> lomba!', $this->teks($xpath, '//h1[@id="judul-acara"]'));
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($xpath, '//*[contains(@class, "pembuka-nama")]'));

        $paragraf = $xpath->query('//*[contains(@class, "pembuka-teks")]/p');
        $this->assertSame(2, $paragraf->length);
        $this->assertSame(1, $xpath->query('//*[contains(@class, "pembuka-teks")]/p[1]/br')->length, 'Baris baru di dalam paragraf menjadi <br>.');
        $this->assertSame('Paragraf <script>alert(1)</script> satu baris kedua', trim((string) preg_replace('/\s+/u', ' ', $paragraf->item(0)->textContent)));
        $this->assertSame('Paragraf dua', trim($paragraf->item(1)->textContent));

        $hasil->assertDontSee('<script>alert(1)</script>');
        $hasil->assertDontSee('<b>ikut</b>');
    }

    // ---------- Keadaan pendaftaran ----------

    public function testKeadaanDisiapkanTanpaTeksStatisLama(): void
    {
        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertKeadaan($xpath, 'disiapkan', 'Sedang disiapkan', 'Pendaftaran sedang disiapkan.', 'bi-hourglass-split');
        $hasil->assertDontSee('Panitia sedang menyiapkan formulir pendaftaran untuk sekolah.');
        $this->assertSame(0, $xpath->query('//*[@id="judul-status"]')->length);
    }

    public function testEmpatKeadaanPendaftaran(): void
    {
        $this->lengkapiPengaturan();

        $this->assertKeadaan($this->xpathDari($this->get('/')), 'terbuka', 'Terbuka', 'Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.', 'bi-door-open-fill');

        Time::setTestNow('2026-09-30 12:00:00', 'Asia/Jakarta');
        $this->assertKeadaan($this->xpathDari($this->get('/')), 'belum_dibuka', 'Belum dibuka', 'Pendaftaran dibuka 1 Oktober 2026.', 'bi-calendar-event');

        Time::setTestNow('2026-12-01 00:00:00', 'Asia/Jakarta');
        $this->assertKeadaan($this->xpathDari($this->get('/')), 'ditutup', 'Ditutup', 'Pendaftaran sudah ditutup pada 30 November 2026 pukul 23.59.', 'bi-door-closed-fill');
    }

    // ---------- Tombol ----------

    public function testTombolNonaktifSegera(): void
    {
        $this->lengkapiPengaturan();

        $xpath  = $this->xpathDari($this->get('/'));
        $tombol = [];

        foreach ($xpath->query('//*[contains(@class, "pembuka-tombol")]/*') as $simpul) {
            $tombol[$this->teksSimpul($simpul)] = $simpul;
        }

        // Tetap nonaktif walau pendaftaran terbuka, sampai halamannya ada (K39).
        // Cek sudah punya halaman (Tahap 5) dan aktif karena pengaturan lengkap.
        $this->assertSame(['Pendaftaran Segera', 'Cek Pendaftaran'], array_keys($tombol));
        $this->assertSame(site_url('cek-pendaftaran'), $tombol['Cek Pendaftaran']->getAttribute('href'));
        unset($tombol['Cek Pendaftaran']);

        foreach ($tombol as $label => $simpul) {
            $this->assertSame('true', $simpul->getAttribute('aria-disabled'), $label);
            $this->assertFalse($simpul->hasAttribute('href'), $label . ' tidak boleh bertautan.');
            $this->assertStringContainsString('disabled', $simpul->getAttribute('class'));
        }

        $this->assertStringContainsString('btn-primary', $tombol['Pendaftaran Segera']->getAttribute('class'));
    }

    // ---------- Footer ----------

    public function testFooterDariPengaturanAtauCadangan(): void
    {
        $this->isiDataAcara();
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame('MilkLife Athletics Challenge 2026', $this->teks($xpath, '//footer'));

        setting('HalamanDepan.footer', "Panitia <i>Karangan</i>\nKudus");
        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('Panitia <i>Karangan</i> Kudus', $this->teks($xpath, '//footer'));
        $this->assertSame(1, $xpath->query('//footer//br')->length);
        $hasil->assertDontSee('<i>Karangan</i>');
    }

    // ---------- Susunan halaman ----------

    public function testBagianKosongTidakTampil(): void
    {
        $xpath = $this->xpathDari($this->get('/'));

        $bagian = array_map(
            static fn (DOMElement $s): string => $s->getAttribute('id'),
            iterator_to_array($xpath->query('//main/section')),
        );

        // Bagian kontak selalu tampil karena tombol "Bagikan ke WA" selalu ada (F3-03).
        $this->assertSame(['pembuka', 'kontak'], $bagian);
        $this->assertSame('judul-acara', $xpath->query('//section[@id="pembuka"]')->item(0)?->getAttribute('aria-labelledby'));
    }

    public function testHalamanTerbukaDenganSemuaDataTerisi(): void
    {
        $this->lengkapiPengaturan();
        $this->db->table('jadwal')->insert(['judul' => 'Sosialisasi daring', 'urutan' => 1, 'tampil' => 1]);
        $this->db->table('tautan')->insert(['jenis' => 'narahubung', 'label' => 'Ibu Karangan', 'nilai' => '6281234567890', 'urutan' => 1]);
        setting('HalamanDepan.nomor_wa_tanya', '6281234567890');

        $hasil = $this->get('/');
        $hasil->assertOK();

        $xpath  = $this->xpathDari($hasil);
        $urutan = array_map(
            static fn (DOMElement $s): string => $s->getAttribute('id'),
            iterator_to_array($xpath->query('//main/section')),
        );

        // Bagian yang sudah diisi tugas lain tampil sesuai urutan K42.
        $this->assertSame('pembuka', $urutan[0]);
        $this->assertSame(
            array_values(array_intersect(['pembuka', 'lomba', 'jadwal', 'unduhan', 'kontak'], $urutan)),
            $urutan,
        );
    }

    public function testPotonganBagianMenerimaDatanya(): void
    {
        // Potongan F3-02 dan F3-03 menerima data kosong tanpa galat dan tanpa isi
        // (komentar penanda tampilan dari bilah debug dibuang).
        $isi = static fn (string $potongan, array $data): string => trim((string) preg_replace('/<!--.*?-->/s', '', view($potongan, $data, ['saveData' => false])));

        $this->assertSame('', $isi('beranda/_lomba', ['kelompokUmur' => []]));
        $this->assertSame('', $isi('beranda/_jadwal', ['jadwal' => []]));
        $this->assertSame('', $isi('beranda/_unduhan', ['unduhan' => []]));
    }

    public function testRuteUnduhanTerdaftar(): void
    {
        $rute = service('routes')->loadRoutes()->getRoutes('GET');

        $this->assertArrayHasKey('unduhan', $rute);
        $this->assertSame('\App\Controllers\UnduhanController::index', $rute['unduhan']);
    }

    // ---------- Bantuan ----------

    private function assertKeadaan(DOMXPath $xpath, string $keadaan, string $label, string $kalimat, string $ikon): void
    {
        $kotak = $xpath->query('//section[@id="pembuka"]//*[@data-keadaan]');
        $this->assertSame(1, $kotak->length);
        $this->assertSame($keadaan, $kotak->item(0)->getAttribute('data-keadaan'));
        $this->assertStringContainsString('keadaan-' . str_replace('_', '-', $keadaan), $kotak->item(0)->getAttribute('class'));
        $this->assertSame($label, $this->teks($xpath, '//*[contains(@class, "keadaan-label")]'));
        $this->assertSame($kalimat, $this->teks($xpath, '//*[contains(@class, "keadaan-kalimat")]'));
        $this->assertSame(1, $xpath->query('//*[@data-keadaan]//i[contains(@class, "' . $ikon . '")]')->length, 'Ikon ' . $ikon);
    }

    private function isiDataAcara(): void
    {
        setting('Acara.nama', 'MilkLife Athletics Challenge');
        setting('Acara.seri', 2);
        setting('Acara.tahun', 2026);
        setting('Acara.tempat', 'Supersoccer Arena, Kudus');
        setting('Acara.tanggal_mulai', '2026-12-09');
        setting('Acara.tanggal_selesai', '2026-12-12');
    }

    /**
     * Data awal ditambah semua isian wajib (data karangan), sehingga
     * pengaturan lengkap dan pendaftaran 1 Oktober–30 November 2026.
     */
    private function lengkapiPengaturan(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));

        $this->db->query('UPDATE kelompok_umur SET biaya_individu = 50000');
        $this->db->query("UPDATE nomor_lomba SET batas_anggota = jumlah_inti + 2, biaya_beregu = 100000 WHERE jenis = 'beregu'");
        $this->db->query("UPDATE nomor_lomba SET jumlah_putra = jumlah_inti DIV 2, jumlah_putri = jumlah_inti DIV 2 WHERE kategori = 'mix'");

        foreach ($this->db->table('kelompok_umur')->select('id')->get()->getResultArray() as $ku) {
            $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku['id'], 'jenjang' => 'A']);
        }

        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '1234567890');
        setting('Pembayaran.pemilik_rekening', 'Panitia Karangan');
        setting('Pembayaran.nomor_wa', '6281234567890');
    }

    private function simpanGambarUji(): string
    {
        $jalur = 'unggahan/halaman-depan/' . bin2hex(random_bytes(16)) . '.png';
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, self::isiPng());

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
