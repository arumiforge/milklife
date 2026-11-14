<?php

use App\Controllers\PendaftaranController;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Pencarian NPSN di wizard pendaftaran (F4-02): data sekolah referensi,
 * penolakan SLB (K28), data susulan (6.6, K51), dan pembatasan laju.
 *
 * @internal
 */
final class PendaftaranSekolahTest extends UjiBasisData
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
        $this->id = $this->isiDataIndukPendaftaran();
    }

    /**
     * @return array<string, mixed>
     */
    private function cari(string $npsn, int $status): array
    {
        $hasil = $this->get('pendaftaran/sekolah?npsn=' . rawurlencode($npsn));
        $hasil->assertStatus($status);
        $hasil->assertHeader('Content-Type', 'application/json; charset=UTF-8');

        return self::json($hasil);
    }

    /**
     * @return array<string, mixed>
     */
    private static function json(TestResponse $hasil): array
    {
        return json_decode((string) $hasil->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testSekolahDitemukan(): void
    {
        $isi = $this->cari(' 9999 9911 ', 200);

        $this->assertSame('ditemukan', $isi['status']);
        $this->assertSame(['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU', 'jenjang' => 'A', 'jenjang_label' => 'SD/MI', 'kabupaten' => 'Kudus', 'lokasi' => '', 'nama_kembar' => 0], $isi['sekolah']);
        $this->assertSame(['tim' => [], 'individu' => []], $isi['susulan']);
        $this->assertArrayHasKey('csrf', $isi);
    }

    public function testLetakSekolahIkutDikirimUntukMembedakanNamaUmum(): void
    {
        $this->db->table('sekolah')->where('npsn', '99999913')->update(['desa_kelurahan' => 'Karanganyar', 'kecamatan' => 'Welahan']);

        $isi = $this->cari('99999913', 200);

        // Kabupaten sudah punya kunci sendiri, jadi tidak diulang di lokasi.
        $this->assertSame('Karanganyar, Kec. Welahan', $isi['sekolah']['lokasi']);
    }

    public function testSmpDitemukan(): void
    {
        $isi = $this->cari('99999913', 200);

        $this->assertSame('B', $isi['sekolah']['jenjang']);
        $this->assertSame('Jepara', $isi['sekolah']['kabupaten']);
    }

    public function testSlbDitolak(): void
    {
        $isi = $this->cari('99999912', 422);

        $this->assertSame('slb', $isi['status']);
        $this->assertStringContainsString('SLB KARANGAN adalah Sekolah Luar Biasa (SLB)', $isi['pesan']);
        $this->assertArrayNotHasKey('sekolah', $isi);
    }

    public function testNpsnTidakSah(): void
    {
        foreach (['', '1234567', '123456789', 'abcdefgh'] as $npsn) {
            $this->assertSame('tidak_sah', $this->cari($npsn, 422)['status'], $npsn);
        }
    }

    public function testNpsnTidakDitemukan(): void
    {
        $isi = $this->cari('88888888', 404);

        $this->assertSame('tidak_ditemukan', $isi['status']);
        $this->assertStringContainsString('Tulis nama sekolah secara lengkap', $isi['pesan']);
    }

    public function testDataSusulanHanyaJumlah(): void
    {
        $this->pakaiFolderFotoSementara();

        try {
            $kiriman            = self::kirimanContoh();
            $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
            $kiriman['atlet']   = [self::atletContoh('9900000001', [10, 12])];

            for ($i = 2; $i <= 5; $i++) {
                $kiriman['atlet'][] = self::atletContoh('990000000' . $i, [20]);
            }

            $simpan = (new App\Libraries\Pendaftaran\SimpanPendaftaran())->simpan(self::kirimanUntukBasisData($kiriman, $this->id), '2026-11-12');
            $this->assertSame([], $simpan['galat']);
        } finally {
            $this->hapusFolderFotoSementara();
        }

        $isi = $this->cari('99999911', 200);

        $this->assertSame([(string) $this->id['kanga'] => 4], $isi['susulan']['tim']);
        $this->assertSame([(string) $this->id['lari60putra'] => 1, (string) $this->id['frog'] => 1], $isi['susulan']['individu']);

        $teks = json_encode($isi, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('9900000001', $teks);
        $this->assertStringNotContainsString('BUDI', $teks);
    }

    public function testPembatasanLaju(): void
    {
        service('throttler')->setTestTime(time());

        for ($ke = 1; $ke <= PendaftaranController::BATAS_SEKOLAH; $ke++) {
            $this->get('pendaftaran/sekolah?npsn=99999911')->assertStatus(200);
        }

        $hasil = $this->get('pendaftaran/sekolah?npsn=99999911');
        $hasil->assertStatus(429);
        $hasil->assertHeader('Retry-After', '1');
        $this->assertSame('terlalu_sering', self::json($hasil)['status']);
        $this->assertStringContainsString('Tunggu 1 detik', self::json($hasil)['pesan']);
    }

    public function testHanyaGet(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->call('post', 'pendaftaran/sekolah', ['npsn' => '99999911']);
    }
}
