<?php

use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Rekap panitia dan angka dasbor (F6-06): `admin/rekap/<jenis>` beserta
 * JSON, Excel, dan PDF-nya, dengan saringan status (K60). Semua nama, NISN,
 * dan NPSN karangan.
 *
 * @internal
 */
final class PanelRekapTest extends UjiBasisData
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
     * Empat pendaftaran karangan dari dua sekolah:
     * - A (SD MAJU, Menunggu): Budi putra KU 10 (Lari 60 m, Frog Jump), Siti putri KU 10 (Lari 60 m);
     * - B (SMP JAYA, Lunas): Dewi putri KU 15 bernomor dada 4001;
     * - C (SD MAJU susulan, Lunas): tim Kanga's Escape 4 putra;
     * - D (SD MAJU susulan, Menunggu): 1 putra tambahan tim Kanga's Escape tanpa biaya tim.
     *
     * @return array<string, array<string, mixed>>
     */
    private function isiContoh(): array
    {
        return [
            'a' => $this->buatPendaftaranPanel([
                'atlet' => [
                    $this->atletPanel(['nama' => 'Budi Karangan', 'nomor' => ['lari60putra', 'frog']]),
                    $this->atletPanel(['nama' => 'Siti Karangan', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2016-08-30', 'nomor' => ['lari60putri']]),
                ],
            ]),
            'b' => $this->buatPendaftaranPanel([
                'sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
                'status'  => 'lunas',
                'atlet'   => [$this->atletPanel(['nama' => 'Dewi Karangan', 'ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
            ]),
            'c' => $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => $this->timPanel('kanga', 4)]),
            'd' => $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 1), 'susulan' => ['kanga']]),
        ];
    }

    /**
     * @return array{kolom: array<string, mixed>, baris: list<array<string, mixed>>, jumlah: array<string, mixed>|null}
     */
    private function data(string $jenis, string $status = ''): array
    {
        $hasil = $this->bukaSebagaiAdmin("admin/rekap/{$jenis}/data" . ($status === '' ? '' : '?status=' . $status));
        $hasil->assertOK();

        return json_decode($hasil->getJSON(), true, 512, JSON_THROW_ON_ERROR);
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

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-rekap-') . '.xlsx';
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
    // Angka tiap rekap
    // ---------------------------------------------------------------

    public function testPesertaPerNomorLombaDenganSaringanStatus(): void
    {
        $this->isiContoh();

        $semua = $this->data('nomor-lomba');
        $this->assertSame(['kelompok_umur', 'nomor_lomba', 'nomor_dada', 'nama', 'nisn', 'nama_sekolah', 'kabupaten', 'status'], array_keys($semua['kolom']));
        $this->assertCount(9, $semua['baris']);
        $this->assertNull($semua['jumlah']);

        // Urut kelompok umur lalu nomor: KU 15 paling akhir.
        $dewi = end($semua['baris']);
        $this->assertSame(['KU 15', 'Lari 80 m putri', '4001', 'Dewi Karangan', 'SMP KARANGAN JAYA', 'Jepara', 'Lunas'], [
            $dewi['kelompok_umur'], $dewi['nomor_lomba'], $dewi['nomor_dada'], $dewi['nama'], $dewi['nama_sekolah'], $dewi['kabupaten'], $dewi['status'],
        ]);

        $this->assertCount(5, $this->data('nomor-lomba', 'lunas')['baris']);
        $this->assertCount(4, $this->data('nomor-lomba', 'menunggu')['baris']);
        $this->assertSame(['Menunggu'], array_values(array_unique(array_column($this->data('nomor-lomba', 'menunggu')['baris'], 'status'))));

        // Status lain berarti semua.
        $this->assertCount(9, $this->data('nomor-lomba', 'lain')['baris']);
    }

    public function testTimBereguMenggabungAnggotaSusulanSekolahYangSama(): void
    {
        $this->isiContoh();

        $semua = $this->data('tim-beregu')['baris'];
        $this->assertCount(1, $semua);
        $this->assertSame('SD KARANGAN MAJU', $semua[0]['nama_sekolah']);
        $this->assertSame("Kanga's Escape putra", $semua[0]['nomor_lomba']);
        $this->assertSame(5, (int) $semua[0]['jumlah_anggota']);

        $this->assertSame(4, (int) $this->data('tim-beregu', 'lunas')['baris'][0]['jumlah_anggota']);
        $this->assertSame(1, (int) $this->data('tim-beregu', 'menunggu')['baris'][0]['jumlah_anggota']);
    }

    public function testPerSekolah(): void
    {
        $c = $this->isiContoh();

        $rekap = $this->data('sekolah');
        $baris = array_column($rekap['baris'], null, 'nama_sekolah');

        $this->assertSame(['SD KARANGAN MAJU', 'SMP KARANGAN JAYA'], array_keys($baris));
        $maju = $baris['SD KARANGAN MAJU'];
        $this->assertSame(['99999911', 'SD/MI', 3, 7, 1, $c['a']['total'] + $c['c']['total'] + $c['d']['total']], [
            $maju['npsn'], $maju['jenjang'], (int) $maju['jumlah_pendaftaran'], (int) $maju['jumlah_atlet'], $maju['jumlah_tim'], (int) $maju['tagihan'],
        ]);
        $this->assertSame(0, $baris['SMP KARANGAN JAYA']['jumlah_tim']);

        $total = $c['a']['total'] + $c['b']['total'] + $c['c']['total'] + $c['d']['total'];
        $this->assertSame(['npsn' => 'Jumlah', 'jumlah_pendaftaran' => 4, 'jumlah_atlet' => 8, 'jumlah_tim' => 1, 'tagihan' => $total], $rekap['jumlah']);

        $lunas = $this->data('sekolah', 'lunas');
        $this->assertSame(['jumlah_pendaftaran' => 2, 'jumlah_atlet' => 5, 'jumlah_tim' => 1, 'tagihan' => $c['b']['total'] + $c['c']['total']], array_slice($lunas['jumlah'], 1));
    }

    public function testPerKelompokUmur(): void
    {
        $this->isiContoh();

        $rekap = $this->data('kelompok-umur');
        $angka = static fn (array $rekap): array => array_map(
            static fn (array $b): array => [(int) $b['putra'], (int) $b['putri'], $b['jumlah_atlet'], (int) $b['tim']],
            array_column($rekap['baris'], null, 'nama'),
        );

        $this->assertSame(['KU 10' => [6, 1, 7, 1], 'KU 12' => [0, 0, 0, 0], 'KU 15' => [0, 1, 1, 0]], $angka($rekap));
        $this->assertSame(['nama' => 'Jumlah', 'putra' => 6, 'putri' => 2, 'jumlah_atlet' => 8, 'tim' => 1], $rekap['jumlah']);
        $this->assertSame(['KU 10' => [4, 0, 4, 1], 'KU 12' => [0, 0, 0, 0], 'KU 15' => [0, 1, 1, 0]], $angka($this->data('kelompok-umur', 'lunas')));
        $this->assertSame(['KU 10' => [2, 1, 3, 1], 'KU 12' => [0, 0, 0, 0], 'KU 15' => [0, 0, 0, 0]], $angka($this->data('kelompok-umur', 'menunggu')));
    }

    public function testKeuanganSelaluMemuatKeduaStatus(): void
    {
        $c = $this->isiContoh();

        $rekap = $this->data('keuangan');
        $this->assertSame([$c['a']['id_pendaftaran'], $c['b']['id_pendaftaran'], $c['c']['id_pendaftaran'], $c['d']['id_pendaftaran']], array_column($rekap['baris'], 'id_pendaftaran'));

        $lunas = $c['b']['total'] + $c['c']['total'];
        $belum = $c['a']['total'] + $c['d']['total'];
        $this->assertSame($lunas + $belum, $rekap['jumlah']['total']);
        $this->assertSame($lunas, $rekap['jumlah']['lunas']);
        $this->assertSame($belum, $rekap['jumlah']['belum']);
        $this->assertSame(['Menunggu', 'Lunas', 'Lunas', 'Menunggu'], array_column($rekap['baris'], 'status'));

        // Saringan status tidak berlaku untuk keuangan.
        $this->assertSame($rekap, $this->data('keuangan', 'lunas'));
    }

    // ---------------------------------------------------------------
    // Halaman, Excel, PDF
    // ---------------------------------------------------------------

    public function testHalamanMenampilkanTabSaringanTabelDanUnduhan(): void
    {
        $this->isiContoh();

        $hasil = $this->bukaSebagaiAdmin('admin/rekap?status=lunas');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        // Tanpa jenis: rekap peserta per nomor lomba.
        $this->assertSame('Peserta per Nomor Lomba', trim($xpath->query('//ul[@id="jenis-rekap"]//a[@aria-current="page"]')->item(0)->textContent));
        $this->assertSame(5, $xpath->query('//table[@id="tabel-rekap"]/tbody/tr')->length);
        $this->assertSame('Lunas', trim($xpath->query('//nav[@id="saringan-status"]/a[@aria-current="page"]')->item(0)->textContent));
        $this->assertSame(site_url('admin/rekap/nomor-lomba/excel') . '?status=lunas', $xpath->query('//a[@id="excel-rekap"]')->item(0)->getAttribute('href'));
        $this->assertSame(site_url('admin/rekap/nomor-lomba/pdf') . '?status=lunas', $xpath->query('//a[@id="pdf-rekap"]')->item(0)->getAttribute('href'));
        $this->assertSame(site_url('admin/rekap/sekolah') . '?status=lunas', $xpath->query('//ul[@id="jenis-rekap"]//a[contains(., "Per Sekolah")]')->item(0)->getAttribute('href'));

        // Keuangan: tanpa saringan, dengan baris jumlah.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/rekap/keuangan?status=lunas'));
        $this->assertSame(0, $xpath->query('//nav[@id="saringan-status"]')->length);
        $this->assertSame(1, $xpath->query('//p[@id="keterangan-status"]')->length);
        $this->assertSame(4, $xpath->query('//table[@id="tabel-rekap"]/tbody/tr')->length);
        $this->assertSame('Jumlah', trim($xpath->query('//table[@id="tabel-rekap"]/tfoot/tr/td')->item(1)->textContent));
        $this->assertSame(site_url('admin/rekap/keuangan/excel'), $xpath->query('//a[@id="excel-rekap"]')->item(0)->getAttribute('href'));
    }

    public function testExcelMengikutiSaringanDanTerbacaUlang(): void
    {
        $c = $this->isiContoh();

        $hasil = $this->bukaSebagaiAdmin('admin/rekap/nomor-lomba/excel?status=lunas');
        $unduhan = $hasil->response();
        $unduhan->buildHeaders();
        $this->assertStringContainsString('filename="RekapNomorLombaLunas_', $unduhan->getHeaderLine('Content-Disposition'));
        $baris = $this->bacaExcel($hasil);
        $this->assertSame(['Kelompok Umur', 'Nomor Lomba', 'Nomor Dada', 'Nama', 'NISN', 'Sekolah', 'Kabupaten', 'Status'], $baris[0]);
        $this->assertCount(6, $baris);

        // Baris jumlah ikut di akhir, rupiah tetap angka.
        $baris = $this->bacaExcel($this->bukaSebagaiAdmin('admin/rekap/sekolah/excel'));
        $this->assertCount(4, $baris);
        $this->assertSame('Jumlah', end($baris)[0]);
        $this->assertSame($c['a']['total'] + $c['b']['total'] + $c['c']['total'] + $c['d']['total'], (int) end($baris)[7]);
    }

    public function testRekapGabunganSatuBerkasBanyakSheet(): void
    {
        $this->isiContoh();

        $hasil   = $this->bukaSebagaiAdmin('admin/rekap/gabungan/excel');
        $unduhan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $unduhan);
        $unduhan->buildHeaders();
        $this->assertStringContainsString('filename="Rekap_Gabungan_', $unduhan->getHeaderLine('Content-Disposition'));

        ob_start();
        $unduhan->sendBody();
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-rekap-') . '.xlsx';
        file_put_contents($berkas, (string) ob_get_clean());
        $this->berkasSementara[] = $berkas;

        $buku = IOFactory::load($berkas);
        $this->assertSame(
            ['Pendaftaran', 'Atlet', 'Peserta per Nomor Lomba', 'Tim Beregu', 'Per Sekolah', 'Per Kelompok Umur', 'Keuangan', 'Tim Tanpa Pembayar'],
            $buku->getSheetNames(),
        );
        // Lembar pendaftaran berisi semua pendaftaran contoh (judul + 4 baris), tanpa saringan status.
        $this->assertSame(5, $buku->getSheetByName('Pendaftaran')->getHighestRow());
        $buku->disconnectWorksheets();

        // Halaman rekap menautkannya.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/rekap'));
        $this->assertSame(site_url('admin/rekap/gabungan/excel'), $xpath->query('//a[@id="excel-gabungan"]')->item(0)?->getAttribute('href'));
    }

    public function testPdfSetiapRekap(): void
    {
        $this->isiContoh();
        setting('Acara.nama', 'MilkLife Athletics Challenge');
        setting('Acara.tahun', '2026');

        foreach (['nomor-lomba', 'tim-beregu', 'sekolah', 'kelompok-umur', 'keuangan'] as $jenis) {
            $tanggapan = $this->bukaSebagaiAdmin("admin/rekap/{$jenis}/pdf?status=menunggu")->response();

            $this->assertSame('application/pdf', explode(';', $tanggapan->getHeaderLine('Content-Type'))[0], $jenis);
            $this->assertStringStartsWith('%PDF', (string) $tanggapan->getBody(), $jenis);
        }

        $this->assertStringContainsString(
            'filename="RekapKelompokUmur_MilkLifeAthleticsChallenge_2026.pdf"',
            $this->bukaSebagaiAdmin('admin/rekap/kelompok-umur/pdf')->response()->getHeaderLine('Content-Disposition'),
        );
    }

    public function testKeadaanKosong(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/rekap/nomor-lomba');
        $hasil->assertOK();
        $hasil->assertSee('Belum ada pendaftaran. Rekap terisi setelah sekolah mendaftar lewat halaman Pendaftaran.');

        $this->bukaSebagaiAdmin('admin/rekap/sekolah?status=lunas')->assertSee('Belum ada data berstatus Lunas. Pilih Semua untuk melihat seluruh pendaftaran.');

        $this->assertSame([], $this->data('keuangan')['baris']);
        $this->assertNull($this->data('keuangan')['jumlah']);
        $this->assertCount(1, $this->bacaExcel($this->bukaSebagaiAdmin('admin/rekap/tim-beregu/excel')));
        $this->assertStringStartsWith('%PDF', (string) $this->bukaSebagaiAdmin('admin/rekap/keuangan/pdf')->response()->getBody());
    }

    // ---------------------------------------------------------------
    // Dasbor
    // ---------------------------------------------------------------

    public function testDasborMenampilkanAngka(): void
    {
        $c = $this->isiContoh();

        $hasil = $this->bukaSebagaiAdmin('admin/dasbor');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $angka = [];

        foreach ($xpath->query('//*[@id="angka-pendaftaran"]//*[@data-angka]') as $kartu) {
            $angka[$kartu->getAttribute('data-angka')] = trim($xpath->query('.//*[contains(@class, "nilai-angka")]', $kartu)->item(0)->textContent);
        }

        $lunas = $c['b']['total'] + $c['c']['total'];
        $belum = $c['a']['total'] + $c['d']['total'];
        $this->assertSame([
            'pendaftaran' => '4',
            'sekolah'     => '2',
            'atlet'       => '8',
            'tim'         => '1',
            'tagihan'     => rupiah($lunas + $belum),
            'lunas'       => rupiah($lunas),
            'belum'       => rupiah($belum),
        ], $angka);

        // Keadaan kosong pendaftaran hanya bila belum ada pendaftaran; daftar kelengkapan tetap.
        $hasil->assertDontSee('Belum ada pendaftaran');
        $this->assertSame(1, $xpath->query('//*[@id="kelengkapan-pengaturan"]')->length);
    }

    public function testDasborKosongMenampilkanNol(): void
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));

        $this->assertSame('0', trim($xpath->query('//*[@data-angka="pendaftaran"]//*[contains(@class, "nilai-angka")]')->item(0)->textContent));
        $this->assertSame(rupiah(0), trim($xpath->query('//*[@data-angka="tagihan"]//*[contains(@class, "nilai-angka")]')->item(0)->textContent));
        $this->assertSame(1, $xpath->query('//*[@id="judul-kosong"]')->length);
    }
}
