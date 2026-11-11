<?php

namespace Tests\Support;

use App\Libraries\Unggahan;

/**
 * Unggahan di uji ditulis ke folder sementara, bukan ke `public/unggahan/`.
 *
 * Sebelum setiap uji, folder dasar `App\Libraries\Unggahan` diganti ke folder
 * baru di folder sementara sistem; sesudahnya folder itu dihapus beserta isinya
 * dan isian berkas (`$_FILES`) dikosongkan.
 *
 * Pemakaian:
 *
 *     final class PengaturanPembayaranTest extends UjiBasisData
 *     {
 *         use KirimFormulir;
 *         use UnggahanSementara;
 *
 *         public function testUnggahLogoBank(): void
 *         {
 *             $this->siapkanBerkas('logo_bank', 'logo.png', self::isiPng());
 *             $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/pembayaran', [...]);
 *
 *             $jalur = setting('Pembayaran.logo_bank');           // 'unggahan/pembayaran/….png'
 *             $this->assertFileExists($this->jalurUnggahan($jalur));
 *         }
 *
 *         public function testBukanGambarDitolak(): void
 *         {
 *             $this->siapkanBerkas('logo_bank', 'logo.png', '<?php echo 1;');
 *             …
 *             $this->assertSame([], $this->isiFolderUnggahan());
 *         }
 *     }
 *
 * Berkas yang dipilih di formulir dibaca pengendali lewat
 * `$this->request->getFile('logo_bank')`, sama seperti unggahan sungguhan.
 */
trait UnggahanSementara
{
    /**
     * Folder dasar unggahan selama uji ini, diakhiri pemisah folder.
     */
    protected string $folderUnggahan = '';

    /**
     * @var list<string> Berkas sumber sementara yang dibuat `siapkanBerkas()`.
     */
    private array $berkasSumberUji = [];

    protected function setUpUnggahanSementara(): void
    {
        $this->folderUnggahan = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-unggahan-' . bin2hex(random_bytes(6)) . DIRECTORY_SEPARATOR;
        mkdir($this->folderUnggahan, 0777, true);

        Unggahan::gantiFolderDasar($this->folderUnggahan);
        service('superglobals')->setFilesArray([]);
    }

    protected function tearDownUnggahanSementara(): void
    {
        Unggahan::gantiFolderDasar(null);
        service('superglobals')->setFilesArray([]);

        foreach ($this->berkasSumberUji as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        $this->berkasSumberUji = [];
        self::hapusFolder($this->folderUnggahan);
    }

    /**
     * Seolah-olah admin memilih berkas di kotak unggah `$kotak`.
     *
     * @param string $namaBerkas Nama berkas di komputer pengunggah, contoh `logo.png`.
     * @param string $isi        Isi berkas (lihat `isiPng()`, `isiJpg()`, `isiWebp()`, `isiPdf()`).
     * @param int    $galat      Kode galat unggah PHP, contoh `UPLOAD_ERR_INI_SIZE` untuk berkas yang melebihi batas PHP.
     */
    protected function siapkanBerkas(string $kotak, string $namaBerkas, string $isi, int $galat = UPLOAD_ERR_OK): void
    {
        $sumber = tempnam(sys_get_temp_dir(), 'milklife-sumber-');
        file_put_contents($sumber, $isi);
        $this->berkasSumberUji[] = $sumber;

        $berkas         = service('superglobals')->getFilesArray();
        $berkas[$kotak] = [
            'name'     => $namaBerkas,
            'type'     => 'application/octet-stream',
            'tmp_name' => $galat === UPLOAD_ERR_OK ? $sumber : '',
            'error'    => $galat,
            'size'     => $galat === UPLOAD_ERR_OK ? strlen($isi) : 0,
        ];

        service('superglobals')->setFilesArray($berkas);
    }

    /**
     * Jalur di disk untuk jalur tersimpan `unggahan/<folder>/<nama>`.
     */
    protected function jalurUnggahan(string $jalur): string
    {
        return $this->folderUnggahan . substr($jalur, strlen('unggahan/'));
    }

    /**
     * Semua berkas yang tersimpan selama uji, sebagai jalur `unggahan/<folder>/<nama>`, berurutan.
     *
     * @return list<string>
     */
    protected function isiFolderUnggahan(): array
    {
        $hasil = [];

        foreach (glob($this->folderUnggahan . '*' . DIRECTORY_SEPARATOR . '*') ?: [] as $berkas) {
            $hasil[] = 'unggahan/' . basename(dirname($berkas)) . '/' . basename($berkas);
        }

        sort($hasil);

        return $hasil;
    }

    /**
     * Gambar PNG 1 × 1 piksel yang sah.
     */
    protected static function isiPng(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    }

    /**
     * Gambar JPEG 1 × 1 piksel yang sah.
     */
    protected static function isiJpg(): string
    {
        return (string) base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////'
            . '2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQAB'
            . 'AQAAAAAAAAAAAAAAAAAAAAP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhED'
            . 'EQA/AKAA/9k=',
            true,
        );
    }

    /**
     * Gambar WEBP 1 × 1 piksel yang sah.
     */
    protected static function isiWebp(): string
    {
        return (string) base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA', true);
    }

    /**
     * Dokumen PDF kecil yang sah.
     */
    protected static function isiPdf(): string
    {
        return "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            . "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            . "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 10 10] >> endobj\n"
            . "trailer << /Root 1 0 R >>\n%%EOF\n";
    }

    private static function hapusFolder(string $folder): void
    {
        if ($folder === '' || ! is_dir($folder)) {
            return;
        }

        foreach (scandir($folder) ?: [] as $nama) {
            if ($nama === '.' || $nama === '..') {
                continue;
            }

            $jalur = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $nama;
            is_dir($jalur) && ! is_link($jalur) ? self::hapusFolder($jalur) : unlink($jalur);
        }

        rmdir($folder);
    }
}
