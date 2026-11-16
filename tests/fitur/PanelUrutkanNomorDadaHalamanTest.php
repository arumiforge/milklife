<?php

use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman Urutkan Nomor Dada (K111) dan keterangannya di daftar atlet,
 * rincian pendaftaran, Cek Pendaftaran, dan bukti PDF.
 *
 * @internal
 */
final class PanelUrutkanNomorDadaHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    private const ALAMAT = 'admin/atlet/nomor-dada';

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

    public function testHalamanHanyaUntukAdmin(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
    }

    public function testDaftarAtletPunyaTombolKeHalamanIni(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/atlet');

        $this->assertSame(site_url(self::ALAMAT), $this->xpathDari($hasil)->query('//a[@id="tombol-urutkan-nomor-dada"]')->item(0)->getAttribute('href'));
        $hasil->assertDontSeeElement('#keterangan-nomor-dada');
    }

    public function testRingkasanDanTanpaKetikUrutkanDitolak(): void
    {
        $p = $this->buatPendaftaranPanel();
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertOK();
        $halaman->assertSee('Nomor atlet (BIB) belum pernah diurutkan ulang.');
        $this->assertStringContainsString('2 atlet dari 1 sekolah', $this->teksSimpul($this->xpathDari($halaman)->query('//*[@id="ringkasan-jumlah"]')->item(0)));

        $this->assertGagalDialihkan(
            $this->kirimSebagaiAdmin(self::ALAMAT, ['konfirmasi' => 'ya']),
            self::ALAMAT,
            'Nomor atlet (BIB) belum diurutkan. Ketik URUTKAN di kotak konfirmasi, lalu tekan tombolnya lagi.',
        );
        $this->assertNull(setting('NomorDada.diurutkan_pada'));
    }

    public function testUrutkanLaluKeteranganTampilDiPanelCekDanPdf(): void
    {
        $status = new StatusPembayaran();
        $x      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $y      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel()]]);
        $status->tandaiLunas($y['id']);
        $status->tandaiLunas($x['id']);

        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin(self::ALAMAT, ['konfirmasi' => 'urutkan']),
            self::ALAMAT,
            'Nomor atlet (BIB) 2 atlet dari 2 sekolah sudah diurutkan ulang per sekolah.',
        );
        $this->assertSame('2001', $this->db->table('atlet')->where('id', $x['atlet'][0])->get()->getRow()->nomor_dada);

        // Panel: halaman ini, daftar atlet, dan rincian pendaftaran.
        $this->bukaSebagaiAdmin(self::ALAMAT)->assertSee('Terakhir diurutkan ulang');
        $this->bukaSebagaiAdmin('admin/atlet')->assertSeeElement('#keterangan-nomor-dada');
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $x['id'])->assertSeeElement('#keterangan-nomor-dada');

        // Cek Pendaftaran.
        $cek = $this->get('cek-pendaftaran?kode=' . $x['id_pendaftaran']);
        $cek->assertOK();
        $cek->assertSee('Nomor atlet (BIB) sudah diurutkan ulang per sekolah oleh panitia');

        // Bukti PDF: keterangan di blok penutup, dasar halaman terakhir.
        $penutup = view('bukti/pdf_qr', ['keteranganNomorDada' => App\Libraries\Panel\UrutkanNomorDada::keterangan('lunas')]);
        $this->assertStringContainsString('class="keterangan-nomor-dada"', $penutup);
        $this->assertStringContainsString('diurutkan ulang per sekolah', $penutup);
        $this->assertStringNotContainsString('keterangan-nomor-dada', view('bukti/pdf_qr', ['keteranganNomorDada' => null]));
        $this->assertStringStartsWith('%PDF', (new BuktiPendaftaran((new DataPendaftaran())->cariId($x['id_pendaftaran'])))->pdf());
    }

    private function teksSimpul(DOMNode $simpul): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $simpul->textContent));
    }
}
