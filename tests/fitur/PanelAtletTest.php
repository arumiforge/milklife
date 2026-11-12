<?php

use App\Controllers\Admin\AtletController;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Daftar atlet di panel (F6-03): halaman `admin/atlet`, JSON
 * `admin/atlet/data`, dan Excel `admin/atlet/excel` dengan saringan yang
 * sama. Semua nama dan NISN karangan.
 *
 * @internal
 */
final class PanelAtletTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    /**
     * @var list<string>
     */
    private array $berkasSementara = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();

        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        parent::tearDown();
    }

    /**
     * Tiga pendaftaran karangan:
     * - A (SD, Menunggu): putra KU 10 (Lari 60 m putra, Frog Jump) dan putri KU 10 (Lari 60 m putri);
     * - B (SMP, Lunas): putri KU 15 bernomor dada 4001, NISN diawali nol;
     * - C (SD, Menunggu): tim Kanga's Escape 4 putra.
     *
     * @return array{a: array<string, mixed>, b: array<string, mixed>, c: array<string, mixed>}
     */
    private function isiContoh(): array
    {
        $a = $this->buatPendaftaranPanel([
            'atlet' => [
                $this->atletPanel(['nama' => 'Budi Karangan', 'nisn' => '9900100001', 'nomor' => ['lari60putra', 'frog']]),
                $this->atletPanel(['nama' => 'Siti Karangan', 'nisn' => '9900100002', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2016-08-30', 'nomor' => ['lari60putri']]),
            ],
        ]);
        $b = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
            'status'  => 'lunas',
            'atlet'   => [$this->atletPanel(['nama' => 'Dewi Karangan', 'nisn' => '0012345678', 'ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
        ]);
        $c = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4)]);

        return ['a' => $a, 'b' => $b, 'c' => $c];
    }

    /**
     * @param array<string, string> $kueri
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    private function data(array $kueri = []): array
    {
        $hasil = $this->bukaSebagaiAdmin('admin/atlet/data' . ($kueri === [] ? '' : '?' . http_build_query($kueri)));
        $hasil->assertOK();

        return json_decode($hasil->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $kueri
     *
     * @return list<string>
     */
    private function namaAtlet(array $kueri = []): array
    {
        $nama = array_column($this->data($kueri + ['length' => '100'])['data'], 'nama');
        sort($nama);

        return $nama;
    }

    /**
     * Isi lembar Excel sebagai larik baris (baris pertama judul).
     *
     * @return list<list<mixed>>
     */
    private function bacaExcel(TestResponse $hasil): array
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $isi = (string) ob_get_clean();

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-atlet-') . '.xlsx';
        file_put_contents($berkas, $isi);
        $this->berkasSementara[] = $berkas;

        $buku = IOFactory::load($berkas);

        try {
            return $buku->getActiveSheet()->toArray(null, false, false, false);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    // ---------------------------------------------------------------
    // Halaman
    // ---------------------------------------------------------------

    public function testHalamanMenampilkanTabelSaringanDanTombolExcel(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/atlet');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $tabel = $xpath->query('//table[@id="tabel-atlet"][@data-tabel-admin]')->item(0);
        $this->assertNotNull($tabel);
        $this->assertSame(site_url('admin/atlet/data'), $tabel->getAttribute('data-alamat'));
        $this->assertSame('#saringan-atlet', $tabel->getAttribute('data-saringan'));
        $this->assertSame('#excel-atlet', $tabel->getAttribute('data-excel'));
        $this->assertSame(AtletController::PESAN_KOSONG, $tabel->getAttribute('data-kosong'));

        $kolom = [];

        foreach ($xpath->query('.//thead/tr/th', $tabel) as $th) {
            $kolom[] = $th->getAttribute('data-kolom');
        }

        $this->assertSame(['nomor_dada', 'nama', 'nisn', 'jenis_kelamin', 'tanggal_lahir', 'kelompok_umur', 'nomor_lomba', 'nama_sekolah', 'id_pendaftaran', 'status'], $kolom);
        $this->assertSame('tautan', $xpath->query('.//th[@data-kolom="id_pendaftaran"]', $tabel)->item(0)->getAttribute('data-bentuk'));

        // Saringan: kelompok umur, jenis kelamin, nomor lomba (dikelompokkan per kelompok umur), status.
        $nama = [];

        foreach ($xpath->query('//form[@id="saringan-atlet"]//select') as $select) {
            $nama[] = $select->getAttribute('name');
        }

        $this->assertSame(['kelompok_umur', 'jenis_kelamin', 'nomor_lomba', 'status'], $nama);
        $this->assertSame(['', (string) $this->idInduk['ku10'], (string) $this->idInduk['ku12'], (string) $this->idInduk['ku15']], $this->nilaiPilihan($xpath, 'kelompok_umur'));
        $this->assertSame(['', 'L', 'P'], $this->nilaiPilihan($xpath, 'jenis_kelamin'));
        $this->assertSame(['', 'lunas', 'menunggu'], $this->nilaiPilihan($xpath, 'status'));
        $this->assertSame(['KU 10', 'KU 12', 'KU 15'], array_map(
            static fn (DOMNode $g): string => $g->getAttribute('label'),
            iterator_to_array($xpath->query('//select[@name="nomor_lomba"]/optgroup')),
        ));
        $this->assertSame(
            'Frog Jump putra',
            trim($xpath->query('//select[@name="nomor_lomba"]//option[@value="' . $this->idInduk['frog'] . '"]')->item(0)->textContent),
        );

        $this->assertSame(site_url('admin/atlet/excel'), $xpath->query('//a[@id="excel-atlet"]')->item(0)->getAttribute('href'));

        // Hanya untuk melihat dan mengunduh: tidak ada formulir yang mengubah data.
        $this->assertSame(0, $xpath->query('//form[@method="post" and not(@action="' . site_url('admin/keluar') . '")]')->length);
    }

    /**
     * @return list<string>
     */
    private function nilaiPilihan(DOMXPath $xpath, string $nama): array
    {
        $nilai = [];

        foreach ($xpath->query('//select[@name="' . $nama . '"]//option') as $pilihan) {
            $nilai[] = $pilihan->getAttribute('value');
        }

        return $nilai;
    }

    public function testYangBelumMasukDialihkan(): void
    {
        $this->isiContoh();

        foreach (['admin/atlet', 'admin/atlet/data', 'admin/atlet/excel'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }
    }

    // ---------------------------------------------------------------
    // JSON
    // ---------------------------------------------------------------

    public function testKosong(): void
    {
        $data = $this->data(['draw' => '3']);

        $this->assertSame(['draw' => 3, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], $data);
    }

    public function testSatuBarisPerAtletDenganSemuaKolom(): void
    {
        $contoh = $this->isiContoh();
        $data   = $this->data(['draw' => '1']);

        $this->assertSame(1, $data['draw']);
        $this->assertSame(7, $data['recordsTotal']);
        $this->assertSame(7, $data['recordsFiltered']);
        $this->assertCount(7, $data['data']);

        $budi = array_values(array_filter($data['data'], static fn (array $b): bool => $b['nama'] === 'Budi Karangan'))[0];
        $this->assertNull($budi['nomor_dada']);
        $this->assertSame('9900100001', $budi['nisn']);
        $this->assertSame('Laki-laki', $budi['jenis_kelamin']);
        $this->assertSame('2017-04-12', $budi['tanggal_lahir']);
        $this->assertSame('KU 10', $budi['kelompok_umur']);
        $this->assertSame('Lari 60 m putra, Frog Jump putra', $budi['nomor_lomba']);
        $this->assertSame('SD KARANGAN MAJU', $budi['nama_sekolah']);
        $this->assertSame('menunggu', $budi['status']);
        $this->assertSame(
            ['alamat' => site_url('admin/pendaftaran/' . $contoh['a']['id']), 'teks' => $contoh['a']['id_pendaftaran']],
            $budi['id_pendaftaran'],
        );

        $dewi = array_values(array_filter($data['data'], static fn (array $b): bool => $b['nama'] === 'Dewi Karangan'))[0];
        $this->assertSame('4001', $dewi['nomor_dada']);
        $this->assertSame('0012345678', $dewi['nisn']);
        $this->assertSame('Perempuan', $dewi['jenis_kelamin']);
        $this->assertSame('KU 15', $dewi['kelompok_umur']);
        $this->assertSame('Lari 80 m putri', $dewi['nomor_lomba']);
        $this->assertSame('lunas', $dewi['status']);
    }

    public function testUrutanBawaanMenurutKelompokUmurLaluNomorDada(): void
    {
        $this->isiContoh();

        $kelompok = array_column($this->data()['data'], 'kelompok_umur');
        $this->assertSame(['KU 10', 'KU 10', 'KU 10', 'KU 10', 'KU 10', 'KU 10', 'KU 15'], $kelompok);
    }

    public function testSaringanKelompokUmurJenisKelaminNomorLombaDanStatus(): void
    {
        $this->isiContoh();

        $this->assertSame(['Dewi Karangan'], $this->namaAtlet(['kelompok_umur' => (string) $this->idInduk['ku15']]));
        $this->assertSame([], $this->namaAtlet(['kelompok_umur' => (string) $this->idInduk['ku12']]));
        $this->assertSame(['Dewi Karangan', 'Siti Karangan'], $this->namaAtlet(['jenis_kelamin' => 'P']));
        $this->assertSame(['Budi Karangan'], $this->namaAtlet(['nomor_lomba' => (string) $this->idInduk['frog']]));
        $this->assertCount(4, $this->namaAtlet(['nomor_lomba' => (string) $this->idInduk['kanga']]));
        $this->assertSame(['Dewi Karangan'], $this->namaAtlet(['status' => 'lunas']));
        $this->assertCount(6, $this->namaAtlet(['status' => 'menunggu']));

        // Gabungan saringan.
        $this->assertSame(['Siti Karangan'], $this->namaAtlet(['jenis_kelamin' => 'P', 'status' => 'menunggu']));
        $this->assertSame([], $this->namaAtlet(['nomor_lomba' => (string) $this->idInduk['frog'], 'status' => 'lunas']));

        $tersaring = $this->data(['jenis_kelamin' => 'P']);
        $this->assertSame(7, $tersaring['recordsTotal']);
        $this->assertSame(2, $tersaring['recordsFiltered']);
    }

    public function testNilaiSaringanYangTidakDikenalDiabaikan(): void
    {
        $this->isiContoh();

        foreach ([
            ['nomor_lomba' => '1 OR 1=1'],
            ['kelompok_umur' => 'abc'],
            ['jenis_kelamin' => 'X'],
            ['status' => "lunas' --"],
        ] as $kueri) {
            $this->assertCount(7, $this->namaAtlet($kueri), json_encode($kueri));
        }
    }

    public function testPencarianNamaNisnNomorDadaSekolahDanIdPendaftaran(): void
    {
        $contoh = $this->isiContoh();

        $this->assertSame(['Budi Karangan'], $this->namaAtlet(['search' => ['value' => 'budi']]));
        $this->assertSame(['Dewi Karangan'], $this->namaAtlet(['search' => ['value' => '0012345678']]));
        $this->assertSame(['Dewi Karangan'], $this->namaAtlet(['search' => ['value' => '4001']]));
        $this->assertSame(['Dewi Karangan'], $this->namaAtlet(['search' => ['value' => 'smp karangan']]));
        $this->assertCount(4, $this->namaAtlet(['search' => ['value' => $contoh['c']['id_pendaftaran']]]));
        $this->assertSame([], $this->namaAtlet(['search' => ['value' => '%']]));
    }

    public function testUrutPilihanPengguna(): void
    {
        $this->isiContoh();

        $data = $this->data(['order' => [['column' => '1', 'dir' => 'desc']], 'columns' => [1 => ['data' => 'nama']]])['data'];
        $this->assertSame('Siti Karangan', $data[0]['nama']);

        // Kolom yang tidak bisa diurutkan diabaikan; urutan bawaan dipakai.
        $data = $this->data(['order' => [['column' => '6', 'dir' => 'desc']], 'columns' => [6 => ['data' => 'nomor_lomba']]])['data'];
        $this->assertSame('KU 15', $data[6]['kelompok_umur']);
    }

    // ---------------------------------------------------------------
    // Excel
    // ---------------------------------------------------------------

    public function testExcelMemuatSemuaAtletDenganJudulKolom(): void
    {
        $contoh = $this->isiContoh();
        $hasil  = $this->bukaSebagaiAdmin('admin/atlet/excel');

        $hasil->assertOK();
        $tanggapan = $hasil->response();
        $tanggapan->buildHeaders();
        $this->assertStringContainsString('Atlet_' . date('Ymd') . '.xlsx', $tanggapan->getHeaderLine('Content-Disposition'));

        $baris = $this->bacaExcel($hasil);
        $this->assertSame(
            ['Nomor Dada', 'Nama', 'NISN', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelompok Umur', 'Nomor Lomba', 'Sekolah', 'ID Pendaftaran', 'Status'],
            $baris[0],
        );
        $this->assertCount(8, $baris);

        $dewi = array_values(array_filter($baris, static fn (array $b): bool => $b[1] === 'Dewi Karangan'))[0];
        $this->assertSame(['4001', 'Dewi Karangan', '0012345678', 'Perempuan', '1 Mei 2012', 'KU 15', 'Lari 80 m putri', 'SMP KARANGAN JAYA', $contoh['b']['id_pendaftaran'], 'Lunas'], $dewi);

        $budi = array_values(array_filter($baris, static fn (array $b): bool => $b[1] === 'Budi Karangan'))[0];
        $this->assertSame([null, 'Budi Karangan', '9900100001', 'Laki-laki', '12 April 2017', 'KU 10', 'Lari 60 m putra, Frog Jump putra', 'SD KARANGAN MAJU', $contoh['a']['id_pendaftaran'], 'Menunggu'], $budi);
    }

    public function testExcelMengikutiSaringanPencarianDanUrutan(): void
    {
        $this->isiContoh();

        $baris = $this->bacaExcel($this->bukaSebagaiAdmin('admin/atlet/excel?' . http_build_query(['jenis_kelamin' => 'P'])));
        $nama  = array_column(array_slice($baris, 1), 1);
        sort($nama);
        $this->assertSame(['Dewi Karangan', 'Siti Karangan'], $nama);

        $baris = $this->bacaExcel($this->bukaSebagaiAdmin('admin/atlet/excel?' . http_build_query(['nomor_lomba' => (string) $this->idInduk['kanga'], 'status' => 'menunggu'])));
        $this->assertCount(5, $baris);

        $baris = $this->bacaExcel($this->bukaSebagaiAdmin('admin/atlet/excel?' . http_build_query(['cari' => 'karangan', 'urut' => 'nama', 'arah' => 'desc', 'status' => 'menunggu'])));
        $this->assertSame('Siti Karangan', $baris[1][1]);
        $this->assertCount(7, $baris);

        // Tanpa atlet: hanya baris judul.
        $this->assertCount(1, $this->bacaExcel($this->bukaSebagaiAdmin('admin/atlet/excel?status=lunas&jenis_kelamin=L')));
    }
}
