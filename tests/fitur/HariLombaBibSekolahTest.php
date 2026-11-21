<?php

use App\Libraries\HariLomba\CetakBib;
use App\Libraries\HariLomba\TampilanBib;
use App\Libraries\HariLomba\UnduhanZipAlir;
use App\Libraries\HariLomba\ZipAlir;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Cetak BIB per sekolah, gambar JPG, latar penuh, dan ZIP semua sekolah (K137).
 *
 * @internal
 */
final class HariLombaBibSekolahTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    private const ALAMAT = 'admin/nomor-atlet/cetak';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testHalamanHanyaUntukAdmin(): void
    {
        foreach ([self::ALAMAT . '/contoh', self::ALAMAT . '/zip', self::ALAMAT . '/sekolah/99999911/pdf', self::ALAMAT . '/sekolah/99999911/zip'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        $this->assertBelumMasukDialihkan(self::ALAMAT . '/tampilan', 'post');
        $this->assertTokenCsrfWajib(self::ALAMAT . '/tampilan', ['warna_teks' => 'gelap']);
    }

    public function testSekolahPerNpsnDanTanpaNpsnHanyaLunas(): void
    {
        $this->siapkanTigaSekolah();
        $bib     = new CetakBib();
        $sekolah = $bib->sekolah();

        $this->assertSame(['SD KARANGAN LAIN', 'SD KARANGAN MAJU', 'SD KARANGAN TANPA NPSN'], array_column($sekolah, 'nama'));
        $this->assertSame(['99999913', '99999911'], array_slice(array_column($sekolah, 'kunci'), 0, 2));
        $this->assertMatchesRegularExpression('/^p\d+$/', $sekolah[2]['kunci']);
        $this->assertSame('', $sekolah[2]['npsn']);
        $this->assertSame([1, 2, 1], array_column($sekolah, 'atlet'));

        $this->assertSame(['2001', '2002'], array_column($bib->atletSekolah('99999911'), 'nomor_dada'));
        $this->assertSame([], $bib->atletSekolah('99999914'), 'Sekolah Menunggu tidak dicetak.');
        $this->assertSame([], $bib->atletSekolah('bukan kunci'));
        $this->assertCount(1, $bib->atletSekolah($sekolah[2]['kunci']));

        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertOK();
        $halaman->assertSeeElement('#unduh-zip-semua');
        $tautan = $this->xpathDari($halaman)->query('//*[@id="daftar-bib-sekolah"]//a');
        $this->assertSame(6, $tautan->length);
        $this->assertSame(site_url(self::ALAMAT . '/sekolah/99999913/pdf'), $tautan->item(0)->getAttribute('href'));
        $this->assertSame(site_url(self::ALAMAT . '/sekolah/99999913/zip'), $tautan->item(1)->getAttribute('href'));
    }

    public function testUnduhPdfDanZipPerSekolah(): void
    {
        $this->siapkanTigaSekolah();

        $pdf = $this->bukaSebagaiAdmin(self::ALAMAT . '/sekolah/99999911/pdf');
        $pdf->assertOK();
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());
        $this->assertStringContainsString('BIB_SDKARANGANMAJU', $pdf->response()->getHeaderLine('Content-Disposition'));

        $isi = $this->bacaZip($this->bukaSebagaiAdmin(self::ALAMAT . '/sekolah/99999911/zip'));
        $this->assertSame(['2001 - ', '2002 - ', 'BIB_SDKARANGANMAJU'], array_map(static fn (string $n): string => substr($n, 0, $n[0] === 'B' ? 18 : 7), array_keys($isi)));
        $this->assertStringStartsWith('%PDF', end($isi));

        $jpg = reset($isi);
        $this->assertSame([TampilanBib::LEBAR, TampilanBib::TINGGI, IMAGETYPE_JPEG], array_slice(getimagesizefromstring($jpg) ?: [], 0, 3));

        $this->expectException(PageNotFoundException::class);
        $this->bukaSebagaiAdmin(self::ALAMAT . '/sekolah/99999914/zip');
    }

    public function testZipSemuaSekolahSatuFolderPerSekolah(): void
    {
        $tanpaNpsn = $this->siapkanTigaSekolah();

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/zip');
        $this->assertStringContainsString('BIB_SemuaSekolah', $hasil->response()->getHeaderLine('Content-Disposition'));
        $isi    = $this->bacaZip($hasil);
        $folder = array_values(array_unique(array_map(static fn (string $n): string => dirname($n), array_keys($isi))));

        $this->assertSame(['SD KARANGAN LAIN - 99999913', 'SD KARANGAN MAJU - 99999911', 'SD KARANGAN TANPA NPSN'], $folder);
        $this->assertCount(4 + 3, $isi, 'Empat gambar atlet dan tiga PDF sekolah.');
        $this->assertArrayHasKey('SD KARANGAN TANPA NPSN/' . CetakBib::namaBerkasSekolah('SD KARANGAN TANPA NPSN', 'pdf'), $isi);
        $this->assertNotSame('', $tanpaNpsn);
    }

    public function testZipKosongDanNamaBerkasAman(): void
    {
        $data = '';
        $zip  = new ZipAlir(static function (string $bita) use (&$data): void {
            $data .= $bita;
        });
        $this->assertSame(['sekolah' => 0, 'atlet' => 0], (new CetakBib())->isiZip($zip));
        $zip->selesai();
        $this->assertSame(['BACA.txt'], array_keys($this->bukaZip($data)));

        $this->assertSame('MI MAARIF NU 01 (KUDUS)', CetakBib::namaFolder("MI MA'ARIF NU 01 / (KUDUS)"));
        $this->assertSame('Tanpa nama', CetakBib::namaFolder('../..'));
    }

    public function testLatarDisimpanDipotongDanDipakaiPdfSertaGambar(): void
    {
        $this->siapkanTigaSekolah();
        $this->siapkanBerkas('latar', 'latar.png', self::gambarPng(400, 200));

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'terang']);
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);

        $latar = (string) setting('Bib.latar');
        $this->assertMatchesRegularExpression('#^unggahan/bib/[a-f0-9]{32}\.jpg$#', $latar);
        $this->assertSame([TampilanBib::LEBAR, TampilanBib::TINGGI], array_slice(getimagesize($this->jalurUnggahan($latar)) ?: [], 0, 2));
        $this->assertFalse(setting('Bib.tampilkan_kepala'));
        $this->assertSame('terang', setting('Bib.warna_teks'));

        $tampilan = TampilanBib::dariPengaturan();
        $this->assertFalse($tampilan->kepala);
        $this->assertSame('#FFFFFF', $tampilan->warna['nomor']);
        $this->assertNotNull($tampilan->latarJpg());

        $html = CetakBib::html((new CetakBib())->atlet(null), 'MILKLIFE', '', false, $tampilan, true);
        $this->assertSame(4, substr_count($html, 'src="var:latar"'));
        $this->assertStringNotContainsString('<table class="kepala">', $html);
        $this->assertStringStartsWith('%PDF', $this->bukaSebagaiAdmin(self::ALAMAT . '/pdf?ku=semua')->response()->getBody());

        $contoh = $this->bukaSebagaiAdmin(self::ALAMAT . '/contoh');
        $contoh->assertOK();
        $this->assertStringContainsString('image/jpeg', $contoh->response()->getHeaderLine('Content-Type'));
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($contoh->response()->getBody())[2] ?? null);

        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertSeeElement('#hapus_latar');
        $this->assertSame('checked', $this->xpathDari($halaman)->query('//input[@id="warna_terang"]')->item(0)?->getAttribute('checked'));

        // Latar baru menggantikan yang lama; berkas lama dihapus.
        $this->siapkanBerkas('latar', 'latar.jpg', self::gambarPng(100, 300));
        $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'gelap', 'tampilkan_kepala' => '1']);
        $baru = (string) setting('Bib.latar');
        $this->assertNotSame($latar, $baru);
        $this->assertSame([$baru], $this->isiFolderUnggahan());
        $this->assertTrue(TampilanBib::dariPengaturan()->kepala);

        // Hapus latar.
        service('superglobals')->setFilesArray([]);
        $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'terang', 'hapus_latar' => '1']);
        $this->assertSame('', setting('Bib.latar'));
        $this->assertSame([], $this->isiFolderUnggahan());

        // Tanpa latar: kepala selalu dicetak dan tulisan gelap.
        $tampilan = TampilanBib::dariPengaturan();
        $this->assertTrue($tampilan->kepala);
        $this->assertSame('gelap', $tampilan->namaWarna);
    }

    public function testLatarDanWarnaSalahDitolak(): void
    {
        $this->siapkanBerkas('latar', 'latar.png', '<?php echo 1;');
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'gelap']);
        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertSame('', setting('Bib.latar'));
        $this->assertSame([], $this->isiFolderUnggahan());

        service('superglobals')->setFilesArray([]);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'merah']), self::ALAMAT, 'Pilih warna tulisan Gelap atau Terang.');
        $this->assertSame('gelap', setting('Bib.warna_teks'));
    }

    /**
     * Latar bawaan G9-01 (F9-16): hanya ditawarkan bila berkasnya ada; dipilih
     * menggantikan latar unggahan (berkas unggahan dihapus, berkas bawaan tidak).
     */
    public function testLatarBawaanHanyaBilaBerkasAda(): void
    {
        $folder = FCPATH . 'aset/gambar/bib/';
        $berkas = $folder . 'latar-gelap-1.jpg';

        if (is_file($berkas)) {
            $this->markTestSkipped('Berkas latar bawaan G9-01 sudah ada; uji ini memakai berkas buatannya sendiri.');
        }

        // Tanpa berkas: tidak ditawarkan dan ditolak.
        $this->assertSame([], array_intersect_key(TampilanBib::daftarBawaan(), ['latar-gelap-1' => true]));
        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT))->query('//input[@name="latar_bawaan"][@value="latar-gelap-1"]')->length);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'terang', 'latar_bawaan' => 'latar-gelap-1']), self::ALAMAT, 'Latar bawaan itu tidak tersedia. Pilih latar lain.');
        $this->assertSame('', setting('Bib.latar'));

        $folderBaru = ! is_dir($folder);

        if ($folderBaru) {
            mkdir($folder, 0775, true);
        }

        $gambar = imagecreatetruecolor(248, 175);
        imagefill($gambar, 0, 0, (int) imagecolorallocate($gambar, 2, 31, 123));
        imagejpeg($gambar, $berkas, 90);

        try {
            $this->siapkanTigaSekolah();
            $halaman = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));
            $this->assertSame(1, $halaman->query('//*[@data-latar-bawaan]//input[@type="radio"][@name="latar_bawaan"][@value="latar-gelap-1"]')->length);
            $this->assertSame('checked', $halaman->query('//input[@name="latar_bawaan"][@value=""]')->item(0)?->getAttribute('checked'));

            // Latar unggahan dulu, lalu diganti latar bawaan: berkas unggahan dihapus.
            $this->siapkanBerkas('latar', 'latar.png', self::gambarPng(400, 200));
            $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'gelap', 'latar_bawaan' => '']);
            $this->assertCount(1, $this->isiFolderUnggahan());

            service('superglobals')->setFilesArray([]);
            $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'terang', 'latar_bawaan' => 'latar-gelap-1']);
            $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
            $this->assertSame('bawaan:latar-gelap-1', setting('Bib.latar'));
            $this->assertSame([], $this->isiFolderUnggahan());

            $tampilan = TampilanBib::dariPengaturan();
            $this->assertSame('latar-gelap-1', $tampilan->kunciBawaan());
            $this->assertSame((string) file_get_contents($berkas), $tampilan->latarJpg());
            $this->assertStringContainsString('src="var:latar"', CetakBib::html((new CetakBib())->atlet(null), 'MILKLIFE', '', false, $tampilan, true));
            $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($this->bukaSebagaiAdmin(self::ALAMAT . '/contoh')->response()->getBody())[2] ?? null);

            $halaman = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));
            $this->assertSame('checked', $halaman->query('//input[@name="latar_bawaan"][@value="latar-gelap-1"]')->item(0)?->getAttribute('checked'));
            $this->assertSame(1, $halaman->query('//input[@id="hapus_latar"]')->length);

            // Kunci asing ditolak; "Tanpa latar bawaan" kembali ke BIB putih tanpa menghapus berkas bawaan.
            $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'gelap', 'latar_bawaan' => '../../index']), self::ALAMAT);
            $this->assertSame('bawaan:latar-gelap-1', setting('Bib.latar'));
            $this->kirimSebagaiAdmin(self::ALAMAT . '/tampilan', ['warna_teks' => 'gelap', 'latar_bawaan' => '']);
            $this->assertSame('', setting('Bib.latar'));
            $this->assertFileExists($berkas);
            $this->assertNull(TampilanBib::dariPengaturan()->latar);
        } finally {
            @unlink($berkas);

            if ($folderBaru) {
                @rmdir($folder);
            }
        }
    }

    public function testContohTanpaAtlet(): void
    {
        $contoh = $this->bukaSebagaiAdmin(self::ALAMAT . '/contoh');
        $contoh->assertOK();
        $this->assertSame([TampilanBib::LEBAR, TampilanBib::TINGGI], array_slice(getimagesizefromstring($contoh->response()->getBody()) ?: [], 0, 2));
        $this->bukaSebagaiAdmin(self::ALAMAT)->assertSeeElement('#bib-sekolah-kosong');
    }

    /**
     * Dua sekolah Lunas ber-NPSN, satu Lunas tanpa NPSN, satu Menunggu.
     *
     * @return string kunci sekolah tanpa NPSN
     */
    private function siapkanTigaSekolah(): string
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel();
        $b      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-01', 'nomor' => ['lari60putraKu12']])]]);
        $c      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN TANPA NPSN'], 'atlet' => [$this->atletPanel(['nomor' => ['lari60putra']])]]);
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999914', 'nama' => 'SD KARANGAN MENUNGGU']]);

        foreach ([$a, $b, $c] as $p) {
            $status->tandaiLunas($p['id']);
        }

        return 'p' . $c['id'];
    }

    /**
     * Isi ZIP unduhan: nama berkas => isi, urut nama. Berkas sementara dihapus.
     *
     * @return array<string, string>
     */
    private function bacaZip(TestResponse $hasil): array
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(UnduhanZipAlir::class, $tanggapan);
        $this->assertStringContainsString('application/zip', $tanggapan->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('attachment; filename="BIB_', $tanggapan->getHeaderLine('Content-Disposition'));

        ob_start();
        $tanggapan->sendBody();

        return $this->bukaZip((string) ob_get_clean());
    }

    /**
     * Isi ZIP dibaca ZipArchive (memastikan ZipAlir menulis ZIP yang sah).
     *
     * @return array<string, string>
     */
    private function bukaZip(string $data): array
    {
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-bib-');
        file_put_contents($berkas, $data);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($berkas, ZipArchive::CHECKCONS));
        $isi = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama       = (string) $zip->getNameIndex($i);
            $isi[$nama] = (string) $zip->getFromIndex($i);
        }

        $zip->close();
        @unlink($berkas);
        ksort($isi);

        return $isi;
    }

    private static function gambarPng(int $lebar, int $tinggi): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagefill($gambar, 0, 0, (int) imagecolorallocate($gambar, 10, 40, 120));
        ob_start();
        imagepng($gambar);

        return (string) ob_get_clean();
    }
}
