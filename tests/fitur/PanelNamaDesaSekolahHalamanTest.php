<?php

use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Beri nama desa ke sekolah bernama kembar (K114): pratinjau dan terapkan di
 * `admin/sekolah/nama-desa`. Semua sekolah karangan.
 *
 * @internal
 */
final class PanelNamaDesaSekolahHalamanTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/sekolah/nama-desa';

    /**
     * @var array<string, int> npsn => id
     */
    private array $id = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('sekolah')->insertBatch([
            ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'jenjang' => 'SD', 'desa_kelurahan' => 'Rekaan', 'kecamatan' => 'Anggitan', 'kabupaten' => 'Blora'],
            ['npsn' => '99100002', 'nama' => 'MIS KARANGAN', 'jenjang' => 'SD', 'desa_kelurahan' => 'Khayalan', 'kecamatan' => 'Contoh', 'kabupaten' => 'Kudus'],
            ['npsn' => '99100003', 'nama' => 'SMP KARANGAN JAYA', 'jenjang' => 'SMP', 'desa_kelurahan' => 'Sukamaju', 'kecamatan' => 'Contoh', 'kabupaten' => 'Kudus'],
            // Kembar di desa, kecamatan, dan kabupaten yang sama: diubah sendiri.
            ['npsn' => '99100004', 'nama' => 'MI REKAAN', 'jenjang' => 'SD', 'desa_kelurahan' => 'Ngarang', 'kecamatan' => 'Contoh', 'kabupaten' => 'Pati'],
            ['npsn' => '99100005', 'nama' => 'MI REKAAN', 'jenjang' => 'SD', 'desa_kelurahan' => 'Ngarang', 'kecamatan' => 'Contoh', 'kabupaten' => 'Pati'],
        ]);

        foreach ($this->db->table('sekolah')->select('id, npsn')->get()->getResultArray() as $baris) {
            $this->id[$baris['npsn']] = (int) $baris['id'];
        }
    }

    /**
     * @param array<string, mixed> $ubah
     */
    private function pendaftaran(array $ubah): void
    {
        $this->db->table('pendaftaran')->insert($ubah + [
            'jenjang' => 'A', 'kabupaten' => 'Blora', 'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0,
        ]);
    }

    public function testYangBelumMasukDialihkan(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
    }

    public function testDaftarSekolahMenautkanHalamanNamaDesa(): void
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/sekolah'));

        $this->assertSame(site_url(self::ALAMAT), $xpath->query('//a[@id="tautan-nama-desa"]')->item(0)?->getAttribute('href'));
    }

    public function testTanpaNamaKembarTombolTidakTampil(): void
    {
        $this->db->table('sekolah')->whereIn('npsn', ['99100002', '99100005'])->delete();
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/sekolah'));

        $this->assertSame(0, $xpath->query('//a[@id="tautan-nama-desa"]')->length);
    }

    public function testPratinjauMenampilkanNamaBaruDanYangPerluDiubahSendiri(): void
    {
        $this->pendaftaran(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN']);
        $this->pendaftaran(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN']);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $baris = $xpath->query('//table[@id="tabel-nama-baru"]/tbody/tr');
        $this->assertSame(2, $baris->length);
        $this->assertSame('99100001', $baris->item(0)->getAttribute('data-npsn'));
        $this->assertStringContainsString('MIS KARANGAN REKAAN', $baris->item(0)->textContent);
        $this->assertStringContainsString('Rekaan, Kec. Anggitan, Kab. Blora', $baris->item(0)->textContent);
        $this->assertSame('2', trim($xpath->query('td[last()]', $baris->item(0))->item(0)->textContent));
        $this->assertStringContainsString('MIS KARANGAN KHAYALAN', $baris->item(1)->textContent);

        // Kotak centang bawaannya tercentang dan mengirim id sekolah.
        $kotak = $xpath->query('//form[@id="form-nama-desa"]//input[@name="id[]"][@checked]');
        $this->assertSame(2, $kotak->length);
        $this->assertSame((string) $this->id['99100001'], $kotak->item(0)->getAttribute('value'));

        $lewati = $xpath->query('//table[@id="tabel-lewati"]/tbody/tr');
        $this->assertSame(2, $lewati->length);
        $this->assertSame(
            site_url('admin/sekolah/' . $this->id['99100004'] . '/ubah'),
            $xpath->query('.//a', $lewati->item(0))->item(0)->getAttribute('href'),
        );
    }

    public function testTerapkanMenggantiNamaSekolahDanPendaftaranBernamaLama(): void
    {
        $this->pendaftaran(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN']);
        // Nama usulan pendaftar yang belum diputuskan tidak diubah (K99).
        $this->pendaftaran(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN BARAT']);
        $this->pendaftaran(['npsn' => '99100002', 'nama_sekolah' => 'MIS KARANGAN']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['id' => [(string) $this->id['99100001'], (string) $this->id['99100002']]]);

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/sekolah',
            '2 sekolah sekarang memakai nama baru dengan nama desa. Nama sekolah di 2 pendaftaran ikut diganti.'
            . ' Bila ada nama yang kurang tepat, pilih "Sudah disunting admin" di saringan Disunting, lalu tekan Ubah pada sekolah itu.',
        );

        $sekolah = $this->db->table('sekolah')->where('npsn', '99100001')->get()->getRowArray();
        $this->assertSame('MIS KARANGAN REKAAN', $sekolah['nama']);
        $this->assertNotNull($sekolah['disunting_pada']);
        $this->seeInDatabase('sekolah', ['npsn' => '99100002', 'nama' => 'MIS KARANGAN KHAYALAN']);
        $this->seeInDatabase('sekolah', ['npsn' => '99100004', 'nama' => 'MI REKAAN', 'disunting_pada' => null]);

        $this->assertSame(1, $this->db->table('pendaftaran')->where(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN'])->countAllResults());
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN BARAT']);
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100002', 'nama_sekolah' => 'MIS KARANGAN KHAYALAN']);
    }


    public function testTerapkanTetapTersimpanWalauKoneksiPernahMenggagalkanTransaksi(): void
    {
        $this->pendaftaran(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN']);
        $this->tinggalkanStatusTransaksiGagal();

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['id' => [(string) $this->id['99100001']]]);

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah');
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN REKAAN']);
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN']);
    }

    public function testHanyaYangDicentangDiterapkan(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['id' => [(string) $this->id['99100002'], (string) $this->id['99100004'], 'x']]);

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah');
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'disunting_pada' => null]);
        $this->seeInDatabase('sekolah', ['npsn' => '99100002', 'nama' => 'MIS KARANGAN KHAYALAN']);
        // Sekolah yang tidak bisa dibedakan otomatis tidak diubah walau dikirim.
        $this->seeInDatabase('sekolah', ['npsn' => '99100004', 'nama' => 'MI REKAAN', 'disunting_pada' => null]);
    }

    public function testTanpaCentangDitolak(): void
    {
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT, []), self::ALAMAT);
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN']);
    }

    public function testSekolahYangSudahTidakKembarTidakDiubah(): void
    {
        // Admin lain sudah membedakan satu sekolah lebih dulu.
        $this->db->table('sekolah')->where('npsn', '99100002')->update(['nama' => 'MIS KARANGAN KUDUS']);

        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT, ['id' => [(string) $this->id['99100001']]]), self::ALAMAT);
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN']);
    }

    public function testNamaBaruMasihBisaDiubahLagiPerSekolah(): void
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, ['id' => [(string) $this->id['99100001'], (string) $this->id['99100002']]]);
        $this->pendaftaran(['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN']);

        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/' . $this->id['99100001'], [
            'nama'           => 'MIS KARANGAN REKAAN WETAN',
            'bentuk'         => '',
            'jenjang'        => 'SD',
            'status'         => '',
            'alamat'         => '',
            'desa_kelurahan' => 'Rekaan',
            'kecamatan'      => 'Anggitan',
            'kabupaten'      => 'Blora',
            'terapkan'       => '1',
        ]);

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah');
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN REKAAN WETAN']);
        $this->seeInDatabase('pendaftaran', ['npsn' => '99100001', 'nama_sekolah' => 'MIS KARANGAN REKAAN WETAN']);
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, ['id' => [(string) $this->id['99100001']]]);
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN']);
    }
}
