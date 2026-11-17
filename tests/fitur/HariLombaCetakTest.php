<?php

use App\Libraries\HariLomba\CetakBib;
use App\Libraries\HariLomba\DaftarPeserta;
use App\Libraries\HariLomba\DaftarPesertaPdf;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Cetak nomor atlet (BIB) massal dan daftar peserta per nomor lomba (F8-03).
 *
 * @internal
 */
final class HariLombaCetakTest extends UjiBasisData
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

    public function testHalamanHanyaUntukAdmin(): void
    {
        foreach (['admin/nomor-atlet/cetak', 'admin/nomor-atlet/cetak/pdf', 'admin/daftar-peserta', 'admin/daftar-peserta/1/pdf', 'admin/daftar-peserta/kelompok-umur/1/excel'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }
    }

    public function testBibHanyaAtletLunasUrutNomorAtlet(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel();
        $b      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-01', 'nomor' => ['lari60putraKu12']])]]);
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999914', 'nama' => 'SD KARANGAN MENUNGGU']]);
        $status->tandaiLunas($b['id']);
        $status->tandaiLunas($a['id']);

        $bib = new CetakBib();
        $this->assertSame(['2001', '2002', '3001'], array_column($bib->atlet(null), 'nomor_dada'));
        $this->assertSame(['3001'], array_column($bib->atlet($this->idInduk['ku12']), 'nomor_dada'));
        $this->assertSame(['nomor_dada' => '3001', 'sekolah' => 'SD KARANGAN LAIN', 'kelompok_umur' => 'KU 12'], array_diff_key($bib->atlet($this->idInduk['ku12'])[0], ['nama' => 1]));

        $halaman = $this->bukaSebagaiAdmin('admin/nomor-atlet/cetak');
        $halaman->assertOK();
        $halaman->assertSee('KU 10');
        $halaman->assertSee('3 atlet · 2 halaman');
        $this->assertSame(site_url('admin/nomor-atlet/cetak/pdf') . '?ku=' . $this->idInduk['ku10'], $this->xpathDari($halaman)->query('//div[@id="daftar-bib"]/a')->item(0)->getAttribute('href'));

        // Isi PDF: dua BIB per halaman, nama atlet berhuruf besar.
        $html = CetakBib::html($bib->atlet(null), 'MILKLIFE ATHLETICS CHALLENGE', 'Seri 2 Tahun 2026', false);
        $this->assertSame(3, substr_count($html, 'class="bib '));
        $this->assertSame(1, substr_count($html, '<pagebreak />'));
        $this->assertStringContainsString('SITI KARANGAN', $html);

        $pdf = $this->bukaSebagaiAdmin('admin/nomor-atlet/cetak/pdf?ku=semua');
        $pdf->assertOK();
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());
        $this->assertStringContainsString('BIB_Semua', $pdf->response()->getHeaderLine('Content-Disposition'));

        $this->assertStringStartsWith('%PDF', $this->bukaSebagaiAdmin('admin/nomor-atlet/cetak/pdf?ku=' . $this->idInduk['ku12'])->response()->getBody());
    }

    public function testBibKelompokUmurTidakAdaDanKosong(): void
    {
        $this->bukaSebagaiAdmin('admin/nomor-atlet/cetak')->assertSeeElement('#bib-kosong');

        $this->expectException(PageNotFoundException::class);
        $this->bukaSebagaiAdmin('admin/nomor-atlet/cetak/pdf?ku=9999');
    }

    public function testDaftarPesertaIndividuHanyaLunasDenganTandaHadir(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel();
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999914', 'nama' => 'SD KARANGAN MENUNGGU']]);
        $status->tandaiLunas($a['id']);
        $this->db->table('atlet')->where('id', $a['atlet'][0])->update(['hadir_pada' => '2026-12-09 07:00:00']);

        $daftar = (new DaftarPeserta())->susun($this->idInduk['lari60putra']);
        $this->assertSame('Lari 60 m putra', $daftar['nama']);
        $this->assertSame('KU 10', $daftar['kelompok_umur']);
        $this->assertCount(1, $daftar['peserta']);
        $this->assertSame(['nomor_dada' => '2001', 'sekolah' => 'SD KARANGAN MAJU', 'hadir' => true, 'peran' => null], array_diff_key($daftar['peserta'][0], ['nama' => 1]));
        $this->assertSame([['no' => 1, 'nomor_dada' => '2001', 'nama' => $daftar['peserta'][0]['nama'], 'sekolah' => 'SD KARANGAN MAJU', 'hadir' => 'Hadir', 'catatan' => '']], DaftarPeserta::barisExcel($daftar));

        $halaman = $this->bukaSebagaiAdmin('admin/daftar-peserta');
        $halaman->assertOK();
        $halaman->assertSee('Lari 60 m putra');
        $halaman->assertSee('Semua nomor:');

        $html = (new DaftarPesertaPdf([$daftar]))->html(false);
        $this->assertStringContainsString('Daftar Peserta Lari 60 m putra – KU 10', $html);
        $this->assertStringContainsString('Catatan juri', $html);
        $this->assertStringContainsString('&#10003;', $html);

        $pdf = $this->bukaSebagaiAdmin('admin/daftar-peserta/' . $this->idInduk['lari60putra'] . '/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());
        $this->assertStringContainsString('DaftarPeserta_KU10Lari60mputra', $pdf->response()->getHeaderLine('Content-Disposition'));

        $lembar = $this->bacaExcel($this->bukaSebagaiAdmin('admin/daftar-peserta/' . $this->idInduk['lari60putra'] . '/excel'))->getSheet(0)->toArray();
        $this->assertSame(['No', 'No. Atlet', 'Nama', 'Sekolah', 'Hadir', 'Catatan juri'], $lembar[0]);
        $this->assertSame('2001', $lembar[1][1]);
        $this->assertSame('Hadir', $lembar[1][4]);
    }

    public function testDaftarPesertaBereguPerReguIntiLaluCadangan(): void
    {
        $status = new StatusPembayaran();
        // Regu A: 4 inti dan 1 cadangan (cadangan didaftarkan lebih dulu), regu B: 4 inti.
        $cadangan = $this->atletPanel(['nomor' => ['kanga'], 'regu' => ['kanga' => ['regu' => 'A', 'peran' => 'cadangan']], 'nama' => 'Cadangan Karangan']);
        $reguA    = $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'A', 'peran' => 'inti']]]);
        $reguB    = $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'B', 'peran' => 'inti']]]);
        $p        = $this->buatPendaftaranPanel(['atlet' => [$cadangan, ...$reguA, ...$reguB]]);
        $status->tandaiLunas($p['id']);

        $daftar = (new DaftarPeserta())->susun($this->idInduk['kanga']);
        $this->assertSame('beregu', $daftar['jenis']);
        $this->assertSame([], $daftar['peserta']);
        $this->assertSame(['SD KARANGAN MAJU – Regu A', 'SD KARANGAN MAJU – Regu B'], array_column($daftar['regu'], 'nama'));
        $this->assertSame(['Inti', 'Inti', 'Inti', 'Inti', 'Cadangan'], array_column($daftar['regu'][0]['anggota'], 'peran'));
        $this->assertSame('Cadangan Karangan', $daftar['regu'][0]['anggota'][4]['nama']);
        $this->assertCount(4, $daftar['regu'][1]['anggota']);

        $html = (new DaftarPesertaPdf([$daftar]))->html(false);
        $this->assertSame(2, substr_count($html, 'class="regu"'));
        $this->assertStringContainsString('2 regu', $html);

        // Semua nomor KU 10: PDF satu nomor per halaman, Excel satu lembar per nomor.
        $semua   = (new DaftarPeserta())->susunKelompokUmur($this->idInduk['ku10']);
        $nomorKu = $this->db->table('nomor_lomba')->where('kelompok_umur_id', $this->idInduk['ku10'])->countAllResults();
        $this->assertCount($nomorKu, $semua);
        $this->assertSame($nomorKu - 1, substr_count((new DaftarPesertaPdf($semua))->html(false), '<pagebreak />'));

        $pdf = $this->bukaSebagaiAdmin('admin/daftar-peserta/kelompok-umur/' . $this->idInduk['ku10'] . '/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());

        $buku = $this->bacaExcel($this->bukaSebagaiAdmin('admin/daftar-peserta/kelompok-umur/' . $this->idInduk['ku10'] . '/excel'));
        $this->assertSame($nomorKu, $buku->getSheetCount());
        $kanga = $buku->getSheetByName('KU 10 Kanga s Escape putra')->toArray();
        $this->assertSame(['No', 'No. Atlet', 'Nama', 'Sekolah', 'Regu', 'Peran', 'Hadir', 'Catatan juri'], $kanga[0]);
        $this->assertSame(['Regu A', 'Cadangan'], [$kanga[5][4], $kanga[5][5]]);
        $this->assertSame([1, 'Regu B'], [(int) $kanga[6][0], $kanga[6][4]]);
    }

    public function testDaftarPesertaNomorTidakAda(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->bukaSebagaiAdmin('admin/daftar-peserta/9999/excel');
    }

    private function bacaExcel(TestResponse $hasil): Spreadsheet
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $isi = (string) ob_get_clean();

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-peserta-') . '.xlsx';
        file_put_contents($berkas, $isi);

        try {
            return IOFactory::load($berkas);
        } finally {
            @unlink($berkas);
        }
    }
}
