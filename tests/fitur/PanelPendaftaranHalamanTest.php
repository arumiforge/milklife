<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UjiHalaman;

/**
 * Halaman pendaftaran panel admin (F6-02): daftar, JSON tabel, Excel,
 * rincian, dan foto penanggung jawab.
 *
 * @internal
 */
final class PanelPendaftaranHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testSemuaHalamanMenolakYangBelumMasuk(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true]);

        foreach ([
            'admin/pendaftaran',
            'admin/pendaftaran/data',
            'admin/pendaftaran/excel',
            'admin/pendaftaran/' . $p['id'],
            'admin/pendaftaran/' . $p['id'] . '/foto/' . $p['penanggung_jawab'][0],
        ] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach (['lunas', 'menunggu', 'hapus'] as $tindakan) {
            $this->assertBelumMasukDialihkan('admin/pendaftaran/' . $p['id'] . '/' . $tindakan, 'post');
        }

        $this->assertSame('menunggu', $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRow()->status);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testHalamanDaftarMemuatTabelDanSaringan(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $tabel = $xpath->query('//table[@id="tabel-pendaftaran"][@data-tabel-admin]')->item(0);

        $this->assertNotNull($tabel);
        $this->assertSame(site_url('admin/pendaftaran/data'), $tabel->getAttribute('data-alamat'));
        $this->assertSame('#saringan-pendaftaran', $tabel->getAttribute('data-saringan'));
        $this->assertSame('#excel-pendaftaran', $tabel->getAttribute('data-excel'));
        $this->assertStringContainsString('Belum ada pendaftaran.', $tabel->getAttribute('data-kosong'));

        $kolom = [];

        foreach ($xpath->query('//table[@id="tabel-pendaftaran"]/thead//th') as $th) {
            $kolom[] = $th->getAttribute('data-kolom');
        }

        $this->assertSame(['id', '', 'id_pendaftaran', 'tanggal', 'nama_sekolah', 'npsn', 'jenjang', 'kabupaten', 'jumlah_atlet', 'total', 'status', 'alamat'], $kolom);

        foreach (['status' => ['', 'lunas', 'menunggu'], 'jenjang' => ['', 'A', 'B', 'C'], 'kabupaten' => ['', 'Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara']] as $nama => $nilai) {
            $pilihan = [];

            foreach ($xpath->query('//form[@id="saringan-pendaftaran"]//select[@name="' . $nama . '"]/option') as $opsi) {
                $pilihan[] = $opsi->getAttribute('value');
            }

            $this->assertSame($nilai, $pilihan, $nama);
        }

        $this->assertSame(site_url('admin/pendaftaran/excel'), $xpath->query('//a[@id="excel-pendaftaran"]')->item(0)->getAttribute('href'));

        // Aset tabel dan bentuk kolom halaman ini.
        $this->assertStringContainsString(aset('js/tabel-admin.js'), (string) $hasil->response()->getBody());
        $this->assertLessThan(
            strpos((string) $hasil->response()->getBody(), aset('js/admin-pendaftaran.js')),
            strpos((string) $hasil->response()->getBody(), aset('js/tabel-admin.js')),
            'admin-pendaftaran.js dimuat setelah tabel-admin.js.',
        );
        $this->assertFileExists(FCPATH . 'aset/js/admin-pendaftaran.js');
    }

    public function testDataJsonDenganSaringanDanPencarian(): void
    {
        $a = $this->buatPendaftaranPanel(['dibuat_pada' => '2026-11-10 08:00:00']);
        $b = $this->buatPendaftaranPanel([
            'sekolah'     => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
            'status'      => 'lunas',
            'atlet'       => [$this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
            'dibuat_pada' => '2026-11-11 08:00:00',
        ]);
        $c = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4), 'sekolah' => ['npsn' => null, 'nama' => 'SD TANPA NPSN', 'kabupaten' => 'Pati']]);

        $semua = $this->json(['draw' => '3']);
        $this->assertSame(3, $semua['draw']);
        $this->assertSame(3, $semua['recordsTotal']);
        $this->assertSame(3, $semua['recordsFiltered']);
        // Bawaan: terbaru di atas.
        $this->assertSame([$c['id'], $b['id'], $a['id']], array_column($semua['data'], 'id'));

        $baris = $semua['data'][1];
        $this->assertSame($b['id_pendaftaran'], $baris['id_pendaftaran']);
        $this->assertSame('2026-11-11 08:00:00', $baris['tanggal']);
        $this->assertSame('SMP KARANGAN JAYA', $baris['nama_sekolah']);
        $this->assertSame('99999913', $baris['npsn']);
        $this->assertSame('SMP/MTs', $baris['jenjang']);
        $this->assertSame('Jepara', $baris['kabupaten']);
        $this->assertSame(1, $baris['jumlah_atlet']);
        $this->assertSame(200000, $baris['total']);
        $this->assertSame('lunas', $baris['status']);
        $this->assertSame(site_url('admin/pendaftaran/' . $b['id']), $baris['alamat']);
        $this->assertNull($semua['data'][0]['npsn']);
        $this->assertSame(4, $semua['data'][0]['jumlah_atlet']);

        $this->assertSame([$b['id']], array_column($this->json(['status' => 'lunas'])['data'], 'id'));
        $this->assertSame([$c['id'], $a['id']], array_column($this->json(['status' => 'menunggu'])['data'], 'id'));
        $this->assertSame([$b['id']], array_column($this->json(['jenjang' => 'B'])['data'], 'id'));
        $this->assertSame([$c['id']], array_column($this->json(['kabupaten' => 'Pati'])['data'], 'id'));
        $this->assertSame([], $this->json(['kabupaten' => 'Kudus', 'status' => 'lunas'])['data']);

        // Pencarian: ID pendaftaran, nama sekolah, NPSN.
        $this->assertSame([$b['id']], array_column($this->json(['search' => ['value' => 'karangan jaya']])['data'], 'id'));
        $this->assertSame([$a['id']], array_column($this->json(['search' => ['value' => $a['id_pendaftaran']]])['data'], 'id'));
        $this->assertSame([$b['id']], array_column($this->json(['search' => ['value' => '99999913']])['data'], 'id'));

        // Urut jumlah atlet, terbanyak dulu.
        $urut = $this->json(['order' => [['column' => '6', 'dir' => 'desc']], 'columns' => [6 => ['data' => 'jumlah_atlet']]]);
        $this->assertSame([$c['id'], $a['id'], $b['id']], array_column($urut['data'], 'id'));

        // Nilai saringan di luar daftar diabaikan; nama kolom tidak dipakai mentah.
        $this->assertCount(3, $this->json(['status' => "lunas' OR 1=1 -- ", 'kabupaten' => 'Semarang'])['data']);
        $suntik = $this->json(['order' => [['column' => '0', 'dir' => 'desc']], 'columns' => [['data' => 'p.id; DROP TABLE pendaftaran']]]);
        $this->assertCount(3, $suntik['data']);
        $this->assertSame(3, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testDataKosong(): void
    {
        $hasil = $this->json(['draw' => '1']);

        $this->assertSame(0, $hasil['recordsTotal']);
        $this->assertSame([], $hasil['data']);
    }

    public function testExcelMengikutiSaringan(): void
    {
        $this->buatPendaftaranPanel();
        $lunas = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
            'status'  => 'lunas',
            'atlet'   => [$this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri']])],
        ]);

        $hasil = $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/pendaftaran/excel', ['status' => 'lunas']);
        $hasil->assertStatus(200);
        $hasil->response()->buildHeaders();
        $this->assertMatchesRegularExpression('/filename="Pendaftaran_\d{8}_\d{6}\.xlsx"/', $hasil->response()->getHeaderLine('Content-Disposition'));

        $lembar = $this->bacaExcel($hasil)->getActiveSheet();

        $this->assertSame('ID Pendaftaran', $lembar->getCell('A1')->getValue());
        $this->assertSame('Waktu Lunas', $lembar->getCell('L1')->getValue());
        $this->assertSame($lunas['id_pendaftaran'], $lembar->getCell('A2')->getValue());
        $this->assertSame('SMP KARANGAN JAYA', $lembar->getCell('C2')->getValue());
        $this->assertSame('99999913', $lembar->getCell('D2')->getValue());
        $this->assertSame('SMP/MTs', $lembar->getCell('E2')->getValue());
        $this->assertEquals(1, $lembar->getCell('G2')->getValue());
        $this->assertEquals(200000, $lembar->getCell('J2')->getValue());
        $this->assertSame('Lunas', $lembar->getCell('K2')->getValue());
        $this->assertNull($lembar->getCell('A3')->getValue(), 'Pendaftaran Menunggu tidak ikut.');

        $semua = $this->bacaExcel($this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/pendaftaran/excel'))->getActiveSheet();
        $this->assertSame(3, $semua->getHighestDataRow());
        $this->assertSame('Menunggu Konfirmasi Pembayaran', $semua->getCell('K3')->getValue());
    }

    public function testRincianMenampilkanSemuaBagian(): void
    {
        $p = $this->buatPendaftaranPanel([
            'foto'             => true,
            'penanggung_jawab' => [['nama' => 'Ibu Karangan', 'nomor_wa' => '6281200000001']],
            'atlet'            => [
                $this->atletPanel(['nama' => 'Budi Karangan', 'nisn' => '9900177771', 'nomor' => ['lari60putra', 'frog']]),
                ...$this->timPanel('kanga', 4),
            ],
        ]);

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $hasil->assertOK();

        foreach ([
            $p['id_pendaftaran'], 'SD KARANGAN MAJU', '99999911', 'SD/MI', 'Kudus', 'Ibu Karangan', '0812-0000-0001',
            'Budi Karangan', '9900177771', '12 April 2017', 'KU 10', 'Lari 60 m putra', 'Frog Jump putra',
            "Kanga's Escape putra", 'Rp100.000', 'Rp300.000', 'Rp400.000', 'Menunggu Konfirmasi Pembayaran', 'Belum lunas',
        ] as $teks) {
            $hasil->assertSee($teks);
        }

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(5, $xpath->query('//table[@id="tabel-atlet"]/tbody/tr')->length);
        $this->assertSame(1, $xpath->query('//div[contains(@class, "tim-beregu")]')->length);
        $this->assertSame(4, $xpath->query('//div[contains(@class, "tim-beregu")]//li')->length);

        // Foto penanggung jawab lewat rute admin.
        $foto = site_url('admin/pendaftaran/' . $p['id'] . '/foto/' . $p['penanggung_jawab'][0]);
        // Gambar kecil dan gambar besar di jendela (K76).
        $this->assertSame(2, $xpath->query('//img[@src="' . $foto . '"]')->length);
        $this->assertSame(1, $xpath->query('//div[contains(@class, "modal")]//img[@src="' . $foto . '"]')->length);
        // Kotak "tanpa gambar" bukti bayar (F6-03) tidak dihitung.
        $this->assertSame(0, $xpath->query('//*[contains(@class, "tanpa-gambar")][not(ancestor::section[@id="bukti-bayar"])]')->length);

        // Tombol tindakan dengan jendela konfirmasi.
        $lunas = $xpath->query('//form[@action="' . site_url('admin/pendaftaran/' . $p['id'] . '/lunas') . '"]')->item(0);
        $this->assertNotNull($lunas);
        $this->assertStringContainsString('5 atlet yang belum punya nomor dada akan mendapat nomor baru', $lunas->getAttribute('data-konfirmasi'));
        $this->assertSame(0, $xpath->query('//form[@action="' . site_url('admin/pendaftaran/' . $p['id'] . '/menunggu') . '"]')->length);

        $hapus = $xpath->query('//form[@action="' . site_url('admin/pendaftaran/' . $p['id'] . '/hapus') . '"]')->item(0);
        $this->assertNotNull($hapus);
        $this->assertStringContainsString($p['id_pendaftaran'], $hapus->getAttribute('data-konfirmasi'));
        $this->assertStringContainsString('SD KARANGAN MAJU', $hapus->getAttribute('data-konfirmasi'));
        $this->assertStringContainsString('5 atlet', $hapus->getAttribute('data-konfirmasi'));
        $this->assertStringContainsString('NISN atletnya bisa didaftarkan lagi', $hapus->getAttribute('data-konfirmasi'));
        $this->assertStringNotContainsString('tidak dipakai ulang', $hapus->getAttribute('data-konfirmasi'));

        foreach ([$lunas, $hapus] as $formulir) {
            $this->assertSame(1, (new DOMXPath($formulir->ownerDocument))->query('.//input[@name="' . csrf_token() . '"]', $formulir)->length);
        }

        $this->assertStringContainsString(aset('js/konfirmasi.js'), (string) $hasil->response()->getBody());
    }

    public function testRincianLunasMenampilkanNomorDadaDanTombolKembali(): void
    {
        $p = $this->buatPendaftaranPanel([
            'status' => 'lunas',
            'atlet'  => [$this->atletPanel(['nomor_dada' => '2001']), $this->atletPanel(['nomor_dada' => '2002'])],
        ]);

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $xpath = $this->xpathDari($hasil);

        $nomor = [];

        foreach ($xpath->query('//table[@id="tabel-atlet"]//td[contains(@class, "nomor-dada")]') as $sel) {
            $nomor[] = trim($sel->textContent);
        }

        $this->assertSame(['2001', '2002'], $nomor);
        $this->assertSame(0, $xpath->query('//form[@action="' . site_url('admin/pendaftaran/' . $p['id'] . '/lunas') . '"]')->length);

        $menunggu = $xpath->query('//form[@action="' . site_url('admin/pendaftaran/' . $p['id'] . '/menunggu') . '"]')->item(0);
        $this->assertNotNull($menunggu);
        $this->assertStringContainsString('Nomor dada atlet tetap', $menunggu->getAttribute('data-konfirmasi'));

        $hapus = $xpath->query('//form[@action="' . site_url('admin/pendaftaran/' . $p['id'] . '/hapus') . '"]')->item(0);
        $this->assertStringContainsString('Nomor dada yang sudah dibagikan tidak dipakai ulang', $hapus->getAttribute('data-konfirmasi'));

        $this->assertStringNotContainsString('Belum lunas', trim($xpath->query('//*[@id="waktu-lunas"]')->item(0)->textContent));
    }

    public function testFotoYangTidakAdaTampilSebagaiTanpaGambar(): void
    {
        // Satu tanpa foto, satu fotonya tercatat tetapi berkasnya hilang.
        $p = $this->buatPendaftaranPanel(['penanggung_jawab' => [
            ['nama' => 'Ibu Tanpa Foto', 'nomor_wa' => '6281200000001'],
            ['nama' => 'Bapak Foto Hilang', 'nomor_wa' => '6281200000002'],
        ]]);
        $this->db->table('penanggung_jawab')->where('id', $p['penanggung_jawab'][1])->update(['foto' => 'penanggung-jawab/' . str_repeat('a', 32) . '.jpg']);

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $xpath = $this->xpathDari($hasil);

        // Kotak "tanpa gambar" bukti bayar (F6-03) tidak dihitung.
        $this->assertSame(2, $xpath->query('//*[contains(@class, "tanpa-gambar ")][not(ancestor::section[@id="bukti-bayar"])]')->length);
        $this->assertSame(0, $xpath->query('//img[contains(@src, "/foto/")]')->length);
        $hasil->assertSee('Foto Ibu Tanpa Foto');

        foreach ($p['penanggung_jawab'] as $id) {
            $this->assertTidakDitemukan('admin/pendaftaran/' . $p['id'] . '/foto/' . $id);
        }
    }

    public function testFotoDisajikanHanyaUntukPendaftarannya(): void
    {
        $p    = $this->buatPendaftaranPanel(['foto' => true]);
        $lain = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null]]);

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/foto/' . $p['penanggung_jawab'][0]);
        $hasil->assertOK();
        $this->assertStringStartsWith('image/jpeg', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));
        $this->assertSame('nosniff', $hasil->response()->getHeaderLine('X-Content-Type-Options'));

        $info = getimagesizefromstring((string) $hasil->response()->getBody());
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);

        // Penanggung jawab pendaftaran lain lewat alamat pendaftaran ini.
        $this->assertTidakDitemukan('admin/pendaftaran/' . $p['id'] . '/foto/' . $lain['penanggung_jawab'][0]);
        $this->assertTidakDitemukan('admin/pendaftaran/999/foto/' . $p['penanggung_jawab'][0]);
    }

    public function testRincianTidakDitemukan(): void
    {
        $this->assertTidakDitemukan('admin/pendaftaran/999');
    }

    /**
     * @param array<string, mixed> $parameter
     *
     * @return array<string, mixed>
     */
    private function json(array $parameter = []): array
    {
        $hasil = $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/pendaftaran/data', $parameter);
        $hasil->assertOK();
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));

        return json_decode((string) $hasil->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function bacaExcel(TestResponse $hasil): Spreadsheet
    {
        ob_start();
        $hasil->response()->sendBody();
        $isi = (string) ob_get_clean();

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-excel-');
        file_put_contents($berkas, $isi);

        try {
            return IOFactory::createReader('Xlsx')->load($berkas);
        } finally {
            unlink($berkas);
        }
    }

    private function assertTidakDitemukan(string $alamat): void
    {
        try {
            $this->bukaSebagaiAdmin($alamat);
            $this->fail("{$alamat} seharusnya tidak ditemukan.");
        } catch (PageNotFoundException $e) {
            $this->assertSame(404, $e->getCode(), $alamat);
        }
    }
}
