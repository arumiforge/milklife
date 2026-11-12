<?php

use App\Controllers\CekPendaftaranController;
use CodeIgniter\Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\CekDataPendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Halaman cek pendaftaran (F5-02, rancangan 8).
 *
 * @internal
 */
final class CekPendaftaranTest extends UjiBasisData
{
    use CekDataPendaftaran;
    use KirimFormulir;
    use StreamFilterTrait;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-11-11 10:00:00', 'Asia/Jakarta');
        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testBelumLengkapTanpaFormulir(): void
    {
        $this->isiDataCek();

        $hasil = $this->get('cek-pendaftaran?kode=20300001');
        $hasil->assertOK();
        $hasil->assertSee('Cek pendaftaran belum bisa dipakai');
        $hasil->assertDontSee('SD NEGERI CONTOH 1');
        $this->assertSame(0, $this->xpathDari($hasil)->query('//form[@role="search"]')->length);

        // Tombol di halaman depan nonaktif, menu tetap menaut.
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame('true', $xpath->query('//*[contains(@class, "tombol-cek")]')->item(0)->getAttribute('aria-disabled'));
        $this->assertSame(site_url('cek-pendaftaran'), $xpath->query('//nav[@aria-label="Menu utama"]//a[contains(., "Cek Pendaftaran")]')->item(0)->getAttribute('href'));
    }

    public function testFormulirKosongDanTombolAktif(): void
    {
        $this->lengkapiPengaturanCek();

        $hasil = $this->get('cek-pendaftaran');
        $hasil->assertOK();
        $hasil->assertSee('NPSN atau ID pendaftaran');
        $hasil->assertDontSee('alert-warning');

        $tombol = $this->xpathDari($this->get('/'))->query('//*[contains(@class, "tombol-cek")]')->item(0);
        $this->assertSame(site_url('cek-pendaftaran'), $tombol->getAttribute('href'));
    }

    public function testNpsnMenampilkanSemuaPendaftaranBerurutan(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $hasil = $this->get('cek-pendaftaran?kode=+2030 0001+');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('SD NEGERI CONTOH 1', trim($xpath->query('//h2[@id="cek-sekolah"]')->item(0)->textContent));
        $kartu = $xpath->query('//article[contains(@class, "cek-kartu")]');
        $this->assertSame(2, $kartu->length);
        $this->assertStringContainsString('MAC2A0000126', $kartu->item(0)->textContent);
        $this->assertStringContainsString('MAC2A0000226', $kartu->item(1)->textContent);

        // Data penuh, nilai tersimpan, status, nomor dada.
        $hasil->assertSee('9900000002');
        $hasil->assertSee('1 Mei 2016');
        $hasil->assertSee('Lompat Jauh');
        $hasil->assertSee('Rp650.000');
        $hasil->assertSee('Menunggu Konfirmasi Pembayaran');
        $hasil->assertSee('Lunas');
        $hasil->assertSee('1001');
        $hasil->assertSee('tanpa biaya tim, tim sudah terdaftar');
        $hasil->assertSee('Ibu Karangan');
        $this->assertStringContainsString('Rp650.000', $xpath->query('//*[contains(@class, "cek-total-semua")]')->item(0)->textContent);
        $this->assertSame(site_url('bukti/MAC2A0000226'), $xpath->query('//a[contains(., "Cetak bukti")]')->item(1)->getAttribute('href'));

        // Foto penanggung jawab tidak pernah tampil.
        $hasil->assertDontSee('foto-penanggung-jawab');
        $this->assertSame(0, $xpath->query('//main//img')->length);
    }

    public function testIdMenampilkanSatuPendaftaranDanDiEscape(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $hasil = $this->get('cek-pendaftaran?kode=mac2b0000326');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(1, $xpath->query('//article[contains(@class, "cek-kartu")]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class, "cek-total-semua")]')->length);
        $hasil->assertSee('Tanpa NPSN');
        $hasil->assertSee('SMP &lt;b&gt;Contoh&lt;/b&gt; &amp; Mandiri', null, false);
        $hasil->assertSee('GILANG &lt;SCRIPT&gt;', null, false);
        $hasil->assertDontSee('<SCRIPT>', null, false);
        $this->assertSame('MAC2B0000326', $xpath->query('//input[@name="kode"]')->item(0)->getAttribute('value'));
    }

    public function testPesanUntukMasukanKosongKeliruDanTidakDitemukan(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $this->get('cek-pendaftaran?kode=')->assertSee('NPSN atau ID pendaftaran belum diisi');
        $this->get('cek-pendaftaran?kode=12345')->assertSee('Tulis 8 angka NPSN sekolah (contoh 20300001)');
        $this->get('cek-pendaftaran?kode=20399999')->assertSee('Belum ada pendaftaran dengan NPSN 20399999');
        $this->get('cek-pendaftaran?kode=MAC2A9999926')->assertSee('Pendaftaran dengan ID MAC2A9999926 tidak ditemukan');
    }

    public function testPembatasanLaju(): void
    {
        $this->lengkapiPengaturanCek();
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= CekPendaftaranController::BATAS_PENCARIAN; $ke++) {
            $this->get('cek-pendaftaran?kode=20399999')->assertStatus(200);
        }

        // Membuka formulir saja tidak dihitung.
        $this->get('cek-pendaftaran')->assertStatus(200);

        $hasil = $this->get('cek-pendaftaran?kode=20300001');
        $hasil->assertStatus(429);
        $hasil->assertSee('Terlalu banyak pencarian dalam waktu singkat. Tunggu 6 detik lagi');
        $hasil->assertHeader('Retry-After', '6');
    }
}
