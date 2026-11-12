<?php

use App\Filters\BatasUnggahFilter;
use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UjiHalaman;

/**
 * Kiriman yang melebihi `post_max_size` PHP (F3-04).
 *
 * PHP membuang seluruh isian dan berkas kiriman seperti itu, termasuk token
 * CSRF. Filter `batasunggah` (sebelum `csrf`) mengenalinya dari
 * `CONTENT_LENGTH` yang besar sementara isian dan berkasnya kosong, lalu
 * mengalihkan kembali ke halaman asal dengan pesan kilat `pesan_galat`.
 *
 * Di uji, kiriman kebesaran ditiru dengan `CONTENT_LENGTH` di atas batas dan
 * isian kosong, sama seperti yang diterima aplikasi dari PHP.
 *
 * @internal
 */
final class BatasUnggahTest extends UjiBasisData
{
    use KirimFormulir;

    private const HALAMAN = 'admin/pengaturan/halaman-depan';

    private ?string $panjangAsli = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panjangAsli = $_SERVER['CONTENT_LENGTH'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->panjangAsli === null) {
            unset($_SERVER['CONTENT_LENGTH']);
        } else {
            $_SERVER['CONTENT_LENGTH'] = $this->panjangAsli;
        }

        parent::tearDown();
    }

    // ---------- Aturan ----------

    public function testUkuranDariPhpIni(): void
    {
        $this->assertSame(12 * 1024 * 1024, BatasUnggahFilter::bitaDariIni('12M'));
        $this->assertSame(512 * 1024, BatasUnggahFilter::bitaDariIni('512K'));
        $this->assertSame(1024 ** 3, BatasUnggahFilter::bitaDariIni('1g'));
        $this->assertSame(8_388_608, BatasUnggahFilter::bitaDariIni('8388608'));
        $this->assertSame(0, BatasUnggahFilter::bitaDariIni('0'));
        $this->assertSame(0, BatasUnggahFilter::bitaDariIni(''));
        $this->assertSame(0, BatasUnggahFilter::bitaDariIni('banyak'));
    }

    public function testPesanMenyebutBatasDanCaraMemperbaiki(): void
    {
        $pesan = BatasUnggahFilter::pesan(12 * 1024 * 1024);

        $this->assertStringContainsString('batas 12 MB', $pesan);
        $this->assertStringContainsString('Perkecil dulu ukuran berkasnya', $pesan);
        $this->assertStringContainsString('lalu kirim lagi', $pesan);

        $this->assertStringContainsString('batas 512 KB', BatasUnggahFilter::pesan(512 * 1024));
    }

    public function testAlamatSendiri(): void
    {
        $dasar = rtrim(base_url(), '/');

        $this->assertTrue(BatasUnggahFilter::alamatSendiri($dasar . '/admin/pengaturan/halaman-depan'));
        $this->assertTrue(BatasUnggahFilter::alamatSendiri($dasar . '/admin/pengaturan/jadwal/3/ubah?tab=1'));
        $this->assertTrue(BatasUnggahFilter::alamatSendiri($dasar . '/'));
        $this->assertTrue(BatasUnggahFilter::alamatSendiri($dasar));

        foreach ([
            null,
            '',
            'https://jahat.example/admin',
            '//jahat.example/admin',
            '/admin/dasbor',
            $dasar . '.jahat.example/admin',
            $dasar . '@jahat.example/admin',
            $dasar . '/admin/../../keluar',
            $dasar . "/admin\r\nLocation: https://jahat.example/",
            $dasar . '/admin\\jahat',
            'javascript:alert(1)',
            $dasar . '/' . str_repeat('a', 2001),
        ] as $alamat) {
            $this->assertFalse(BatasUnggahFilter::alamatSendiri($alamat), 'Seharusnya ditolak: ' . var_export($alamat, true));
        }
    }

    // ---------- Kiriman kebesaran ----------

    public function testKirimanKebesaranKembaliKeHalamanAsalDenganPesan(): void
    {
        setting('HalamanDepan.judul', 'Judul lama');

        $asal  = site_url(self::HALAMAN);
        $hasil = $this->kirimKebesaran(self::HALAMAN, ['Referer' => $asal]);

        $hasil->assertRedirectTo($asal);
        $this->assertPesanBatas($hasil);
        $this->assertSame('Judul lama', setting('HalamanDepan.judul'), 'Pengaturan tidak boleh berubah.');
    }

    public function testPesanTampilDiHalamanPengaturan(): void
    {
        $this->kirimKebesaran(self::HALAMAN, ['Referer' => site_url(self::HALAMAN)]);

        // Sesi dari kiriman tadi (berisi pesan kilat) dibawa ke halaman berikutnya.
        $hasil = $this->bukaSebagaiAdmin(self::HALAMAN, $_SESSION);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $kotak = $xpath->query('//main//div[@data-pesan="pesan_galat"]')->item(0);
        $this->assertNotNull($kotak, 'Pesan galat tidak tampil di halaman pengaturan.');
        $this->assertStringContainsString('lebih dari batas ' . $this->batasTeks(), $kotak->textContent);
    }

    public function testHalamanDaftarDataKembaliKeFormulirUbah(): void
    {
        // `POST admin/pengaturan/unduhan/5` tidak punya `GET`, jadi kembali ke
        // halaman asal yang dibuka peramban.
        $asal  = site_url('admin/pengaturan/unduhan/5/ubah');
        $hasil = $this->kirimKebesaran('admin/pengaturan/unduhan/5', ['Referer' => $asal]);

        $hasil->assertRedirectTo($asal);
        $this->assertPesanBatas($hasil);
    }

    public function testHalamanTerakhirDiSesiDidahulukan(): void
    {
        $asal  = site_url('admin/pengaturan/unduhan/tambah');
        $hasil = $this->kirimKebesaran('admin/pengaturan/unduhan', ['Referer' => site_url('admin/pengaturan')], ['_ci_previous_url' => $asal]);

        $hasil->assertRedirectTo($asal);
    }

    public function testAlamatAsalDariSitusLainTidakDipakai(): void
    {
        foreach ([
            'https://jahat.example/tiruan',
            rtrim(base_url(), '/') . '.jahat.example/admin',
            '//jahat.example/',
        ] as $referer) {
            $hasil = $this->kirimKebesaran(self::HALAMAN, ['Referer' => $referer], ['_ci_previous_url' => 'https://jahat.example/lain']);

            $hasil->assertRedirectTo(site_url('admin/dasbor'));
            $this->assertPesanBatas($hasil);
        }
    }

    public function testTanpaAlamatAsalKeDasbor(): void
    {
        $hasil = $this->kirimKebesaran(self::HALAMAN);

        $hasil->assertRedirectTo(site_url('admin/dasbor'));
        $this->assertPesanBatas($hasil);
    }

    public function testAlamatCadanganHalamanPublikKeHalamanDepan(): void
    {
        $permintaan = Services::incomingrequest(null, false);
        $permintaan->setPath('pendaftaran');

        $this->assertSame(site_url('/'), BatasUnggahFilter::alamatKembali($permintaan));

        $permintaan->setPath('admin/pengaturan/acara');
        $this->assertSame(site_url('admin/dasbor'), BatasUnggahFilter::alamatKembali($permintaan));
    }

    // ---------- Kiriman lain tidak berubah ----------

    public function testKirimanBiasaTetapTersimpan(): void
    {
        $_SERVER['CONTENT_LENGTH'] = '2048';

        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/acara', [
            'nama'            => 'Lomba Lari Karangan',
            'seri'            => '3',
            'tahun'           => '2027',
            'tempat'          => 'Stadion Karangan',
            'tanggal_mulai'   => '2027-01-30',
            'tanggal_selesai' => '2027-02-01',
        ]);

        $this->assertBerhasilDialihkan($hasil, 'admin/pengaturan/acara', 'Data acara tersimpan.');
        $this->assertSame('Lomba Lari Karangan', setting('Acara.nama'));
    }

    public function testCsrfTetapMemeriksaKirimanKecilTanpaToken(): void
    {
        $_SERVER['CONTENT_LENGTH'] = '0';

        $this->assertTokenCsrfWajib(self::HALAMAN, []);
        $this->assertTokenCsrfWajib('admin/pengaturan/acara', ['nama' => 'Lomba Lari Karangan']);
        $this->assertSame('', setting('Acara.nama'));
    }

    public function testKirimanBesarYangMasihBerisiTetapDiperiksaCsrf(): void
    {
        // Ukuran besar tetapi isiannya ada: bukan kiriman yang dibuang PHP,
        // jadi filter tidak ikut campur dan CSRF tetap menolak token salah.
        $_SERVER['CONTENT_LENGTH'] = (string) ($this->batas() + 1);

        $this->assertTokenCsrfWajib('admin/pengaturan/acara', ['nama' => 'Lomba Lari Karangan']);
        $this->assertSame('', setting('Acara.nama'));
    }

    public function testBatasNolBerartiTanpaBatas(): void
    {
        $permintaan = Services::incomingrequest(null, false);
        $permintaan->setGlobal('server', ['CONTENT_LENGTH' => '999999999999']);

        $this->assertFalse(BatasUnggahFilter::kebesaran($permintaan, 0));
        $this->assertTrue(BatasUnggahFilter::kebesaran($permintaan, 1024));

        $permintaan->setGlobal('server', ['CONTENT_LENGTH' => '1024']);
        $this->assertFalse(BatasUnggahFilter::kebesaran($permintaan, 1024));

        $permintaan->setGlobal('server', ['CONTENT_LENGTH' => 'banyak']);
        $this->assertFalse(BatasUnggahFilter::kebesaran($permintaan, 1024));

        $permintaan->setGlobal('server', []);
        $this->assertFalse(BatasUnggahFilter::kebesaran($permintaan, 1024));
    }

    public function testFilterBerjalanSebelumCsrf(): void
    {
        $sebelum = config('Filters')->globals['before'];

        $this->assertContains('batasunggah', $sebelum);
        $this->assertContains('csrf', $sebelum);
        $this->assertLessThan(array_search('csrf', $sebelum, true), array_search('batasunggah', $sebelum, true));
    }

    // ---------- Bantuan ----------

    /**
     * Kirim `POST` seperti yang diterima aplikasi setelah PHP membuang
     * kiriman kebesaran: `CONTENT_LENGTH` di atas batas, isian kosong,
     * tanpa token CSRF.
     *
     * @param array<string, string> $kepala
     * @param array<string, mixed>  $sesi
     */
    private function kirimKebesaran(string $alamat, array $kepala = [], array $sesi = []): TestResponse
    {
        $_SERVER['CONTENT_LENGTH'] = (string) ($this->batas() + 1);
        Services::resetSingle('security');

        try {
            return $this->withSession($sesi + UjiHalaman::SESI_ADMIN)->withHeaders($kepala)->post($alamat);
        } catch (SecurityException $e) {
            $this->fail('Kiriman kebesaran seharusnya dialihkan dengan pesan, bukan ditolak CSRF: ' . $e->getMessage());
        }
    }

    private function assertPesanBatas(TestResponse $hasil): void
    {
        $pesan = $_SESSION['pesan_galat'] ?? null;

        $this->assertIsString($pesan, 'Pesan kilat pesan_galat tidak ada.');
        $this->assertSame(BatasUnggahFilter::pesan($this->batas()), $pesan);
        $this->assertStringContainsString('lebih dari batas ' . $this->batasTeks(), $pesan);
    }

    private function batas(): int
    {
        $batas = BatasUnggahFilter::bitaDariIni((string) ini_get('post_max_size'));

        if ($batas <= 0) {
            $this->markTestSkipped('post_max_size tidak dibatasi di PHP ini.');
        }

        return $batas;
    }

    private function batasTeks(): string
    {
        return App\Libraries\Unggahan::ukuranTeks($this->batas());
    }
}
