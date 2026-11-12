<?php

use App\Libraries\Unggahan;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\UnggahanSementara;

/**
 * Penyimpanan gambar dan berkas unggahan (`App\Libraries\Unggahan`).
 *
 * Folder dasar diganti ke folder sementara (`UnggahanSementara`), jadi uji
 * tidak menulis ke `public/unggahan/`.
 *
 * @internal
 */
final class UnggahanTest extends CIUnitTestCase
{
    use UnggahanSementara;

    private Unggahan $unggahan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unggahan = new Unggahan();
    }

    public function testFolderDasarDigantiSelamaUji(): void
    {
        $this->assertSame($this->folderUnggahan, Unggahan::folderDasar());
        $this->assertStringNotContainsString(FCPATH, Unggahan::folderDasar());

        Unggahan::gantiFolderDasar(null);
        $this->assertSame(FCPATH . 'unggahan' . DIRECTORY_SEPARATOR, Unggahan::folderDasar());
    }

    public function testSimpanGambarPng(): void
    {
        $berkas = $this->berkas('logo bank.png', self::isiPng());

        $this->assertNull($this->unggahan->periksaGambar($berkas));
        $jalur = $this->unggahan->simpanGambar($berkas, 'pembayaran');

        $this->assertMatchesRegularExpression('#^unggahan/pembayaran/[a-f0-9]{32}\.png$#', $jalur);
        $this->assertFileExists($this->jalurUnggahan($jalur));
        $this->assertSame(self::isiPng(), file_get_contents($this->jalurUnggahan($jalur)));
        $this->assertSame([$jalur], $this->isiFolderUnggahan());
        $this->assertTrue($this->unggahan->ada($jalur));
        $this->assertSame('PNG', $this->unggahan->jenis($jalur));
        $this->assertSame(strlen(self::isiPng()), $this->unggahan->ukuran($jalur));
    }

    public function testEkstensiDariIsiBukanDariNamaBerkas(): void
    {
        $this->assertStringEndsWith('.jpg', $this->unggahan->simpanGambar($this->berkas('foto.JPEG', self::isiJpg()), 'halaman-depan'));
        $this->assertStringEndsWith('.webp', $this->unggahan->simpanGambar($this->berkas('kepala.webp', self::isiWebp()), 'halaman-depan'));

        // PNG yang namanya .jpg tetap disimpan sebagai .png.
        $this->assertStringEndsWith('.png', $this->unggahan->simpanGambar($this->berkas('logo.jpg', self::isiPng()), 'halaman-depan'));

        // Nama tanpa ekstensi pun disimpan dengan ekstensi dari isinya.
        $this->assertStringEndsWith('.png', $this->unggahan->simpanGambar($this->berkas('logo', self::isiPng()), 'halaman-depan'));
    }

    public function testNamaBerkasAcakDanTidakBertabrakan(): void
    {
        $a = $this->unggahan->simpanGambar($this->berkas('logo.png', self::isiPng()), 'pembayaran');
        $b = $this->unggahan->simpanGambar($this->berkas('logo.png', self::isiPng()), 'pembayaran');

        $this->assertNotSame($a, $b);
        $this->assertStringNotContainsString('logo', $a);
        $this->assertCount(2, $this->isiFolderUnggahan());
    }

    public function testSkripDenganNamaGambarDitolak(): void
    {
        foreach (['logo.png' => "<?php echo 'halo';", 'logo.php' => self::isiPng() . '<?php', 'gambar.svg' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'catatan.txt' => 'teks biasa'] as $nama => $isi) {
            $berkas = $this->berkas($nama, $isi);
            $pesan  = $this->unggahan->periksaGambar($berkas);

            if ($nama === 'logo.php') {
                // Isinya PNG sungguhan; disimpan sebagai .png, tidak pernah .php.
                $this->assertNull($pesan);
                $this->assertStringEndsWith('.png', $this->unggahan->simpanGambar($berkas, 'pembayaran'));

                continue;
            }

            $this->assertSame('Jenis gambar tidak bisa dipakai. Unggah gambar JPG, PNG, atau WEBP.', $pesan, $nama);
        }

        foreach ($this->isiFolderUnggahan() as $jalur) {
            $this->assertStringEndsWith('.png', $jalur);
        }
    }

    public function testPdfBukanGambar(): void
    {
        $this->assertSame(
            'Jenis gambar tidak bisa dipakai. Unggah gambar JPG, PNG, atau WEBP.',
            $this->unggahan->periksaGambar($this->berkas('poster.pdf', self::isiPdf())),
        );
    }

    public function testGambarRusakDitolakWalauAwalannyaGambar(): void
    {
        $rusak = substr(self::isiPng(), 0, 16);

        $this->assertNotNull($this->unggahan->periksaGambar($this->berkas('rusak.png', $rusak)));
    }

    public function testGambarMelebihiDuaMegabitaDitolak(): void
    {
        $besar = self::isiPng() . str_repeat("\0", Unggahan::BATAS_GAMBAR);
        $pesan = $this->unggahan->periksaGambar($this->berkas('besar.png', $besar));

        $this->assertSame('Ukuran gambar 2 MB, lebih dari batas 2 MB. Perkecil dulu, lalu unggah lagi.', $pesan);
    }

    public function testBatasPhpTerlampauiDitolakDenganPesanRamah(): void
    {
        foreach ([UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE] as $kode) {
            $this->assertSame(
                'Ukuran gambar terlalu besar. Paling besar 2 MB; perkecil dulu, lalu unggah lagi.',
                $this->unggahan->periksaGambar($this->berkas('besar.png', '', $kode)),
            );
        }

        $this->assertSame(
            'Ukuran berkas terlalu besar. Paling besar 10 MB; perkecil dulu, lalu unggah lagi.',
            $this->unggahan->periksaBerkas($this->berkas('juknis.pdf', '', UPLOAD_ERR_INI_SIZE)),
        );

        $this->assertStringContainsString('terputus', (string) $this->unggahan->periksaGambar($this->berkas('a.png', '', UPLOAD_ERR_PARTIAL)));
    }

    public function testBelumMemilihBerkas(): void
    {
        $kosong = $this->berkas('', '', UPLOAD_ERR_NO_FILE);

        $this->assertFalse(Unggahan::dipilih($kosong));
        $this->assertFalse(Unggahan::dipilih(null));
        $this->assertTrue(Unggahan::dipilih($this->berkas('logo.png', self::isiPng())));
        $this->assertSame('Belum ada gambar yang dipilih. Pilih gambar JPG, PNG, atau WEBP dari komputer atau ponsel Anda.', $this->unggahan->periksaGambar($kosong));
        $this->assertStringStartsWith('Belum ada berkas yang dipilih.', (string) $this->unggahan->periksaBerkas(null));
    }

    public function testBerkasKosongDitolak(): void
    {
        $this->assertSame('Gambar yang dipilih kosong. Pilih gambar lain yang berisi.', $this->unggahan->periksaGambar($this->berkas('kosong.png', '')));
    }

    public function testSimpanGambarTidakSahMelemparPesanRamah(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Jenis gambar tidak bisa dipakai. Unggah gambar JPG, PNG, atau WEBP.');

        try {
            $this->unggahan->simpanGambar($this->berkas('logo.png', 'bukan gambar'), 'pembayaran');
        } finally {
            $this->assertSame([], $this->isiFolderUnggahan());
        }
    }

    public function testFolderTidakDikenalDitolak(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Folder unggahan tidak dikenal: ../luar.');

        $this->unggahan->simpanGambar($this->berkas('logo.png', self::isiPng()), '../luar');
    }

    public function testBerkasUnduhan(): void
    {
        $pdf = $this->unggahan->simpanBerkas($this->berkas('Juknis Seri 2.pdf', self::isiPdf()), 'unduhan');
        $jpg = $this->unggahan->simpanBerkas($this->berkas('flyer.jpg', self::isiJpg()), 'unduhan');

        $this->assertMatchesRegularExpression('#^unggahan/unduhan/[a-f0-9]{32}\.pdf$#', $pdf);
        $this->assertStringEndsWith('.jpg', $jpg);
        $this->assertSame('PDF', $this->unggahan->jenis($pdf));

        // WEBP hanya untuk gambar, bukan untuk berkas unduhan.
        $this->assertSame(
            'Jenis berkas tidak bisa dipakai. Unggah berkas PDF, JPG, PNG, DOC, DOCX, XLS, atau XLSX.',
            $this->unggahan->periksaBerkas($this->berkas('gambar.webp', self::isiWebp())),
        );
        $this->assertNotNull($this->unggahan->periksaBerkas($this->berkas('skrip.php', "<?php echo 'halo';")));
        $this->assertNotNull($this->unggahan->periksaBerkas($this->berkas('halaman.html', '<!doctype html><html><body>halo</body></html>')));
    }

    public function testDokumenOfficeDipilihDariEkstensiYangDiizinkan(): void
    {
        $zip = $this->isiZip();

        $this->assertStringEndsWith('.docx', $this->unggahan->simpanBerkas($this->berkas('surat.docx', $zip), 'unduhan'));
        $this->assertStringEndsWith('.xlsx', $this->unggahan->simpanBerkas($this->berkas('rekap.XLSX', $zip), 'unduhan'));

        // ZIP yang bukan bernama dokumen Office ditolak.
        $this->assertNotNull($this->unggahan->periksaBerkas($this->berkas('arsip.zip', $zip)));
        $this->assertNotNull($this->unggahan->periksaBerkas($this->berkas('surat.php', $zip)));
    }

    public function testBerkasUnduhanMelebihiSepuluhMegabitaDitolak(): void
    {
        $besar = self::isiPdf() . str_repeat(' ', Unggahan::BATAS_BERKAS);

        $this->assertSame(
            'Ukuran berkas 10 MB, lebih dari batas 10 MB. Perkecil dulu, lalu unggah lagi.',
            $this->unggahan->periksaBerkas($this->berkas('besar.pdf', $besar)),
        );
    }

    public function testHapusBerkasLama(): void
    {
        $jalur = $this->unggahan->simpanGambar($this->berkas('logo.png', self::isiPng()), 'halaman-depan');

        $this->assertTrue($this->unggahan->hapus($jalur));
        $this->assertFileDoesNotExist($this->jalurUnggahan($jalur));
        $this->assertFalse($this->unggahan->ada($jalur));
        $this->assertNull($this->unggahan->ukuran($jalur));

        // Sudah tidak ada, kosong, atau di luar folder unggahan: diabaikan.
        $this->assertFalse($this->unggahan->hapus($jalur));
        $this->assertFalse($this->unggahan->hapus(''));
        $this->assertFalse($this->unggahan->hapus(null));
    }

    public function testJalurDiLuarFolderUnggahanTidakDiterima(): void
    {
        $penting = $this->folderUnggahan . 'penting.txt';
        file_put_contents($penting, 'jangan dihapus');

        foreach ([
            'unggahan/../penting.txt',
            'unggahan/pembayaran/../../penting.txt',
            '/etc/passwd',
            'unggahan/lain/' . str_repeat('a', 32) . '.png',
            'unggahan/pembayaran/logo.png',
            'unggahan/pembayaran/' . str_repeat('a', 32) . '.png/../x',
            'aset/css/tema.css',
        ] as $jalur) {
            $this->assertNull($this->unggahan->jalurPenuh($jalur), $jalur);
            $this->assertFalse($this->unggahan->hapus($jalur), $jalur);
            $this->assertSame('', $this->unggahan->jenis($jalur), $jalur);
        }

        $this->assertFileExists($penting);
    }

    public function testBerkasBukanUnggahanSungguhanDitolakDiLuarUji(): void
    {
        Unggahan::gantiFolderDasar(null);

        $pesan = $this->unggahan->periksaGambar($this->berkas('logo.png', self::isiPng()));

        $this->assertSame('Gambar gagal diterima. Coba unggah sekali lagi; bila masih gagal, hubungi pengelola komputer server.', $pesan);
    }

    public function testUkuranTeks(): void
    {
        $this->assertSame('500 bita', Unggahan::ukuranTeks(500));
        $this->assertSame('1 KB', Unggahan::ukuranTeks(1024));
        $this->assertSame('850 KB', Unggahan::ukuranTeks(850 * 1024));
        $this->assertSame('1,5 MB', Unggahan::ukuranTeks((int) (1.5 * 1024 * 1024)));
        $this->assertSame('2 MB', Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR));
        $this->assertSame('10 MB', Unggahan::ukuranTeks(Unggahan::BATAS_BERKAS));
    }

    public function testFolderDanBatasSesuaiRencana(): void
    {
        $this->assertSame(['halaman-depan', 'pembayaran', 'unduhan'], Unggahan::FOLDER);
        $this->assertSame(['jpg', 'png', 'webp'], array_keys(Unggahan::JENIS_GAMBAR));
        $this->assertSame(['pdf', 'jpg', 'png', 'doc', 'docx', 'xls', 'xlsx'], array_keys(Unggahan::JENIS_BERKAS));
        $this->assertSame(2 * 1024 * 1024, Unggahan::BATAS_GAMBAR);
        $this->assertSame(10 * 1024 * 1024, Unggahan::BATAS_BERKAS);
    }

    public function testBerkasDariIsianFormulir(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', self::isiPng());
        $this->siapkanBerkas('kosong', '', '', UPLOAD_ERR_NO_FILE);

        $permintaan = service('incomingrequest', null, false);
        $berkas     = $permintaan->getFile('logo_bank');

        $this->assertInstanceOf(UploadedFile::class, $berkas);
        $this->assertSame('unggahan/pembayaran/', substr($this->unggahan->simpanGambar($berkas, 'pembayaran'), 0, 20));
        $this->assertFalse(Unggahan::dipilih($permintaan->getFile('kosong')));
    }

    /**
     * Berkas unggahan tiruan dengan isi tertentu.
     */
    public function testGambarPngBesarDikecilkanDanTetapTransparan(): void
    {
        $jalur = $this->unggahan->simpanGambar($this->berkas('kepala.png', self::gambar('png', 2400, 1200)), 'halaman-depan');
        $penuh = $this->jalurUnggahan($jalur);

        $this->assertStringEndsWith('.png', $jalur);
        $info = getimagesize($penuh);
        $this->assertSame([1920, 960, IMAGETYPE_PNG], [$info[0], $info[1], $info[2]]);

        $gambar = imagecreatefrompng($penuh);
        // Sudut kiri atas bening, tengah merah pekat.
        $this->assertSame(127, imagecolorsforindex($gambar, imagecolorat($gambar, 5, 5))['alpha']);
        $tengah = imagecolorsforindex($gambar, imagecolorat($gambar, 960, 480));
        $this->assertSame([255, 0, 0, 0], [$tengah['red'], $tengah['green'], $tengah['blue'], $tengah['alpha']]);
        $this->assertSame(filesize($penuh), $this->unggahan->ukuran($jalur));
    }

    public function testGambarJpgDanWebpBesarDikecilkanDenganFormatAsli(): void
    {
        $jpg = $this->jalurUnggahan($this->unggahan->simpanGambar($this->berkas('kepala.jpg', self::gambar('jpg', 1500, 3000)), 'halaman-depan'));
        $info = getimagesize($jpg);
        $this->assertSame([960, 1920, IMAGETYPE_JPEG], [$info[0], $info[1], $info[2]]);

        $webp = $this->jalurUnggahan($this->unggahan->simpanGambar($this->berkas('logo.webp', self::gambar('webp', 2000, 2000)), 'pembayaran'));
        $info = getimagesize($webp);
        $this->assertSame([1920, 1920, IMAGETYPE_WEBP], [$info[0], $info[1], $info[2]]);
        // WEBP lossy: sudut tetap (hampir) bening.
        $gambar = imagecreatefromwebp($webp);
        $this->assertGreaterThanOrEqual(120, imagecolorsforindex($gambar, imagecolorat($gambar, 5, 5))['alpha']);
    }

    public function testGambarKecilDisimpanApaAdanya(): void
    {
        foreach (['png' => [1920, 1080], 'jpg' => [800, 600], 'webp' => [1080, 1920]] as $jenis => [$lebar, $tinggi]) {
            $isi   = self::gambar($jenis, $lebar, $tinggi);
            $jalur = $this->unggahan->simpanGambar($this->berkas('gambar.' . $jenis, $isi), 'halaman-depan');

            $this->assertSame($isi, file_get_contents($this->jalurUnggahan($jalur)), $jenis);
        }

        $this->assertFalse(Unggahan::kecilkanGambar($this->folderUnggahan . 'tidak-ada.png'));
    }

    public function testBerkasUnduhanBergambarTidakDikecilkan(): void
    {
        $isi   = self::gambar('png', 2400, 1200);
        $jalur = $this->unggahan->simpanBerkas($this->berkas('denah.png', $isi), 'unduhan');

        $this->assertSame($isi, file_get_contents($this->jalurUnggahan($jalur)));
    }

    private function berkas(string $nama, string $isi, int $galat = UPLOAD_ERR_OK): UploadedFile
    {
        $this->siapkanBerkas('sementara', $nama, $isi, $galat);
        $data = service('superglobals')->getFilesArray()['sementara'];

        return new UploadedFile($data['tmp_name'], $data['name'], $data['type'], $data['size'], $data['error']);
    }

    /**
     * ZIP kecil yang sah (bentuk dasar DOCX/XLSX).
     */
    private function isiZip(): string
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Ekstensi PHP zip tidak terpasang, jadi contoh DOCX/XLSX tidak bisa dibuat.');
        }

        $jalur = tempnam(sys_get_temp_dir(), 'milklife-zip-');
        $zip   = new ZipArchive();
        $zip->open($jalur, ZipArchive::OVERWRITE);
        $zip->addFromString('isi.txt', 'contoh');
        $zip->close();

        $isi = (string) file_get_contents($jalur);
        unlink($jalur);

        return $isi;
    }

    /**
     * Gambar karangan: latar bening (JPG: putih) dengan kotak merah di tengah.
     */
    private static function gambar(string $jenis, int $lebar, int $tinggi): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($gambar, false);
        imagesavealpha($gambar, true);
        imagefill($gambar, 0, 0, $jenis === 'jpg' ? imagecolorallocate($gambar, 255, 255, 255) : imagecolorallocatealpha($gambar, 0, 0, 0, 127));
        imagefilledrectangle($gambar, intdiv($lebar, 4), intdiv($tinggi, 4), intdiv($lebar * 3, 4), intdiv($tinggi * 3, 4), imagecolorallocate($gambar, 255, 0, 0));

        ob_start();
        match ($jenis) {
            'png'  => imagepng($gambar, null, 9),
            'jpg'  => imagejpeg($gambar, null, 85),
            'webp' => imagewebp($gambar, null, 85),
        };
        imagedestroy($gambar);

        return (string) ob_get_clean();
    }
}
