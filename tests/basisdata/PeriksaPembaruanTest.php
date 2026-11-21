<?php

use App\Libraries\Hasil\DataTabelSkor;
use App\Libraries\PemeriksaPembaruan;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\UjiBasisData;

/**
 * Pemeriksaan setelah `git pull` dengan basis data uji (K115):
 * `PemeriksaPembaruan::periksa()` dengan folder proyek karangan, dan perintah
 * `php spark pembaruan:periksa`.
 *
 * @internal
 */
final class PeriksaPembaruanTest extends UjiBasisData
{
    use StreamFilterTrait;

    private string $akar = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->akar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-pembaruan-' . bin2hex(random_bytes(6)) . DIRECTORY_SEPARATOR;
        mkdir($this->akar . 'vendor/composer', 0o777, true);
        mkdir($this->akar . 'data/sekolah', 0o777, true);
        mkdir($this->akar . 'aset/js', 0o777, true);

        $paket = ['packages' => [['name' => 'contoh/pustaka', 'version' => '1.0.0']]];
        file_put_contents($this->akar . 'composer.lock', json_encode($paket));
        file_put_contents($this->akar . 'vendor/composer/installed.json', json_encode($paket));
        file_put_contents($this->akar . 'data/sekolah/sekolah.json', json_encode([
            ['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Blora'],
        ]));
        file_put_contents($this->akar . 'aset/js/contoh.js', 'var a = 1;');

        $this->db->table('sekolah')->insert(['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU', 'jenjang' => 'SD', 'kabupaten' => 'Blora']);
    }

    protected function tearDown(): void
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->akar, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $berkas) {
            $berkas->isDir() ? rmdir($berkas->getPathname()) : unlink($berkas->getPathname());
        }

        rmdir($this->akar);
        parent::tearDown();
    }

    /**
     * @return array<string, array{kunci: string, judul: string, perlu: bool, perintah: string|null, keterangan: string}>
     */
    private function periksa(bool $minify = false): array
    {
        $hasil = (new PemeriksaPembaruan($this->db, $this->akar, $this->akar . 'aset', $minify))->periksa();

        return array_column($hasil, null, 'kunci');
    }

    public function testSemuaBeresTanpaPerintah(): void
    {
        $hasil = $this->periksa();

        $this->assertSame(['composer', 'migrasi', 'sekolah', 'gabung', 'tabel_skor', 'aset'], array_keys($hasil));
        $this->assertSame([], array_filter(array_column($hasil, 'perlu')), json_encode($hasil, JSON_PRETTY_PRINT));
        $this->assertSame([null, null, null, null, null, null], array_column($hasil, 'perintah'));
        $this->assertStringContainsString('migrasi sudah dijalankan', $hasil['migrasi']['keterangan']);
    }

    public function testMasingMasingPerintahMunculBilaPerlu(): void
    {
        file_put_contents($this->akar . 'composer.lock', json_encode(['packages' => [['name' => 'contoh/pustaka', 'version' => '1.1.0']]]));
        file_put_contents($this->akar . 'data/sekolah/sekolah.json', json_encode([
            ['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Blora'],
            ['npsn' => '99000002', 'nama' => 'SD KARANGAN DUA', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Kudus'],
        ]));

        foreach ([1, 2] as $_) {
            $this->db->table('pendaftaran')->insert([
                'jenjang' => 'A', 'npsn' => '99000001', 'nama_sekolah' => 'SD KARANGAN SATU', 'kabupaten' => 'Blora',
                'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0, 'status' => 'lunas',
            ]);
        }

        $hasil = $this->periksa(true);

        $this->assertSame('composer install --no-dev --optimize-autoloader', $hasil['composer']['perintah']);
        $this->assertStringContainsString('contoh/pustaka (1.0.0 → 1.1.0)', $hasil['composer']['keterangan']);
        $this->assertNull($hasil['migrasi']['perintah']);
        $this->assertSame('php spark sekolah:impor', $hasil['sekolah']['perintah']);
        $this->assertStringContainsString('1 sekolah baru', $hasil['sekolah']['keterangan']);
        $this->assertSame('php spark pendaftaran:gabung', $hasil['gabung']['perintah']);
        $this->assertSame('php spark aset:minify', $hasil['aset']['perintah']);
        $this->assertStringContainsString('js/contoh.js', $hasil['aset']['keterangan']);
    }

    public function testTabelSkorBawaanDanTrilombaPoin(): void
    {
        // F9-10, K143: tabel bawaan belum terpasang → tabel-skor:impor --trilomba.
        mkdir($this->akar . 'data/tabel-skor', 0o777, true);
        copy(ROOTPATH . 'data/tabel-skor/trilomba-o2sn-smp.json', $this->akar . 'data/tabel-skor/trilomba-o2sn-smp.json');
        $ku = $this->db->table('kelompok_umur')->insert(['nama' => 'KU 12', 'tahun_lahir_awal' => 2014, 'tahun_lahir_akhir' => 2015, 'biaya_individu' => 75000]) ? (int) $this->db->insertID() : 0;
        $this->db->table('nomor_lomba')->insert(['kelompok_umur_id' => $ku, 'nama' => 'Trilomba', 'jenis' => 'individu', 'kategori' => 'putra', 'jenis_hasil' => 'poin']);

        $hasil = $this->periksa()['tabel_skor'];
        $this->assertSame('php spark tabel-skor:impor --trilomba', $hasil['perintah']);
        $this->assertStringContainsString('Trilomba O2SN SMP', $hasil['keterangan']);
        $this->assertStringContainsString('1 nomor Trilomba masih berjenis hasil poin', $hasil['keterangan']);

        (new DataTabelSkor($this->db))->pasangBawaan(false, $this->akar . 'data/tabel-skor');
        $this->assertSame(1, (new DataTabelSkor($this->db))->pakaiUntukTrilomba());
        $this->assertFalse($this->periksa()['tabel_skor']['perlu']);
    }

    public function testBerkasRusakTidakDianggapBeres(): void
    {
        file_put_contents($this->akar . 'vendor/composer/installed.json', '{rusak');

        $composer = $this->periksa()['composer'];

        $this->assertTrue($composer['perlu']);
        $this->assertNull($composer['perintah']);
        $this->assertStringStartsWith('Belum bisa diperiksa', $composer['keterangan']);
    }

    public function testPerintahSparkMenulisDaftarPerintah(): void
    {
        // Basis data uji belum berisi data sekolah, jadi sekolah:impor perlu.
        $this->db->table('sekolah')->truncate();

        $kode     = service('commands')->run('pembaruan:periksa', []);
        $keluaran = (string) preg_replace('/\e\[[0-9;]*m/', '', $this->getStreamFilterBuffer());

        $this->assertSame(EXIT_ERROR, $kode);
        $this->assertStringContainsString('[beres] Struktur basis data (migrasi)', $keluaran);
        $this->assertStringContainsString('[PERLU] Data sekolah', $keluaran);
        $this->assertStringContainsString('Jalankan berurutan di folder proyek:', $keluaran);
        $this->assertStringContainsString('  php spark cadangan:buat', $keluaran);
        $this->assertStringContainsString('  php spark sekolah:impor', $keluaran);
        $this->assertStringNotContainsString('  php spark migrate --all', $keluaran);
    }
}
