<?php

use App\Libraries\Galeri\AlbumBawaan;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Unggahan;
use App\Models\GaleriModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Galeri tautan luar (F9-06, K140 butir 11): halaman `/galeri` bersakelar
 * (bawaan mati), Pengaturan > Galeri, album bawaan "Seri sebelumnya", dan
 * ikut Backup data/impor (K76). Judul dan tautan album karangan.
 *
 * @internal
 */
final class GaleriTest extends UjiBasisData
{
    use KirimFormulir;
    use StreamFilterTrait;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/galeri';

    private const TAUTAN = 'https://drive.google.com/drive/folders/contohAlbumKarangan';

    // ---------- Sakelar dan halaman publik ----------

    public function testHalamanGaleriMatiSecaraBawaan(): void
    {
        $this->assertFalse((bool) setting('Galeri.tampil'));
        $this->tambahAlbum(['judul' => 'Album karangan', 'tautan' => self::TAUTAN]);

        try {
            $this->get('galeri');
            $this->fail('/galeri seharusnya 404 selama sakelar mati.');
        } catch (PageNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }

        // Butir menu Galeri tidak tampil selama mati.
        $xpath = $this->xpathDari($this->get('unduhan'));
        $this->assertSame(0, $xpath->query('//a[@href="' . site_url('galeri') . '"]')->length);
    }

    public function testSakelarMenyalakanHalamanDanMenu(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/tampil', 'post');

        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin(self::ALAMAT . '/tampil', ['tampil_galeri' => '1']),
            self::ALAMAT . '#sakelar-galeri',
            'Halaman Galeri sekarang tampil untuk umum.',
        );
        $this->assertTrue((bool) setting('Galeri.tampil'));

        $hasil = $this->get('galeri');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Galeri', trim($xpath->query('//h1')->item(0)?->textContent ?? ''));
        $this->assertGreaterThan(0, $xpath->query('//nav//a[@href="' . site_url('galeri') . '"][normalize-space()="Galeri"]')->length, 'Butir menu Galeri tampil.');
        $this->assertSame(0, $xpath->query('//link[contains(@href, "bootstrap")]')->length, 'Tata letak Tailwind tanpa Bootstrap.');

        // Mati lagi: kotak centang tidak terkirim.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/tampil', []), self::ALAMAT . '#sakelar-galeri', 'Halaman Galeri disembunyikan.');
        $this->assertFalse((bool) setting('Galeri.tampil'));
    }

    public function testKartuHanyaAlbumAktifBertautanDanMembukaTabBaru(): void
    {
        setting('Galeri.tampil', true);
        $sampul = $this->simpanSampulUji();
        $this->tambahAlbum(['judul' => 'Hari <b>pertama</b>', 'keterangan' => 'Lari & lompat', 'tautan' => 'https://www.instagram.com/p/karangan/', 'sampul' => $sampul, 'urutan' => 2]);
        $this->tambahAlbum(['judul' => 'Hari kedua', 'tautan' => self::TAUTAN, 'urutan' => 1]);
        $this->tambahAlbum(['judul' => 'Tanpa tautan', 'tautan' => null, 'urutan' => 3]);
        $this->tambahAlbum(['judul' => 'Disembunyikan', 'tautan' => self::TAUTAN, 'aktif' => 0, 'urutan' => 4]);
        $this->tambahAlbum(['judul' => 'Sampul hilang', 'tautan' => 'https://contoh.example/album', 'sampul' => 'unggahan/galeri/' . str_repeat('c', 32) . '.webp', 'urutan' => 5]);

        $hasil = $this->get('galeri');
        $hasil->assertOK();
        $hasil->assertDontSee('Hari <b>pertama</b>');
        $hasil->assertDontSee('Tanpa tautan');
        $hasil->assertDontSee('Disembunyikan');
        $xpath = $this->xpathDari($hasil);

        $kartu = $xpath->query('//ul[@data-daftar-galeri]/li[@data-album-galeri]');
        $this->assertSame(3, $kartu->length);

        $judul = [];

        foreach ($kartu as $li) {
            $tautan = $xpath->query('./a', $li)->item(0);
            $this->assertNotNull($tautan);
            $this->assertSame('_blank', $tautan->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $tautan->getAttribute('rel'));
            $this->assertStringContainsString('(terbuka di tab baru)', $tautan->textContent);
            $judul[] = trim($xpath->query('.//span[contains(@class, "font-bold")]', $tautan)->item(0)?->textContent ?? '');
        }

        $this->assertSame(['Hari kedua', 'Hari <b>pertama</b>', 'Sampul hilang'], $judul);

        // Sampul ada → gambar; sampul hilang atau kosong → latar pengganti.
        $this->assertSame(1, $xpath->query('//li[@data-album-galeri]//img[@src="' . base_url($sampul) . '"]')->length);
        $this->assertSame(2, $xpath->query('//li[@data-album-galeri]//*[@data-tanpa-sampul]')->length);

        // Label tombol menyebut tempat album.
        $this->assertStringContainsString('Buka di Google Drive', $kartu->item(0)->textContent);
        $this->assertStringContainsString('Buka di Instagram', $kartu->item(1)->textContent);
        $this->assertStringContainsString('Buka album', $kartu->item(2)->textContent);
        $this->assertSame('https://www.instagram.com/p/karangan/', $xpath->query('./a', $kartu->item(1))->item(0)?->getAttribute('href'));
    }

    public function testGaleriKosongMenampilkanKeadaanKosong(): void
    {
        setting('Galeri.tampil', true);
        $this->tambahAlbum(['judul' => 'Belum bertautan', 'tautan' => null]);

        $hasil = $this->get('galeri');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//*[@data-galeri-kosong]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-daftar-galeri]')->length);
        $hasil->assertSee('Belum ada album foto');

        // Ilustrasi G9-04 hanya bila berkasnya ada (K41).
        $adaIlustrasi = is_file(FCPATH . 'aset/gambar/keadaan/kosong-data.webp');
        $this->assertSame($adaIlustrasi ? 1 : 0, $xpath->query('//*[@data-galeri-kosong]//img[contains(@src, "gambar/keadaan/kosong-data.webp")]')->length);
    }

    // ---------- Pengaturan > Galeri ----------

    public function testHalamanPengaturanGaleri(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/tambah');

        $this->tambahAlbum(['judul' => 'Album <i>karangan</i>', 'tautan' => null]);
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $hasil->assertDontSee('Album <i>karangan</i>');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(1, $xpath->query('//section[@id="sakelar-galeri"]//input[@type="checkbox"][@name="tampil_galeri"][not(@checked)]')->length, 'Sakelar bawaan mati.');
        $this->assertSame(1, $xpath->query('//tr[@data-album]//*[@data-tanpa-tautan]')->length);
        $this->assertSame(1, $xpath->query('//tr[@data-album]//form[contains(@action, "/hapus")][@data-konfirmasi]')->length, 'Hapus dengan konfirmasi.');
        $this->assertSame(1, $xpath->query('//a[contains(@class, "navigasi-pengaturan-tautan")][@aria-current="page"][contains(., "Galeri")]')->length);

        $formulir = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT . '/tambah'));
        $this->assertSame(1, $formulir->query('//form[@data-formulir-album][@enctype="multipart/form-data"]//input[@type="file"][@name="sampul"]')->length);
    }

    public function testAdminMenambahAlbumDenganSampulWebp(): void
    {
        // Foto mendatar 2400 × 1600 piksel (karangan, dibuat GD) dikecilkan ke WebP 1200 piksel.
        $gambar = imagecreatetruecolor(2400, 1600);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 232, 95, 49));
        ob_start();
        imagejpeg($gambar, null, 80);
        $isi = (string) ob_get_clean();

        $this->siapkanBerkas('sampul', 'sampul.jpg', $isi);
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'judul'      => '  Seri 1   2025 ',
            'keterangan' => 'Foto karangan',
            'tautan'     => self::TAUTAN,
            'urutan'     => '',
            'aktif'      => '1',
        ]);
        service('superglobals')->setFilesArray([]);

        $album = model(GaleriModel::class)->first();
        $this->assertIsArray($album);
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#album-' . $album['id'], 'Album "Seri 1 2025" tersimpan.');
        $this->assertSame(self::TAUTAN, $album['tautan']);
        $this->assertSame(1, (int) $album['urutan']);
        $this->assertMatchesRegularExpression('#^unggahan/galeri/[a-f0-9]{32}\.webp$#', $album['sampul']);

        $ukuran = getimagesize($this->jalurUnggahan($album['sampul']));
        $this->assertNotFalse($ukuran);
        $this->assertSame([GaleriModel::SISI_SAMPUL, 800, IMAGETYPE_WEBP], [$ukuran[0], $ukuran[1], $ukuran[2]]);
        $this->assertSame([$album['sampul']], $this->isiFolderUnggahan(), 'Berkas JPG asli tidak tertinggal.');
    }

    public function testAlbumTanpaTautanTersimpanDenganPengingat(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => 'Belum ada tautan', 'aktif' => '1']);
        $album = model(GaleriModel::class)->first();

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#album-' . $album['id'], 'Album "Belum ada tautan" tersimpan. Album belum tampil di halaman Galeri sampai tautannya diisi.');
        $this->assertNull($album['tautan']);
        $this->assertNull($album['sampul']);
    }

    public function testTautanHanyaHttps(): void
    {
        foreach (['http://drive.google.com/album', 'javascript:alert(1)', 'drive.google.com/album', 'https://', 'https://drive google.com/x', 'ftp://contoh.example/a', 'https://pengguna:sandi@contoh.example/a'] as $tautan) {
            $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => 'Album', 'tautan' => $tautan, 'aktif' => '1']);
            $this->assertIsianSalahDitampilkanUlang($hasil, ['tautan']);
        }

        $this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => 'Album', 'tautan' => 'http://drive.google.com/a', 'aktif' => '1'])
            ->assertSee('Tautan harus diawali https://');
        $this->seeNumRecords(0, 'galeri', []);

        $this->assertTrue(GaleriModel::tautanSah('https://www.instagram.com/milklife/'));
        $this->assertTrue(GaleriModel::tautanSah('https://drive.google.com/drive/folders/1AbC-dE_f?usp=sharing'));
        $this->assertSame('Instagram', GaleriModel::tempat('https://www.instagram.com/p/x/'));
        $this->assertSame('Google Drive', GaleriModel::tempat('https://drive.google.com/drive/folders/x'));
        $this->assertSame('', GaleriModel::tempat('https://contoh.example/x'));
    }

    public function testJudulWajibDanSampulBukanGambarDitolak(): void
    {
        $this->assertIsianSalahDitampilkanUlang($this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => '   ', 'tautan' => self::TAUTAN]), ['judul']);

        $this->siapkanBerkas('sampul', 'bukan.jpg', self::isiPdf());
        $this->assertIsianSalahDitampilkanUlang($this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => 'Album', 'tautan' => self::TAUTAN]), ['sampul']);
        service('superglobals')->setFilesArray([]);

        // Sampul sah, isian lain salah: diminta memilih ulang, tidak ada berkas tersimpan.
        $this->siapkanBerkas('sampul', 'sampul.png', self::isiPng());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => '', 'tautan' => self::TAUTAN]);
        service('superglobals')->setFilesArray([]);
        $this->assertSame(1, $this->xpathDari($hasil)->query('//*[contains(@class, "pilih-ulang")]')->length);

        $this->seeNumRecords(0, 'galeri', []);
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testUbahMenggantiDanMenghapusSampul(): void
    {
        $lama = $this->simpanSampulUji();
        $id   = $this->tambahAlbum(['judul' => 'Album lama', 'tautan' => null, 'sampul' => $lama]);

        $this->bukaSebagaiAdmin(self::ALAMAT . '/' . $id . '/ubah')->assertSee('Sampul sekarang');

        // Ganti sampul dan isi tautan.
        $this->siapkanBerkas('sampul', 'baru.png', self::isiPng());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, ['judul' => 'Album baru', 'tautan' => self::TAUTAN, 'urutan' => '3', 'aktif' => '1']);
        service('superglobals')->setFilesArray([]);
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#album-' . $id, 'Perubahan album "Album baru" tersimpan.');

        $album = model(GaleriModel::class)->find($id);
        $this->assertSame(['Album baru', self::TAUTAN, 3], [$album['judul'], $album['tautan'], (int) $album['urutan']]);
        $this->assertNotSame($lama, $album['sampul']);
        $this->assertMatchesRegularExpression('#^unggahan/galeri/[a-f0-9]{32}\.webp$#', $album['sampul']);
        $this->assertFalse((new Unggahan())->ada($lama), 'Sampul lama dihapus.');

        // Ubah tanpa memilih sampul: sampul tetap.
        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, ['judul' => 'Album baru', 'tautan' => self::TAUTAN, 'aktif' => '1']);
        $this->assertSame($album['sampul'], model(GaleriModel::class)->find($id)['sampul']);

        // Hapus sampul.
        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, ['judul' => 'Album baru', 'tautan' => self::TAUTAN, 'aktif' => '1', 'hapus_sampul' => '1']);
        $this->assertNull(model(GaleriModel::class)->find($id)['sampul']);
        $this->assertSame([], $this->isiFolderUnggahan());

        // Album yang tidak ada.
        $this->assertGagalDialihkan($this->bukaSebagaiAdmin(self::ALAMAT . '/9999/ubah'), self::ALAMAT);
    }

    public function testUrutkanSembunyikanDanHapus(): void
    {
        $sampul = $this->simpanSampulUji();
        $a      = $this->tambahAlbum(['judul' => 'A', 'urutan' => 1, 'sampul' => $sampul]);
        $b      = $this->tambahAlbum(['judul' => 'B', 'urutan' => 1]);
        $c      = $this->tambahAlbum(['judul' => 'C', 'urutan' => 2]);

        $urutan = static fn (): array => array_column(model(GaleriModel::class)->semua(), 'judul');

        $this->assertBelumMasukDialihkan(self::ALAMAT . '/' . $c . '/naik', 'post');
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $c . '/naik'), self::ALAMAT . '#album-' . $c);
        $this->assertSame(['A', 'C', 'B'], $urutan());
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $a . '/naik'), self::ALAMAT . '#album-' . $a, 'Album ini sudah paling atas.');
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $a . '/turun'), self::ALAMAT . '#album-' . $a);
        $this->assertSame(['C', 'A', 'B'], $urutan());

        // Sembunyikan dan tampilkan lagi.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $b . '/aktif'), self::ALAMAT . '#album-' . $b, 'Album "B" disembunyikan dari halaman Galeri.');
        $this->assertSame(0, (int) model(GaleriModel::class)->find($b)['aktif']);
        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $b . '/aktif');
        $this->assertSame(1, (int) model(GaleriModel::class)->find($b)['aktif']);

        // Hapus beserta sampulnya.
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/' . $a . '/hapus', 'post');
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $a . '/hapus'), self::ALAMAT, 'Album "A" sudah dihapus beserta sampulnya.');
        $this->assertSame(['C', 'B'], $urutan());
        $this->assertFalse((new Unggahan())->ada($sampul));
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $a . '/hapus'), self::ALAMAT);
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, ['judul' => 'Album']);
        $this->assertTokenCsrfWajib(self::ALAMAT . '/tampil', ['tampil_galeri' => '1']);
    }

    // ---------- Album bawaan ----------

    public function testDataAwalMemasangAlbumBawaanSekali(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));
        $this->assertStringContainsString('Album galeri "Seri sebelumnya" dipasang.', $this->getStreamFilterBuffer());

        $album = model(GaleriModel::class)->semua();
        $this->assertCount(1, $album);
        $this->assertSame([AlbumBawaan::JUDUL, null, 1], [$album[0]['judul'], $album[0]['tautan'], (int) $album[0]['aktif']]);
        $this->assertMatchesRegularExpression('#^unggahan/galeri/[a-f0-9]{32}\.webp$#', $album[0]['sampul']);
        $this->assertStringStartsWith($this->folderUnggahan, (string) (new Unggahan())->jalurPenuh($album[0]['sampul']), 'Uji tidak menulis ke public/unggahan.');
        $ukuran = getimagesize($this->jalurUnggahan($album[0]['sampul']));
        $this->assertNotFalse($ukuran);
        $this->assertLessThanOrEqual(GaleriModel::SISI_SAMPUL, max($ukuran[0], $ukuran[1]));
        $this->assertFileExists(ROOTPATH . 'data/' . AlbumBawaan::SAMPUL, 'Sampul dari foto carousel bawaan F9-03.');

        // Belum bertautan: tidak tampil di publik walau halaman dinyalakan.
        setting('Galeri.tampil', true);
        $this->assertSame([], model(GaleriModel::class)->publik());

        // Perintah server lama tidak memasang lagi.
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('galeri:awal', []));
        $this->assertStringContainsString('sudah pernah dipasang', $this->getStreamFilterBuffer());
        $this->seeNumRecords(1, 'galeri', []);
    }

    public function testPerintahServerLamaMenghormatiIsianAdmin(): void
    {
        // Admin sudah punya album sendiri: tidak ditambah.
        $this->tambahAlbum(['judul' => 'Album admin', 'tautan' => self::TAUTAN]);
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('galeri:awal', []));
        $this->assertSame(['Album admin'], array_column(model(GaleriModel::class)->semua(), 'judul'));

        // Server lama yang belum punya album: dipasang sekali.
        $this->db->table('galeri')->truncate();
        setting()->forget('Galeri.bawaan_dipasang');
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('galeri:awal', []));
        $this->assertSame([AlbumBawaan::JUDUL], array_column(model(GaleriModel::class)->semua(), 'judul'));

        // Admin menghapus album bawaan: tidak muncul lagi.
        $id = (int) model(GaleriModel::class)->first()['id'];
        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id . '/hapus');
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('galeri:awal', []));
        $this->seeNumRecords(0, 'galeri', []);
    }

    // ---------- Backup data/impor (K76) ----------

    public function testAlbumIkutBackupDanImporBesertaSampul(): void
    {
        $sampul = $this->simpanSampulUji();
        $this->tambahAlbum(['judul' => 'Album pindah', 'keterangan' => 'Karangan', 'tautan' => self::TAUTAN, 'sampul' => $sampul, 'urutan' => 2]);
        $this->tambahAlbum(['judul' => 'Disembunyikan', 'tautan' => null, 'aktif' => 0, 'urutan' => 1]);
        $kolom   = 'judul, keterangan, tautan, urutan, aktif';
        $sebelum = $this->db->table('galeri')->select($kolom)->orderBy('urutan')->get()->getResultArray();

        $zip = tempnam(sys_get_temp_dir(), 'galeri') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $arsip = new ZipArchive();
            $this->assertTrue($arsip->open($zip));
            $this->assertNotFalse($arsip->getFromName('galeri/' . basename($sampul)));
            $arsip->close();

            // Server baru: album bawaan dari data:awal diganti album dari paket.
            $this->db->table('galeri')->truncate();
            unlink($this->jalurUnggahan($sampul));
            AlbumBawaan::pasang($this->db);
            $bawaan = model(GaleriModel::class)->first()['sampul'];

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $this->assertSame($sebelum, $this->db->table('galeri')->select($kolom)->orderBy('urutan')->get()->getResultArray());
        $baru = $this->db->table('galeri')->where('judul', 'Album pindah')->get()->getRowArray()['sampul'];
        $this->assertMatchesRegularExpression('#^unggahan/galeri/[a-f0-9]{32}\.png$#', $baru);
        $this->assertTrue((new Unggahan())->ada($baru), 'Sampul ikut pindah.');
        $this->assertFalse((new Unggahan())->ada($bawaan), 'Sampul album bawaan yang diganti dihapus.');
        $this->assertSame([$baru], $this->isiFolderUnggahan());
    }

    public function testPaketTanpaAlbumTidakMengubahGaleriServerBaru(): void
    {
        $zip = tempnam(sys_get_temp_dir(), 'galeri') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $id = $this->tambahAlbum(['judul' => 'Album server baru', 'tautan' => self::TAUTAN]);
            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $this->assertSame(['Album server baru'], array_column(model(GaleriModel::class)->semua(), 'judul'));
        $this->assertSame($id, (int) model(GaleriModel::class)->first()['id']);
    }

    // ---------- Pembantu ----------

    /**
     * @param array<string, mixed> $data
     */
    private function tambahAlbum(array $data): int
    {
        return (int) model(GaleriModel::class)->insert($data + [
            'keterangan' => '',
            'tautan'     => self::TAUTAN,
            'sampul'     => null,
            'urutan'     => 1,
            'aktif'      => 1,
        ]);
    }

    private function simpanSampulUji(): string
    {
        $jalur = 'unggahan/galeri/' . bin2hex(random_bytes(16)) . '.png';
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, self::isiPng());

        return $jalur;
    }
}
