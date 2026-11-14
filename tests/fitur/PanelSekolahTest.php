<?php

use App\Controllers\Admin\SekolahController;
use CodeIgniter\HTTP\DownloadResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Data sekolah di panel (K97): daftar `admin/sekolah`, JSON
 * `admin/sekolah/data`, Excel, dan formulir ubah. Semua sekolah karangan.
 *
 * @internal
 */
final class PanelSekolahTest extends UjiBasisData
{
    use KirimFormulir;

    /**
     * @var array<string, int> npsn => id
     */
    private array $id = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('sekolah')->insertBatch([
            ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'bentuk' => 'MI', 'jenjang' => 'SD', 'status' => 'SWASTA', 'desa_kelurahan' => 'Rekaan', 'kecamatan' => 'Anggitan', 'kabupaten' => 'Blora'],
            ['npsn' => '99100002', 'nama' => 'MIS KARANGAN', 'bentuk' => 'MI', 'jenjang' => 'SD', 'status' => 'SWASTA', 'desa_kelurahan' => 'Khayalan', 'kecamatan' => 'Contoh', 'kabupaten' => 'Kudus'],
            ['npsn' => '99100003', 'nama' => 'SMP KARANGAN JAYA', 'bentuk' => 'SMP', 'jenjang' => 'SMP', 'status' => 'NEGERI', 'desa_kelurahan' => 'Sukamaju', 'kecamatan' => 'Contoh', 'kabupaten' => 'Kudus'],
        ]);

        foreach ($this->db->table('sekolah')->select('id, npsn')->get()->getResultArray() as $baris) {
            $this->id[$baris['npsn']] = (int) $baris['id'];
        }
    }

    /**
     * @param array<string, string> $kueri
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function data(array $kueri = []): array
    {
        $hasil = $this->bukaSebagaiAdmin('admin/sekolah/data' . ($kueri === [] ? '' : '?' . http_build_query($kueri)));
        $hasil->assertOK();

        return json_decode($hasil->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    private function isianSah(array $ubah = []): array
    {
        return $ubah + [
            'nama'           => 'MIS KARANGAN REKAAN',
            'bentuk'         => 'MI',
            'jenjang'        => 'SD',
            'status'         => 'SWASTA',
            'alamat'         => 'Dk. Rekaan RT 01 RW 02',
            'desa_kelurahan' => 'Rekaan',
            'kecamatan'      => 'Anggitan',
            'kabupaten'      => 'Blora',
            'terapkan'       => '1',
        ];
    }

    public function testYangBelumMasukDialihkan(): void
    {
        foreach (['admin/sekolah', 'admin/sekolah/data', 'admin/sekolah/excel', 'admin/sekolah/' . $this->id['99100001'] . '/ubah'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        $this->assertBelumMasukDialihkan('admin/sekolah/' . $this->id['99100001'], 'post');
    }

    public function testHalamanMenampilkanTabelSaringanDanPeringatanNamaKembar(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/sekolah');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $tabel = $xpath->query('//table[@id="tabel-sekolah"][@data-tabel-admin]')->item(0);
        $this->assertNotNull($tabel);
        $this->assertSame(site_url('admin/sekolah/data'), $tabel->getAttribute('data-alamat'));
        $this->assertSame(SekolahController::PESAN_KOSONG, $tabel->getAttribute('data-kosong'));

        $nama = [];

        foreach ($xpath->query('//form[@id="saringan-sekolah"]//select') as $select) {
            $nama[] = $select->getAttribute('name');
        }

        $this->assertSame(['kabupaten', 'jenjang', 'nama', 'disunting'], $nama);
        $this->assertStringContainsString('2 sekolah', $xpath->query('//div[contains(@class, "alert-warning")]')->item(0)->textContent);
    }

    public function testDataMenghitungNamaSamaDanBisaDisaring(): void
    {
        $semua = $this->data(['length' => '100']);
        $this->assertSame(3, $semua['recordsTotal']);

        $perNpsn = array_column($semua['data'], null, 'npsn');
        $this->assertSame(1, $perNpsn['99100001']['nama_sama']);
        $this->assertSame(0, $perNpsn['99100003']['nama_sama']);
        $this->assertSame('SD/MI', $perNpsn['99100001']['jenjang']);
        $this->assertSame('Swasta', $perNpsn['99100001']['status']);
        $this->assertSame(['alamat' => site_url('admin/sekolah/' . $this->id['99100001'] . '/ubah'), 'teks' => 'Ubah'], $perNpsn['99100001']['ubah']);

        $kembar = array_column($this->data(['nama' => 'kembar'])['data'], 'npsn');
        sort($kembar);
        $this->assertSame(['99100001', '99100002'], $kembar);
        $this->assertSame(['99100003'], array_column($this->data(['nama' => 'tunggal'])['data'], 'npsn'));
        $this->assertSame(['99100001'], array_column($this->data(['kabupaten' => 'Blora'])['data'], 'npsn'));
        $this->assertSame(['99100003'], array_column($this->data(['jenjang' => 'SMP'])['data'], 'npsn'));

        // Pencarian juga membaca desa dan kecamatan.
        $this->assertSame(['99100002'], array_column($this->data(['search' => ['value' => 'Khayalan']])['data'], 'npsn'));
    }

    public function testFormulirUbahMenampilkanSekolahLainBernamaSama(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/sekolah/' . $this->id['99100001'] . '/ubah');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('MIS KARANGAN', $xpath->query('//input[@name="nama"]')->item(0)->getAttribute('value'));
        $this->assertSame('Blora', $xpath->query('//select[@name="kabupaten"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertStringContainsString('99100002', $xpath->query('//ul[@id="daftar-nama-sama"]')->item(0)->textContent);
        $this->assertStringContainsString('Khayalan, Kec. Contoh, Kab. Kudus', $xpath->query('//ul[@id="daftar-nama-sama"]')->item(0)->textContent);
    }

    public function testSimpanNamaPembedaMenandaiDisuntingDanMenggantiNamaDiPendaftaran(): void
    {
        $this->db->table('pendaftaran')->insertBatch([
            ['jenjang' => 'A', 'npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN', 'kabupaten' => 'Blora', 'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0],
            ['jenjang' => 'A', 'npsn' => '99100002', 'nama_sekolah' => 'MIS KARANGAN', 'kabupaten' => 'Kudus', 'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0],
        ]);

        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/' . $this->id['99100001'], $this->isianSah());

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah', 'Perubahan sekolah MIS KARANGAN REKAAN (NPSN 99100001) tersimpan. Nama sekolah di 1 pendaftaran ikut diganti.');
        $sekolah = $this->db->table('sekolah')->where('npsn', '99100001')->get()->getRowArray();
        $this->assertSame('MIS KARANGAN REKAAN', $sekolah['nama']);
        $this->assertSame('Dk. Rekaan RT 01 RW 02', $sekolah['alamat']);
        $this->assertNotNull($sekolah['disunting_pada']);

        // Hanya pendaftaran ber-NPSN itu yang ikut berganti.
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN']);
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100002', 'nama_sekolah' => 'MIS KARANGAN']);
        $this->assertSame(['99100001'], array_column($this->data(['disunting' => 'ya'])['data'], 'npsn'));
    }

    public function testTanpaCentangPendaftaranTidakBerubah(): void
    {
        $this->db->table('pendaftaran')->insert(['jenjang' => 'A', 'npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN', 'kabupaten' => 'Blora', 'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0]);

        $isian = $this->isianSah();
        unset($isian['terapkan']);
        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/' . $this->id['99100001'], $isian);

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah', 'Perubahan sekolah MIS KARANGAN REKAAN (NPSN 99100001) tersimpan.');
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN']);
    }

    public function testNamaBaruYangSamaDenganSekolahLainDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/' . $this->id['99100001'], $this->isianSah(['nama' => 'smp karangan jaya']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama']);
        $this->assertStringContainsString('NPSN 99100003', (string) $hasil->getBody());
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'disunting_pada' => null]);
    }

    public function testNamaLamaYangMasihKembarBolehDipertahankan(): void
    {
        // Admin hanya membetulkan alamat; nama tetap sama dengan sekolah lain.
        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/' . $this->id['99100001'], $this->isianSah(['nama' => 'MIS KARANGAN']));

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah');
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'alamat' => 'Dk. Rekaan RT 01 RW 02']);
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/' . $this->id['99100001'], $this->isianSah([
            'nama'      => '',
            'jenjang'   => 'TK',
            'status'    => 'LAIN',
            'kabupaten' => 'Demak',
            'kecamatan' => str_repeat('a', 61),
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama', 'jenjang', 'status', 'kabupaten', 'kecamatan']);
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN']);
    }

    public function testSekolahTidakDikenalDialihkan(): void
    {
        $this->assertGagalDialihkan($this->bukaSebagaiAdmin('admin/sekolah/999999/ubah'), 'admin/sekolah');
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/sekolah/999999', $this->isianSah()), 'admin/sekolah');
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib('admin/sekolah/' . $this->id['99100001'], $this->isianSah());
    }

    public function testExcelMengikutiSaringan(): void
    {
        $tanggapan = $this->bukaSebagaiAdmin('admin/sekolah/excel?kabupaten=Kudus')->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-sekolah-');
        file_put_contents($berkas, (string) ob_get_clean());

        try {
            $buku  = IOFactory::load($berkas);
            $baris = $buku->getActiveSheet()->toArray(null, false, false, false);
            $buku->disconnectWorksheets();
        } finally {
            unlink($berkas);
        }

        $this->assertSame('NPSN', $baris[0][0]);
        $npsn = array_column(array_slice($baris, 1), 0);
        sort($npsn);
        $this->assertSame(['99100002', '99100003'], $npsn);
    }
}
