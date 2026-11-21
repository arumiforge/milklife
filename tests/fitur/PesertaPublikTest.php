<?php

use App\Controllers\PesertaController;
use App\Libraries\MenuPublik;
use App\Libraries\Panel\StatusPembayaran;
use App\Libraries\PesertaPublik;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman Peserta publik (`/peserta`, `/peserta/data`, F9-12): hanya Lunas,
 * tanpa NISN, tanggal lahir, foto, dan data official, sakelar bawaan mati.
 *
 * @internal
 */
final class PesertaPublikTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();

        Services::resetSingle('throttler');
        service('cache')->clean();
        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    /**
     * Dua sekolah Lunas (satu dengan dua regu Kanga's Escape) dan satu Menunggu.
     *
     * @return array{lunas: array<string, mixed>, lain: array<string, mixed>, menunggu: array<string, mixed>}
     */
    private function siapkanPeserta(): array
    {
        $status = new StatusPembayaran();

        $lunas = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN MAJU', 'npsn' => '99999911'],
            'foto'    => true,
            'atlet'   => [
                $this->atletPanel(['nama' => 'Budi Karangan', 'nisn' => '9911100001', 'tanggal_lahir' => '2017-04-12', 'nomor' => ['lari60putra', 'frog']]),
                $this->atletPanel(['nama' => 'Siti Karangan', 'nisn' => '9911100002', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2016-08-30', 'nomor' => ['lari60putri']]),
                ...$this->timPanel('kanga', 4),
                ...array_map(fn (array $a): array => $a + ['regu' => ['kanga' => ['regu' => 'B', 'peran' => 'inti']]], $this->timPanel('kanga', 4)),
            ],
            'penanggung_jawab' => [['nama' => 'Ibu Official Rahasia', 'nomor_wa' => '6281299990001']],
        ]);
        $lain = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN LAIN', 'npsn' => '99999913'],
            'atlet'   => [
                $this->atletPanel(['nama' => 'Dewi Karangan', 'nisn' => '9911100003', 'ku' => 'ku12', 'tanggal_lahir' => '2015-03-01', 'nomor' => ['lari60putraKu12']]),
                ...$this->timPanel('kanga', 4),
            ],
        ]);
        $menunggu = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN MENUNGGU', 'npsn' => '99999914'],
            'atlet'   => [$this->atletPanel(['nama' => 'Tono Belum Lunas', 'nisn' => '9911100004'])],
        ]);

        $status->tandaiLunas($lunas['id']);
        $status->tandaiLunas($lain['id']);

        return ['lunas' => $lunas, 'lain' => $lain, 'menunggu' => $menunggu];
    }

    public function testSakelarBawaanMatiMenjawab404DanMenuTersembunyi(): void
    {
        $this->assertFalse(PesertaController::tampil());

        foreach (['peserta', 'peserta/data'] as $alamat) {
            try {
                $this->get($alamat);
                $this->fail('Alamat ' . $alamat . ' seharusnya 404 selama sakelar mati.');
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertNotContains('Peserta', array_column(MenuPublik::butir(), 'label'));

        setting('HalamanDepan.tampil_peserta', true);
        $this->assertContains('Peserta', array_column(MenuPublik::butir(), 'label'));
        $this->get('peserta')->assertOK();
    }

    public function testHalamanHanyaLunasTanpaDataPribadi(): void
    {
        $data = $this->siapkanPeserta();
        setting('HalamanDepan.tampil_peserta', true);

        $hasil = $this->get('peserta');
        $hasil->assertOK();
        $hasil->assertHeader('X-Robots-Tag', 'noindex');
        $html = (string) $hasil->getBody();

        $this->assertStringContainsString('Budi Karangan', $html);
        $this->assertStringContainsString('Dewi Karangan', $html);
        $this->assertStringNotContainsString('Tono Belum Lunas', $html);
        $this->assertStringNotContainsString('SD KARANGAN MENUNGGU', $html);
        $this->assertDataPribadiTidakAda($html);

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Peserta', trim($xpath->query('//main//h1')->item(0)->textContent));
        // Kartu (ponsel) dan baris tabel (layar lebar), di luar templat JS.
        $total = 2 + 8 + 1 + 4;
        $this->assertSame($total, $xpath->query('//ul[@data-daftar-kartu]/li[@data-peserta]')->length);
        $this->assertSame($total, $xpath->query('//tbody[@data-daftar-baris]/tr[@data-peserta]')->length);
        $this->assertSame(1, $xpath->query('//meta[@name="robots"][@content="noindex"]')->length);
        $hasil->assertSee(aset('js/peserta.js'));
        $hasil->assertSee('Menampilkan 1–15 dari 15 atlet');
        $this->assertSame(1, $xpath->query('//header//a[@href="' . site_url('peserta') . '"][@aria-current="page"]')->length);

        // Atlet Menunggu juga tidak ada di daftar pilihan nomor (KU 15 tanpa peserta Lunas).
        $this->assertSame(0, $xpath->query('//select[@id="saring-ku"]/option[.="KU 15"]')->length);
        $this->assertSame(1, $xpath->query('//select[@id="saring-ku"]/option[.="KU 12"]')->length);
        $this->assertNotEmpty($data['menunggu']['atlet']);
    }

    public function testJsonHanyaKunciAmanDanReguK108(): void
    {
        $this->siapkanPeserta();
        setting('HalamanDepan.tampil_peserta', true);

        $hasil = $this->get('peserta/data');
        $hasil->assertOK();
        $hasil->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertDataPribadiTidakAda((string) $hasil->getJSON());

        $json = json_decode((string) $hasil->getJSON(), true);
        $this->assertSame(['baris', 'total', 'halaman', 'jumlah_halaman', 'per_halaman'], array_keys($json));
        $this->assertSame(15, $json['total']);

        foreach ($json['baris'] as $baris) {
            $this->assertSame(PesertaPublik::KUNCI_BARIS, array_keys($baris));
        }

        $peta = [];
        foreach ($json['baris'] as $b) {
            $peta[$b['sekolah']][] = $b;
        }

        // Nomor atlet urut per sekolah (sekolah Lunas lebih dulu mendapat nomor lebih kecil).
        $nomorAtlet = array_column($json['baris'], 'nomor_atlet');
        $urut       = $nomorAtlet;
        sort($urut);
        $this->assertSame($urut, $nomorAtlet);
        $this->assertNotContains(null, $nomorAtlet);

        $budi = array_values(array_filter($json['baris'], static fn (array $b): bool => $b['nama'] === 'Budi Karangan'))[0];
        $this->assertSame(['Lari 60 m putra', 'Frog Jump putra'], $budi['nomor']);
        $this->assertSame('KU 10', $budi['kelompok_umur']);
        $this->assertSame('Putra', $budi['jenis_kelamin']);

        // K108: sekolah dengan dua regu menulis nama regunya; regu tunggal tanpa nama regu.
        $nomorMaju = array_merge(...array_column($peta['SD KARANGAN MAJU'], 'nomor'));
        $this->assertContains("Kanga's Escape putra Regu A", $nomorMaju);
        $this->assertContains("Kanga's Escape putra Regu B", $nomorMaju);
        $nomorLain = array_merge(...array_column($peta['SD KARANGAN LAIN'], 'nomor'));
        $this->assertContains("Kanga's Escape putra", $nomorLain);
        $this->assertNotContains("Kanga's Escape putra Regu A", $nomorLain);
    }

    public function testCariDanSaringan(): void
    {
        $this->siapkanPeserta();
        setting('HalamanDepan.tampil_peserta', true);

        $nama = fn (string $kueri): array => array_column(json_decode((string) $this->get('peserta/data?' . $kueri)->getJSON(), true)['baris'], 'nama');

        $this->assertSame(['Budi Karangan'], $nama('q=budi'));
        $this->assertSame(['Budi Karangan'], $nama('q=' . rawurlencode('karangan  BUDI')), 'Semua kata harus cocok, huruf besar tidak dibedakan.');
        $this->assertCount(5, $nama('q=' . rawurlencode('karangan lain')), 'Nama sekolah ikut dicari.');
        $this->assertSame([], $nama('q=tono'), 'Atlet Menunggu tidak pernah tampil.');
        $this->assertSame([], $nama('q=9911100001'), 'NISN tidak bisa dipakai mencari.');

        $budi = json_decode((string) $this->get('peserta/data?q=budi')->getJSON(), true)['baris'][0]['nomor_atlet'];
        $this->assertSame(['Budi Karangan'], $nama('q=' . $budi), 'Nomor atlet bisa dicari.');

        $this->assertSame(['Dewi Karangan'], $nama('ku=' . $this->idInduk['ku12']));
        $this->assertSame(['Siti Karangan'], $nama('jk=P'));
        $this->assertSame(['Budi Karangan'], $nama('nomor=' . $this->idInduk['frog']));
        $this->assertCount(12, $nama('nomor=' . $this->idInduk['kanga']));
        $this->assertCount(15, $nama('jk=X&ku=abc&nomor=-1'), 'Saringan tidak sah diabaikan.');
    }

    public function testPaginasiServerTanpaJavaScript(): void
    {
        $besar = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN BESAR', 'npsn' => '99999915'],
            'atlet'   => array_map(fn (int $i): array => $this->atletPanel(['nama' => sprintf('Atlet Urut %02d', $i)]), range(1, 45)),
        ]);
        (new StatusPembayaran())->tandaiLunas($besar['id']);
        setting('HalamanDepan.tampil_peserta', true);

        $hasil = $this->get('peserta?halaman=3');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(5, $xpath->query('//ul[@data-daftar-kartu]/li')->length);
        $hasil->assertSee('Halaman 3 dari 3');
        $hasil->assertSee('Menampilkan 41–45 dari 45 atlet');
        $this->assertSame(1, $xpath->query('//a[@data-ke-halaman="sebelum"][@href="' . site_url('peserta') . '?halaman=2"]')->length);
        $this->assertSame(1, $xpath->query('//a[@data-ke-halaman="berikut"][@aria-disabled="true"]')->length);

        // Halaman di luar jangkauan dijepit ke halaman terakhir.
        $json = json_decode((string) $this->get('peserta/data?halaman=99')->getJSON(), true);
        $this->assertSame(3, $json['halaman']);
        $this->assertSame(PesertaPublik::PER_HALAMAN, $json['per_halaman']);

        // Saringan ikut di tautan halaman.
        $saring = $this->xpathDari($this->get('peserta?q=urut&jk=L'));
        $this->assertSame(1, $saring->query('//a[@data-ke-halaman="berikut"][@href="' . site_url('peserta') . '?q=urut&jk=L&halaman=2"]')->length);
    }

    public function testKeadaanKosong(): void
    {
        setting('HalamanDepan.tampil_peserta', true);

        $hasil = $this->get('peserta');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(0, $xpath->query('//div[@data-kosong-semua][contains(@class, "hidden")]')->length);
        $hasil->assertSee('Belum ada peserta');

        $cari = $this->xpathDari($this->get('peserta?q=tidakada'));
        $this->assertSame(0, $cari->query('//div[@data-kosong-cari][contains(@class, "hidden")]')->length);
    }

    public function testDibatasiThrottler(): void
    {
        setting('HalamanDepan.tampil_peserta', true);
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= PesertaController::BATAS_PERMINTAAN; $ke++) {
            $this->get($ke % 2 === 0 ? 'peserta/data' : 'peserta')->assertStatus(200);
        }

        $json = $this->get('peserta/data');
        $json->assertStatus(429);
        $this->assertSame(['pesan' => PesertaController::PESAN_BATAS], json_decode((string) $json->getJSON(), true));

        $halaman = $this->get('peserta');
        $halaman->assertStatus(429);
        $halaman->assertSee(PesertaController::PESAN_BATAS);
    }

    public function testSakelarDiPengaturanHalamanDepan(): void
    {
        $this->assertBelumMasukDialihkan('admin/pengaturan/halaman-depan/peserta', 'post');
        $this->assertTokenCsrfWajib('admin/pengaturan/halaman-depan/peserta', ['tampil_peserta' => '1']);

        $halaman = $this->bukaSebagaiAdmin('admin/pengaturan/halaman-depan');
        $halaman->assertOK();
        $xpath = $this->xpathDari($halaman);
        $this->assertSame(1, $xpath->query('//form[@action="' . site_url('admin/pengaturan/halaman-depan/peserta') . '"]//input[@name="tampil_peserta"][not(@checked)]')->length);

        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/halaman-depan/peserta', ['tampil_peserta' => '1']);
        $hasil->assertRedirect();
        $this->assertTrue(PesertaController::tampil());
        $this->assertSame(1, $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan/halaman-depan'))->query('//input[@name="tampil_peserta"][@checked]')->length);

        // Kotak tidak dicentang = mati lagi.
        $this->kirimSebagaiAdmin('admin/pengaturan/halaman-depan/peserta', []);
        $this->assertFalse(PesertaController::tampil());
    }

    private function assertDataPribadiTidakAda(string $keluaran): void
    {
        foreach (['9911100001', '9911100002', '9911100003', '2017-04-12', '2016-08-30', '2015-03-01', '12-04-2017', '12 April 2017', 'Ibu Official Rahasia', '6281299990001', 'unggahan/'] as $rahasia) {
            $this->assertStringNotContainsString($rahasia, $keluaran, 'Data pribadi tidak boleh keluar: ' . $rahasia);
        }

        // Semua NISN atlet di basis data (termasuk tim karangan) tidak pernah keluar.
        foreach (db_connect()->table('atlet')->select('nisn, tanggal_lahir')->get()->getResultArray() as $a) {
            $this->assertStringNotContainsString((string) $a['nisn'], $keluaran);
            $this->assertStringNotContainsString((string) $a['tanggal_lahir'], $keluaran);
        }
    }
}
