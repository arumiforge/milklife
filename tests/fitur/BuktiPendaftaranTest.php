<?php

use App\Controllers\BuktiController;
use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\CekDataPendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Bukti pendaftaran PDF (F5-03, rancangan 8, K49). PDF diperiksa di memori;
 * isi teks diperiksa pada HTML sebelum diubah mPDF.
 *
 * @internal
 */
final class BuktiPendaftaranTest extends UjiBasisData
{
    use CekDataPendaftaran;
    use KirimFormulir;
    use StreamFilterTrait;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('throttler');
        service('cache')->clean();

        // mPDF membaca SCRIPT_NAME (selalu ada di server web); uji lain bisa menghapusnya.
        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
    }

    public function testPdfDenganNamaBerkasDariPengaturan(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $hasil = $this->get('bukti/MAC2A0000126');
        $hasil->assertOK();
        $this->assertStringStartsWith('application/pdf', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertSame('inline; filename="BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_SDNEGERICONTOH1_2026.pdf"', $hasil->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $hasil->response()->getBody());

        // Nama acara, seri, dan tahun mengikuti pengaturan.
        setting('Acara.nama', 'Lomba Lari Karangan');
        setting('Acara.seri', 3);
        setting('Acara.tahun', 2027);
        $bukti = new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2B0000326'));
        $this->assertSame('BuktiPendaftaran_LombaLariKaranganSeri3_SMPbContohbMandiri_2027.pdf', $bukti->namaBerkas());
        $this->assertStringContainsString('LOMBA LARI KARANGAN', $bukti->html());
        $this->assertStringContainsString('Seri 3 Tahun 2027', $bukti->html());
    }

    public function testIsiBuktiDariNilaiTersimpan(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000126')))->html();

        foreach ([
            'MILKLIFE ATHLETICS CHALLENGE', 'Seri 2 Tahun 2026',
            'Nama Sekolah : <b>SD NEGERI CONTOH 1</b>', 'Tanggal Pendaftaran : <b>12 November 2026</b>',
            'Kabupaten / Kota : <b>Kudus</b>', 'ID Pendaftaran : <b>MAC2A0000126</b>',
            'Ibu Karangan', '081200000002',
            'CANDRA REKAAN', '9900000002', '1 Mei 2016', 'Lompat Jauh', 'Rp150.000',
            'Estafet 4 × 50 m', 'EKO KARANGAN (–)', 'Rp200.000',
            'Rp450.000', 'Rp650.000', 'Menunggu Konfirmasi Pembayaran',
        ] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }

        // Tarif sekarang (Rp175.000) tidak dipakai; foto tidak ikut.
        $this->assertStringNotContainsString('Rp175.000', $html);
        $this->assertStringNotContainsString('foto-penanggung-jawab', $html);
        // Logo persegi bawaan (K47) dari pengaturan.
        $this->assertMatchesRegularExpression('#<img src="[^"]+\.webp"#', $html);
    }

    public function testSusulanLunasDanTeksDiEscape(): void
    {
        $this->isiDataCek();

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000226')))->html();
        $this->assertStringContainsString('FAJAR REKAAN (1001)', $html);
        $this->assertStringContainsString('Tanpa biaya (tim sudah terdaftar)', $html);
        $this->assertStringContainsString('Tidak ada nomor individu', $html);
        $this->assertStringContainsString('<b>Lunas</b>', $html);
        $this->assertStringNotContainsString('<img', $html, 'Tanpa logo bila belum diunggah.');

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2B0000326')))->html();
        $this->assertStringContainsString('SMP &lt;b&gt;Contoh&lt;/b&gt; &amp; Mandiri', $html);
        $this->assertStringContainsString('GILANG &lt;SCRIPT&gt;', $html);
        $this->assertStringNotContainsString('<SCRIPT>', $html);
    }

    public function testIdTidakAdaMenghasilkan404(): void
    {
        $this->isiDataCek();

        foreach (['MAC2A9999926', 'bukan-id'] as $id) {
            try {
                $this->get('bukti/' . $id);
                $this->fail('Seharusnya 404: ' . $id);
            } catch (PageNotFoundException $e) {
                $this->assertStringContainsString('tidak ditemukan', $e->getMessage());
            }
        }
    }

    public function testPembatasanLaju(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= BuktiController::BATAS_PERMINTAAN; $ke++) {
            try {
                $this->get('bukti/MAC2A9999926');
            } catch (PageNotFoundException) {
                // Permintaan yang tidak ditemukan tetap dihitung.
            }
        }

        $hasil = $this->get('bukti/MAC2A0000126');
        $hasil->assertStatus(429);
        $hasil->assertHeader('Retry-After', '6');
        $hasil->assertSee('Tunggu 6 detik lagi');
    }
}
