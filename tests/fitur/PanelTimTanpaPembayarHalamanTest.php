<?php

use App\Libraries\Panel\HapusPendaftaran;
use App\Libraries\Panel\Rekap;
use App\Libraries\Panel\RekapPdf;
use App\Libraries\Panel\UbahPendaftaran;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Tim tanpa pembayar di halaman panel (K68, F7-03): peringatan di rincian,
 * jumlah di dasbor, daftar di rekap keuangan (halaman, Excel, PDF), kotak
 * "Bebankan biaya tim" di Ubah, dan peringatan jendela hapus serta jendela
 * simpan Ubah. Semua nama, NISN, dan NPSN karangan.
 *
 * Contoh: A membayar tim Kanga's Escape sekolah 99999911 (1 atlet Lari 60 m
 * dan 4 anggota tim), B susulan 2 anggota tanpa biaya tim.
 *
 * @internal
 */
final class PanelTimTanpaPembayarHalamanTest extends UjiBasisData
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

    public function testTanpaTimTanpaPembayarTidakAdaPeringatan(): void
    {
        [$a, $b] = $this->contoh();

        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $b['id']))->query('//*[contains(@class, "tim-tanpa-pembayar")]')->length);
        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'))->query('//*[@id="tim-tanpa-pembayar"]')->length);
        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $b['id'] . '/ubah'))->query('//input[@name="bebankan_tim[]"]')->length);

        $rekap = $this->bukaSebagaiAdmin('admin/rekap/keuangan');
        $rekap->assertSee('Semua tim beregu sudah punya pembayar biaya tim.');
        $this->assertStringNotContainsString('Perhatian', $this->kalimatHapus($b['id']));
        $this->assertStringContainsString('Perhatian: pendaftaran ini membayar biaya tim', $this->kalimatHapus($a['id']));
        $this->assertStringContainsString($b['id_pendaftaran'], $this->kalimatHapus($a['id']));
    }

    public function testDitandaiSamaDiRincianDasborDanRekapSetelahPembayarDihapus(): void
    {
        [$a, $b] = $this->contoh();

        $hapus = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['id'] . '/hapus');
        $this->assertStringContainsString("Tim Kanga's Escape putra kini tanpa pembayar biaya tim", (string) session('pesan_sukses'));
        $hapus->assertRedirectTo(site_url('admin/pendaftaran'));

        // Rincian B: peringatan di kartu tim.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $b['id']));
        $tanda = $xpath->query('//*[contains(@class, "tim-tanpa-pembayar")]');
        $this->assertSame(1, $tanda->length);
        $this->assertSame((string) $this->idInduk['kanga'], $tanda->item(0)->getAttribute('data-nomor'));
        $this->assertStringContainsString('Rp 100.000', $tanda->item(0)->textContent);

        // Dasbor: jumlahnya.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));
        $this->assertSame('1', trim($xpath->query('//*[@id="tim-tanpa-pembayar"]//strong')->item(0)->textContent));

        // Rekap keuangan: halaman, JSON, Excel, PDF.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/rekap/keuangan'));
        $baris = $xpath->query('//table[@id="tabel-tim-tanpa-pembayar"]/tbody/tr');
        $this->assertSame(1, $baris->length);
        $this->assertStringContainsString($b['id_pendaftaran'], $baris->item(0)->textContent);

        $json = json_decode($this->bukaSebagaiAdmin('admin/rekap/keuangan/data')->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $json['tim_tanpa_pembayar']['baris']);

        $lembar = $this->bacaExcel($this->bukaSebagaiAdmin('admin/rekap/keuangan/excel'));
        $this->assertSame(['Keuangan', 'Tim Tanpa Pembayar'], array_keys($lembar));
        $this->assertSame('Biaya Tim Belum Ditagih', $lembar['Tim Tanpa Pembayar'][0][5]);
        $this->assertSame("Kanga's Escape putra", $lembar['Tim Tanpa Pembayar'][1][1]);
        $this->assertSame(100000, (int) $lembar['Tim Tanpa Pembayar'][1][5]);
        $this->assertCount(2, $lembar['Tim Tanpa Pembayar']);

        $html = (new RekapPdf('keuangan', null, (new Rekap())->susun('keuangan')))->html(false);
        $this->assertStringContainsString('Tim Tanpa Pembayar Biaya Tim', $html);
        $this->assertStringContainsString(esc($b['id_pendaftaran']), $html);

        $pdf = $this->bukaSebagaiAdmin('admin/rekap/keuangan/pdf');
        $this->assertStringStartsWith('%PDF', (string) $pdf->response()->getBody());
    }

    public function testExcelRekapLainTetapSatuLembar(): void
    {
        $this->contoh();

        $this->assertSame(['Tim Beregu'], array_keys($this->bacaExcel($this->bukaSebagaiAdmin('admin/rekap/tim-beregu/excel'))));
    }

    public function testBebankanLewatUbahMenghilangkanTanda(): void
    {
        [$a, $b] = $this->contoh();
        (new HapusPendaftaran())->hapus($a['id']);

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $b['id'] . '/ubah'));
        $kotak = $xpath->query('//input[@name="bebankan_tim[]"]');
        $this->assertSame(1, $kotak->length);
        $this->assertSame((string) $this->idInduk['kanga'], $kotak->item(0)->getAttribute('value'));
        $this->assertStringContainsString('Bebankan biaya tim ke pendaftaran ini', $xpath->query('//*[@id="tim-tanpa-pembayar"]')->item(0)->textContent);

        // Tim B tinggal 2 anggota: lengkapi sekaligus membebankan biayanya.
        $isian                 = $this->isianDari($b['id'], 2);
        $isian['bebankan_tim'] = [(string) $this->idInduk['kanga']];

        $pratinjau = json_decode((string) $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'], $isian + ['pratinjau' => '1'])->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $pratinjau['galat']);
        $this->assertSame([0, 100000], [$pratinjau['total_lama'], $pratinjau['total']]);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'], $isian);

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran/' . $b['id'],
            'Perubahan pendaftaran ' . $b['id_pendaftaran'] . ' tersimpan. Total sekarang Rp 100.000. Biaya 1 tim tanpa pembayar kini dibebankan ke pendaftaran ini.',
        );
        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $b['id']))->query('//*[contains(@class, "tim-tanpa-pembayar")]')->length);
        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'))->query('//*[@id="tim-tanpa-pembayar"]')->length);
    }

    public function testBebankanYangDitolakDitampilkanUlangTetapTercentang(): void
    {
        [$a, $b] = $this->contoh();
        (new HapusPendaftaran())->hapus($a['id']);

        // Tim belum lengkap: galat kelengkapan, centangnya tetap.
        $isian                 = $this->isianDari($b['id']);
        $isian['bebankan_tim'] = [(string) $this->idInduk['kanga']];

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'], $isian);
        $hasil->assertStatus(200);
        $this->assertTrue($this->xpathDari($hasil)->query('//input[@name="bebankan_tim[]"]')->item(0)->hasAttribute('checked'));
        $this->assertSame(0, (int) $this->db->table('pendaftaran')->where('id', $b['id'])->get()->getRow()->total);
    }

    public function testSimpanUbahYangMenghapusTimMemperingatkan(): void
    {
        [$a] = $this->contoh();

        $isian          = $this->isianDari($a['id']);
        $isian['atlet'] = array_values(array_filter($isian['atlet'], fn (array $atlet): bool => ! in_array((string) $this->idInduk['kanga'], $atlet['nomor'], true)));

        $pratinjau = json_decode((string) $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['id'], $isian + ['pratinjau' => '1'])->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([], $pratinjau['galat']);
        $this->assertSame('Tim akan kehilangan pembayar', $pratinjau['judul']);
        $this->assertStringContainsString('Perhatian: pendaftaran ini membayar biaya tim', $pratinjau['kalimat']);

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['id'], $isian);

        $this->assertStringContainsString("Tim Kanga's Escape putra kini tanpa pembayar biaya tim", (string) session('pesan_sukses'));
        $this->assertSame(1, (new Rekap())->ringkasan()['tim_tanpa_pembayar']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function contoh(): array
    {
        return [
            $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(), ...$this->timPanel('kanga', 4)]]),
            $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 2), 'susulan' => ['kanga']]),
        ];
    }

    private function kalimatHapus(int $id): string
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $id));

        return $xpath->query('//form[contains(@action, "/hapus")]')->item(0)->getAttribute('data-konfirmasi');
    }

    /**
     * Isian formulir seperti yang dikirim peramban, ditambah `$tambahKanga`
     * atlet baru Kanga's Escape.
     *
     * @return array<string, mixed>
     */
    private function isianDari(int $id, int $tambahKanga = 0): array
    {
        $isian = (new UbahPendaftaran())->isian($id);
        $atlet = array_map(static fn (array $a): array => [
            'id'            => (string) $a['id'],
            'nama'          => $a['nama'],
            'nisn'          => $a['nisn'],
            'tanggal_lahir' => $a['tanggal_lahir'],
            'jenis_kelamin' => $a['jenis_kelamin'],
            'nomor'         => array_map('strval', $a['nomor']),
        ], $isian['atlet']);

        foreach ($this->timPanel('kanga', $tambahKanga) as $a) {
            $atlet[] = [
                'id' => '', 'nama' => $a['nama'], 'nisn' => $a['nisn'], 'tanggal_lahir' => $a['tanggal_lahir'],
                'jenis_kelamin' => $a['jenis_kelamin'], 'nomor' => [(string) $this->idInduk['kanga']],
            ];
        }

        return [
            'sekolah'          => $isian['sekolah'],
            'penanggung_jawab' => array_map(static fn (array $o): array => [
                'id' => (string) $o['id'], 'nama' => $o['nama'], 'nomor_wa' => $o['nomor_wa'], 'foto' => '',
            ], $isian['penanggung_jawab']),
            'atlet' => $atlet,
        ];
    }

    /**
     * Semua lembar Excel: judul lembar => baris (baris pertama judul kolom).
     *
     * @return array<string, list<list<mixed>>>
     */
    private function bacaExcel(TestResponse $hasil): array
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $isi = (string) ob_get_clean();

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-tim-') . '.xlsx';
        file_put_contents($berkas, $isi);
        $this->berkasSementara[] = $berkas;

        $buku = IOFactory::load($berkas);

        try {
            $hasil = [];

            foreach ($buku->getWorksheetIterator() as $lembar) {
                $hasil[$lembar->getTitle()] = $lembar->toArray(null, false, false, false);
            }

            return $hasil;
        } finally {
            $buku->disconnectWorksheets();
        }
    }
}
