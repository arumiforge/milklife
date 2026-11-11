<?php

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Dasbor (daftar centang kelengkapan dan keadaan pendaftaran) dan tanda
 * lengkap/belum di halaman daftar pengaturan (F2-09).
 *
 * Kolom batas tahun lahir (K36) ditambahkan di persiapan selama migrasi F2-03
 * belum ada, seperti `DataKelengkapanTest`.
 *
 * @internal
 */
final class DasborKelengkapanTest extends UjiBasisData
{
    use KirimFormulir;
    use StreamFilterTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->db->query("SHOW COLUMNS FROM kelompok_umur LIKE 'tahun_lahir_awal'")->getNumRows() === 0) {
            $this->db->query('ALTER TABLE kelompok_umur ADD tahun_lahir_awal SMALLINT UNSIGNED NULL AFTER nama, ADD tahun_lahir_akhir SMALLINT UNSIGNED NULL AFTER tahun_lahir_awal');
        }

        Time::setTestNow('2026-11-11 10:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    // ---------- Dasbor ----------

    public function testDasborTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan('admin/dasbor');
    }

    public function testDasborBasisDataKosong(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/dasbor');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(
            ['acara' => 'tidak', 'pendaftaran' => 'tidak', 'kelompok-umur' => 'tidak', 'nomor-lomba' => 'tidak', 'pembayaran' => 'tidak'],
            $this->centang($xpath),
        );
        $hasil->assertSee('0 dari 5 sudah lengkap.');
        $hasil->assertSee('Belum diisi: nama bank, nomor rekening, nama pemilik rekening, nomor WA pembayaran.');

        // Tiap butir menautkan halaman yang melengkapinya.
        $tautan = [];

        foreach ($xpath->query('//ul[contains(@class, "daftar-kelengkapan")]/li//a') as $a) {
            $tautan[] = $a->getAttribute('href');
        }

        $this->assertSame([
            site_url('admin/pengaturan/acara'),
            site_url('admin/pengaturan/pendaftaran'),
            site_url('admin/pengaturan/kelompok-umur'),
            site_url('admin/pengaturan/nomor-lomba'),
            site_url('admin/pengaturan/pembayaran'),
        ], $tautan);

        $this->assertSame('disiapkan', $xpath->query('//*[@id="keadaan-pendaftaran"]')->item(0)?->getAttribute('data-keadaan'));
        $hasil->assertSee('Pendaftaran sedang disiapkan.');
        $hasil->assertSee('Sedang disiapkan');

        // Keadaan kosong pendaftaran tetap ada.
        $hasil->assertSee('Belum ada pendaftaran');
    }

    public function testDasborSetelahDataAwal(): void
    {
        $this->jalankanDataAwal();

        $hasil = $this->bukaSebagaiAdmin('admin/dasbor');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(
            ['acara' => 'ya', 'pendaftaran' => 'ya', 'kelompok-umur' => 'tidak', 'nomor-lomba' => 'tidak', 'pembayaran' => 'tidak'],
            $this->centang($xpath),
        );
        $hasil->assertSee('2 dari 5 sudah lengkap.');
        $hasil->assertSee('KU 8 (Seneng Playon), KU 10, KU 12, KU 15, KU 18 belum punya jenjang sekolah.');
        $hasil->assertSee('1 Oktober 2026 pukul 00.00 sampai 30 November 2026 pukul 23.59');
        // Jadwal sudah ada, tetapi pengaturan belum lengkap.
        $hasil->assertSee('Pendaftaran sedang disiapkan.');
    }

    public function testDasborSemuaLengkapDanTerbukaSesuaiWaktu(): void
    {
        $this->jalankanDataAwal();
        $this->lengkapiSemua();

        $hasil = $this->bukaSebagaiAdmin('admin/dasbor');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(['ya'], array_values(array_unique($this->centang($xpath))));
        $hasil->assertSee('Semua pengaturan wajib sudah lengkap.');
        $this->assertSame('terbuka', $xpath->query('//*[@id="keadaan-pendaftaran"]')->item(0)?->getAttribute('data-keadaan'));
        $hasil->assertSee('Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.');
        $hasil->assertSee('Belum ada pendaftaran');

        Time::setTestNow('2026-12-01 00:00:00', 'Asia/Jakarta');
        $this->bukaSebagaiAdmin('admin/dasbor')->assertSee('Pendaftaran sudah ditutup pada 30 November 2026 pukul 23.59.');

        Time::setTestNow('2026-09-30 12:00:00', 'Asia/Jakarta');
        $this->bukaSebagaiAdmin('admin/dasbor')->assertSee('Pendaftaran dibuka 1 Oktober 2026.');
    }

    public function testDasborBukaManual(): void
    {
        $this->jalankanDataAwal();
        $this->lengkapiSemua();
        setting('Pendaftaran.buka_manual', true);
        setting('Pendaftaran.buka_manual_sampai', '2026-12-03 12:00:00');
        Time::setTestNow('2026-12-02 08:00:00', 'Asia/Jakarta');

        $hasil = $this->bukaSebagaiAdmin('admin/dasbor');

        $hasil->assertSee('Pendaftaran dibuka sampai 3 Desember 2026 pukul 12.00.');
        $hasil->assertSee('Pendaftaran terbuka karena dibuka manual oleh admin, di luar jadwal.');
        $hasil->assertSee('Menyala sampai 3 Desember 2026 pukul 12.00');
    }

    public function testKeteranganDiEscape(): void
    {
        $this->db->table('kelompok_umur')->insert(['nama' => '<b>KU Karangan</b>', 'aktif' => 1]);

        $html = $this->bukaSebagaiAdmin('admin/dasbor')->getBody();

        $this->assertStringContainsString('&lt;b&gt;KU Karangan&lt;/b&gt; belum punya', $html);
        $this->assertStringNotContainsString('<b>KU Karangan</b>', $html);
    }

    // ---------- Halaman daftar pengaturan ----------

    public function testTandaHalamanPengaturanSetelahDataAwal(): void
    {
        $this->jalankanDataAwal();

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan'));
        $tanda = [];

        foreach ($xpath->query('//a[contains(@class, "kartu-pengaturan")]') as $kartu) {
            $lencana = $xpath->query('.//*[contains(@class, "tanda-kelengkapan")]', $kartu)->item(0);
            $tanda[$kartu->getAttribute('data-kunci')] = $lencana === null ? null : trim($lencana->textContent);
        }

        $this->assertSame([
            'acara'         => 'Lengkap',
            'pendaftaran'   => 'Lengkap',
            'kelompok-umur' => 'Belum lengkap',
            'nomor-lomba'   => 'Belum lengkap',
            'pembayaran'    => 'Belum lengkap',
            'halaman-depan' => null,
            'jadwal'        => null,
            'unduhan'       => null,
            'kontak'        => null,
            'akun'          => null,
        ], $tanda);

        $keterangan = $xpath->query('//a[@data-kunci="kelompok-umur"]//*[contains(@class, "keterangan-kelengkapan")]')->item(0);
        $this->assertNotNull($keterangan);
        $this->assertStringContainsString('belum punya jenjang sekolah.', $keterangan->textContent);
        $this->assertSame(0, $xpath->query('//a[@data-kunci="acara"]//*[contains(@class, "keterangan-kelengkapan")]')->length);
    }

    public function testTandaHalamanPengaturanSemuaLengkap(): void
    {
        $this->jalankanDataAwal();
        $this->lengkapiSemua();

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan'));

        $this->assertSame(5, $xpath->query('//*[contains(@class, "tanda-kelengkapan")][contains(., "Lengkap")]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class, "tanda-kelengkapan")][contains(., "Belum")]')->length);
    }

    // ---------- Bantuan ----------

    /**
     * Kunci butir => 'ya'/'tidak' dari daftar centang dasbor.
     *
     * @return array<string, string>
     */
    private function centang(DOMXPath $xpath): array
    {
        $hasil = [];

        foreach ($xpath->query('//ul[contains(@class, "daftar-kelengkapan")]/li') as $li) {
            $hasil[$li->getAttribute('data-kunci')] = $li->getAttribute('data-lengkap');
        }

        return $hasil;
    }

    private function jalankanDataAwal(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));
    }

    /**
     * Isi semua yang dikosongkan `data:awal`, seperti admin mengisi semua halaman.
     */
    private function lengkapiSemua(): void
    {
        $this->db->query('UPDATE kelompok_umur SET tahun_lahir_awal = COALESCE(tahun_lahir_awal, 2000 + id), biaya_individu = 50000');
        $this->db->query("UPDATE nomor_lomba SET batas_anggota = jumlah_inti + 2 WHERE jenis = 'beregu'");
        $this->db->query("UPDATE nomor_lomba SET jumlah_putra = jumlah_inti DIV 2, jumlah_putri = jumlah_inti DIV 2 WHERE kategori = 'mix'");

        foreach ($this->db->table('kelompok_umur')->select('id')->get()->getResultArray() as $ku) {
            $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku['id'], 'jenjang' => 'A']);
        }

        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '1234567890');
        setting('Pembayaran.pemilik_rekening', 'Panitia Karangan');
        setting('Pembayaran.nomor_wa', '6281234567890');
    }
}
