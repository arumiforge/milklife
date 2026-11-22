<?php

use App\Libraries\Bantuan\JawabanFaq;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\UnduhanKlasemen;
use App\Libraries\IsiHalamanDepan;
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

        $semua = (new HasilLomba())->klasemen();
        $this->assertSame(Klasemen::hitung((new HasilLomba())->juara(false, 8)), $semua, 'Bawaan: metode medali, poin 7-5-4-3-2-1.');
        $this->assertSame(['SD KARANGAN MAJU', 'SD KARANGAN LAIN'], array_column($semua['A']['baris'], 'sekolah'));
        $this->assertSame([2, 0], array_column($semua['A']['baris'], 'emas'));

        // Publik: hanya Lari 60 m putra yang terbit.
        $publik = $this->get('hasil/klasemen');
        $publik->assertOK();
        $terbit = (new HasilLomba())->klasemen(true);
        $this->assertSame([1, 1], [$terbit['A']['baris'][0]['emas'], $terbit['A']['baris'][1]['perak']]);
        $publik->assertDontSee('belum terbit');
        $publik->assertSee('juara I terbanyak');
        $publik->assertSee('data-kolom="poin"', null);
        $publik->assertSee('SD KARANGAN LAIN');
        $publik->assertDontSee('data-medali-juara-umum');
        $this->get('hasil')->assertSee('Lihat klasemen sekolah');

        // Dengan berkas G9-06: piala di judul jenjang dan medali di samping lencana juara umum.
        foreach (['piala', 'medali-emas', 'medali-perak', 'medali-perunggu'] as $berkas) {
            $this->taruhGambarUji('layar/' . $berkas . '.webp');
        }

        $xpath = $this->xpathDari($this->get('hasil/klasemen'));
        $this->assertSame(1, $xpath->query('//section//h2/img[@data-piala-klasemen][contains(@src, "aset/gambar/layar/piala.webp")]')->length);
        $this->assertStringContainsString('medali-emas.webp', (string) $xpath->query('//tr[@data-juara-umum="1"]//img[@data-medali-juara-umum="1"]')->item(0)?->getAttribute('src'));
        $this->assertStringContainsString('medali-perak.webp', (string) $xpath->query('//tr[@data-juara-umum="2"]//img[@data-medali-juara-umum="2"]')->item(0)?->getAttribute('src'));

        // Unduhan.
        $this->assertStringStartsWith('%PDF', $this->get('hasil/klasemen/pdf')->response()->getBody());
        $this->assertStringStartsWith('%PDF', $this->bukaSebagaiAdmin('admin/klasemen/pdf')->response()->getBody());
        $this->assertStringContainsString('Juara umum I', (new UnduhanKlasemen($semua, false))->html(false));

        $lembar = $this->bacaExcel($this->bukaSebagaiAdmin('admin/klasemen/excel'));
        $this->assertSame(['Peringkat', 'Sekolah', 'NPSN', 'Juara I', 'Juara II', 'Juara III', 'Jumlah', 'Poin', 'Juara umum'], $lembar[0]);
        $this->assertSame(['SD KARANGAN MAJU', '99999911', 2, 14, 'Juara umum I'], [$lembar[1][1], (string) $lembar[1][2], (int) $lembar[1][3], (int) $lembar[1][7], $lembar[1][8]]);
    }

    public function testMetodePoinDipakaiDiSemuaTampilan(): void
    {
        $this->siapkanJuara();

        // Poin peringkat 2 dibuat sama dengan peringkat 1: KARANGAN MAJU (2 × juara I) dan KARANGAN LAIN
        // (2 × juara II) sama-sama 14 poin; pemecah seri juara I memenangkan KARANGAN MAJU.
        $simpan = $this->kirimSebagaiAdmin('admin/pengaturan/hasil', ['metode' => 'poin', 'poin' => ['7', '7', '4', '3', '2', '1', '0', '0']]);
        $simpan->assertRedirectTo(site_url('admin/pengaturan/hasil'));
        $this->assertSame('poin', setting('Klasemen.metode'));
        $this->assertSame('7,7,4,3,2,1,0,0', setting('Klasemen.poin'));

        $baris = (new HasilLomba())->klasemen()['A']['baris'];
        $this->assertSame(['SD KARANGAN MAJU', 'SD KARANGAN LAIN'], array_column($baris, 'sekolah'));
        $this->assertSame([14, 14], array_column($baris, 'poin'));
        $this->assertSame([1, 2], array_column($baris, 'peringkat'), 'Poin sama: juara I memecah seri.');

        $admin = $this->bukaSebagaiAdmin('admin/klasemen');
        $admin->assertSee('sekolah dengan poin terbanyak');
        $admin->assertSee('Poin');

        $this->assertStringContainsString('sekolah dengan poin terbanyak', (new UnduhanKlasemen((new HasilLomba())->klasemen(), false))->html(false));
        $this->get('hasil/klasemen')->assertSee('sekolah dengan poin terbanyak');
        $this->assertSame('poin', (new IsiHalamanDepan())->hasilTerbaru()['metode']);
        $this->assertSame([7, 7], array_column((new IsiHalamanDepan())->hasilTerbaru()['juara_umum'][0]['baris'], 'poin'));
        $this->assertStringContainsString('sekolah dengan poin terbanyak (peringkat 1 = 7, 2 = 7, 3 = 4', (new JawabanFaq())->teks('{cara_juara_umum}'));
        $this->assertSame(['Peringkat', 'Sekolah', 'NPSN', 'Juara I', 'Juara II', 'Juara III', 'Jumlah', 'Poin', 'Juara umum'], $this->bacaExcel($this->bukaSebagaiAdmin('admin/klasemen/excel'))[0]);
    }

    public function testPengaturanHasilMenolakIsianSalah(): void
    {
        $halaman = $this->bukaSebagaiAdmin('admin/pengaturan/hasil');
        $halaman->assertOK();
        $this->assertMatchesRegularExpression('/id="metode-medali"[^>]*checked/', $halaman->getBody());
        $this->assertMatchesRegularExpression('/id="poin_1" name="poin\[\]" value="7"/', $halaman->getBody());
        $this->assertMatchesRegularExpression('/id="poin_8" name="poin\[\]" value="0"/', $halaman->getBody());

        $salah = $this->kirimSebagaiAdmin('admin/pengaturan/hasil', ['metode' => 'poin', 'poin' => ['7', '5', '9', '3', '2', '1', '0', '0']]);
        $salah->assertOK();
        $salah->assertSee('Poin peringkat 3 tidak boleh lebih besar dari poin peringkat 2.');
        $this->assertMatchesRegularExpression('/id="poin_3" name="poin\[\]" value="9" aria-invalid="true"/', $salah->getBody());
        $this->assertMatchesRegularExpression('/id="metode-poin"[^>]*checked/', $salah->getBody());
        $this->assertSame('medali', setting('Klasemen.metode'));

        $this->assertBelumMasukDialihkan('admin/pengaturan/hasil');
        $this->assertBelumMasukDialihkan('admin/pengaturan/hasil', 'post');
        $this->assertTokenCsrfWajib('admin/pengaturan/hasil', ['metode' => 'medali', 'poin' => ['7', '5', '4', '3', '2', '1', '0', '0']]);
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
