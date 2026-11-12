<?php

use App\Controllers\Admin\BuktiBayarController;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Models\BuktiBayarModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Bukti bayar di panel (K34; F6-03): unggah, lihat, unduh, dan hapus lewat
 * `BuktiBayarController`, serta potongan `admin/pendaftaran/_bukti_bayar`.
 *
 * Rincian pendaftaran (F6-02) belum tentu ada, jadi pengendali diuji
 * langsung: setelah unggah atau hapus, admin dialihkan ke
 * `admin/pendaftaran/<id>` dengan pesan kilat. Semua gambar dibuat GD di uji.
 *
 * @internal
 */
final class PanelBuktiBayarPengendaliTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->pakaiFolderBuktiBayarSementara();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    private function jumlahBukti(int $pendaftaranId): int
    {
        return model(BuktiBayarModel::class)->jumlahMilik($pendaftaranId);
    }

    /**
     * Kirim formulir unggah dengan berkas `$isi` di kotak `bukti`.
     */
    private function unggah(int $pendaftaranId, string $isi, string $nama = 'bukti.png', int $galat = UPLOAD_ERR_OK): CodeIgniter\Test\TestResponse
    {
        $this->siapkanBerkas(BuktiBayarController::KOTAK, $nama, $isi, $galat);

        return $this->kirimSebagaiAdmin('admin/pendaftaran/' . $pendaftaranId . '/bukti-bayar');
    }

    private function assertTidakDitemukan(callable $buka, string $keadaan): void
    {
        try {
            $buka();
            $this->fail($keadaan . ' seharusnya tidak ditemukan.');
        } catch (PageNotFoundException $e) {
            $this->assertSame(404, $e->getCode(), $keadaan);
        }
    }

    // ---------------------------------------------------------------
    // Unggah
    // ---------------------------------------------------------------

    public function testUnggahMenyimpanGambarYangDikecilkanLaluKembaliKeRincian(): void
    {
        $p     = $this->buatPendaftaranPanel();
        $hasil = $this->unggah($p['id'], self::gambarPanel(2400, 1200, 'png'));

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Bukti bayar tersimpan untuk pendaftaran ' . $p['id_pendaftaran'] . ' (1 dari 5 gambar).');

        $baris = model(BuktiBayarModel::class)->milikPendaftaran($p['id']);
        $this->assertCount(1, $baris);
        $this->assertMatchesRegularExpression('#^bukti-bayar/[0-9a-f]{32}\.jpg$#D', $baris[0]['berkas']);
        $this->assertSame([$baris[0]['berkas']], $this->berkasBuktiBayarTersimpan());

        // Dikecilkan ke sisi terpanjang 1600 piksel dan disimpan sebagai JPEG.
        $info = getimagesize($this->folderBuktiBayar . $baris[0]['berkas']);
        $this->assertSame([1600, 800, IMAGETYPE_JPEG], [$info[0], $info[1], $info[2]]);
        $this->assertSame(filesize($this->folderBuktiBayar . $baris[0]['berkas']), (int) $baris[0]['ukuran']);
        $this->assertNotNull($baris[0]['waktu_unggah']);
    }

    public function testJpgDanWebpDiterima(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->assertBerhasilDialihkan($this->unggah($p['id'], self::gambarPanel(300, 200, 'jpg'), 'transfer.jpg'), 'admin/pendaftaran/' . $p['id']);
        $this->assertBerhasilDialihkan($this->unggah($p['id'], self::gambarPanel(300, 200, 'webp'), 'transfer.webp'), 'admin/pendaftaran/' . $p['id'], 'Bukti bayar tersimpan untuk pendaftaran ' . $p['id_pendaftaran'] . ' (2 dari 5 gambar).');
        $this->assertSame(2, $this->jumlahBukti($p['id']));
    }

    public function testGambarKeenamDitolakDenganPesanRamah(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id'], BuktiBayarModel::BATAS);
        $sebelum = $this->berkasBuktiBayarTersimpan();

        $hasil = $this->unggah($p['id'], self::gambarPanel(200, 100));

        $this->assertGagalDialihkan(
            $hasil,
            'admin/pendaftaran/' . $p['id'],
            'Bukti bayar pendaftaran ini sudah 5 gambar, batas paling banyak. Hapus gambar yang tidak dipakai lebih dulu, lalu unggah lagi.',
        );
        $this->assertSame(5, $this->jumlahBukti($p['id']));
        $this->assertSame($sebelum, $this->berkasBuktiBayarTersimpan(), 'Gambar keenam tidak boleh tertinggal di disk.');
    }

    public function testBatasDihitungPerPendaftaran(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara']]);
        $this->buatBuktiBayarPanel($a['id'], BuktiBayarModel::BATAS);

        $this->assertBerhasilDialihkan($this->unggah($b['id'], self::gambarPanel(200, 100)), 'admin/pendaftaran/' . $b['id']);
        $this->assertSame(1, $this->jumlahBukti($b['id']));
    }

    public function testBerkasBukanGambarDitolak(): void
    {
        $p = $this->buatPendaftaranPanel();

        foreach ([
            'PDF'            => [self::isiPdf(), 'bukti.pdf'],
            'teks bernama png' => ['<?php echo "bukan gambar";', 'bukti.png'],
            'GIF'            => [base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true), 'bukti.gif'],
        ] as $keadaan => [$isi, $nama]) {
            $hasil = $this->unggah($p['id'], (string) $isi, $nama);

            $this->assertGagalDialihkan(
                $hasil,
                'admin/pendaftaran/' . $p['id'],
                'Bukti bayar belum tersimpan. Berkas ini bukan gambar yang bisa dipakai. Unggah foto atau tangkapan layar bukti bayar (JPG, PNG, atau WEBP).',
            );
            $this->assertSame(0, $this->jumlahBukti($p['id']), $keadaan);
        }

        $this->assertSame([], $this->berkasBuktiBayarTersimpan());
    }

    public function testTanpaBerkasDanBerkasTerlaluBesarDitolak(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->assertGagalDialihkan(
            $this->unggah($p['id'], '', 'bukti.png', UPLOAD_ERR_NO_FILE),
            'admin/pendaftaran/' . $p['id'],
            'Bukti bayar belum tersimpan. Belum ada gambar yang dipilih. Pilih foto atau tangkapan layar bukti bayar (JPG, PNG, atau WEBP).',
        );
        $this->assertGagalDialihkan(
            $this->unggah($p['id'], '', 'besar.jpg', UPLOAD_ERR_INI_SIZE),
            'admin/pendaftaran/' . $p['id'],
            'Bukti bayar belum tersimpan. Gambar terlalu besar. Paling besar 10 MB; perkecil dulu, lalu unggah lagi.',
        );
        $this->assertSame(0, $this->jumlahBukti($p['id']));
    }

    public function testUnggahKePendaftaranYangTidakAdaTidakDitemukan(): void
    {
        $this->assertTidakDitemukan(fn () => $this->unggah(999, self::gambarPanel(200, 100)), 'Unggah ke pendaftaran 999');
        $this->assertSame([], $this->berkasBuktiBayarTersimpan());
    }

    // ---------------------------------------------------------------
    // Lihat dan unduh
    // ---------------------------------------------------------------

    public function testLihatMenyajikanGambarJpeg(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'])[0];

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id);

        $hasil->assertOK();
        $hasil->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('no-store', $hasil->response()->getHeaderLine('Cache-Control'));
        $this->assertStringStartsWith('inline;', $hasil->response()->getHeaderLine('Content-Disposition'));

        $baris = model(BuktiBayarModel::class)->find($id);
        $this->assertSame(file_get_contents($this->folderBuktiBayar . $baris['berkas']), $hasil->response()->getBody());
    }

    public function testUnduhMemberiNamaBerkasDenganIdPendaftaranDanUrutan(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'], 2);

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id[1] . '/unduh');

        // Isi unduhan dikirim saat tanggapan dikirim, jadi badannya dibaca lewat `sendBody()`.
        $hasil->assertStatus(200);
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);
        $tanggapan->buildHeaders();

        $disposisi = $tanggapan->getHeaderLine('Content-Disposition');
        $this->assertStringStartsWith('attachment;', $disposisi);
        $this->assertStringContainsString('filename="BuktiBayar_' . $p['id_pendaftaran'] . '_2.jpg"', $disposisi);
        $this->assertStringContainsString('image/jpeg', $tanggapan->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('no-store', $tanggapan->getHeaderLine('Cache-Control'));

        ob_start();
        $tanggapan->sendBody();
        $isi = (string) ob_get_clean();

        $baris = model(BuktiBayarModel::class)->find($id[1]);
        $this->assertSame(file_get_contents($this->folderBuktiBayar . $baris['berkas']), $isi);
    }

    public function testBuktiPendaftaranLainTidakBisaDibukaLewatAlamatPendaftaranIni(): void
    {
        $a  = $this->buatPendaftaranPanel();
        $b  = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara']]);
        $id = $this->buatBuktiBayarPanel($a['id'])[0];

        foreach (['', '/unduh'] as $akhiran) {
            $this->assertTidakDitemukan(fn () => $this->bukaSebagaiAdmin('admin/pendaftaran/' . $b['id'] . '/bukti-bayar/' . $id . $akhiran), 'Bukti A lewat B' . $akhiran);
            $this->assertTidakDitemukan(fn () => $this->bukaSebagaiAdmin('admin/pendaftaran/' . $a['id'] . '/bukti-bayar/' . ($id + 1) . $akhiran), 'Bukti yang tidak ada' . $akhiran);
            $this->assertTidakDitemukan(fn () => $this->bukaSebagaiAdmin('admin/pendaftaran/999/bukti-bayar/' . $id . $akhiran), 'Pendaftaran yang tidak ada' . $akhiran);
        }
    }

    public function testBerkasYangHilangDariDiskTidakDitemukan(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'])[0];
        unlink($this->folderBuktiBayar . model(BuktiBayarModel::class)->find($id)['berkas']);

        $this->assertTidakDitemukan(fn () => $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id), 'Lihat berkas hilang');
        $this->assertTidakDitemukan(fn () => $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id . '/unduh'), 'Unduh berkas hilang');
    }

    public function testJalurBerkasDiBasisDataTidakBisaKeluarFolder(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'])[0];
        $this->db->table('bukti_bayar')->where('id', $id)->update(['berkas' => '../../.env']);

        $this->assertTidakDitemukan(fn () => $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id), 'Jalur keluar folder');
    }

    // ---------------------------------------------------------------
    // Hapus
    // ---------------------------------------------------------------

    public function testHapusMenghapusBarisDanBerkasnya(): void
    {
        $p      = $this->buatPendaftaranPanel();
        $id     = $this->buatBuktiBayarPanel($p['id'], 3);
        $berkas = array_column(model(BuktiBayarModel::class)->milikPendaftaran($p['id']), 'berkas', 'id');

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id[1] . '/hapus');

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Bukti bayar ke-2 sudah dihapus.');
        $this->assertNull(model(BuktiBayarModel::class)->find($id[1]));
        $this->assertFileDoesNotExist($this->folderBuktiBayar . $berkas[$id[1]]);

        $sisa = [$berkas[$id[0]], $berkas[$id[2]]];
        sort($sisa);
        $this->assertSame($sisa, $this->berkasBuktiBayarTersimpan());
        $this->assertSame(2, $this->jumlahBukti($p['id']));
    }

    public function testSetelahHapusBolehUnggahLagi(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'], BuktiBayarModel::BATAS);

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id[0] . '/hapus');
        $hasil = $this->unggah($p['id'], self::gambarPanel(200, 100));

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Bukti bayar tersimpan untuk pendaftaran ' . $p['id_pendaftaran'] . ' (5 dari 5 gambar).');
    }

    public function testHapusBuktiYangBerkasnyaSudahHilangTetapBerhasil(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'])[0];
        unlink($this->folderBuktiBayar . model(BuktiBayarModel::class)->find($id)['berkas']);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id . '/hapus');

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Bukti bayar ke-1 sudah dihapus.');
        $this->assertSame(0, $this->jumlahBukti($p['id']));
    }

    public function testHapusBuktiPendaftaranLainTidakDitemukan(): void
    {
        $a  = $this->buatPendaftaranPanel();
        $b  = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara']]);
        $id = $this->buatBuktiBayarPanel($a['id'])[0];

        $this->assertTidakDitemukan(fn () => $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'] . '/bukti-bayar/' . $id . '/hapus'), 'Hapus bukti A lewat B');
        $this->assertSame(1, $this->jumlahBukti($a['id']));
        $this->assertCount(1, $this->berkasBuktiBayarTersimpan());
    }

    // ---------------------------------------------------------------
    // Penjagaan
    // ---------------------------------------------------------------

    public function testYangBelumMasukTidakBisaMembukaGambarAtauMengubahBukti(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'])[0];

        $dasar = 'admin/pendaftaran/' . $p['id'] . '/bukti-bayar';
        $this->assertBelumMasukDialihkan($dasar . '/' . $id);
        $this->assertBelumMasukDialihkan($dasar . '/' . $id . '/unduh');
        $this->assertBelumMasukDialihkan($dasar, 'post');
        $this->assertBelumMasukDialihkan($dasar . '/' . $id . '/hapus', 'post');

        $this->assertSame(1, $this->jumlahBukti($p['id']));
        $this->assertCount(1, $this->berkasBuktiBayarTersimpan());
    }

    public function testBerkasDisimpanDiLuarFolderPublik(): void
    {
        BerkasBuktiBayar::gantiFolderDasar(null);

        try {
            $this->assertStringStartsWith(WRITEPATH, BerkasBuktiBayar::folderDasar());
            $this->assertStringStartsNotWith(FCPATH, BerkasBuktiBayar::folderDasar());
        } finally {
            BerkasBuktiBayar::gantiFolderDasar($this->folderBuktiBayar);
        }
    }

    public function testTokenCsrfWajib(): void
    {
        $p  = $this->buatPendaftaranPanel();
        $id = $this->buatBuktiBayarPanel($p['id'])[0];

        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'bukti.png', self::gambarPanel(200, 100));
        $this->assertTokenCsrfWajib('admin/pendaftaran/' . $p['id'] . '/bukti-bayar');
        $this->assertTokenCsrfWajib('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $id . '/hapus');

        $this->assertSame(1, $this->jumlahBukti($p['id']));
        $this->assertCount(1, $this->berkasBuktiBayarTersimpan());
    }

    // ---------------------------------------------------------------
    // Potongan tampilan
    // ---------------------------------------------------------------

    private function xpathPotongan(array $data): DOMXPath
    {
        $html = view('admin/pendaftaran/_bukti_bayar', $data, ['saveData' => false]);
        $dom  = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    public function testPotonganTanpaBuktiMenampilkanKotakTanpaGambarDanFormulirUnggah(): void
    {
        $p     = $this->buatPendaftaranPanel();
        $xpath = $this->xpathPotongan(['pendaftaran' => ['id' => $p['id'], 'id_pendaftaran' => $p['id_pendaftaran']]]);

        $this->assertSame('0', $xpath->query('//*[@id="bukti-bayar"]/@data-jumlah-bukti')->item(0)?->nodeValue);
        // Kotak kosong berikon (K76), tanpa gambar.
        $this->assertSame(1, $xpath->query('//*[@class="bukti-kosong"]')->length);
        $this->assertSame(0, $xpath->query('//img')->length);

        $formulir = $xpath->query('//form[@id="unggah-bukti-bayar"]')->item(0);
        $this->assertNotNull($formulir);
        $this->assertSame(site_url('admin/pendaftaran/' . $p['id'] . '/bukti-bayar'), $formulir->getAttribute('action'));
        $this->assertSame('post', $formulir->getAttribute('method'));
        $this->assertSame('multipart/form-data', $formulir->getAttribute('enctype'));
        $this->assertSame(1, $xpath->query('.//input[@name="' . csrf_token() . '"]', $formulir)->length);

        $kotak = $xpath->query('.//input[@type="file"]', $formulir)->item(0);
        $this->assertSame(BuktiBayarController::KOTAK, $kotak->getAttribute('name'));
        $this->assertSame('image/jpeg,image/png,image/webp', $kotak->getAttribute('accept'));
        $this->assertSame((string) BerkasBuktiBayar::BATAS_BITA, $kotak->getAttribute('data-ukuran-maksimal'));
    }

    public function testPotonganMenampilkanGambarKecilDanTombolPerGambar(): void
    {
        $p     = $this->buatPendaftaranPanel();
        $id    = $this->buatBuktiBayarPanel($p['id'], 2);
        $xpath = $this->xpathPotongan(['pendaftaran' => ['id' => $p['id'], 'id_pendaftaran' => $p['id_pendaftaran']]]);
        $dasar = site_url('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/');

        $this->assertSame('2', $xpath->query('//*[@id="bukti-bayar"]/@data-jumlah-bukti')->item(0)?->nodeValue);
        $this->assertSame(0, $xpath->query('//*[contains(@class, "tanpa-gambar")]')->length);

        foreach ($id as $urutan => $buktiId) {
            $butir = $xpath->query('//li[@data-bukti-id="' . $buktiId . '"]')->item(0);
            $this->assertNotNull($butir, 'Butir bukti ' . $buktiId);

            // Gambar kecil dan gambar besar dari rute admin.
            $gambar = $xpath->query('.//img', $butir);
            $this->assertSame(2, $gambar->length);

            foreach ($gambar as $img) {
                $this->assertSame($dasar . $buktiId, $img->getAttribute('src'));
                $this->assertSame('Bukti bayar ke-' . ($urutan + 1) . ' pendaftaran ' . $p['id_pendaftaran'], $img->getAttribute('alt'));
            }

            $this->assertSame(1, $xpath->query('.//button[@data-bs-toggle="modal"][@data-bs-target="#bukti-bayar-besar-' . $buktiId . '"]', $butir)->length);
            $this->assertGreaterThan(0, $xpath->query('.//a[@href="' . $dasar . $buktiId . '/unduh"]', $butir)->length);

            $hapus = $xpath->query('.//form[@action="' . $dasar . $buktiId . '/hapus"]', $butir)->item(0);
            $this->assertNotNull($hapus);
            $this->assertSame('post', $hapus->getAttribute('method'));
            $this->assertStringContainsString('Bukti bayar ke-' . ($urutan + 1) . ' akan dihapus', $hapus->getAttribute('data-konfirmasi'));
            $this->assertSame(1, $xpath->query('.//input[@name="' . csrf_token() . '"]', $hapus)->length);
        }

        $this->assertSame(1, $xpath->query('//form[@id="unggah-bukti-bayar"]')->length);
        $this->assertStringContainsString('Masih bisa 3 gambar lagi.', $xpath->query('//*[@id="bukti-bayar-bantuan"]')->item(0)->textContent);
    }

    public function testPotonganMenyembunyikanFormulirUnggahSetelahLimaGambar(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id'], BuktiBayarModel::BATAS);

        // Bisa juga dipanggil dengan `pendaftaranId` saja.
        $xpath = $this->xpathPotongan(['pendaftaranId' => $p['id']]);

        $this->assertSame(5, $xpath->query('//li[@data-bukti-id]')->length);
        $this->assertSame(0, $xpath->query('//form[@id="unggah-bukti-bayar"]')->length);
        $this->assertStringContainsString('Sudah 5 gambar, batas paling banyak.', $xpath->query('//*[@id="bukti-bayar-penuh"]')->item(0)->textContent);
        $this->assertStringContainsString('5 dari 5 gambar', $xpath->query('//*[@id="jumlah-bukti-bayar"]')->item(0)->textContent);
    }

    public function testRincianPendaftaranMemuatBuktiBayarDanPesanSetelahUnggah(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id'], 2);

        $hasil = $this->unggah($p['id'], self::gambarPanel(200, 100));
        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id']);

        $halaman = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'], ['pesan_sukses' => 'Bukti bayar tersimpan.', '__ci_vars' => ['pesan_sukses' => 'new']]);
        $halaman->assertOK();
        $xpath = $this->xpathDari($halaman);

        $this->assertSame(1, $xpath->query('//section[@id="bukti-bayar"]')->length);
        $this->assertSame('3', $xpath->query('//section[@id="bukti-bayar"]/@data-jumlah-bukti')->item(0)?->nodeValue);
        $this->assertSame(3, $xpath->query('//section[@id="bukti-bayar"]//li[@data-bukti-id]')->length);
        $this->assertSame(1, $xpath->query('//form[@id="unggah-bukti-bayar"]')->length);
        $this->assertStringContainsString('Bukti bayar tersimpan.', (string) $xpath->query('//*[@data-pesan="pesan_sukses"]')->item(0)?->textContent);

        // konfirmasi.js dimuat sekali oleh rincian.
        $this->assertSame(1, $xpath->query('//script[contains(@src, "js/konfirmasi.js")]')->length);
    }

    public function testPotonganHanyaMenampilkanBuktiPendaftaranItu(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara']]);
        $this->buatBuktiBayarPanel($a['id'], 2);
        $idB = $this->buatBuktiBayarPanel($b['id'])[0];

        $xpath = $this->xpathPotongan(['pendaftaran' => ['id' => $b['id']]]);

        $this->assertSame(1, $xpath->query('//li[@data-bukti-id]')->length);
        $this->assertSame(1, $xpath->query('//li[@data-bukti-id="' . $idB . '"]')->length);
    }
}
