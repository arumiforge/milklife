<?php

use App\Libraries\PemeriksaPembaruan;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan murni pemeriksaan setelah `git pull` (K115): paket Composer, migrasi
 * tertunda, beda data sekolah, dan berkas `.min` yang usang.
 *
 * @internal
 */
final class PemeriksaPembaruanTest extends CIUnitTestCase
{
    private string $folder = '';

    protected function tearDown(): void
    {
        if ($this->folder !== '' && is_dir($this->folder)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->folder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $berkas) {
                $berkas->isDir() ? rmdir($berkas->getPathname()) : unlink($berkas->getPathname());
            }

            rmdir($this->folder);
        }

        parent::tearDown();
    }

    public function testPaketYangBelumTerpasangAtauBedaVersiDisebut(): void
    {
        $kunci = ['packages' => [
            ['name' => 'contoh/sama', 'version' => '1.0.0'],
            ['name' => 'contoh/naik', 'version' => '2.1.0'],
            ['name' => 'contoh/baru', 'version' => 'v3.0.0'],
        ], 'packages-dev' => [['name' => 'contoh/uji', 'version' => '9.0.0']]];
        $terpasang = ['packages' => [
            ['name' => 'contoh/sama', 'version' => '1.0.0'],
            ['name' => 'contoh/naik', 'version' => '2.0.0'],
        ]];

        // Paket pengembang (packages-dev) tidak diperiksa: server memakai --no-dev.
        $this->assertSame(
            ['contoh/baru (belum ada → v3.0.0)', 'contoh/naik (2.0.0 → 2.1.0)'],
            PemeriksaPembaruan::paketTertinggal($kunci, $terpasang),
        );
        $this->assertSame([], PemeriksaPembaruan::paketTertinggal(['packages' => [$kunci['packages'][0]]], $terpasang));
    }

    public function testMigrasiYangBelumTercatatDisebutBerurutan(): void
    {
        $berkas = [
            ['version' => '2026-11-14-000200', 'class' => 'App\Database\Migrations\Kedua', 'name' => 'Kedua'],
            ['version' => '2026-11-11-000100', 'class' => 'App\Database\Migrations\Pertama', 'name' => 'Pertama'],
            ['version' => '2026-11-16-000300', 'class' => 'App\Database\Migrations\Ketiga', 'name' => 'Ketiga'],
        ];
        $riwayat = [['version' => '2026-11-11-000100', 'class' => 'App\Database\Migrations\Pertama']];

        $this->assertSame(['Kedua', 'Ketiga'], PemeriksaPembaruan::migrasiTertunda($berkas, $riwayat));
        $this->assertSame([], PemeriksaPembaruan::migrasiTertunda([$berkas[1]], $riwayat));
    }

    public function testBedaSekolahMenghitungBaruDanBerubahTanpaYangDisunting(): void
    {
        $daftar = [
            ['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU', 'jenjang' => 'SD', 'desa_kelurahan' => 'Rekaan', 'kabupaten_kota' => 'Kab. Blora'],
            ['npsn' => '99000002', 'nama' => 'SD KARANGAN DUA', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Kudus'],
            ['npsn' => '99000003', 'nama' => 'SD KARANGAN TIGA', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Pati'],
            ['npsn' => '99000004', 'nama' => 'SD KARANGAN EMPAT', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Pati'],
            // Tidak sah dan NPSN ganda dilewati, sama seperti sekolah:impor.
            ['npsn' => '123', 'nama' => 'SALAH', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Pati'],
            ['npsn' => '99000004', 'nama' => 'SD GANDA', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Pati'],
        ];
        $kosong    = ['bentuk' => null, 'status' => null, 'alamat' => null, 'kecamatan' => null, 'disunting_pada' => null];
        $tersimpan = [
            // Sama persis.
            '99000001' => ['nama' => 'SD KARANGAN SATU', 'jenjang' => 'SD', 'desa_kelurahan' => 'Rekaan', 'kabupaten' => 'Blora'] + $kosong,
            // Nama berubah di berkas baru.
            '99000002' => ['nama' => 'SD KARANGAN II', 'jenjang' => 'SD', 'desa_kelurahan' => null, 'kabupaten' => 'Kudus'] + $kosong,
            // Disunting admin: tidak akan ditimpa, jadi tidak dihitung.
            '99000003' => ['nama' => 'SD KARANGAN TIGA REKAAN', 'jenjang' => 'SD', 'desa_kelurahan' => null, 'kabupaten' => 'Pati', 'disunting_pada' => '2026-11-17 08:00:00'] + $kosong,
        ];

        $this->assertSame(['baru' => 1, 'berubah' => 1], PemeriksaPembaruan::bedaSekolah($daftar, $tersimpan));
    }

    public function testAsetMinYangBelumAdaAtauLebihTuaDisebut(): void
    {
        $this->folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-periksa-' . bin2hex(random_bytes(6));
        mkdir($this->folder . '/css', 0o777, true);
        mkdir($this->folder . '/js', 0o777, true);
        mkdir($this->folder . '/vendor/pustaka', 0o777, true);

        $tulis = function (string $berkas, int $waktu): void {
            file_put_contents($this->folder . '/' . $berkas, 'a{}');
            touch($this->folder . '/' . $berkas, $waktu);
        };

        $tulis('css/tema.css', 1000);
        $tulis('js/baru.js', 1000);
        $tulis('js/berubah.js', 2000);
        $tulis('js/berubah.min.js', 1000);
        $tulis('js/sama.js', 1000);
        $tulis('js/sama.min.js', 1000);
        $tulis('vendor/pustaka/pustaka.js', 1000);

        $this->assertSame(
            ['usang' => ['css/tema.css', 'js/baru.js', 'js/berubah.js'], 'ada_min' => true],
            PemeriksaPembaruan::asetUsang($this->folder),
        );

        unlink($this->folder . '/js/berubah.min.js');
        unlink($this->folder . '/js/sama.min.js');
        $this->assertFalse(PemeriksaPembaruan::asetUsang($this->folder)['ada_min']);
    }
}
