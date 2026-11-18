<?php

use App\Controllers\PendaftaranController;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\PendaftaranContoh;
use Tests\Support\TiruanSiteverify;
use Tests\Support\TurnstileUji;
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
    use TurnstileUji;

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
        $this->matikanTurnstile();
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
        // Alamat bukti memakai kode acak tersimpan (K77).
        $this->assertSame(site_url('bukti/' . (string) db_connect()->table('pendaftaran')->where('id_pendaftaran', $id)->get()->getRow()->kode_bukti . '/' . $id), $isi['bukti']);
        $this->assertMatchesRegularExpression('#/bukti/[0-9a-f]{24}/#', $isi['bukti']);
        $this->assertSame('https://wa.me/6281200000099?text=' . rawurlencode("Konfirmasi {$id} SD Karangan Maju total Rp 450.000"), $isi['wa']);
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
     * Jumlah baris di semua tabel yang ditulis `SimpanPendaftaran`, dan berkas foto.
     *
     * @return array<string, mixed>
     */
    private function potretTulisan(): array
    {
        $jumlah = [];

        foreach (['pendaftaran', 'penanggung_jawab', 'atlet', 'atlet_nomor', 'pendaftaran_beregu', 'bukti_bayar', 'usulan_nama_sekolah'] as $tabel) {
            $jumlah[$tabel] = $this->db->table($tabel)->countAllResults();
        }

        return $jumlah + [
            'diubah' => $this->db->table('pendaftaran')->select('diubah_pada')->get()->getResultArray(),
            'foto'   => $this->berkasFoto(),
        ];
    }

    /**
     * B2 (K118): jawaban pertama tidak sampai, ofisial menekan Kirim lagi
     * dengan kunci yang sama dan mendapat pendaftaran yang sudah tersimpan.
     */
    public function testKirimGandaDenganKunciSamaMenjawabPendaftaranYangAda(): void
    {
        setting('Pembayaran.templat_pesan', 'Konfirmasi {id_pendaftaran} total {total}');
        $kiriman = ['kunci_kirim' => str_repeat('9f', 16)] + $this->kirimanSah();

        $pertama = $this->kirimJson('pendaftaran/simpan', $kiriman);
        $pertama->assertStatus(201);
        $satu = self::json($pertama);
        $this->assertFalse($satu['ulang']);
        $this->seeInDatabase('pendaftaran', ['id_pendaftaran' => $satu['id_pendaftaran'], 'kunci_kirim' => str_repeat('9f', 16)]);

        $sebelum = $this->potretTulisan();

        foreach ([1, 2] as $ke) {
            $ulang = $this->kirimJson('pendaftaran/simpan', $kiriman);

            $ulang->assertStatus(201);
            $dua = self::json($ulang);
            $this->assertSame('tersimpan', $dua['status'], "Kirim ulang ke-{$ke}.");
            $this->assertTrue($dua['ulang']);
            $this->assertStringContainsString('sudah tersimpan sebelumnya dengan ID ' . $satu['id_pendaftaran'], $dua['pesan']);

            foreach (['id_pendaftaran', 'bukti', 'wa'] as $kunci) {
                $this->assertSame($satu[$kunci], $dua[$kunci], $kunci);
            }

            foreach (['subtotal_individu', 'subtotal_beregu', 'total'] as $kunci) {
                $this->assertSame($satu['rincian'][$kunci], $dua['rincian'][$kunci], $kunci);
            }

            $this->assertSame($satu['rincian']['sekolah']['nama'], $dua['rincian']['sekolah']['nama']);
            $this->assertSame($satu['rincian']['sekolah']['npsn'], $dua['rincian']['sekolah']['npsn']);
            $this->assertSame(array_column($satu['rincian']['individu'], 'nisn'), array_column($dua['rincian']['individu'], 'nisn'));
            $this->assertSame(array_column($satu['rincian']['individu'], 'biaya'), array_column($dua['rincian']['individu'], 'biaya'));
            $this->assertSame(array_column($satu['rincian']['beregu'], 'biaya'), array_column($dua['rincian']['beregu'], 'biaya'));

            // Tidak ada yang ditulis: baris, waktu ubah, foto, dan bukti transfer tetap.
            $this->assertSame($sebelum, $this->potretTulisan());
        }

        // Pendaftaran sudah ditutup saat kirim ulang: jawabannya tetap pendaftaran yang tersimpan.
        setting('Pendaftaran.akhir', '2026-01-02 00:00:00');
        $tutup = $this->kirimJson('pendaftaran/simpan', $kiriman);
        $tutup->assertStatus(201);
        $this->assertSame($satu['id_pendaftaran'], self::json($tutup)['id_pendaftaran']);
        $this->assertSame($sebelum, $this->potretTulisan());
    }

    public function testKunciBerbedaAtauTanpaKunciTetapDitolakKarenaNisnTerdaftar(): void
    {
        $this->kirimJson('pendaftaran/simpan', ['kunci_kirim' => str_repeat('ab', 16)] + $this->kirimanSah())->assertStatus(201);

        foreach ([str_repeat('cd', 16), null, 'pendek', str_repeat('ab', 16) . ' ', ['bukan teks']] as $kunci) {
            $kiriman = $this->kirimanSah();

            if ($kunci !== null) {
                $kiriman['kunci_kirim'] = $kunci;
            }

            $hasil = $this->kirimJson('pendaftaran/simpan', $kiriman);
            $hasil->assertStatus(422);
            $this->assertStringContainsString('sudah terdaftar', self::json($hasil)['galat'][0]['pesan']);
        }

        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testKunciDariPendaftaranYangDihapusMenyimpanBaru(): void
    {
        $kiriman = ['kunci_kirim' => str_repeat('e5', 16)] + $this->kirimanSah();
        $satu    = self::json($this->kirimJson('pendaftaran/simpan', $kiriman));
        $this->db->table('pendaftaran')->where('id_pendaftaran', $satu['id_pendaftaran'])->delete();

        $hasil = $this->kirimJson('pendaftaran/simpan', $kiriman);

        $hasil->assertStatus(201);
        $dua = self::json($hasil);
        $this->assertFalse($dua['ulang']);
        $this->assertNotSame($satu['id_pendaftaran'], $dua['id_pendaftaran']);
        $this->seeInDatabase('pendaftaran', ['id_pendaftaran' => $dua['id_pendaftaran'], 'kunci_kirim' => str_repeat('e5', 16)]);
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
        $susulan['atlet'] = [self::atletContoh('9900000075', [20]) + ['regu' => [20 => ['regu' => 'A', 'peran' => 'cadangan']]]];

        return $susulan;
    }

    public function testTanpaBuktiTransferTidakTersimpan(): void
    {
        foreach (['', 'data:image/jpeg;base64,bukan-gambar'] as $bukti) {
            $hasil = $this->kirimJson('pendaftaran/simpan', ['bukti_bayar' => $bukti] + $this->kirimanSah());

            $hasil->assertStatus(422);
            $galat = self::json($hasil)['galat'];
            $this->assertSame(5, $galat[0]['langkah']);
            $this->assertSame('bukti_bayar', $galat[0]['jalur']);
        }

        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame(0, $this->db->table('bukti_bayar')->countAllResults());
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
                $this->assertSame(['konfirmasi.' . $this->id['kanga'] . ':A'], array_column($galat, 'jalur'), $alamat);
                $this->assertSame([3], array_column($galat, 'langkah'));
                // Satu-satunya regu sekolah di nomor ini: tanpa nama regu (K108).
                $this->assertStringContainsString("Kanga's Escape putra: sekolah ini sudah punya regu ini", $galat[0]['pesan']);
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

    public function testTurnstileMatiTanpaKunci(): void
    {
        // .env uji tanpa kunci rahasia: tidak ada widget dan kiriman tanpa token tersimpan.
        $this->assertNull(self::dataWizard($this->get('pendaftaran')->getBody())['turnstile']);
        $this->kirimJson('pendaftaran/simpan', $this->kirimanSah())->assertStatus(201);
    }

    public function testTurnstileWajibSaatKirimPendaftaran(): void
    {
        $tiruan = $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_LOLOS, 'kirim_pendaftaran');

        $halaman = $this->get('pendaftaran')->getBody();
        $this->assertSame(['kunci_situs' => TiruanSiteverify::SITUS_LOLOS, 'aksi' => 'kirim_pendaftaran', 'skrip' => 'https://challenges.cloudflare.com/turnstile/v0/api.js'], self::dataWizard($halaman)['turnstile']);
        $this->assertStringContainsString('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>', $halaman);
        $this->assertStringContainsString('id="turnstile-kirim" class="cf-turnstile" data-jalur="turnstile" data-sitekey="' . TiruanSiteverify::SITUS_LOLOS . '" data-action="kirim_pendaftaran"', $halaman);

        // Tanpa token: 403, tidak ada yang tersimpan, Cloudflare tidak dihubungi.
        $tanpa = $this->kirimJson('pendaftaran/simpan', $this->kirimanSah());
        $tanpa->assertStatus(403);
        $this->assertSame('pemeriksaan_keamanan', self::json($tanpa)['status']);
        $this->assertStringContainsString('Pemeriksaan keamanan belum selesai', self::json($tanpa)['pesan']);
        $this->assertSame([], $tiruan->kiriman);

        // Periksa (langkah 4) tidak meminta token.
        $this->kirimJson('pendaftaran/periksa', $this->kirimanSah())->assertStatus(200);
        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());

        // Dengan token di badan JSON: tersimpan.
        $kiriman = ['cf-turnstile-response' => TiruanSiteverify::TOKEN, 'kunci_kirim' => str_repeat('7a', 16)] + $this->kirimanSah();
        $this->kirimJson('pendaftaran/simpan', $kiriman)->assertStatus(201);
        $this->assertSame(TiruanSiteverify::TOKEN, $tiruan->kiriman[0]['response']);
        $this->assertSame('0.0.0.0', $tiruan->kiriman[0]['remoteip']);

        // Kirim ulang B2 dengan token baru setelah reset widget: pendaftaran yang sama.
        $ulang = $this->kirimJson('pendaftaran/simpan', $kiriman);
        $ulang->assertStatus(201);
        $this->assertTrue(self::json($ulang)['ulang']);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testTurnstileGagalMenolakKiriman(): void
    {
        $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_GAGAL, 'kirim_pendaftaran');

        $hasil = $this->kirimJson('pendaftaran/simpan', ['cf-turnstile-response' => TiruanSiteverify::TOKEN] + $this->kirimanSah());

        $hasil->assertStatus(403);
        $this->assertStringContainsString('lalu coba lagi', self::json($hasil)['pesan']);
        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());

        // Token dari tempat lain (aksi berbeda) juga ditolak.
        $this->matikanTurnstile();
        $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_LOLOS, 'masuk_admin');
        $this->kirimJson('pendaftaran/simpan', ['cf-turnstile-response' => TiruanSiteverify::TOKEN] + $this->kirimanSah())->assertStatus(403);
        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());
    }

    /**
     * @return array<string, mixed>
     */
    private static function dataWizard(string $html): array
    {
        preg_match('#<script type="application/json" id="data-pendaftaran">(.*?)</script>#s', $html, $cocok);

        return json_decode($cocok[1] ?? 'null', true, 512, JSON_THROW_ON_ERROR);
    }
}
