<?php

use App\Libraries\Panel\BerkasBuktiBayar;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\PanelDataContoh;

/**
 * Penyimpanan berkas bukti bayar (K34): gambar dikecilkan dan disimpan ulang
 * sebagai JPEG, berkas bukan gambar ditolak, jalur tidak bisa keluar folder.
 * Gambar dibuat GD di uji.
 *
 * Bagian yang memakai tabel `bukti_bayar` diuji di
 * `tests/basisdata/PanelBuktiBayarTest.php`.
 *
 * @internal
 */
final class PanelBerkasBuktiBayarTest extends CIUnitTestCase
{
    use PanelDataContoh;

    /**
     * @var list<string>
     */
    private array $sumberSementara = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pakaiFolderBuktiBayarSementara();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();

        foreach ($this->sumberSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        parent::tearDown();
    }

    private function unggahan(string $isi, string $nama = 'bukti.png', int $galat = UPLOAD_ERR_OK): UploadedFile
    {
        $sumber = tempnam(sys_get_temp_dir(), 'milklife-sumber-');
        file_put_contents($sumber, $isi);
        $this->sumberSementara[] = $sumber;

        return new UploadedFile($galat === UPLOAD_ERR_OK ? $sumber : '', $nama, 'image/png', strlen($isi), $galat);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function ukuranGambar(string $jalur): array
    {
        $info = getimagesize((string) (new BerkasBuktiBayar())->jalurPenuh($jalur));
        $this->assertIsArray($info);

        return [$info[0], $info[1], $info[2]];
    }

    public function testGambarBesarDikecilkanKeSisiTerpanjang1600(): void
    {
        $hasil = (new BerkasBuktiBayar())->simpanIsi(self::gambarPanel(3200, 2000, 'png'));

        $this->assertMatchesRegularExpression('#^bukti-bayar/[0-9a-f]{32}\.jpg$#D', $hasil['berkas']);
        $this->assertSame([1600, 1000, IMAGETYPE_JPEG], $this->ukuranGambar($hasil['berkas']));
        $this->assertSame(filesize($this->folderBuktiBayar . $hasil['berkas']), $hasil['ukuran']);
        $this->assertSame([$hasil['berkas']], $this->berkasBuktiBayarTersimpan());
    }

    public function testGambarTegakDikecilkanMenurutTinggi(): void
    {
        $hasil = (new BerkasBuktiBayar())->simpanIsi(self::gambarPanel(900, 2400, 'jpg'));

        $this->assertSame([600, 1600, IMAGETYPE_JPEG], $this->ukuranGambar($hasil['berkas']));
    }

    public function testGambarKecilTidakDibesarkanTetapiTetapJpeg(): void
    {
        foreach (['png', 'jpg', 'webp'] as $jenis) {
            $hasil = (new BerkasBuktiBayar())->simpanIsi(self::gambarPanel(300, 200, $jenis));
            $this->assertSame([300, 200, IMAGETYPE_JPEG], $this->ukuranGambar($hasil['berkas']), $jenis);
        }

        // Nama acak, tidak pernah sama.
        $this->assertCount(3, array_unique($this->berkasBuktiBayarTersimpan()));
    }

    public function testBukanGambarDitolak(): void
    {
        $berkas = new BerkasBuktiBayar();

        foreach ([
            'teks'      => 'bukan gambar',
            'php'       => '<?php echo 1;',
            'pdf'       => "%PDF-1.4\n%%EOF\n",
            'gif'       => (string) base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true),
            'kosong'    => '',
            'png rusak' => substr(self::gambarPanel(50, 50), 0, 40),
        ] as $nama => $isi) {
            try {
                $berkas->simpanIsi($isi);
                $this->fail("{$nama} seharusnya ditolak.");
            } catch (RuntimeException $e) {
                $this->assertNotSame('', $e->getMessage(), $nama);
            }
        }

        $this->assertSame([], $this->berkasBuktiBayarTersimpan());
    }

    public function testPeriksaUnggahan(): void
    {
        $berkas = new BerkasBuktiBayar();

        $this->assertNull($berkas->periksa($this->unggahan(self::gambarPanel(40, 30))));
        $this->assertStringContainsString('bukan gambar', (string) $berkas->periksa($this->unggahan('<?php echo 1;', 'bukti.jpg')));
        $this->assertStringContainsString('Belum ada gambar', (string) $berkas->periksa(null));
        $this->assertStringContainsString('Belum ada gambar', (string) $berkas->periksa($this->unggahan('', 'a.png', UPLOAD_ERR_NO_FILE)));
        $this->assertStringContainsString('terlalu besar', (string) $berkas->periksa($this->unggahan('', 'a.png', UPLOAD_ERR_INI_SIZE)));
        $this->assertStringContainsString('kosong', (string) $berkas->periksa($this->unggahan('')));
    }

    public function testSimpanUnggahan(): void
    {
        $hasil = (new BerkasBuktiBayar())->simpan($this->unggahan(self::gambarPanel(2000, 500), 'struk transfer.PNG'));

        $this->assertSame([1600, 400, IMAGETYPE_JPEG], $this->ukuranGambar($hasil['berkas']));

        $this->expectException(RuntimeException::class);
        (new BerkasBuktiBayar())->simpan($this->unggahan('bukan gambar'));
    }

    public function testUnggahanSungguhanWajibDiLuarUji(): void
    {
        BerkasBuktiBayar::gantiFolderDasar(null);

        try {
            // Berkas biasa di disk, bukan hasil unggahan HTTP: ditolak.
            $this->assertStringContainsString('gagal diterima', (string) (new BerkasBuktiBayar())->periksa($this->unggahan(self::gambarPanel(10, 10))));
        } finally {
            BerkasBuktiBayar::gantiFolderDasar($this->folderBuktiBayar);
        }
    }

    public function testJalurPenuhHanyaUntukJalurSah(): void
    {
        $berkas = new BerkasBuktiBayar();
        $sah    = 'bukti-bayar/' . str_repeat('a1', 16) . '.jpg';

        $this->assertSame($this->folderBuktiBayar . $sah, $berkas->jalurPenuh($sah));

        foreach ([
            null,
            '',
            '../.env',
            'bukti-bayar/../../.env',
            'penanggung-jawab/' . str_repeat('a1', 16) . '.jpg',
            'bukti-bayar/' . str_repeat('a1', 16) . '.php',
            'bukti-bayar/' . str_repeat('A1', 16) . '.jpg',
            '/bukti-bayar/' . str_repeat('a1', 16) . '.jpg',
            'bukti-bayar/' . str_repeat('a1', 16) . ".jpg\n",
        ] as $jalur) {
            $this->assertNull($berkas->jalurPenuh($jalur), var_export($jalur, true));
        }
    }

    public function testHapus(): void
    {
        $berkas = new BerkasBuktiBayar();
        $a      = $berkas->simpanIsi(self::gambarPanel(20, 20))['berkas'];
        $b      = $berkas->simpanIsi(self::gambarPanel(20, 20))['berkas'];

        $this->assertTrue($berkas->hapus($a));
        $this->assertFalse($berkas->hapus($a));
        $this->assertFalse($berkas->hapus('../.env'));
        $this->assertSame([$b], $this->berkasBuktiBayarTersimpan());

        $this->assertSame(1, $berkas->hapusBerkas([$a, $b, 'bukti-bayar/x.jpg']));
        $this->assertSame([], $this->berkasBuktiBayarTersimpan());
    }

    public function testFolderBawaanDiLuarPublik(): void
    {
        BerkasBuktiBayar::gantiFolderDasar(null);

        try {
            $this->assertSame(WRITEPATH . 'unggahan' . DIRECTORY_SEPARATOR, BerkasBuktiBayar::folderDasar());
            $this->assertStringNotContainsString(FCPATH, BerkasBuktiBayar::folderDasar());
        } finally {
            BerkasBuktiBayar::gantiFolderDasar($this->folderBuktiBayar);
        }
    }
}
