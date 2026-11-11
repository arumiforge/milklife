<?php

use App\Libraries\Unggahan;
use App\Models\UnduhanModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Halaman pengaturan unduhan (F2-07): daftar, tambah, ubah, hapus, dan
 * berkasnya di folder unggahan (folder sementara selama uji).
 *
 * @internal
 */
final class PengaturanUnduhanTest extends UjiBasisData
{
    use KirimFormulir;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/unduhan';

    protected function tearDown(): void
    {
        Factories::reset('models');

        parent::tearDown();
    }

    // ---------- Akses ----------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/tambah');
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1/ubah');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1', 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1/hapus', 'post');
    }

    public function testTokenCsrfWajib(): void
    {
        $id = $this->buatBaris();

        $this->siapkanBerkas('berkas', 'juknis.pdf', self::isiPdf());
        $this->assertTokenCsrfWajib(self::ALAMAT, $this->isianSah());
        $this->assertTokenCsrfWajib(self::ALAMAT . '/' . $id, $this->isianSah(['judul' => 'Judul Baru']));
        $this->assertTokenCsrfWajib(self::ALAMAT . '/' . $id . '/hapus');

        $this->assertSame(1, $this->model()->countAllResults());
        $this->assertSame('Juknis Lama', $this->model()->find($id)['judul']);
        $this->assertCount(1, $this->isiFolderUnggahan(), 'Kiriman tanpa token tidak boleh menyimpan berkas.');
    }

    // ---------- Daftar ----------

    public function testDaftarKosongMenampilkanAjakanMenambah(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSee('Belum ada berkas unduhan');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]')->length);
        $this->assertGreaterThan(0, $xpath->query('//a[@href="' . site_url(self::ALAMAT . '/tambah') . '"]')->length);
        $this->assertSame(0, $xpath->query('//table')->length);
    }

    public function testDaftarMenampilkanJenisUkuranDanTautanBerkas(): void
    {
        $idPdf = $this->buatBaris(['judul' => 'Juknis Seri 2', 'keterangan' => 'Petunjuk teknis lomba.', 'urutan' => 2]);
        $idPng = $this->buatBaris(['judul' => 'Flyer Pendaftaran', 'urutan' => 1, 'tampil' => 0], 'flyer.png', self::isiPng());

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $baris = $xpath->query('//tbody/tr');
        $this->assertSame(2, $baris->length);

        // Urutan kecil lebih dulu.
        $this->assertSame((string) $idPng, $baris->item(0)->getAttribute('data-id'));
        $this->assertSame((string) $idPdf, $baris->item(1)->getAttribute('data-id'));

        $pdf = $this->model()->find($idPdf);
        $this->assertSame('PDF', trim($xpath->query('.//*[contains(@class, "jenis-berkas")]', $baris->item(1))->item(0)->textContent));
        $this->assertSame(Unggahan::ukuranTeks(strlen(self::isiPdf())), trim($xpath->query('.//*[contains(@class, "ukuran-berkas")]', $baris->item(1))->item(0)->textContent));
        $this->assertSame(1, $xpath->query('.//a[@href="' . base_url($pdf['berkas']) . '" and @target="_blank"]', $baris->item(1))->length);
        $this->assertSame('PNG', trim($xpath->query('.//*[contains(@class, "jenis-berkas")]', $baris->item(0))->item(0)->textContent));

        $hasil->assertSee('Petunjuk teknis lomba.');
        $hasil->assertSee('Disembunyikan');
        $hasil->assertSee('Tampil');

        // Hapus lewat formulir POST ber-konfirmasi dengan token CSRF.
        $hapus = $xpath->query('//form[@action="' . site_url(self::ALAMAT . '/' . $idPdf . '/hapus') . '"]')->item(0);
        $this->assertNotNull($hapus);
        $this->assertSame('post', $hapus->getAttribute('method'));
        $this->assertStringContainsString('Juknis Seri 2', $hapus->getAttribute('data-konfirmasi'));
        $this->assertSame(1, $xpath->query('.//input[@name="' . config('Security')->tokenName . '"]', $hapus)->length);
        $hasil->assertSee(aset('js/konfirmasi.js'));
    }

    public function testDaftarMenandaiBerkasYangHilang(): void
    {
        $id = $this->buatBaris();
        unlink($this->jalurUnggahan($this->model()->find($id)['berkas']));

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSee('Berkas tidak ditemukan. Unggah lagi lewat Ubah.');
    }

    // ---------- Tambah ----------

    public function testFormulirTambahTerbukaDenganUrutanBerikutnya(): void
    {
        $this->buatBaris(['urutan' => 4]);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $form  = $xpath->query('//form[@action="' . site_url(self::ALAMAT) . '"]')->item(0);
        $this->assertNotNull($form);
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertSame('5', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//input[@name="tampil"]')->item(0)->hasAttribute('checked'));

        $kotakBerkas = $xpath->query('//input[@type="file" and @name="berkas"]')->item(0);
        $this->assertNotNull($kotakBerkas);
        $this->assertSame((string) Unggahan::BATAS_BERKAS, $kotakBerkas->getAttribute('data-ukuran-maksimal'));
        $hasil->assertSee('PDF, JPG, PNG, DOC, DOCX, XLS, atau XLSX, paling besar 10 MB.');
    }

    public function testTambahBerkasBerhasil(): void
    {
        $this->siapkanBerkas('berkas', 'Juknis Seri 2.pdf', self::isiPdf());

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah([
            'judul'      => '  Juknis Seri 2  ',
            'keterangan' => 'Petunjuk teknis lomba.',
            'urutan'     => '3',
        ]));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Berkas "Juknis Seri 2" tersimpan.');

        $baris = $this->model()->first();
        $this->assertSame('Juknis Seri 2', $baris['judul']);
        $this->assertSame('Petunjuk teknis lomba.', $baris['keterangan']);
        $this->assertSame(3, (int) $baris['urutan']);
        $this->assertSame(1, (int) $baris['tampil']);
        $this->assertMatchesRegularExpression('#^unggahan/unduhan/[a-f0-9]{32}\.pdf$#', $baris['berkas']);
        $this->assertSame([$baris['berkas']], $this->isiFolderUnggahan());
        $this->assertSame(self::isiPdf(), file_get_contents($this->jalurUnggahan($baris['berkas'])));
    }

    public function testTambahTanpaCentangTampilDanKeteranganKosong(): void
    {
        $this->siapkanBerkas('berkas', 'flyer.jpg', self::isiJpg());

        $isian = $this->isianSah(['keterangan' => '   ']);
        unset($isian['tampil']);
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $isian);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $baris = $this->model()->first();
        $this->assertSame(0, (int) $baris['tampil']);
        $this->assertNull($baris['keterangan']);
        $this->assertStringEndsWith('.jpg', $baris['berkas']);
    }

    public function testTambahTanpaBerkasDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['berkas']);
        $hasil->assertSee('Belum ada berkas yang dipilih.');
        $this->assertSame(0, $this->model()->countAllResults());
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3: string}>
     */
    public static function berkasDitolak(): iterable
    {
        yield 'skrip PHP bernama pdf' => ['juknis.pdf', '<?php echo "halo";', UPLOAD_ERR_OK, 'Jenis berkas tidak bisa dipakai.'];
        yield 'teks biasa' => ['catatan.txt', "Catatan biasa\n", UPLOAD_ERR_OK, 'Jenis berkas tidak bisa dipakai.'];
        yield 'berkas kosong' => ['kosong.pdf', '', UPLOAD_ERR_OK, 'Berkas yang dipilih kosong.'];
        yield 'melebihi batas PHP' => ['besar.pdf', '', UPLOAD_ERR_INI_SIZE, 'Ukuran berkas terlalu besar. Paling besar 10 MB'];
        yield 'terputus' => ['setengah.pdf', '', UPLOAD_ERR_PARTIAL, 'Pengiriman berkas terputus.'];
    }

    /**
     * @dataProvider berkasDitolak
     */
    public function testBerkasTidakSahDitolak(string $nama, string $isi, int $kode, string $pesan): void
    {
        $this->siapkanBerkas('berkas', $nama, $isi, $kode);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah(['judul' => 'Juknis <Seri 2>']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['berkas']);
        $hasil->assertSee($pesan);
        // Isian yang diketik tetap ada (di-escape).
        $this->assertSame('Juknis <Seri 2>', $this->xpathDari($hasil)->query('//input[@name="judul"]')->item(0)->getAttribute('value'));
        $this->assertSame(0, $this->model()->countAllResults());
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testBerkasLebihDariSepuluhMbDitolak(): void
    {
        $this->siapkanBerkas('berkas', 'besar.pdf', self::isiPdf() . str_repeat('0', Unggahan::BATAS_BERKAS));

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['berkas']);
        $hasil->assertSee('lebih dari batas 10 MB');
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testIsianSalahDitampilkanUlangDenganPesan(): void
    {
        $this->siapkanBerkas('berkas', 'juknis.pdf', self::isiPdf());

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'judul'      => '',
            'keterangan' => str_repeat('a', UnduhanModel::PANJANG_KETERANGAN + 1),
            'urutan'     => '-1',
            'tampil'     => '1',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'keterangan', 'urutan']);
        $hasil->assertSee('Ada 3 isian yang perlu diperbaiki:');
        $hasil->assertSee('Judul belum diisi.');
        $hasil->assertSee('Keterangan terlalu panjang.');
        $hasil->assertSee('Urutan harus angka bulat tanpa tanda');
        // Berkas sah, tetapi belum disimpan: admin diminta memilihnya lagi.
        $hasil->assertSee('Pilih berkasnya lagi sebelum menyimpan.');
        $this->assertSame(0, $this->model()->countAllResults());
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testUrutanDanJudulDiBatas(): void
    {
        $this->siapkanBerkas('berkas', 'juknis.pdf', self::isiPdf());

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah([
            'judul'  => str_repeat('j', UnduhanModel::PANJANG_JUDUL + 1),
            'urutan' => (string) (UnduhanModel::URUTAN_TERBESAR + 1),
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'urutan']);
        $hasil->assertSee('Judul terlalu panjang.');
        $hasil->assertSee('Urutan terlalu besar.');

        $this->siapkanBerkas('berkas', 'juknis.pdf', self::isiPdf());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah([
            'judul'  => str_repeat('é', UnduhanModel::PANJANG_JUDUL),
            'urutan' => (string) UnduhanModel::URUTAN_TERBESAR,
        ]));
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
    }

    public function testBarisGagalDisimpanTidakMeninggalkanBerkas(): void
    {
        Factories::injectMock('models', UnduhanModel::class, new class () extends UnduhanModel {
            public function insert($row = null, bool $returnID = true)
            {
                return false;
            }
        });
        $this->siapkanBerkas('berkas', 'juknis.pdf', self::isiPdf());

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isianSah());

        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertStringContainsString('belum tersimpan', (string) session('pesan_galat'));
        $this->assertSame([], $this->isiFolderUnggahan(), 'Berkas baru harus dihapus lagi bila barisnya gagal disimpan.');
    }

    // ---------- Ubah ----------

    public function testFormulirUbahTerisi(): void
    {
        $id    = $this->buatBaris(['judul' => 'Juknis Lama', 'keterangan' => 'Versi pertama.', 'urutan' => 7, 'tampil' => 0]);
        $jalur = $this->model()->find($id)['berkas'];

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/' . $id . '/ubah');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertNotNull($xpath->query('//form[@action="' . site_url(self::ALAMAT . '/' . $id) . '"]')->item(0));
        $this->assertSame('Juknis Lama', $xpath->query('//input[@name="judul"]')->item(0)->getAttribute('value'));
        $this->assertSame('Versi pertama.', $xpath->query('//textarea[@name="keterangan"]')->item(0)->textContent);
        $this->assertSame('7', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'));
        $this->assertFalse($xpath->query('//input[@name="tampil"]')->item(0)->hasAttribute('checked'));
        $this->assertFalse($xpath->query('//input[@name="berkas"]')->item(0)->hasAttribute('required'));
        $this->assertSame(1, $xpath->query('//*[contains(@class, "berkas-sekarang")]//a[@href="' . base_url($jalur) . '"]')->length);
    }

    public function testUbahYangTidakAdaTidakDitemukan(): void
    {
        foreach ([
            static fn ($uji) => $uji->bukaSebagaiAdmin(self::ALAMAT . '/999/ubah'),
            static fn ($uji) => $uji->kirimSebagaiAdmin(self::ALAMAT . '/999', ['judul' => 'X', 'urutan' => '1']),
            static fn ($uji) => $uji->kirimSebagaiAdmin(self::ALAMAT . '/999/hapus'),
        ] as $buka) {
            try {
                $buka($this);
                $this->fail('Berkas unduhan yang tidak ada seharusnya tidak ditemukan.');
            } catch (PageNotFoundException $e) {
                $this->assertSame(404, $e->getCode());
            }
        }
    }

    public function testUbahTanpaMenggantiBerkas(): void
    {
        $id    = $this->buatBaris();
        $jalur = $this->model()->find($id)['berkas'];

        $isian = ['judul' => 'Juknis Baru', 'keterangan' => '', 'urutan' => '9'];
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, $isian);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Perubahan berkas "Juknis Baru" tersimpan.');
        $baris = $this->model()->find($id);
        $this->assertSame('Juknis Baru', $baris['judul']);
        $this->assertNull($baris['keterangan']);
        $this->assertSame(9, (int) $baris['urutan']);
        $this->assertSame(0, (int) $baris['tampil']);
        $this->assertSame($jalur, $baris['berkas']);
        $this->assertSame([$jalur], $this->isiFolderUnggahan());
    }

    public function testGantiBerkasMenghapusBerkasLama(): void
    {
        $id    = $this->buatBaris();
        $lama  = $this->model()->find($id)['berkas'];

        $this->siapkanBerkas('berkas', 'juknis-baru.png', self::isiPng());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, $this->isianSah(['judul' => 'Juknis Revisi']));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $baru = $this->model()->find($id)['berkas'];
        $this->assertNotSame($lama, $baru);
        $this->assertStringEndsWith('.png', $baru);
        $this->assertSame([$baru], $this->isiFolderUnggahan(), 'Hanya berkas baru yang tersisa.');
    }

    public function testGantiDenganBerkasTidakSahMempertahankanBerkasLama(): void
    {
        $id   = $this->buatBaris();
        $lama = $this->model()->find($id)['berkas'];

        $this->siapkanBerkas('berkas', 'ganti.docx', 'bukan dokumen');
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, $this->isianSah(['judul' => 'Judul Lain']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['berkas']);
        $hasil->assertSee('Jenis berkas tidak bisa dipakai.');
        $baris = $this->model()->find($id);
        $this->assertSame('Juknis Lama', $baris['judul']);
        $this->assertSame($lama, $baris['berkas']);
        $this->assertSame([$lama], $this->isiFolderUnggahan());
    }

    public function testUbahDenganIsianSalah(): void
    {
        $id = $this->buatBaris();

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, ['judul' => '', 'urutan' => 'satu']);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'urutan']);
        $hasil->assertSee('Urutan harus angka bulat tanpa tanda');
        $this->assertSame('Juknis Lama', $this->model()->find($id)['judul']);
    }

    public function testBarisGagalDiperbaruiMempertahankanBerkasLama(): void
    {
        $id   = $this->buatBaris();
        $lama = $this->model()->find($id)['berkas'];

        Factories::injectMock('models', UnduhanModel::class, new class () extends UnduhanModel {
            public function update($id = null, $row = null): bool
            {
                return false;
            }
        });
        $this->siapkanBerkas('berkas', 'baru.pdf', self::isiPdf());

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id, $this->isianSah(['judul' => 'Judul Lain']));

        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertStringContainsString('Berkas lama tetap dipakai.', (string) session('pesan_galat'));
        $this->assertSame([$lama], $this->isiFolderUnggahan(), 'Berkas baru dihapus, berkas lama tetap ada.');
    }

    // ---------- Hapus ----------

    public function testHapusMenghapusBarisDanBerkas(): void
    {
        $id   = $this->buatBaris(['judul' => 'Juknis Seri 2']);
        $sisa = $this->buatBaris(['judul' => 'Flyer']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id . '/hapus');

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Berkas "Juknis Seri 2" sudah dihapus.');
        $this->assertNull($this->model()->find($id));
        $this->assertSame([$this->model()->find($sisa)['berkas']], $this->isiFolderUnggahan());
    }

    public function testHapusTetapBerjalanBilaBerkasSudahTidakAda(): void
    {
        $id = $this->buatBaris();
        unlink($this->jalurUnggahan($this->model()->find($id)['berkas']));

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id . '/hapus');

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->assertNull($this->model()->find($id));
    }

    public function testBarisGagalDihapusMempertahankanBerkas(): void
    {
        $id    = $this->buatBaris();
        $jalur = $this->model()->find($id)['berkas'];

        Factories::injectMock('models', UnduhanModel::class, new class () extends UnduhanModel {
            public function delete($id = null, bool $purge = false)
            {
                return false;
            }
        });

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $id . '/hapus');

        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertStringContainsString('belum terhapus', (string) session('pesan_galat'));
        Factories::reset('models');
        $this->assertNotNull($this->model()->find($id));
        $this->assertSame([$jalur], $this->isiFolderUnggahan());
    }

    // ---------- Bantuan ----------

    private function model(): UnduhanModel
    {
        return new UnduhanModel();
    }

    /**
     * @param array<string, string> $timpa
     *
     * @return array<string, string>
     */
    private function isianSah(array $timpa = []): array
    {
        return $timpa + [
            'judul'      => 'Juknis Seri 2',
            'keterangan' => '',
            'urutan'     => '1',
            'tampil'     => '1',
        ];
    }

    /**
     * Baris unduhan beserta berkasnya di folder unggahan sementara, lewat
     * `Unggahan` seperti pengendali.
     *
     * @param array<string, mixed> $data
     */
    private function buatBaris(array $data = [], string $nama = 'juknis.pdf', ?string $isi = null): int
    {
        $this->siapkanBerkas('sumber_uji', $nama, $isi ?? self::isiPdf());
        $jalur = (new Unggahan())->simpanBerkas($this->berkasUji('sumber_uji'), 'unduhan');
        service('superglobals')->setFilesArray([]);

        return (int) $this->model()->insert($data + [
            'judul'  => 'Juknis Lama',
            'urutan' => 1,
            'tampil' => 1,
            'berkas' => $jalur,
        ]);
    }

    private function berkasUji(string $kotak): UploadedFile
    {
        $data = service('superglobals')->getFilesArray()[$kotak];

        return new UploadedFile($data['tmp_name'], $data['name'], $data['type'], $data['size'], $data['error']);
    }
}
