<?php

use CodeIgniter\Config\Services;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Uji gabungan Tahap 4 dan Tahap 5 (F4-05, kesepakatan dua sesi butir 7):
 * pendaftaran yang disimpan wizard tampil di `/cek-pendaftaran` lewat NPSN dan
 * ID, dan tautan cetak bukti membuka PDF yang benar. Juga tombol dan menu
 * Pendaftaran mengikuti keadaan pendaftaran, dan pesan kilat di tata letak
 * publik.
 *
 * @internal
 */
final class PendaftaranGabunganTest extends UjiBasisData
{
    use KirimFormulir;
    use PendaftaranContoh;

    /**
     * @var array<string, int>
     */
    private array $id = [];

    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('throttler');
        service('cache')->clean();
        $this->pakaiFolderFotoSementara();
        $this->id = $this->bukaPendaftaranUji();
    }

    protected function tearDown(): void
    {
        $this->hapusFolderFotoSementara();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function simpanLewatWizard(array $kiriman): array
    {
        $token = bin2hex(random_bytes(32));
        Services::resetSingle('security');

        $hasil = $this->withSession([config('Security')->tokenName => $token])
            ->withHeaders(['X-CSRF-TOKEN' => $token, 'Content-Type' => 'application/json'])
            ->withBodyFormat('json')
            ->post('pendaftaran/simpan', self::kirimanUntukBasisData($kiriman, $this->id));

        $hasil->assertStatus(201);

        return json_decode((string) $hasil->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function teksHalaman(TestResponse $hasil): string
    {
        return (string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $hasil->response()->getBody()), ENT_QUOTES | ENT_HTML5));
    }

    public function testWizardKeCekKeBukti(): void
    {
        // Pendaftaran pertama: tim Kanga's Escape baru (4 atlet) dan dua nomor individu.
        $pertama            = self::kirimanContoh();
        $pertama['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $pertama['atlet']   = [self::atletContoh('9900000001', [10, 20])];

        foreach ([2, 3, 4] as $n) {
            $pertama['atlet'][] = self::atletContoh('990000000' . $n, [20]);
        }

        // Pendaftaran susulan dari NPSN yang sama: tambahan anggota tim, tanpa biaya tim.
        $susulan            = self::kirimanContoh();
        $susulan['sekolah'] = $pertama['sekolah'];
        $susulan['atlet']   = [self::atletContoh('9900000005', [20, 12])];

        // Official mencentang konfirmasi tim susulan (K57).
        $susulan['konfirmasi_susulan'] = [20];

        $satu = $this->simpanLewatWizard($pertama);
        $dua  = $this->simpanLewatWizard($susulan);

        $this->assertSame(250000, $satu['rincian']['total']);
        $this->assertSame(150000, $dua['rincian']['total']);
        $this->assertTrue($dua['rincian']['beregu'][0]['susulan']);

        // Cek lewat ID.
        $cekId = $this->get('cek-pendaftaran?kode=' . $satu['id_pendaftaran']);
        $cekId->assertOK();
        $teks = $this->teksHalaman($cekId);
        foreach (['SD KARANGAN MAJU', $satu['id_pendaftaran'], 'BUDI KARANGAN 01', 'Lari 60 m putra', "Kanga's Escape putra", 'Rp 250.000', 'Ibu Karangan', '0812-0000-0001', 'Menunggu Konfirmasi Pembayaran'] as $harap) {
            $this->assertStringContainsString($harap, $teks);
        }
        $this->assertStringNotContainsString($dua['id_pendaftaran'], $teks);
        $this->assertStringNotContainsString('penanggung-jawab/', (string) $cekId->response()->getBody(), 'Foto tidak tampil di halaman cek.');

        // Cek lewat NPSN menggabungkan kedua pendaftaran.
        $teks = $this->teksHalaman($this->get('cek-pendaftaran?kode=99999911'));
        $this->assertStringContainsString($satu['id_pendaftaran'], $teks);
        $this->assertStringContainsString($dua['id_pendaftaran'], $teks);
        $this->assertStringContainsString('Frog Jump putra', $teks);
        $this->assertStringContainsString('Rp 400.000', $teks, 'Total keseluruhan dua pendaftaran.');

        // Tautan cetak bukti dari langkah 5 membuka PDF pendaftaran itu.
        $alamatBukti = 'bukti/' . (string) db_connect()->table('pendaftaran')->where('id_pendaftaran', $dua['id_pendaftaran'])->get()->getRow()->kode_bukti . '/' . $dua['id_pendaftaran'];
        $this->assertSame(site_url($alamatBukti), $dua['bukti']);
        $bukti = $this->get($alamatBukti);
        $bukti->assertOK();
        $this->assertStringStartsWith('application/pdf', $bukti->response()->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $bukti->response()->getBody());
        $this->assertStringContainsString('BuktiPendaftaran_', $bukti->response()->getHeaderLine('Content-Disposition'));
    }

    public function testTombolDaftarMengikutiKeadaan(): void
    {
        // K81: Pendaftaran dibuka dari tombol di bagian pembuka halaman depan, bukan dari menu.
        $tombol = fn () => $this->xpathDari($this->get('/'))->query('//section[@id="pembuka"]//*[contains(@class, "tombol-pendaftaran")]')->item(0);

        // Terbuka: menaut ke wizard.
        $this->assertSame(site_url('pendaftaran'), $tombol()->getAttribute('href'));
        $this->assertSame('Daftar Sekarang', trim($tombol()->textContent));

        // Ditutup: nonaktif dengan keterangan.
        setting('Pendaftaran.akhir', '2026-01-02 00:00:00');
        $this->assertSame('true', $tombol()->getAttribute('aria-disabled'));
        $this->assertFalse($tombol()->hasAttribute('href'));
        $this->assertSame('Pendaftaran Ditutup', trim((string) preg_replace('/\s+/u', ' ', $tombol()->textContent)));

        // Belum dibuka.
        setting('Pendaftaran.mulai', '2099-01-01 00:00:00');
        setting('Pendaftaran.akhir', '2099-02-01 00:00:00');
        $this->assertSame('Pendaftaran Belum dibuka', trim((string) preg_replace('/\s+/u', ' ', $tombol()->textContent)));
    }

    public function testMenuTanpaPendaftaranDiHalamanPendaftaran(): void
    {
        // Menu tidak memuat Pendaftaran (K81), jadi tidak ada butir yang ditandai aktif.
        $xpath = $this->xpathDari($this->get('pendaftaran'));

        $this->assertSame(0, $xpath->query('//nav[@aria-label="Menu utama"]//a[@aria-current="page"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Menu utama"]//a[contains(@class, "tombol-panitia")]')->length);
    }

    public function testPesanKilatDiTataLetakPublik(): void
    {
        $hasil = $this->withSession([
            'pesan_galat'  => 'Berkas terlalu besar <b>uji</b>.',
            'pesan_sukses' => 'Berhasil.',
            '__ci_vars'    => ['pesan_galat' => 'new', 'pesan_sukses' => 'new'],
        ])->get('unduhan');

        $xpath = $this->xpathDari($hasil);
        $galat = $xpath->query('//main//div[@data-pesan="pesan_galat"]')->item(0);
        $this->assertNotNull($galat);
        $this->assertSame('alert', $galat->getAttribute('role'));
        $this->assertSame('Berkas terlalu besar <b>uji</b>.', trim($galat->textContent));
        $this->assertNotNull($xpath->query('//main//div[@data-pesan="pesan_sukses"]')->item(0));

        // Tanpa pesan kilat, tidak ada kotak pesan.
        $this->assertSame(0, $this->xpathDari($this->withSession([])->get('unduhan'))->query('//*[contains(@class, "pesan-kilat")]')->length);
    }
}
