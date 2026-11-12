<?php

use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\PendaftaranContoh;

/**
 * Foto penanggung jawab (F4-01, K50): hanya JPEG kecil yang diterima, disimpan
 * ulang di luar `public/`.
 *
 * @internal
 */
final class PendaftaranFotoTest extends CIUnitTestCase
{
    use PendaftaranContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pakaiFolderFotoSementara();
    }

    protected function tearDown(): void
    {
        $this->hapusFolderFotoSementara();
        parent::tearDown();
    }

    public function testJpegKecilDiterima(): void
    {
        $isi = FotoPenanggungJawab::baca(self::fotoContoh(800, 600));

        $this->assertIsString($isi);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($isi)[2]);
    }

    public function testFotoYangDitolak(): void
    {
        $this->assertNull(FotoPenanggungJawab::baca(null));
        $this->assertNull(FotoPenanggungJawab::baca(''));
        $this->assertNull(FotoPenanggungJawab::baca(['data:image/jpeg;base64,']));
        // Lebih dari 800 piksel.
        $this->assertNull(FotoPenanggungJawab::baca(self::fotoContoh(801, 10)));
        // Bukan JPEG.
        $gambar = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($gambar);
        $png = (string) ob_get_clean();
        $this->assertNull(FotoPenanggungJawab::baca('data:image/jpeg;base64,' . base64_encode($png)));
        $this->assertNull(FotoPenanggungJawab::baca('data:image/png;base64,' . base64_encode($png)));
        // Base64 rusak dan isi bukan gambar.
        $this->assertNull(FotoPenanggungJawab::baca('data:image/jpeg;base64,@@@'));
        $this->assertNull(FotoPenanggungJawab::baca('data:image/jpeg;base64,' . base64_encode('<?php echo 1;')));
        // Terlalu besar.
        $this->assertNull(FotoPenanggungJawab::baca('data:image/jpeg;base64,' . str_repeat('A', 700 * 1024)));
    }

    public function testSimpanDiLuarFolderPublikDanHapus(): void
    {
        $foto  = new FotoPenanggungJawab();
        $jalur = $foto->simpan((string) FotoPenanggungJawab::baca(self::fotoContoh()));

        $this->assertMatchesRegularExpression('#^penanggung-jawab/[0-9a-f]{32}\.jpg$#', $jalur);
        $this->assertFileExists($this->folderFoto . $jalur);
        $this->assertSame(IMAGETYPE_JPEG, getimagesize($this->folderFoto . $jalur)[2]);
        $this->assertStringStartsNotWith(FCPATH, (string) $foto->jalurPenuh($jalur));

        $this->assertTrue($foto->hapus($jalur));
        $this->assertFileDoesNotExist($this->folderFoto . $jalur);
        $this->assertFalse($foto->hapus($jalur));
    }

    public function testFolderBawaanDiWritable(): void
    {
        FotoPenanggungJawab::gantiFolderDasar(null);

        $this->assertSame(WRITEPATH . 'unggahan' . DIRECTORY_SEPARATOR, FotoPenanggungJawab::folderDasar());
    }

    public function testJalurTidakSahDitolak(): void
    {
        $foto = new FotoPenanggungJawab();

        $this->assertNull($foto->jalurPenuh('../../.env'));
        $this->assertNull($foto->jalurPenuh('penanggung-jawab/../x.jpg'));
        $this->assertNull($foto->jalurPenuh(null));
        $this->assertFalse($foto->hapus('penanggung-jawab/../../app/Config/App.php'));
    }
}
