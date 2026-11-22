<?php

use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\RiwayatAdmin;
use App\Libraries\Unggahan;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Pengaturan > Cadangan (F11-11, K174): cadangan lengkap semua data,
 * pengaturan, dan berkas unggahan (`CadanganLengkap`), impor yang mengganti
 * semua setelah cadangan otomatis dan konfirmasi kata sandi, serta
 * `pendaftaran:impor` dan `cadangan:pulihkan` untuk berkas yang sama.
 * Semua nama, NISN, dan berkas karangan.
 *
 * @internal
 */
final class PengaturanCadanganTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/cadangan';

    private const SANDI = 'sandi-karangan-cadangan-1';

    private string $zip = '';

    private string $folderOtomatis = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->pakaiFolderFotoSementara();
        $this->pakaiFolderBuktiBayarSementara();
        $this->folderOtomatis = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-cadangan-' . bin2hex(random_bytes(6));
        CadanganLengkap::gantiFolderOtomatis($this->folderOtomatis);
        $this->zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-cadangan-' . bin2hex(random_bytes(6)) . '.zip';
        $this->bersihkanSementara();
        // Pembatas percobaan kata sandi mulai dari nol di setiap uji.
        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->zip . '*') ?: [] as $berkas) {
            @unlink($berkas);
        }

        CadanganLengkap::gantiFolderOtomatis(null);
        self::hapusFolder($this->folderOtomatis);
        $this->bersihkanSementara();
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testEksporLaluImporMengembalikanSemuaDataPengaturanDanBerkas(): void
    {
        $this->isiContoh();
        $sebelum = $this->potret();

        $hasil = (new CadanganLengkap())->ekspor($this->zip);
        $this->assertSame(2, $hasil['pendaftaran']);
        $this->assertGreaterThanOrEqual(5, $hasil['berkas']);

        // Isi zip: Backup data lama tetap ada, ditambah semua tabel dan manifes.
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($this->zip));
        $manifes = json_decode((string) $zip->getFromName(CadanganLengkap::MANIFES), true);
        $this->assertNotFalse($zip->locateName('data.json'));
        $this->assertNotFalse($zip->locateName('tabel/settings.jsonl'));
        $this->assertNotFalse($zip->locateName('tabel/atlet_diganti.jsonl'));
        $this->assertFalse($zip->locateName('tabel/admin.jsonl'), 'Akun admin tidak ikut cadangan.');
        $this->assertFalse($zip->locateName('tabel/migrations.jsonl'));
        $this->assertNotFalse($zip->locateName('unggahan/videotron/' . str_repeat('ab', 16) . '.webp'));
        $this->assertNotFalse($zip->locateName('unggahan/unduhan/' . str_repeat('cd', 16) . '.pdf'));
        $zip->close();
        $this->assertSame(CadanganLengkap::VERSI, $manifes['versi']);
        $this->assertSame(2, $manifes['tabel']['pendaftaran']['baris']);
        $this->assertArrayHasKey('kolom', $manifes['tabel']['hasil']);

        // Data berubah setelah cadangan dibuat.
        $this->ubahSetelahCadangan();
        $sesudahUbah = $this->potret();
        $this->assertNotSame($sebelum, $sesudahUbah);

        $impor = (new CadanganLengkap())->impor($this->zip);

        $this->assertSame($sebelum, $this->potret());
        $this->assertSame([], $impor['peringatan']);
        $this->assertSame('[{"nama":"Gabungan SD","kelompok_umur":[1,2]}]', (string) setting('Klasemen.gabungan'));
        $this->assertSame('unggahan/videotron/' . str_repeat('ab', 16) . '.webp', (string) setting('Videotron.latar'));
        $this->assertSame([], glob(Unggahan::folderDasar() . '*.{pulih,lama}-*', GLOB_BRACE) ?: [], 'Folder sementara impor sudah dibersihkan.');

        // Cadangan otomatis menyimpan data yang tadi diganti.
        $otomatis = CadanganLengkap::daftarOtomatis();
        $this->assertCount(1, $otomatis);
        $this->assertSame($this->folderOtomatis . DIRECTORY_SEPARATOR . $otomatis[0]['nama'], $impor['cadangan_otomatis']);
        (new CadanganLengkap())->impor((string) $impor['cadangan_otomatis'], false);
        $this->assertSame($sesudahUbah, $this->potret());
    }

    public function testPendaftaranImporTetapBisaMemakaiCadanganBaru(): void
    {
        $this->isiContoh();
        (new CadanganLengkap())->ekspor($this->zip);

        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['pendaftaran', 'atlet', 'atlet_nomor', 'penanggung_jawab', 'bukti_bayar', 'pendaftaran_beregu', 'atlet_diganti', 'riwayat_admin'] as $tabel) {
            $this->db->query('DELETE FROM `' . $tabel . '`');
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->assertSame(EXIT_SUCCESS, service('commands')->run('pendaftaran:impor', [$this->zip]));
        $this->seeNumRecords(2, 'pendaftaran', []);
    }

    public function testPerintahPulihkanCadangan(): void
    {
        $this->isiContoh();
        $sebelum = $this->potret();
        (new CadanganLengkap())->ekspor($this->zip);
        $this->ubahSetelahCadangan();

        ob_start();
        $kode = service('commands')->run('cadangan:pulihkan', [$this->zip, 'ya' => null]);
        ob_end_clean();

        $this->assertSame(EXIT_SUCCESS, $kode);
        $this->assertSame($this->tanpaRiwayat($sebelum), $this->tanpaRiwayat($this->potret()));
        $this->assertSame(RiwayatAdmin::IMPOR_CADANGAN, $this->riwayatTerakhir()['tindakan']);
        $this->assertCount(1, CadanganLengkap::daftarOtomatis());
        $this->assertSame(EXIT_ERROR, service('commands')->run('cadangan:pulihkan', []));
    }

    /**
     * @return iterable<string, array{0: Closure(string): void, 1: string, 2: bool}>
     */
    public static function berkasRusak(): iterable
    {
        $ubahZip = static fn (Closure $ubah): Closure => static function (string $jalur) use ($ubah): void {
            $zip = new ZipArchive();
            $zip->open($jalur);
            $ubah($zip);
            $zip->close();
        };
        $ubahManifes = static fn (Closure $ubah): Closure => $ubahZip(static function (ZipArchive $zip) use ($ubah): void {
            $manifes = json_decode((string) $zip->getFromName(CadanganLengkap::MANIFES), true);
            $zip->addFromString(CadanganLengkap::MANIFES, (string) json_encode($ubah($manifes)));
        });

        yield 'bukan zip' => [static fn (string $jalur) => file_put_contents($jalur, 'bukan zip sama sekali'), 'bukan zip', false];
        yield 'Backup data lama' => [$ubahZip(static fn (ZipArchive $zip) => $zip->deleteName(CadanganLengkap::MANIFES)), 'pendaftaran:impor', false];
        yield 'bukan cadangan' => [$ubahManifes(static fn (array $m): array => ['jenis' => 'lain'] + $m), 'bukan cadangan', false];
        yield 'versi tak dikenal' => [$ubahManifes(static fn (array $m): array => ['versi' => 99] + $m), 'Versi cadangan ini (99)', false];
        yield 'web lebih baru' => [$ubahManifes(static fn (array $m): array => ['migrasi' => '9999-12-31-235959'] + $m), 'versi lebih baru', false];
        yield 'tabel tak dikenal' => [$ubahManifes(static function (array $m): array {
            $m['tabel']['tabel_asing'] = ['kolom' => ['id'], 'baris' => 0];

            return $m;
        }), 'tabel_asing', false];
        yield 'kolom tak dikenal' => [$ubahManifes(static function (array $m): array {
            $m['tabel']['pendaftaran']['kolom'][] = 'kolom_asing';

            return $m;
        }), 'kolom_asing', false];
        yield 'jalur keluar folder' => [$ubahZip(static fn (ZipArchive $zip) => $zip->addFromString('unggahan/galeri/../../../luar.php', '<?php echo 1;')), 'tidak dikenal', false];
        yield 'berkas php' => [$ubahZip(static fn (ZipArchive $zip) => $zip->addFromString('berkas/penanggung-jawab/jahat.php', '<?php echo 1;')), 'tidak dikenal', false];
        yield 'baris rusak' => [$ubahZip(static fn (ZipArchive $zip) => $zip->addFromString('tabel/pendaftaran.jsonl', (string) $zip->getFromName('tabel/pendaftaran.jsonl') . "{rusak\n")), 'rusak', true];
        yield 'baris kurang' => [$ubahZip(static function (ZipArchive $zip): void {
            $baris = explode("\n", trim((string) $zip->getFromName('tabel/atlet.jsonl')));
            $zip->addFromString('tabel/atlet.jsonl', $baris[0] . "\n");
        }), 'tidak lengkap', true];
        yield 'nilai bukan teks' => [$ubahZip(static function (ZipArchive $zip): void {
            $baris    = json_decode(explode("\n", (string) $zip->getFromName('tabel/faq.jsonl'))[0], true) ?? ['id' => 1];
            $baris['pertanyaan'] = ['bersarang' => true];
            $zip->addFromString('tabel/faq.jsonl', json_encode($baris) . "\n");
        }), 'rusak', true];
    }

    /**
     * @param Closure(string): void $rusakkan
     *
     * @dataProvider berkasRusak
     */
    public function testBerkasRusakDitolakTanpaMengubahApaPun(Closure $rusakkan, string $pesan, bool $setelahCadanganOtomatis): void
    {
        $this->isiContoh();
        $this->db->table('faq')->insert(['kategori' => 'umum', 'pertanyaan' => 'Pertanyaan karangan?', 'jawaban' => 'Jawaban karangan.', 'urutan' => 1, 'aktif' => 1]);
        (new CadanganLengkap())->ekspor($this->zip);
        $this->ubahSetelahCadangan();
        $sebelum = $this->potret();
        $rusakkan($this->zip);

        try {
            (new CadanganLengkap())->impor($this->zip);
            $this->fail('Berkas rusak harus ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($pesan, $e->getMessage());
            $this->assertStringEndsWith('Tidak ada yang berubah.', $e->getMessage());
        }

        $this->assertSame($sebelum, $this->potret());
        $this->assertCount($setelahCadanganOtomatis ? 1 : 0, CadanganLengkap::daftarOtomatis());
        $this->assertSame([], glob(Unggahan::folderDasar() . '*.{pulih,lama}-*', GLOB_BRACE) ?: []);
        $this->assertSame([], glob(BerkasBuktiBayar::folderDasar() . '*.{pulih,lama}-*', GLOB_BRACE) ?: []);
        $this->assertFileDoesNotExist(dirname(Unggahan::folderDasar()) . DIRECTORY_SEPARATOR . 'luar.php');
    }

    // ---------- Halaman ----------

    public function testHalamanCadanganDanAlamatLama(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $hasil->assertSee('Impor mengganti semua data dan pengaturan');
        $hasil->assertSee('php spark cadangan:pulihkan');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//form[@action="' . site_url(self::ALAMAT . '/unduh') . '"][@method="post"]//button[@id="unduh-cadangan"]')->length);
        $this->assertSame(1, $xpath->query('//form[@action="' . site_url(self::ALAMAT . '/impor') . '"][@enctype="multipart/form-data"][@data-konfirmasi]//input[@type="password"][@name="sandi"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@href="' . site_url(self::ALAMAT) . '"][@aria-current="page"]')->length);

        // Tombol Backup data tidak lagi di Pendaftaran; alamat lamanya mengarah ke sini.
        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran'))->query('//*[@id="ekspor-pendaftaran"]')->length);
        $this->bukaSebagaiAdmin('admin/pendaftaran/ekspor')->assertRedirectTo(site_url(self::ALAMAT));

        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/unduh', 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/impor', 'post');
        $this->assertTokenCsrfWajib(self::ALAMAT . '/unduh');
        $this->assertTokenCsrfWajib(self::ALAMAT . '/impor', ['sandi' => self::SANDI]);
        $this->assertCount(0, $this->db->table('riwayat_admin')->get()->getResultArray());
    }

    public function testUnduhLewatWebDialirkanDanDicatat(): void
    {
        $this->isiContoh();

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/unduh');
        $hasil->assertStatus(200);

        // K118 B4: dikirim dari berkas di `writable/cache/ekspor/`, tanpa dimuat ke memori.
        $jawaban = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $jawaban);
        $this->assertSame('', (string) $jawaban->getBody());
        $jawaban->buildHeaders();
        $this->assertStringStartsWith('application/zip', $jawaban->getHeaderLine('Content-Type'));
        $this->assertMatchesRegularExpression('/attachment; filename="Cadangan_MilkLife_\d{8}_\d{6}\.zip"/', $jawaban->getHeaderLine('Content-Disposition'));

        ob_start();
        $jawaban->sendBody();
        file_put_contents($this->zip, (string) ob_get_clean());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($this->zip));
        $this->assertNotFalse($zip->locateName(CadanganLengkap::MANIFES));
        $zip->close();

        $riwayat = $this->riwayatTerakhir();
        $this->assertSame(RiwayatAdmin::UNDUH_CADANGAN, $riwayat['tindakan']);
        $this->assertSame('Admin Uji', $riwayat['nama_admin']);
        $this->assertStringContainsString('2 pendaftaran', $riwayat['ringkasan']);
        $this->bukaSebagaiAdmin(self::ALAMAT)->assertSee('Unduh cadangan · Admin Uji');
    }

    public function testImporLewatWebMenggantiSemuaSetelahCadanganOtomatis(): void
    {
        $this->buatAdmin();
        $this->isiContoh();
        $sebelum = $this->potret();
        (new CadanganLengkap())->ekspor($this->zip);
        $this->ubahSetelahCadangan();
        $sesudahUbah = $this->potret();

        $this->siapkanBerkas('berkas', 'Cadangan_MilkLife_20261124_101500.zip', (string) file_get_contents($this->zip));
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => self::SANDI]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->assertStringStartsWith('Cadangan berhasil diimpor.', (string) ($_SESSION['pesan_sukses'] ?? ''));
        $this->assertSame($this->tanpaRiwayat($sebelum), $this->tanpaRiwayat($this->potret()));
        $riwayat = $this->riwayatTerakhir();
        $this->assertSame(RiwayatAdmin::IMPOR_CADANGAN, $riwayat['tindakan']);
        $this->assertStringContainsString('Cadangan_MilkLife_20261124_101500.zip diimpor', $riwayat['ringkasan']);
        $this->assertSame([], glob($this->folderOtomatis . DIRECTORY_SEPARATOR . 'Impor_*') ?: [], 'Berkas unggahan sementara dihapus.');

        // Cadangan otomatis tampil, bisa diunduh, dan berisi data sebelum impor.
        $otomatis = CadanganLengkap::daftarOtomatis();
        $this->assertCount(1, $otomatis);
        $this->bukaSebagaiAdmin(self::ALAMAT)->assertSee($otomatis[0]['nama']);
        $unduh = $this->bukaSebagaiAdmin(self::ALAMAT . '/' . $otomatis[0]['nama'] . '/unduh');
        $unduh->assertStatus(200);
        $this->assertInstanceOf(DownloadResponse::class, $unduh->response());

        try {
            $this->bukaSebagaiAdmin(self::ALAMAT . '/Sebelum_Impor_20260101_000000_000000.zip/unduh');
            $this->fail('Cadangan otomatis yang tidak ada harus 404.');
        } catch (PageNotFoundException) {
            // Halaman tidak ditemukan.
        }

        (new CadanganLengkap())->impor($this->folderOtomatis . DIRECTORY_SEPARATOR . $otomatis[0]['nama'], false);
        $this->assertSame($this->tanpaRiwayat($sesudahUbah), $this->tanpaRiwayat($this->potret()));
        $this->seeNumRecords(1, 'admin', []);
    }

    public function testImporLewatWebDitolakTanpaPerubahan(): void
    {
        $this->buatAdmin();
        $this->isiContoh();
        (new CadanganLengkap())->ekspor($this->zip);
        $this->ubahSetelahCadangan();
        $sebelum = $this->potret();

        // Kata sandi salah.
        $this->siapkanBerkas('berkas', 'cadangan.zip', (string) file_get_contents($this->zip));
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => 'sandi-yang-salah']);
        $this->assertIsianSalahDitampilkanUlang($hasil, ['sandi']);
        $hasil->assertSee(esc(\App\Controllers\Admin\Pengaturan\CadanganController::PESAN_SANDI_SALAH));

        // Tanpa kata sandi dan tanpa berkas.
        service('superglobals')->setFilesArray([]);
        $this->assertIsianSalahDitampilkanUlang($this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => '']), ['berkas', 'sandi']);

        // Bukan zip, dan berkas melebihi batas unggah server.
        $this->siapkanBerkas('berkas', 'cadangan.sql', 'SELECT 1;');
        $this->assertIsianSalahDitampilkanUlang($this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => self::SANDI]), ['berkas']);
        $this->siapkanBerkas('berkas', 'cadangan.zip', '', UPLOAD_ERR_INI_SIZE);
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => self::SANDI]);
        $this->assertIsianSalahDitampilkanUlang($hasil, ['berkas']);
        $hasil->assertSee('cadangan:pulihkan');

        // Zip rusak: sandi benar, tetap ditolak dengan pesan di halaman.
        $this->siapkanBerkas('berkas', 'cadangan.zip', 'PK rusak');
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => self::SANDI]);
        $this->assertIsianSalahDitampilkanUlang($hasil, ['berkas']);
        $hasil->assertSee('Tidak ada yang berubah.');

        $this->assertSame($sebelum, $this->potret());
        $this->assertSame([], CadanganLengkap::daftarOtomatis());
    }

    public function testPercobaanSandiDibatasi(): void
    {
        $this->buatAdmin();
        $this->siapkanBerkas('berkas', 'cadangan.zip', 'PK rusak');

        for ($i = 0; $i < 5; $i++) {
            $this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => 'sandi-yang-salah'])->assertSee('Kata sandi salah');
        }

        $this->kirimSebagaiAdmin(self::ALAMAT . '/impor', ['sandi' => self::SANDI])->assertSee('Terlalu banyak percobaan kata sandi');
    }

    // ---------- Pembantu ----------

    /**
     * Data karangan di semua bagian: pendaftaran berfoto dan berbukti bayar,
     * pendaftaran yang dihapus, atlet diganti, pengaturan, dan unggahan admin.
     */
    private function isiContoh(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(['nama' => 'Bima Karangan', 'nomor' => ['lari60putra']])]]);
        $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN CADANGAN', 'npsn' => '99999931']]);
        $c = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN DIHAPUS', 'npsn' => '99999932']]);
        $this->buatBuktiBayarPanel($a['id'], 2);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['id'] . '/lunas');
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $c['id'] . '/hapus');

        $this->db->table('atlet_diganti')->insert([
            'pendaftaran_id' => $a['id'], 'nama' => 'Atlet Lama Karangan', 'nisn' => '9900199001', 'tanggal_lahir' => '2017-01-02', 'jenis_kelamin' => 'L',
            'kelompok_umur' => 'KU 10', 'nomor_dada' => null, 'nomor_lomba' => '[]', 'pengganti_nama' => 'Bima Karangan', 'pengganti_nisn' => '9900100001',
            'pengganti_nomor_dada' => null, 'alasan' => 'Sakit (karangan).', 'diganti_oleh' => 'Admin Uji', 'dibuat_pada' => '2026-11-20 08:00:00',
        ]);

        setting('Klasemen.gabungan', '[{"nama":"Gabungan SD","kelompok_umur":[1,2]}]');
        setting('Videotron.latar', 'unggahan/videotron/' . str_repeat('ab', 16) . '.webp');
        $this->tulisUnggahan('videotron/' . str_repeat('ab', 16) . '.webp', 'RIFF-latar-karangan');
        $this->tulisUnggahan('unduhan/' . str_repeat('cd', 16) . '.pdf', '%PDF-1.4 juknis karangan');
        Services::resetSingle('settings');
    }

    /**
     * Perubahan setelah cadangan dibuat: pendaftaran baru, pengaturan, dan berkas.
     */
    private function ubahSetelahCadangan(): void
    {
        $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN SESUDAH', 'npsn' => '99999933']]);
        $this->db->table('atlet_diganti')->where('id >', 0)->delete();
        setting('Klasemen.gabungan', '[]');
        setting('Acara.nama', 'Acara Karangan Baru');
        unlink(Unggahan::folderDasar() . 'videotron' . DIRECTORY_SEPARATOR . str_repeat('ab', 16) . '.webp');
        $this->tulisUnggahan('galeri/' . str_repeat('ef', 16) . '.webp', 'RIFF-sampul-baru');
        Services::resetSingle('settings');
    }

    private function tulisUnggahan(string $jalur, string $isi): void
    {
        $penuh = Unggahan::folderDasar() . $jalur;

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, $isi);
    }

    private function buatAdmin(): void
    {
        $this->db->table('admin')->insert(['id' => 1, 'nama' => 'Admin Uji', 'nama_pengguna' => 'admin.uji', 'sandi' => password_hash(self::SANDI, PASSWORD_DEFAULT)]);
    }

    /**
     * Isi semua tabel yang ikut cadangan (baris diurutkan) dan semua berkas unggahan (nama => md5).
     *
     * @return array<string, mixed>
     */
    private function potret(): array
    {
        $potret = [];

        foreach ($this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->getResultArray() as $baris) {
            $tabel = (string) reset($baris);

            if (in_array($tabel, CadanganLengkap::TABEL_DILEWATI, true)) {
                continue;
            }

            $isi = array_map(static fn (array $b): string => (string) json_encode($b), $this->db->query('SELECT * FROM `' . $tabel . '`')->getResultArray());
            sort($isi);
            $potret['tabel:' . $tabel] = $isi;
        }

        foreach (CadanganLengkap::petaFolder() as $dalamZip => $folder) {
            $berkas = [];

            foreach (glob($folder . DIRECTORY_SEPARATOR . '*') ?: [] as $penuh) {
                $berkas[basename($penuh)] = md5_file($penuh);
            }

            ksort($berkas);
            $potret['berkas:' . $dalamZip] = $berkas;
        }

        ksort($potret);

        return $potret;
    }

    /**
     * @param array<string, mixed> $potret
     *
     * @return array<string, mixed>
     */
    private function tanpaRiwayat(array $potret): array
    {
        unset($potret['tabel:riwayat_admin']);

        return $potret;
    }

    /**
     * @return array<string, mixed>
     */
    private function riwayatTerakhir(): array
    {
        return (array) $this->db->table('riwayat_admin')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
    }

    private function bersihkanSementara(): void
    {
        foreach (glob(PaketPendaftaran::FOLDER_SEMENTARA . '/*') ?: [] as $berkas) {
            @unlink($berkas);
        }
    }
}
