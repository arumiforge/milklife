<?php

use App\Controllers\PendaftaranController;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Titik akhir `POST pendaftaran/periksa` dan `POST pendaftaran/simpan` (F4-04):
 * CSRF lewat kepala, pendaftaran harus terbuka, galat per langkah, rincian
 * dari server, ID pendaftaran, alamat WA, tautan bukti, dan pembatasan laju.
 *
 * @internal
 */
final class PendaftaranKirimTest extends UjiBasisData
{
    use FeatureTestTrait;
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
     * @param array<string, mixed>|string $kiriman
     */
    private function kirimJson(string $alamat, array|string $kiriman): TestResponse
    {
        $token = bin2hex(random_bytes(32));
        Services::resetSingle('security');

        $uji = $this->withSession([config('Security')->tokenName => $token])
            ->withHeaders(['X-CSRF-TOKEN' => $token, 'Content-Type' => 'application/json']);

        if (is_string($kiriman)) {
            return $uji->withBody($kiriman)->call('post', $alamat);
        }

        return $uji->withBodyFormat('json')->post($alamat, $kiriman);
    }

    /**
     * @return array<string, mixed>
     */
    private static function json(TestResponse $hasil): array
    {
        return json_decode((string) $hasil->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function kirimanSah(): array
    {
        return self::kirimanUntukBasisData(self::kirimanContoh(), $this->id);
    }

    public function testPeriksaMemberiRincianTanpaMenyimpan(): void
    {
        $hasil = $this->kirimJson('pendaftaran/periksa', $this->kirimanSah());

        $hasil->assertStatus(200);
        $isi = self::json($hasil);
        $this->assertSame('sah', $isi['status']);
        $this->assertSame(450000, $isi['rincian']['total']);
        $this->assertSame('SD Karangan Maju', $isi['rincian']['sekolah']['nama']);
        $this->assertNotSame('', $isi['csrf']);
        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame([], $this->berkasFoto());
    }

    public function testPeriksaMengembalikanGalatPerLangkah(): void
    {
        $kiriman                                  = $this->kirimanSah();
        $kiriman['sekolah']['kabupaten']          = 'Demak';
        $kiriman['penanggung_jawab'][0]['foto']   = '';
        $kiriman['atlet'][1]['nisn']              = '123';

        $hasil = $this->kirimJson('pendaftaran/periksa', $kiriman);

        $hasil->assertStatus(422);
        $isi = self::json($hasil);
        $this->assertSame('galat', $isi['status']);
        $this->assertSame(['sekolah.kabupaten', 'penanggung_jawab.0.foto', 'atlet.1.nisn'], array_column($isi['galat'], 'jalur'));
        $this->assertSame([1, 2, 3], array_column($isi['galat'], 'langkah'));
    }

    public function testSimpanBerhasil(): void
    {
        setting('Pembayaran.templat_pesan', 'Konfirmasi {id_pendaftaran} {nama_sekolah} total {total}');

        $hasil = $this->kirimJson('pendaftaran/simpan', $this->kirimanSah());

        $hasil->assertStatus(201);
        $isi = self::json($hasil);
        $id  = $isi['id_pendaftaran'];

        $this->assertSame('tersimpan', $isi['status']);
        $this->assertMatchesRegularExpression('/^MAC2A\d{5}26$/', $id);
        $this->assertSame(site_url('bukti/' . $id), $isi['bukti']);
        $this->assertSame('https://wa.me/6281200000099?text=' . rawurlencode("Konfirmasi {$id} SD Karangan Maju total Rp450.000"), $isi['wa']);
        $this->assertSame(450000, $isi['rincian']['total']);

        $this->assertSame(1, $this->db->table('pendaftaran')->where('id_pendaftaran', $id)->countAllResults());
        $this->assertCount(1, $this->berkasFoto());

        // Kiriman yang sama sekali lagi ditolak karena NISN sudah terdaftar.
        $ulang = $this->kirimJson('pendaftaran/simpan', $this->kirimanSah());
        $ulang->assertStatus(422);
        $this->assertStringContainsString('sudah terdaftar', self::json($ulang)['galat'][0]['pesan']);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    /**
     * Kiriman susulan dari NPSN 99999911 yang sudah punya tim Kanga's Escape
     * (4 atlet): satu atlet tambahan di nomor itu.
     *
     * @return array<string, mixed>
     */
    private function siapkanSusulan(): array
    {
        $pertama            = self::kirimanContoh();
        $pertama['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $pertama['atlet']   = [];

        foreach ([1, 2, 3, 4] as $n) {
            $pertama['atlet'][] = self::atletContoh('990000007' . $n, [20]);
        }

        $this->kirimJson('pendaftaran/simpan', self::kirimanUntukBasisData($pertama, $this->id))->assertStatus(201);

        $susulan          = $pertama;
        $susulan['atlet'] = [self::atletContoh('9900000075', [20])];

        return $susulan;
    }

    public function testSusulanTanpaKonfirmasiDitolak(): void
    {
        $susulan = $this->siapkanSusulan();

        // Tanpa kunci konfirmasi sama sekali, dan dengan daftar kosong.
        foreach ([null, []] as $konfirmasi) {
            $kiriman = self::kirimanUntukBasisData($susulan, $this->id);

            if ($konfirmasi !== null) {
                $kiriman['konfirmasi_susulan'] = $konfirmasi;
            }

            foreach (['pendaftaran/periksa', 'pendaftaran/simpan'] as $alamat) {
                $hasil = $this->kirimJson($alamat, $kiriman);

                $hasil->assertStatus(422);
                $galat = self::json($hasil)['galat'];
                $this->assertSame(['konfirmasi.' . $this->id['kanga']], array_column($galat, 'jalur'), $alamat);
                $this->assertSame([3], array_column($galat, 'langkah'));
                $this->assertStringContainsString("Tim Kanga's Escape putra", $galat[0]['pesan']);
                $this->assertStringContainsString('Centang kotak konfirmasi', $galat[0]['pesan']);
            }
        }

        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testSusulanDenganKonfirmasiDiterima(): void
    {
        $susulan                       = $this->siapkanSusulan();
        $susulan['konfirmasi_susulan'] = [20];
        $kiriman                       = self::kirimanUntukBasisData($susulan, $this->id);

        $periksa = $this->kirimJson('pendaftaran/periksa', $kiriman);
        $periksa->assertStatus(200);
        $this->assertTrue(self::json($periksa)['rincian']['beregu'][0]['susulan']);

        $simpan = $this->kirimJson('pendaftaran/simpan', $kiriman);
        $simpan->assertStatus(201);
        $rincian = self::json($simpan)['rincian'];
        $this->assertSame(0, $rincian['subtotal_beregu']);
        $this->assertSame(0, $rincian['total']);
        $this->assertSame(2, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testDitolakBilaPendaftaranTidakTerbuka(): void
    {
        setting('Pendaftaran.akhir', '2026-01-02 00:00:00');

        foreach (['pendaftaran/periksa', 'pendaftaran/simpan'] as $alamat) {
            $hasil = $this->kirimJson($alamat, $this->kirimanSah());
            $hasil->assertStatus(409);
            $this->assertSame('ditutup', self::json($hasil)['status']);
            $this->assertStringContainsString('Pendaftaran sudah ditutup', self::json($hasil)['pesan']);
        }

        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testKirimanBukanJson(): void
    {
        $hasil = $this->kirimJson('pendaftaran/simpan', 'bukan json');

        $hasil->assertStatus(400);
        $this->assertSame('tidak_sah', self::json($hasil)['status']);
    }

    public function testTokenCsrfWajib(): void
    {
        Services::resetSingle('security');

        $this->expectException(SecurityException::class);

        $this->withSession([])->withBodyFormat('json')->post('pendaftaran/simpan', $this->kirimanSah());
    }

    public function testPembatasanLajuSimpan(): void
    {
        service('throttler')->setTestTime(time());
        $kiriman                    = $this->kirimanSah();
        $kiriman['sekolah']['nama'] = '';

        for ($ke = 1; $ke <= PendaftaranController::BATAS_SIMPAN; $ke++) {
            $this->kirimJson('pendaftaran/simpan', $kiriman)->assertStatus(422);
        }

        $hasil = $this->kirimJson('pendaftaran/simpan', $kiriman);
        $hasil->assertStatus(429);
        $this->assertSame('terlalu_sering', self::json($hasil)['status']);
    }

    public function testHalamanWizardMemuatAlamatDanRekening(): void
    {
        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();

        preg_match('#<script type="application/json" id="data-pendaftaran">(.*?)</script>#s', (string) $hasil->response()->getBody(), $cocok);
        $data = json_decode($cocok[1], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(site_url('pendaftaran/periksa'), $data['alamat']['periksa']);
        $this->assertSame(site_url('pendaftaran/simpan'), $data['alamat']['simpan']);
        $this->assertSame(['bank' => 'Bank Karangan', 'nomor_rekening' => '1234567890', 'pemilik_rekening' => 'Panitia Karangan', 'logo_bank' => null], $data['pembayaran']);
    }
}
