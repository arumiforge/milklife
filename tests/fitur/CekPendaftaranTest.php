<?php

use App\Controllers\CekPendaftaranController;
use CodeIgniter\Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\CekDataPendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\TiruanSiteverify;
use Tests\Support\TurnstileUji;
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
    use TurnstileUji;
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
        $this->matikanTurnstile();
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

        // Tombol di halaman depan nonaktif (menu tidak memuat Cek Pendaftaran, K81).
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame('true', $xpath->query('//*[@data-tombol="cek"]')->item(0)->getAttribute('aria-disabled'));
    }

    public function testFormulirKosongDanTombolAktif(): void
    {
        $this->lengkapiPengaturanCek();

        $hasil = $this->get('cek-pendaftaran');
        $hasil->assertOK();
        $hasil->assertSee('NPSN atau ID pendaftaran');
        $hasil->assertDontSee('alert-warning');

        $tombol = $this->xpathDari($this->get('/'))->query('//*[@data-tombol="cek"]')->item(0);
        $this->assertSame(site_url('cek-pendaftaran'), $tombol->getAttribute('href'));
    }

    public function testNpsnMenampilkanSemuaPendaftaranBerurutan(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $hasil = $this->get('cek-pendaftaran?kode=+2030 0001+');
        $hasil->assertOK();
        // Data siswa tidak boleh disimpan tembolok.
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('SD NEGERI CONTOH 1', trim($xpath->query('//h2[@id="cek-sekolah"]')->item(0)->textContent));
        $kartu = $xpath->query('//article[contains(@class, "cek-kartu")]');
        $this->assertSame(2, $kartu->length);
        $this->assertStringContainsString('MAC2A0000126', $kartu->item(0)->textContent);
        $this->assertStringContainsString('MAC2A0000226', $kartu->item(1)->textContent);

        // Data, nilai tersimpan, status.
        $hasil->assertSee('9900000002');
        $hasil->assertSee('Lompat Jauh');
        $hasil->assertSee('Rp 650.000');
        $hasil->assertSee('Menunggu Konfirmasi Pembayaran');
        $hasil->assertSee('Lunas');
        $hasil->assertSee('tanpa biaya regu, regu sudah terdaftar');
        $hasil->assertSee('Ibu Karangan');
        $this->assertStringContainsString('Rp 650.000', $xpath->query('//*[contains(@class, "cek-total-semua")]')->item(0)->textContent);
        $this->assertSame(site_url('bukti/' . (string) db_connect()->table('pendaftaran')->where('id_pendaftaran', 'MAC2A0000226')->get()->getRow()->kode_bukti . '/MAC2A0000226'), $xpath->query('//a[contains(., "Cetak bukti")]')->item(1)->getAttribute('href'));

        // Tanggal lahir tidak tampil (K52). Nomor dada tampil setelah Lunas,
        // anggota tim satu nama per baris (K88).
        $hasil->assertDontSee('1 Mei 2016');
        $tim = $xpath->query('.//table[contains(@class, "cek-tim-anggota")]//tbody/tr', $kartu->item(1));
        $this->assertSame(1, $tim->length);
        $this->assertStringContainsString('FAJAR REKAAN', $tim->item(0)->textContent);
        $this->assertStringContainsString('1001', $tim->item(0)->textContent);
        $this->assertSame(4, $xpath->query('.//table[contains(@class, "cek-tim-anggota")]//tbody/tr', $kartu->item(0))->length, 'Empat anggota estafet, satu per baris.');
        $this->assertStringNotContainsString('1001', $kartu->item(0)->textContent, 'Pendaftaran Menunggu tanpa nomor atlet (BIB).');

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

    public function testTurnstileWajibSebelumMencari(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();
        $tiruan = $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_LOLOS, 'cek_pendaftaran');

        $halaman = $this->get('cek-pendaftaran');
        $halaman->assertOK();
        $halaman->assertSee('class="cf-turnstile mt-3" data-sitekey="' . TiruanSiteverify::SITUS_LOLOS . '" data-action="cek_pendaftaran"');
        $halaman->assertSee('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>');

        // Tanpa token: 403 dan tanpa data.
        $tanpa = $this->get('cek-pendaftaran?kode=20300001');
        $tanpa->assertStatus(403);
        $tanpa->assertSee('Pemeriksaan keamanan belum selesai');
        $tanpa->assertDontSee('SD NEGERI CONTOH 1');
        $this->assertSame([], $tiruan->kiriman);

        // Dengan token: hasil tampil, widget tidak tampil lagi.
        $dengan = $this->get('cek-pendaftaran?kode=20300001&cf-turnstile-response=' . rawurlencode(TiruanSiteverify::TOKEN));
        $dengan->assertOK();
        $dengan->assertSee('SD NEGERI CONTOH 1');
        $dengan->assertDontSee('cf-turnstile');
        $this->assertCount(1, $tiruan->kiriman);
        $sampai = $_SESSION[CekPendaftaranController::KUNCI_LOLOS];
        $this->assertGreaterThan(time() + CekPendaftaranController::LAMA_LOLOS - 5, $sampai);

        // Sudah lolos: alamat ?kode= (misalnya setelah unggah bukti bayar) terbuka tanpa token baru.
        $lagi = $this->withSession([CekPendaftaranController::KUNCI_LOLOS => $sampai])->get('cek-pendaftaran?kode=20300001');
        $lagi->assertOK();
        $lagi->assertSee('SD NEGERI CONTOH 1');
        $this->assertCount(1, $tiruan->kiriman);

        // Masa lolos habis: diperiksa lagi.
        $this->withSession([CekPendaftaranController::KUNCI_LOLOS => time() - 1])->get('cek-pendaftaran?kode=20300001')->assertStatus(403);
    }

    public function testTurnstileGagalDiCekPendaftaran(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();
        $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_GAGAL, 'cek_pendaftaran');

        $hasil = $this->get('cek-pendaftaran?kode=20300001&cf-turnstile-response=' . rawurlencode(TiruanSiteverify::TOKEN));

        $hasil->assertStatus(403);
        $hasil->assertSee('Pemeriksaan keamanan gagal');
        $hasil->assertDontSee('SD NEGERI CONTOH 1');
        $this->assertArrayNotHasKey(CekPendaftaranController::KUNCI_LOLOS, $_SESSION ?? []);
    }
}
