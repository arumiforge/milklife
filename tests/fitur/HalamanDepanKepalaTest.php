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
        $this->assertSame(0, $xpath->query('//*[@data-foto-pembuka or @data-carousel-beranda]')->length);
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

        // Tanpa foto carousel, gambar kepala tampil diam di bagian pembuka (F9-03).
        $this->assertSame('1', $xpath->query('//section[@id="pembuka"]')->item(0)?->getAttribute('data-pembuka-foto'));
        $this->assertSame(0, $xpath->query('//*[@data-carousel-beranda]')->length, 'Satu gambar tidak berputar.');
        $gambar = $xpath->query('//section[@id="pembuka"]//*[@data-foto-pembuka]/img')->item(0);
        $this->assertNotNull($gambar);
        $this->assertSame(base_url($jalur['gambar_kepala']), $gambar->getAttribute('src'));
        $this->assertSame('Gambar kepala Lomba Lari Karangan', $gambar->getAttribute('alt'));
        $this->assertSame('1', $gambar->getAttribute('width'));
        $this->assertSame('1', $gambar->getAttribute('height'));

        // K93: logo acara di kepala halaman (persegi lebih dulu); logo Djarum di kanan kaki halaman (K84).
        $logoKepala = $xpath->query('//header//a[@data-merek]/img')->item(0);
        $this->assertNotNull($logoKepala);
        $this->assertSame(base_url($jalur['logo_persegi']), $logoKepala->getAttribute('src'));
        $this->assertSame('Logo Lomba Lari Karangan', $logoKepala->getAttribute('alt'));
        $this->assertSame('1', $logoKepala->getAttribute('width'));

        $logo = [];

        $this->assertSame(0, $xpath->query('//section[@id="pembuka"]//img[contains(@alt, "Logo")]')->length);

        foreach ($xpath->query('//footer//img[@data-kaki-logo]') as $img) {
            $logo[] = [$img->getAttribute('src'), $img->getAttribute('alt'), $img->getAttribute('width')];
        }

        $this->assertSame([[base_url($jalur['logo_djarum']), 'Logo Bakti Olahraga Djarum Foundation', '1']], $logo);

        // Tanpa logo persegi panjang, logo persegi yang tampil di kepala.
        setting('HalamanDepan.logo_persegi_panjang', null);
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(base_url($jalur['logo_persegi']), $xpath->query('//header//a[@data-merek]/img')->item(0)?->getAttribute('src'));
    }

    public function testGambarYangBerkasnyaHilangDisembunyikan(): void
    {
        $ada = $this->simpanGambarUji();
        setting('HalamanDepan.logo_djarum', $ada);
        setting('HalamanDepan.gambar_kepala', 'unggahan/halaman-depan/' . str_repeat('c', 32) . '.png');
        setting('HalamanDepan.logo_persegi', 'unggahan/halaman-depan/' . str_repeat('d', 32) . '.webp');

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(0, $xpath->query('//*[@data-foto-pembuka]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="pembuka"]//img')->length);
        $this->assertSame([base_url($ada)], array_map(
            static fn (DOMElement $img): string => $img->getAttribute('src'),
            iterator_to_array($xpath->query('//footer//img')),
        ));

        // Logo kaki juga tampil di halaman publik lain.
        $this->assertSame(base_url($ada), $this->xpathDari($this->get('unduhan'))->query('//footer//img[@data-kaki-logo]')->item(0)?->getAttribute('src'));
    }

    public function testLogoDjarumBawaanBilaAdminBelumMengunggah(): void
    {
        // Tanpa berkas bawaan dan tanpa unggahan: kaki halaman tanpa logo.
        $this->assertSame(0, $this->xpathDari($this->get('/'))->query('//footer//img[@data-kaki-logo]')->length);

        // Logo bawaan public/aset/gambar/logo-djarum.png (F10-01).
        $this->taruhGambarUji('logo-djarum.png', (string) file_get_contents(ROOTPATH . 'sumber/logo/logo-djarum-foundation.png'));
        $logo = $this->xpathDari($this->get('/'))->query('//footer//img[@data-kaki-logo]')->item(0);
        $this->assertNotNull($logo);
        $this->assertStringStartsWith(base_url('aset/gambar/logo-djarum.png') . '?v=', $logo->getAttribute('src'));
        $this->assertSame('Logo Bakti Olahraga Djarum Foundation', $logo->getAttribute('alt'));
        $this->assertSame(['510', '100'], [$logo->getAttribute('width'), $logo->getAttribute('height')]);

        // Unggahan admin menggantikan logo bawaan.
        $jalur = $this->simpanGambarUji();
        setting('HalamanDepan.logo_djarum', $jalur);
        $this->assertSame(base_url($jalur), $this->xpathDari($this->get('/'))->query('//footer//img[@data-kaki-logo]')->item(0)?->getAttribute('src'));
    }

    public function testBerkasLogoDjarumBawaanSamaDenganSumber(): void
    {
        $this->assertFileEquals(ROOTPATH . 'sumber/logo/logo-djarum-foundation.png', FCPATH . 'aset/gambar/logo-djarum.png');
    }

    // ---------- Judul, teks, dan data acara ----------

    public function testJudulKosongMemakaiNamaAcara(): void
    {
        $this->isiDataAcara();

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(1, $xpath->query('//h1')->length, 'Tepat satu h1.');
        $this->assertSame('MilkLife Athletics Challenge', $this->teks($xpath, '//h1[@id="judul-acara"]'));
        $this->assertSame(0, $xpath->query('//*[@data-pembuka-teks]')->length, 'Teks kosong tidak ditampilkan.');
        $this->assertSame('Seri 2 – 2026', $this->teks($xpath, '//header//*[@data-kepala-seri]'));
        // Pembuka tanpa alis (K163): seri hanya di kepala halaman.
        $this->assertSame(0, $xpath->query('//section[@id="pembuka"]//*[@data-pembuka-seri or contains(concat(" ", @class, " "), " alis ")]')->length);
        $this->assertSame('Hari lomba 9–12 Desember 2026 Lokasi Supersoccer Arena, Kudus', $this->teks($xpath, '//*[@data-pembuka-waktu]'));

        // Pita fakta (F10-06): "9–12 Desember" besar di atas tahun; tahun tidak ada di judul (permintaan 20).
        $this->assertSame('9–12 Desember', $this->teks($xpath, '//*[@data-pembuka-info]//*[@data-info-besar]'));
        $this->assertStringNotContainsString('2026', $this->teks($xpath, '//h1[@id="judul-acara"]'));
    }

    public function testFaktaLokasiMenautKeBagianPeta(): void
    {
        $this->isiDataAcara();

        // Peta bawaan tampil (F10-02): nama tempat di pita fakta menaut ke `#lokasi`.
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame('Supersoccer Arena', $this->teks($xpath, '//*[@data-pembuka-info]//a[@data-fakta-lokasi][@href="#lokasi"]'));
        $this->assertSame(1, $xpath->query('//section[@id="lokasi"]')->length);

        // Tanpa peta tidak ada tautan ke bagian yang tidak ada.
        setting('HalamanDepan.peta_embed', '');
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(0, $xpath->query('//*[@data-fakta-lokasi] | //section[@id="lokasi"]')->length);
        $this->assertSame('Hari lomba 9–12 Desember 2026 Lokasi Supersoccer Arena, Kudus', $this->teks($xpath, '//*[@data-pembuka-waktu]'));
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
        $this->assertSame('lomba!', $this->teks($xpath, '//h1//*[@data-judul-sorot]'), 'Judul pendek per kata, kata terakhir disorot (K83).');

        $paragraf = $xpath->query('//*[@data-pembuka-teks]/p');
        $this->assertSame(2, $paragraf->length);
        $this->assertSame(1, $xpath->query('//*[@data-pembuka-teks]/p[1]/br')->length, 'Baris baru di dalam paragraf menjadi <br>.');
        $this->assertSame('Paragraf <script>alert(1)</script> satu baris kedua', trim((string) preg_replace('/\s+/u', ' ', $paragraf->item(0)->textContent)));
        $this->assertSame('Paragraf dua', trim($paragraf->item(1)->textContent));

        $hasil->assertDontSee('<script>alert(1)</script>');
        $hasil->assertDontSee('<b>ikut</b>');
    }

    public function testJudulPerKalimatDenganBarisTerakhirDisorot(): void
    {
        setting('HalamanDepan.judul', 'Berlari. Bermain. Berprestasi.');

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(
            ['Berlari.', 'Bermain.', 'Berprestasi.'],
            array_map($this->teksSimpul(...), iterator_to_array($xpath->query('//h1[@id="judul-acara"]/span'))),
        );
        $this->assertSame('Berprestasi.', $this->teks($xpath, '//h1//*[@data-judul-sorot]'));

        // Satu kalimat: tiap kata satu baris, kata terakhir disorot (K83).
        setting('HalamanDepan.judul', 'MilkLife Athletics Challenge');
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(
            ['MilkLife', 'Athletics', 'Challenge'],
            array_map($this->teksSimpul(...), iterator_to_array($xpath->query('//h1[@id="judul-acara"]/span'))),
        );
        $this->assertSame('Challenge', $this->teks($xpath, '//h1//*[@data-judul-sorot]'));

        // Satu kalimat lebih dari 4 kata mengalir biasa, tidak dipecah per kata (K86).
        setting('HalamanDepan.judul', 'Berkompetisi bersama para atlet muda dari berbagai daerah!');
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(1, $xpath->query('//h1[@id="judul-acara"]/span')->length);
        $this->assertSame(0, $xpath->query('//*[@data-judul-sorot]')->length);

        // Satu kata saja: tidak ada baris yang disorot.
        setting('HalamanDepan.judul', 'Lomba');
        $this->assertSame(0, $this->xpathDari($this->get('/'))->query('//*[@data-judul-sorot]')->length);
    }

    // ---------- Keadaan pendaftaran ----------

    public function testKeadaanDisiapkanTanpaTeksStatisLama(): void
    {
        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertKeadaan($xpath, 'disiapkan', 'Sedang disiapkan', 'Pendaftaran sedang disiapkan.', 'hourglass');
        $hasil->assertDontSee('Panitia sedang menyiapkan formulir pendaftaran untuk sekolah.');
        $this->assertSame(0, $xpath->query('//*[@id="judul-status"]')->length);
    }

    public function testEmpatKeadaanPendaftaran(): void
    {
        $this->lengkapiPengaturan();

        $this->assertKeadaan($this->xpathDari($this->get('/')), 'terbuka', 'Terbuka', 'Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.', 'megaphone');
        // Pita fakta: tanggal tutup lengkap dengan tahun (permintaan 20, F10-06).
        $this->assertSame('Batas pendaftaran 30 November 2026', $this->teks($this->xpathDari($this->get('/')), '//*[@data-keadaan-ringkas]'));

        Time::setTestNow('2026-09-30 12:00:00', 'Asia/Jakarta');
        $this->assertKeadaan($this->xpathDari($this->get('/')), 'belum_dibuka', 'Belum dibuka', 'Pendaftaran dibuka 1 Oktober 2026.', 'calendar-clock');

        Time::setTestNow('2026-12-01 00:00:00', 'Asia/Jakarta');
        $this->assertKeadaan($this->xpathDari($this->get('/')), 'ditutup', 'Ditutup', 'Pendaftaran sudah ditutup pada 30 November 2026 pukul 23.59.', 'door-closed');
    }

    // ---------- Tombol ----------

    public function testTombolPendaftaranMengikutiKeadaan(): void
    {
        $this->lengkapiPengaturan();

        // Terbuka (11 November 2026): Pendaftaran menaut ke wizard (Tahap 4).
        // Cek sudah punya halaman (Tahap 5) dan aktif karena pengaturan lengkap.
        $tombol = $this->tombolKepala();
        $this->assertSame(['Daftar Sekarang', 'Cek Pendaftaran'], array_keys($tombol));
        $this->assertSame(site_url('pendaftaran'), $tombol['Daftar Sekarang']->getAttribute('href'));
        $this->assertTrue($tombol['Daftar Sekarang']->hasAttribute('data-utama'));
        $this->assertSame(site_url('cek-pendaftaran'), $tombol['Cek Pendaftaran']->getAttribute('href'));

        // Belum dibuka dan sudah ditutup: nonaktif dengan keterangannya.
        foreach (['2026-09-30 12:00:00' => 'Pendaftaran Belum dibuka', '2026-12-01 00:00:00' => 'Pendaftaran Ditutup'] as $waktu => $label) {
            Time::setTestNow($waktu, 'Asia/Jakarta');
            $tombol = $this->tombolKepala();

            $this->assertArrayHasKey($label, $tombol);
            $this->assertSame('true', $tombol[$label]->getAttribute('aria-disabled'), $label);
            $this->assertFalse($tombol[$label]->hasAttribute('href'), $label . ' tidak boleh bertautan.');
            $this->assertSame('link', $tombol[$label]->getAttribute('role'), $label);
        }
    }

    public function testTombolPendaftaranSegeraSaatPengaturanBelumLengkap(): void
    {
        $tombol = $this->tombolKepala();

        $this->assertSame(['Pendaftaran Segera', 'Cek Pendaftaran Segera'], array_keys($tombol));
        $this->assertFalse($tombol['Pendaftaran Segera']->hasAttribute('href'));
    }

    /**
     * @return array<string, DOMElement> teks tombol => simpul
     */
    private function tombolKepala(): array
    {
        $tombol = [];

        foreach ($this->xpathDari($this->get('/'))->query('//nav[@data-tombol-utama]/*') as $simpul) {
            $tombol[$this->teksSimpul($simpul)] = $simpul;
        }

        return $tombol;
    }

    public function testTombolDanKartuKelompokUmurDiPembuka(): void
    {
        // K81: tombol ajakan ada di bagian pembuka; kartu kelompok umur hanya
        // tampil bila kelompok umur sudah ada.
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(2, $xpath->query('//section[@id="pembuka"]//nav[@data-tombol-utama]/a')->length);
        $this->assertSame(0, $xpath->query('//*[@data-pembuka-ku]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="lomba"]')->length);

        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]/following-sibling::*[1][@id="lomba"]')->length);
        // Deret kartu kelompok umur tanpa label alis (K163).
        $this->assertSame(0, $xpath->query('//*[@data-pembuka-ku-label]')->length);

        // Tiap kartu menaut ke rincian kelompok umurnya di bagian lomba.
        $kartu = iterator_to_array($xpath->query('//section[@id="pembuka"]//a[@data-ku-kartu]'));
        $this->assertCount(5, $kartu);
        // Nama, julukan, dan jumlah nomor; jumlah 0 tidak ditulis (K88).
        $this->assertSame('KU 8 2 nomor individu', $this->teksSimpul($kartu[0]));
        $this->assertSame('KU 10 6 nomor individu 5 nomor beregu', $this->teksSimpul($kartu[1]));

        // Julukan dalam kurung yang ditambahkan admin tetap tampil terpisah.
        $this->db->table('kelompok_umur')->where('nama', 'KU 8')->update(['nama' => 'KU 8 (Julukan Karangan)']);
        $kartuBaru = $this->xpathDari($this->get('/'))->query('//section[@id="pembuka"]//a[@data-ku-kartu]');
        $this->assertSame('KU 8 Julukan Karangan 2 nomor individu', $this->teksSimpul($kartuBaru->item(0)));

        foreach ($kartu as $a) {
            $this->assertSame(1, $xpath->query('//article[@id="' . substr($a->getAttribute('href'), 1) . '"]')->length, $a->getAttribute('href'));
        }
    }

    // ---------- Footer ----------

    public function testFooterDariPengaturanAtauCadangan(): void
    {
        $this->isiDataAcara();
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame('MilkLife Athletics Challenge 2026', $this->teks($xpath, '//footer//*[@data-kaki-teks]'));

        setting('HalamanDepan.footer', "Panitia <i>Karangan</i>\nKudus");
        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('Panitia <i>Karangan</i> Kudus', $this->teks($xpath, '//footer//*[@data-kaki-teks]'));
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

        // Bagian kontak selalu tampil karena tombol "Bagikan ke WA" selalu ada (F3-03);
        // Lokasi tampil dengan peta bawaan dari `Config\HalamanDepan` (F10-02).
        $this->assertSame(['pembuka', 'lokasi', 'kontak'], $bagian);
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
            array_values(array_intersect(['pembuka', 'lomba', 'jadwal', 'angka', 'hasil', 'faq', 'unduhan', 'lokasi', 'kontak'], $urutan)),
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
        $this->assertSame($label, $this->teks($xpath, '//*[@data-keadaan-label]'));
        $this->assertSame($kalimat, $this->teks($xpath, '//*[@data-keadaan-kalimat]'));
        $this->assertSame(1, $xpath->query('//*[@data-keadaan]//*[name()="use"][substring-after(@href, "#") = "' . $ikon . '"]')->length, 'Ikon ' . $ikon);
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
            $this->db->table('kelompok_umur_jenjang')->ignore(true)->insert(['kelompok_umur_id' => $ku['id'], 'jenjang' => 'A']);
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
