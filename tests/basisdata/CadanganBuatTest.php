<?php

use App\Commands\BuatCadangan;
use App\Libraries\Cadangan\PembuatCadangan;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\Mock\MockInputOutput;
use Config\Cadangan;
use Config\Database;
use Tests\Support\UjiBasisData;

/**
 * `php spark cadangan:buat` dengan basis data uji (K66): isi zip, masa
 * simpan, penolakan folder di dalam proyek, dan kode keluar. Folder cadangan
 * dan folder unggahan dibuat sementara di luar proyek dan dihapus lagi.
 *
 * @internal
 */
final class CadanganBuatTest extends UjiBasisData
{
    private string $sementara;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sementara = sys_get_temp_dir() . '/milklife-uji-cadangan-' . bin2hex(random_bytes(4));
        mkdir($this->sementara . '/cadangan', 0777, true);
        mkdir($this->sementara . '/writable-unggahan/bukti-bayar', 0777, true);
        mkdir($this->sementara . '/public-unggahan/kepala', 0777, true);
        file_put_contents($this->sementara . '/writable-unggahan/bukti-bayar/bukti-karangan.jpg', 'gambar karangan');
        file_put_contents($this->sementara . '/public-unggahan/kepala/kepala-karangan.png', 'gambar kepala');
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();
        $this->hapusFolder($this->sementara);

        parent::tearDown();
    }

    public function testZipBerisiBasisDataDanUnggahan(): void
    {
        $this->db->table('sekolah')->insert([
            'npsn' => '99999901', 'nama' => 'SD Karangan Cadangan', 'bentuk' => 'SD', 'jenjang' => 'SD',
            'status' => 'Negeri', 'kabupaten' => 'Kudus',
        ]);

        $hasil = $this->pembuat()->buat(new DateTimeImmutable('2026-11-12 02:00'));

        $this->assertSame($this->sementara . '/cadangan/cadangan-milklife-2026-11-12-0200.zip', $hasil['berkas']);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($hasil['berkas']));
        $sql = (string) $zip->getFromName('basisdata.sql');
        $this->assertStringContainsString('CREATE TABLE `sekolah`', $sql);
        $this->assertStringContainsString('SD Karangan Cadangan', $sql);
        $this->assertSame('gambar karangan', $zip->getFromName('writable/unggahan/bukti-bayar/bukti-karangan.jpg'));
        $this->assertSame('gambar kepala', $zip->getFromName('public/unggahan/kepala/kepala-karangan.png'));
        $zip->close();
    }

    public function testCadanganLamaDihapusSetelahBerhasil(): void
    {
        $folder = $this->sementara . '/cadangan/';
        touch($folder . 'cadangan-milklife-2026-10-29-0200.zip');
        touch($folder . 'cadangan-milklife-2026-10-30-0200.zip');
        touch($folder . 'catatan.txt');

        $hasil = $this->pembuat()->buat(new DateTimeImmutable('2026-11-12 02:00'));

        $this->assertSame(['cadangan-milklife-2026-10-29-0200.zip'], $hasil['dihapus']);
        $this->assertFileDoesNotExist($folder . 'cadangan-milklife-2026-10-29-0200.zip');
        $this->assertFileExists($folder . 'cadangan-milklife-2026-10-30-0200.zip');
        $this->assertFileExists($folder . 'catatan.txt');
    }

    public function testGagalTidakMenghapusCadanganLama(): void
    {
        $lama = $this->sementara . '/cadangan/cadangan-milklife-2026-01-01-0200.zip';
        touch($lama);

        try {
            $this->pembuat(mysqldump: $this->sementara . '/tidak-ada/mysqldump')->buat(new DateTimeImmutable('2026-11-12 02:00'));
            $this->fail('Seharusnya gagal');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cadangan.mysqldump', $e->getMessage());
        }

        $this->assertFileExists($lama);
        $this->assertSame([basename($lama)], array_map('basename', glob($this->sementara . '/cadangan/*') ?: []));
    }

    public function testMenolakFolderDiDalamProyek(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('berada di dalam folder proyek');

        $this->pembuat(folder: WRITEPATH . 'cache')->buat();
    }

    public function testMenolakFolderKosongAtauTidakAda(): void
    {
        foreach (['' => 'belum diisi', $this->sementara . '/tidak-ada' => 'tidak ditemukan'] as $folder => $pesan) {
            try {
                $this->pembuat(folder: $folder)->buat();
                $this->fail('Seharusnya gagal: ' . $folder);
            } catch (RuntimeException $e) {
                $this->assertStringContainsString($pesan, $e->getMessage());
            }
        }
    }

    public function testPenggunaKhususDenganSandiBerkarakterKhusus(): void
    {
        // Sandi lewat berkas opsi sementara, bukan baris perintah; pengguna
        // hanya punya hak atas basis datanya sendiri seperti di produksi. Host
        // '%' karena di CI MySQL berjalan di kontainer Docker (bukan 127.0.0.1).
        $basisData = $this->sambungan()['database'];
        $sandi     = 'sandi "karangan" #1 \\ \'kutip\'';
        $this->db->query("CREATE USER 'milklife_cadangan'@'%' IDENTIFIED BY " . $this->db->escape($sandi));
        $this->db->query("GRANT ALL PRIVILEGES ON `{$basisData}`.* TO 'milklife_cadangan'@'%'");

        try {
            $sambungan = ['username' => 'milklife_cadangan', 'password' => $sandi] + $this->sambungan();
            $hasil     = (new PembuatCadangan($this->pengaturan(), $sambungan, []))->buat();

            $this->assertFileExists($hasil['berkas']);
        } finally {
            $this->db->query("DROP USER IF EXISTS 'milklife_cadangan'@'%'");
        }
    }

    public function testPerintahKeluarDenganKodeSatuBilaGagal(): void
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        config('Cadangan')->folder = '';

        $kode = (new BuatCadangan(service('logger'), service('commands')))->run([]);

        $this->assertSame(EXIT_ERROR, $kode);
        $this->assertStringContainsString('cadangan.folder di .env belum diisi', $io->getOutput());
    }

    public function testPerintahBerhasil(): void
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        config('Cadangan')->folder = $this->sementara . '/cadangan';

        $kode = (new BuatCadangan(service('logger'), service('commands')))->run([]);

        $this->assertSame(EXIT_SUCCESS, $kode);
        $this->assertStringContainsString('Cadangan dibuat: ', $io->getOutput());
        $this->assertCount(1, glob($this->sementara . '/cadangan/cadangan-milklife-*.zip') ?: []);
    }

    private function pembuat(?string $folder = null, string $mysqldump = 'mysqldump'): PembuatCadangan
    {
        return new PembuatCadangan($this->pengaturan($folder, $mysqldump), $this->sambungan(), [
            'writable/unggahan' => $this->sementara . '/writable-unggahan',
            'public/unggahan'   => $this->sementara . '/public-unggahan',
        ]);
    }

    private function pengaturan(?string $folder = null, string $mysqldump = 'mysqldump'): Cadangan
    {
        $pengaturan            = new Cadangan();
        $pengaturan->folder    = $folder ?? $this->sementara . '/cadangan';
        $pengaturan->mysqldump = $mysqldump;

        return $pengaturan;
    }

    /**
     * @return array<string, mixed>
     */
    private function sambungan(): array
    {
        return config(Database::class)->tests;
    }

    private function hapusFolder(string $folder): void
    {
        if (! is_dir($folder)) {
            return;
        }

        $isi = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($isi as $berkas) {
            $berkas->isDir() ? rmdir($berkas->getPathname()) : unlink($berkas->getPathname());
        }

        rmdir($folder);
    }
}
