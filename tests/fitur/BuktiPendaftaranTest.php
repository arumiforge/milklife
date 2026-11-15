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

        $hasil = $this->get($this->alamatBukti('MAC2A0000126'));
        $hasil->assertOK();
        $this->assertStringStartsWith('application/pdf', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertSame('inline; filename="BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_SDNEGERICONTOH1_2026_MAC2A0000126_Menunggu.pdf"', $hasil->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $hasil->response()->getBody());
        // Kepala keamanan (F7-02) tidak menghalangi PDF dibuka di peramban.
        $this->assertSame('nosniff', $hasil->response()->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $hasil->response()->getHeaderLine('X-Frame-Options'));

        // Nama acara, seri, dan tahun mengikuti pengaturan.
        setting('Acara.nama', 'Lomba Lari Karangan');
        setting('Acara.seri', 3);
        setting('Acara.tahun', 2027);
        $bukti = new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2B0000326'));
        $this->assertSame('BuktiPendaftaran_LombaLariKaranganSeri3_SMPbContohbMandiri_2027_MAC2B0000326_Menunggu.pdf', $bukti->namaBerkas());
        $this->assertStringContainsString('LOMBA LARI KARANGAN', $bukti->html());
        $this->assertStringContainsString('Seri 3 Tahun 2027', $bukti->html());
    }

    public function testAlamatSamaMengikutiStatusTerbaruDanNomorDadaSaatLunas(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();
        $alamat = $this->alamatBukti('MAC2A0000126');
        $db     = db_connect();
        $db->table('atlet')->where('nisn', '9900000001')->update(['nomor_dada' => '2001']);

        // Menunggu: stempel menunggu, nomor dada kosong walau sudah tersimpan.
        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000126')))->html();
        $this->assertStringContainsString('stempel stempel-menunggu', $html);
        $this->assertStringContainsString('No. Dada', $html);
        $this->assertStringNotContainsString('2001', $html);

        // Ditandai Lunas: alamat yang sama (kode QR) langsung memuat status baru,
        // nomor dada, dan nama berkas baru (K87).
        $db->table('pendaftaran')->where('id_pendaftaran', 'MAC2A0000126')->update(['status' => 'lunas', 'waktu_lunas' => '2026-11-15 10:00:00']);
        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000126')))->html();
        $this->assertStringContainsString('stempel stempel-lunas', $html);
        $this->assertStringContainsString('<b>2001</b>', $html);
        // Tabel beregu satu baris per anggota, nomor dada di kolom sendiri (K107).
        $this->assertMatchesRegularExpression('#<td>BUDI KARANGAN</td>\s*<td>9900000001</td>\s*<td><b>2001</b></td>\s*<td class="peran-inti">Inti</td>#', $html, 'Nomor dada anggota tim beregu.');

        Services::resetSingle('throttler');
        $hasil = $this->get($alamat);
        $hasil->assertOK();
        $this->assertStringEndsWith('_MAC2A0000126_Lunas.pdf"', $hasil->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));
    }

    public function testIsiBuktiDariNilaiTersimpan(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000126')))->html();

        foreach ([
            'BUKTI PENDAFTARAN', 'MILKLIFE ATHLETICS CHALLENGE', 'Seri 2 Tahun 2026',
            // Nama sekolah di blok sendiri paling atas, data lain berlabel di bawahnya (K108).
            '<td class="sekolah-label">Nama Sekolah</td>', '<td class="sekolah-nama">SD NEGERI CONTOH 1</td>',
            '<td class="info-label">Tanggal Pendaftaran</td>', '<td class="info-isi">12 November 2026</td>',
            '<td class="info-label">Kabupaten / Kota</td>', '<td class="info-isi">Kudus</td>',
            '<td class="info-label">ID Pendaftaran</td>', '<td class="info-id" width="58mm">MAC2A0000126</td>',
            '<td class="info-label">NPSN</td>', '<td class="info-isi">20300001</td>', '<td class="info-isi">SD/MI</td>',
            'Penanggung Jawab (Official)', '<b>Ibu Karangan</b>', ', <b>Pak Rekaan</b>', '0812-0000-0002',
            'CANDRA REKAAN', '9900000002', 'Lompat Jauh putra', 'Rp 150.000',
            // Regu tunggal: tanpa kolom regu, nomor lomba dan biaya digabung setinggi regunya (K108).
            '<td class="gabung" rowspan="4"><b>Estafet 4 × 50 m putra</b></td>', '<td class="uang gabung" rowspan="4">Rp 200.000</td>',
            '<td>DIMAS CONTOH</td>', '<td>EKO KARANGAN</td>', '(1 regu, 4 anggota)', '<td colspan="6">Subtotal beregu</td>',
            'Rp 450.000', 'Rp 650.000', 'stempel stempel-menunggu', '<td class="status-menunggu">Menunggu Konfirmasi Pembayaran</td>',
            // Kepala dan kaki di setiap halaman (K108).
            '@page { header: html_kepala; footer: html_kaki; }', '<htmlpageheader name="kepala">', '<htmlpagefooter name="kaki">',
            'MAC2A0000126 · dicetak ', 'Halaman {PAGENO} dari {nbpg}',
        ] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }

        $this->assertStringNotContainsString('gabung regu', $html);
        $this->assertStringNotContainsString('Regu</th>', $html);

        // Tarif sekarang (Rp 175.000) tidak dipakai; foto tidak ikut.
        $this->assertStringNotContainsString('Rp 175.000', $html);
        $this->assertStringNotContainsString('foto-penanggung-jawab', $html);
        // Tanggal lahir dan nomor dada tidak dicetak (K52).
        $this->assertStringNotContainsString('1 Mei 2016', $html);
        $this->assertStringNotContainsString('Nomor Dada', $html);
        // Logo persegi bawaan (K47) disisipkan sebagai PNG transparan; kode QR
        // ditulis terpisah di dasar halaman terakhir (K89).
        $this->assertStringContainsString('<img src="var:logo"', $html);
        $this->assertStringNotContainsString('var:qr', $html);
        $qr = view('bukti/pdf_qr');
        $this->assertStringContainsString('<img src="var:qr"', $qr);
        $this->assertStringContainsString('kode QR di sebelah kanan', $qr);
        // Kepala tabel diulang di setiap halaman.
        $this->assertStringContainsString('<thead>', $html);
    }

    public function testKolomReguBilaSekolahPunyaDuaRegu(): void
    {
        // Susulan membuat Regu B: kolom regu tampil dan berisi nama regunya (K108).
        $id = $this->isiDataCek();
        $p2 = (int) db_connect()->table('pendaftaran')->where('id_pendaftaran', 'MAC2A0000226')->get()->getRow()->id;
        db_connect()->table('atlet_nomor')->where('nomor_lomba_id', $id['estafet'])->where('atlet_id IN (SELECT id FROM atlet WHERE pendaftaran_id = ' . $p2 . ')', null, false)
            ->update(['regu' => 'B', 'peran' => 'inti']);

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000126')))->html();
        $this->assertStringContainsString('Regu</th>', $html);
        $this->assertStringContainsString('<td class="gabung regu" rowspan="4">A</td>', $html);
        $this->assertStringContainsString('<td colspan="7">Subtotal beregu</td>', $html);

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000226')))->html();
        $this->assertStringContainsString('<td class="gabung regu" rowspan="1">B</td>', $html);
    }

    public function testPdfBanyakHalamanTetapTerbentuk(): void
    {
        // Banyak atlet: kepala diulang dan PDF lebih dari satu halaman (K108).
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();
        $p = (new DataPendaftaran())->cariId('MAC2A0000126');

        for ($i = 0; $i < 60; $i++) {
            $p['individu'][] = ['nama' => 'ATLET KARANGAN ' . $i, 'nisn' => sprintf('98%08d', $i), 'tanggal_lahir' => '2016-05-01', 'kelompok_umur' => 'KU 10', 'nomor_dada' => null, 'nomor_lomba' => 'Lari 60 m putra', 'biaya' => 25000];
        }

        $pdf = (new BuktiPendaftaran($p))->pdf();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }

    public function testSusulanLunasDanTeksDiEscape(): void
    {
        $this->isiDataCek();

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000226')))->html();
        // Lunas: nomor dada anggota tim ikut dicetak (K87, menggantikan K52).
        $this->assertMatchesRegularExpression('#<td>FAJAR REKAAN</td>\s*<td>\d+</td>\s*<td><b>1001</b></td>\s*<td class="peran-cadangan">Cadangan</td>#', $html);
        $this->assertStringContainsString('Tanpa biaya<br>regu sudah terdaftar', $html);
        $this->assertStringContainsString('Tidak ada nomor individu', $html);
        $this->assertStringContainsString('stempel stempel-lunas', $html);
        $this->assertStringNotContainsString('var:logo', $html, 'Tanpa logo bila belum diunggah.');

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2B0000326')))->html();
        $this->assertStringContainsString('SMP &lt;b&gt;Contoh&lt;/b&gt; &amp; Mandiri', $html);
        $this->assertStringContainsString('GILANG &lt;SCRIPT&gt;', $html);
        $this->assertStringNotContainsString('<SCRIPT>', $html);
    }

    public function testIdTidakAdaAtauKodeSalahMenghasilkan404(): void
    {
        $this->isiDataCek();
        $benar = $this->alamatBukti('MAC2A0000126');
        $kode  = explode('/', $benar)[1];

        foreach (['bukti/' . $kode . '/MAC2A9999926', 'bukti/' . $kode . '/bukan-id', 'bukti/' . str_repeat('0', 24) . '/MAC2A0000126', 'bukti/' . $kode . '/MAC2A0000226'] as $alamat) {
            try {
                $this->get($alamat);
                $this->fail('Seharusnya 404: ' . $alamat);
            } catch (PageNotFoundException $e) {
                $this->assertStringContainsString('tidak ditemukan', $e->getMessage());
            }
        }
    }

    public function testAlamatLamaTanpaKodeTidakAdaLagi(): void
    {
        $this->isiDataCek();

        try {
            $this->get('bukti/MAC2A0000126');
            $this->fail('Alamat tanpa kode seharusnya tidak ditemukan.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testKodeQrBerisiAlamatBuktiDanKertasF4(): void
    {
        $this->lengkapiPengaturanCek();
        $this->isiDataCek();

        $alamat = $this->alamatBukti('MAC2A0000126');
        $this->assertMatchesRegularExpression('#^bukti/[0-9a-f]{24}/MAC2A0000126$#', $alamat);
        $this->assertSame($alamat, $this->alamatBukti('MAC2A0000126'), 'Kode tetap untuk pendaftaran yang sama.');

        $png = App\Libraries\KodeQr::png($alamat, (string) file_get_contents(ROOTPATH . 'data/halaman-depan/logo-persegi.webp'));
        $this->assertSame($alamat, (new chillerlan\QRCode\QRCode())->readFromBlob($png)->data, 'Kode QR berlogo tetap terbaca.');

        // F4 tegak: 215 × 330 mm = 609,45 × 935,43 pt.
        $pdf = (new BuktiPendaftaran((new DataPendaftaran())->cariId('MAC2A0000126')))->pdf();
        $this->assertMatchesRegularExpression('#/MediaBox \[0 0 609\.4\d+ 935\.4\d+\]#', $pdf);
    }

    public function testPembatasanLaju(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= BuktiController::BATAS_PERMINTAAN; $ke++) {
            try {
                $this->get('bukti/' . str_repeat('a', 24) . '/MAC2A9999926');
            } catch (PageNotFoundException) {
                // Permintaan yang tidak ditemukan tetap dihitung.
            }
        }

        $hasil = $this->get('bukti/' . str_repeat('a', 24) . '/MAC2A0000126');
        $hasil->assertStatus(429);
        $hasil->assertHeader('Retry-After', '6');
        $hasil->assertSee('Tunggu 6 detik lagi');
    }

    /**
     * Alamat bukti (dengan kode acak) untuk ID pendaftaran, tanpa awalan situs.
     */
    private function alamatBukti(string $id): string
    {
        $p = (new DataPendaftaran())->cariId($id);

        return substr(BuktiPendaftaran::alamat($p), strlen(rtrim(site_url('/'), '/')) + 1);
    }
}
