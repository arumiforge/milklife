<?php

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\UnduhanKlasemen;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Klasemen sekolah per jenjang dari babak Final (F8-05, K124): halaman admin
 * (semua nomor), halaman publik (hanya nomor terbit), unduhan PDF dan Excel.
 *
 * @internal
 */
final class KlasemenSekolahTest extends UjiBasisData
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

    public function testHalamanAdminHanyaUntukAdmin(): void
    {
        foreach (['admin/klasemen', 'admin/klasemen/pdf', 'admin/klasemen/excel'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }
    }

    public function testKlasemenAdminSemuaNomorPublikHanyaTerbit(): void
    {
        $this->siapkanJuara();

        // Admin: kedua nomor dihitung; SD KARANGAN MAJU dua juara I.
        $admin = $this->bukaSebagaiAdmin('admin/klasemen');
        $admin->assertOK();
        $admin->assertSee('SD/MI');
        $admin->assertSee('Juara umum I');
        $admin->assertSee('belum terbit');
        $admin->assertSeeElement('#belum-terbit');

        $semua = Klasemen::hitung((new HasilLomba())->juara());
        $this->assertSame(['SD KARANGAN MAJU', 'SD KARANGAN LAIN'], array_column($semua['A']['baris'], 'sekolah'));
        $this->assertSame([2, 0], array_column($semua['A']['baris'], 'emas'));

        // Publik: hanya Lari 60 m putra yang terbit.
        $publik = $this->get('hasil/klasemen');
        $publik->assertOK();
        $terbit = Klasemen::hitung((new HasilLomba())->juara(true));
        $this->assertSame([1, 1], [$terbit['A']['baris'][0]['emas'], $terbit['A']['baris'][1]['perak']]);
        $publik->assertDontSee('belum terbit');
        $publik->assertSee('SD KARANGAN LAIN');
        $this->get('hasil')->assertSee('Klasemen sekolah');

        // Unduhan.
        $this->assertStringStartsWith('%PDF', $this->get('hasil/klasemen/pdf')->response()->getBody());
        $this->assertStringStartsWith('%PDF', $this->bukaSebagaiAdmin('admin/klasemen/pdf')->response()->getBody());
        $this->assertStringContainsString('Juara umum I', (new UnduhanKlasemen($semua, false))->html(false));

        $lembar = $this->bacaExcel($this->bukaSebagaiAdmin('admin/klasemen/excel'));
        $this->assertSame(['Peringkat', 'Sekolah', 'NPSN', 'Juara I', 'Juara II', 'Juara III', 'Jumlah', 'Juara umum'], $lembar[0]);
        $this->assertSame(['SD KARANGAN MAJU', '99999911', 2, 'Juara umum I'], [$lembar[1][1], (string) $lembar[1][2], (int) $lembar[1][3], $lembar[1][7]]);
    }

    public function testKlasemenKosong(): void
    {
        $this->get('hasil/klasemen')->assertSeeElement('#klasemen-kosong');
        $this->bukaSebagaiAdmin('admin/klasemen')->assertSeeElement('#klasemen-kosong');
    }

    /**
     * Dua sekolah SD; Final Lari 60 m putra (terbit) dan Frog Jump putra (belum terbit).
     */
    private function siapkanJuara(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(['nomor' => ['lari60putra', 'frog']])]]);
        $b      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999915', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel(['nomor' => ['lari60putra', 'frog']])]]);
        $status->tandaiLunas($a['id']);
        $status->tandaiLunas($b['id']);

        $hasil = new HasilLomba();

        foreach (['lari60putra' => ['9,50', '9,80'], 'frog' => ['5,00', '4,00']] as $kunci => [$pa, $pb]) {
            $babak = $hasil->tambahBabak($this->idInduk[$kunci], 'Final', true, 'semua')['id'];
            $id    = array_column($this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray(), 'id', 'atlet_id');
            $this->assertSame([], $hasil->simpanHasil($babak, [$id[$a['atlet'][0]] => ['prestasi' => $pa], $id[$b['atlet'][0]] => ['prestasi' => $pb]]));
        }

        $this->db->table('nomor_lomba')->where('id', $this->idInduk['frog'])->update(['jenis_hasil' => 'jarak']);
        $hasil->hitungUlang((int) $this->db->table('babak')->where('nomor_lomba_id', $this->idInduk['frog'])->get()->getRow()->id, 'jarak');
        $hasil->terbitkan($this->idInduk['lari60putra'], true);
    }

    /**
     * @return list<list<mixed>>
     */
    private function bacaExcel(TestResponse $hasil): array
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-klasemen-') . '.xlsx';
        file_put_contents($berkas, (string) ob_get_clean());

        try {
            return IOFactory::load($berkas)->getSheet(0)->toArray();
        } finally {
            @unlink($berkas);
        }
    }
}
