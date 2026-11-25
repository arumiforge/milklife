<?php

use App\Controllers\Admin\Pengaturan\CadanganController;
use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Cadangan\KlienR2;
use App\Libraries\Cadangan\SalinanR2;
use App\Libraries\Panel\RiwayatAdmin;
use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Salinan cadangan ke Cloudflare R2 di Pengaturan > Cadangan (F11-21, K187):
 * simpan pengaturan hanya bila sambungan lulus diperiksa, rahasia terenkripsi,
 * isi folder di bucket dengan tautan unduh, periksa ulang, matikan, kiriman
 * `cadangan:buat`, dan tabel pengaturan yang tidak ikut cadangan lengkap.
 * R2 diganti bucket tiruan di memori; semua kunci karangan.
 *
 * @internal
 */
final class PengaturanSalinanR2Test extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/cadangan';

    private const SANDI = 'sandi-karangan-r2-1';

    private const AKUN = 'abcdefabcdefabcdefabcdefabcdef01';

    private const RAHASIA = 'rahasiakarangan00000000000000000000000000000000000000000000r2';

    /**
     * Objek di bucket tiruan: kunci => [isi, waktu].
     *
     * @var array<string, array{isi: string, waktu: int}>
     */
    private array $bucket = [];

    /**
     * @var list<string>
     */
    private array $metode = [];

    /**
     * Status yang dijawab bucket tiruan; selain 200 berupa galat S3.
     */
    private int $status = 200;

    private string $kodeGalat = '';

    private string $kunciLama = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->kunciLama               = config('Encryption')->key;
        config('Encryption')->key      = random_bytes(32);
        Services::resetSingle('encrypter');
        Services::resetSingle('throttler');
        $this->bucket = [];
        $this->metode = [];
        KlienR2::gantiPengirim($this->r2Tiruan(...));
        $this->db->table('admin')->insert(['id' => 1, 'nama' => 'Admin Uji', 'nama_pengguna' => 'admin.uji', 'sandi' => password_hash(self::SANDI, PASSWORD_DEFAULT)]);
    }

    protected function tearDown(): void
    {
        KlienR2::gantiPengirim(null);
        config('Encryption')->key = $this->kunciLama;
        Services::resetSingle('encrypter');
        parent::tearDown();
    }

    public function testBelumDiaturMenampilkanFormulir(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $hasil->assertSee('Salinan di Cloudflare R2');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//*[@data-r2-belum]')->length);
        $this->assertSame(1, $xpath->query('//details[@data-r2-formulir][@open]//form[@action="' . site_url(self::ALAMAT . '/r2') . '"]//input[@type="password"][@name="rahasia"][@required]')->length);
        $this->assertSame(0, $xpath->query('//form[@action="' . site_url(self::ALAMAT . '/r2/hapus') . '"]')->length);
        $this->assertSame([], $this->metode, 'Halaman tidak menghubungi R2 selama belum diatur.');

        $this->assertBelumMasukDialihkan(self::ALAMAT . '/r2', 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/r2/periksa', 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/r2/hapus', 'post');
        $this->assertTokenCsrfWajib(self::ALAMAT . '/r2', $this->isian());
    }

    public function testSimpanSetelahSambunganLulusDanTampilkanIsiBucket(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/r2', $this->isian([
            // Alamat S3 API utuh juga diterima.
            'id_akun' => 'https://' . self::AKUN . '.r2.cloudflarestorage.com/cadangan-panitia',
            'folder'  => '',
        ]));

        $hasil->assertRedirectTo(site_url(self::ALAMAT . '#salinan-r2'));
        $this->assertStringStartsWith('Tersambung ke bucket cadangan-panitia dan pengaturan disimpan. Folder ' . SalinanR2::folderBawaan(), (string) ($_SESSION['pesan_sukses'] ?? ''));

        $baris = $this->db->table('salinan_r2')->get()->getRowArray();
        $this->assertSame(self::AKUN, $baris['id_akun']);
        $this->assertSame(SalinanR2::folderBawaan(), $baris['folder']);
        $this->assertStringNotContainsString(self::RAHASIA, (string) $baris['rahasia'], 'Secret Access Key tidak tersimpan apa adanya.');
        $this->assertSame(self::RAHASIA, service('encrypter')->decrypt(base64_decode((string) $baris['rahasia'], true)));
        $this->assertArrayHasKey(SalinanR2::folderBawaan() . '/' . SalinanR2::BERKAS_UJI, $this->bucket);
        $this->assertSame(['PUT', 'GET'], $this->metode);

        $riwayat = $this->db->table('riwayat_admin')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame(RiwayatAdmin::ATUR_R2, $riwayat['tindakan']);
        $this->assertStringContainsString('Access Key ID 0123…cdef, Secret Access Key baru', $riwayat['ringkasan']);
        $this->assertStringNotContainsString('0123456789abcdef0123456789abcdef', $riwayat['ringkasan']);

        // Halaman menampilkan isi folder dengan tautan unduh bertanda tangan, tanpa rahasia.
        // Zip dibuat lebih baru dari berkas uji, jadi tampil paling atas.
        $this->bucket[SalinanR2::folderBawaan() . '/cadangan-milklife-2026-11-25-0200.zip'] = ['isi' => 'zip karangan', 'waktu' => time() + 60];
        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertOK();
        $halaman->assertSee('cadangan-panitia');
        $halaman->assertSee('cadangan-milklife-2026-11-25-0200.zip');
        $halaman->assertSee('Atur salinan R2 · Admin Uji');
        $halaman->assertDontSee(self::RAHASIA);
        $xpath = $this->xpathDari($halaman);
        $this->assertSame(2, $xpath->query('//ul[@data-r2-isi]/li')->length);
        $tautan = (string) $xpath->query('//ul[@data-r2-isi]/li[1]//a/@href')->item(0)?->nodeValue;
        $this->assertStringStartsWith('https://' . self::AKUN . '.r2.cloudflarestorage.com/cadangan-panitia/' . SalinanR2::folderBawaan() . '/cadangan-milklife-2026-11-25-0200.zip?X-Amz-Algorithm=AWS4-HMAC-SHA256', $tautan);
        $this->assertStringContainsString('X-Amz-Expires=' . SalinanR2::LAMA_UNDUH, $tautan);
        $this->assertSame(0, $xpath->query('//details[@data-r2-formulir][@open]')->length, 'Formulir tertutup setelah diatur.');
        $this->assertSame(1, $xpath->query('//input[@name="rahasia"][not(@required)][@placeholder]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="rahasia"][@value]')->length);

        // Ubah folder tanpa mengisi ulang rahasia: rahasia lama tetap dipakai.
        $this->kirimSebagaiAdmin(self::ALAMAT . '/r2', $this->isian(['rahasia' => '', 'folder' => 'produksi-karangan']))
            ->assertRedirectTo(site_url(self::ALAMAT . '#salinan-r2'));
        $baru = $this->db->table('salinan_r2')->get()->getRowArray();
        $this->assertSame('produksi-karangan', $baru['folder']);
        $this->assertSame($baris['rahasia'], $baru['rahasia']);
        $this->assertArrayHasKey('produksi-karangan/' . SalinanR2::BERKAS_UJI, $this->bucket);
    }

    public function testIsianSalahDanSandiSalahTidakMenyimpan(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/r2', [
            'id_akun'  => 'akun-salah',
            'bucket'   => 'Bucket_Salah',
            'kunci_id' => 'ada spasi',
            'rahasia'  => '',
            'folder'   => 'a/b',
            'sandi'    => 'bukan-sandinya',
        ]);

        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        foreach (['id_akun', 'bucket', 'kunci_id', 'rahasia', 'folder', 'sandi_r2'] as $kotak) {
            $this->assertSame(1, $xpath->query('//*[@id="' . $kotak . '-galat"]')->length, 'Pesan galat ' . $kotak);
        }

        $hasil->assertSee(esc(CadanganController::PESAN_SANDI_SALAH));
        $this->assertSame(1, $xpath->query('//details[@data-r2-formulir][@open]//input[@name="bucket"][@value="bucket_salah"]')->length);
        $this->assertSame(0, $this->db->table('salinan_r2')->countAllResults());
        $this->assertSame([], $this->metode, 'R2 tidak dihubungi selama isian salah.');
    }

    public function testSambunganGagalTidakMenyimpan(): void
    {
        $this->status    = 403;
        $this->kodeGalat = 'AccessDenied';

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/r2', $this->isian());

        $hasil->assertOK();
        $hasil->assertSee('Pengaturan belum disimpan. Token tidak punya izin untuk bucket');
        $this->assertSame(0, $this->db->table('salinan_r2')->countAllResults());
        $this->assertSame(0, $this->db->table('riwayat_admin')->countAllResults());
    }

    public function testPeriksaSambunganDanIsiBucketYangGagalDibaca(): void
    {
        $this->aturLangsung();

        $this->kirimSebagaiAdmin(self::ALAMAT . '/r2/periksa')->assertRedirectTo(site_url(self::ALAMAT . '#salinan-r2'));
        $this->assertStringStartsWith('Sambungan ke bucket cadangan-panitia baik', (string) ($_SESSION['pesan_sukses'] ?? ''));

        $this->status    = 403;
        $this->kodeGalat = 'SignatureDoesNotMatch';
        $this->kirimSebagaiAdmin(self::ALAMAT . '/r2/periksa');
        $this->assertStringContainsString('Periksa Access Key ID dan Secret Access Key', (string) ($_SESSION['pesan_galat'] ?? ''));

        // Halaman tetap terbuka walau isi bucket tidak bisa dibaca.
        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertOK();
        $halaman->assertSee('Isi bucket tidak bisa dibaca');
    }

    public function testMatikanSalinanDenganKataSandi(): void
    {
        $this->aturLangsung();

        $salah = $this->kirimSebagaiAdmin(self::ALAMAT . '/r2/hapus', ['sandi' => 'bukan-sandinya']);
        $salah->assertOK();
        $this->assertSame(1, $this->xpathDari($salah)->query('//*[@id="sandi_hapus_r2-galat"]')->length);
        $this->assertSame(1, $this->db->table('salinan_r2')->countAllResults());

        $this->kirimSebagaiAdmin(self::ALAMAT . '/r2/hapus', ['sandi' => self::SANDI])->assertRedirectTo(site_url(self::ALAMAT . '#salinan-r2'));
        $this->assertSame(0, $this->db->table('salinan_r2')->countAllResults());
        $this->assertStringContainsString('dimatikan (bucket cadangan-panitia', (string) $this->db->table('riwayat_admin')->orderBy('id', 'DESC')->get()->getRowArray()['ringkasan']);
    }

    public function testKirimCadanganMencatatHasil(): void
    {
        $berkas = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cadangan-milklife-2026-11-25-0200.zip';
        file_put_contents($berkas, 'zip karangan');

        try {
            $this->assertNull(SalinanR2::kirim($berkas), 'Belum diatur: tidak dikirim.');
            $this->assertSame([], $this->metode);

            $this->aturLangsung();
            $this->assertSame('cadangan-panitia/produksi-karangan/cadangan-milklife-2026-11-25-0200.zip', SalinanR2::kirim($berkas));
            $this->assertSame('zip karangan', $this->bucket['produksi-karangan/cadangan-milklife-2026-11-25-0200.zip']['isi']);
            $status = SalinanR2::pengaturan();
            $this->assertTrue($status['kirim_berhasil']);
            $this->assertStringStartsWith('cadangan-milklife-2026-11-25-0200.zip (', (string) $status['kirim_pesan']);

            $this->status    = 404;
            $this->kodeGalat = 'NoSuchBucket';

            try {
                SalinanR2::kirim($berkas);
                $this->fail('Kiriman gagal harus menjadi galat.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('Bucket "cadangan-panitia" tidak ada', $e->getMessage());
            }

            $status = SalinanR2::pengaturan();
            $this->assertFalse($status['kirim_berhasil']);
            $this->assertStringContainsString('tidak ada di akun Cloudflare ini', (string) $status['kirim_pesan']);
            $this->bukaSebagaiAdmin(self::ALAMAT)->assertSee('Gagal');
        } finally {
            @unlink($berkas);
        }
    }

    public function testRahasiaTidakBisaDibukaSetelahKunciEnkripsiBerubah(): void
    {
        $this->aturLangsung();
        config('Encryption')->key = random_bytes(32);
        Services::resetSingle('encrypter');

        $this->kirimSebagaiAdmin(self::ALAMAT . '/r2/periksa');
        $this->assertStringContainsString('Isi ulang Secret Access Key', (string) ($_SESSION['pesan_galat'] ?? ''));
    }

    public function testPengaturanR2TidakIkutCadanganLengkap(): void
    {
        $this->assertContains(SalinanR2::TABEL, CadanganLengkap::TABEL_DILEWATI);
    }

    /**
     * @param array<string, string> $ganti
     *
     * @return array<string, string>
     */
    private function isian(array $ganti = []): array
    {
        return $ganti + [
            'id_akun'  => self::AKUN,
            'bucket'   => 'cadangan-panitia',
            'kunci_id' => '0123456789abcdef0123456789abcdef',
            'rahasia'  => self::RAHASIA,
            'folder'   => 'produksi-karangan',
            'sandi'    => self::SANDI,
        ];
    }

    private function aturLangsung(): void
    {
        SalinanR2::simpan(['id_akun' => self::AKUN, 'bucket' => 'cadangan-panitia', 'kunci_id' => '0123456789abcdef0123456789abcdef', 'folder' => 'produksi-karangan'], self::RAHASIA);
    }

    /**
     * Bucket tiruan: PUT menyimpan, GET mendaftar isi berawalan `prefix`.
     *
     * @param array<string, string> $kepala
     *
     * @return array{status: int, isi: string}
     */
    private function r2Tiruan(string $metode, string $url, array $kepala, ?string $berkas, ?string $isi): array
    {
        $this->metode[] = $metode;
        $this->assertStringStartsWith('https://' . self::AKUN . '.r2.cloudflarestorage.com/cadangan-panitia', $url);
        $this->assertStringContainsString('Credential=0123456789abcdef0123456789abcdef/', $kepala['authorization']);

        if ($this->status !== 200) {
            return ['status' => $this->status, 'isi' => '<?xml version="1.0" encoding="UTF-8"?><Error><Code>' . $this->kodeGalat . '</Code></Error>'];
        }

        $bagian = (array) parse_url($url);
        $kunci  = rawurldecode(substr((string) $bagian['path'], strlen('/cadangan-panitia/')));

        if ($metode === 'PUT') {
            $this->bucket[$kunci] = ['isi' => $berkas === null ? (string) $isi : (string) file_get_contents($berkas), 'waktu' => time()];

            return ['status' => 200, 'isi' => ''];
        }

        parse_str((string) ($bagian['query'] ?? ''), $kueri);
        $awalan = (string) ($kueri['prefix'] ?? '');
        $xml    = '<?xml version="1.0" encoding="UTF-8"?><ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><IsTruncated>false</IsTruncated>';
        ksort($this->bucket);

        foreach ($this->bucket as $nama => $objek) {
            if (str_starts_with($nama, $awalan)) {
                $xml .= '<Contents><Key>' . htmlspecialchars($nama) . '</Key><LastModified>' . gmdate('Y-m-d\TH:i:s.000\Z', $objek['waktu']) . '</LastModified><Size>' . strlen($objek['isi']) . '</Size></Contents>';
            }
        }

        return ['status' => 200, 'isi' => $xml . '</ListBucketResult>'];
    }
}
