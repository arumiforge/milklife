<?php

use App\Libraries\Panel\TabelSisiServer;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pembacaan parameter `TabelSisiServer` tanpa basis data. Penerapannya pada
 * query diuji di `tests/basisdata/PanelTabelSisiServerQueryTest.php`.
 *
 * @internal
 */
final class PanelTabelSisiServerTest extends CIUnitTestCase
{
    private function tabel(): TabelSisiServer
    {
        return new TabelSisiServer(
            kolom: [
                'id_pendaftaran' => ['sql' => 'p.id_pendaftaran', 'cari' => true],
                'nama_sekolah'   => ['sql' => 'p.nama_sekolah', 'cari' => true],
                'total'          => 'p.total',
                'tidak_urut'     => ['sql' => 'p.kabupaten', 'urut' => false],
                'aksi'           => [],
            ],
            saringan: [
                'status'    => ['sql' => 'p.status', 'nilai' => ['lunas', 'menunggu']],
                'kabupaten' => 'p.kabupaten',
                'jenis'     => static fn (BaseBuilder $b, string $nilai): BaseBuilder => $b,
            ],
            urutBawaan: ['p.id' => 'desc'],
        );
    }

    /**
     * @param array<string, mixed> $tambahan
     *
     * @return array<string, mixed>
     */
    private static function permintaanDataTables(int $kolom, string $kunci, string $arah = 'asc', array $tambahan = []): array
    {
        return $tambahan + [
            'draw'    => '3',
            'start'   => '50',
            'length'  => '25',
            'search'  => ['value' => '', 'regex' => 'false'],
            'order'   => [['column' => (string) $kolom, 'dir' => $arah]],
            'columns' => [$kolom => ['data' => $kunci]],
        ];
    }

    public function testParameterBawaan(): void
    {
        $this->assertSame([
            'draw'     => 0,
            'mulai'    => 0,
            'panjang'  => 25,
            'cari'     => '',
            'urut'     => [['p.id', 'DESC']],
            'saringan' => [],
        ], $this->tabel()->parameter([]));
    }

    public function testParameterDataTablesDibaca(): void
    {
        $p = $this->tabel()->parameter(self::permintaanDataTables(1, 'nama_sekolah', 'desc', ['search' => ['value' => '  karangan   maju ']]));

        $this->assertSame(3, $p['draw']);
        $this->assertSame(50, $p['mulai']);
        $this->assertSame(25, $p['panjang']);
        $this->assertSame('karangan maju', $p['cari']);
        $this->assertSame([['p.nama_sekolah', 'DESC'], ['p.id', 'DESC']], $p['urut']);
    }

    public function testKolomTakDikenalDiabaikan(): void
    {
        $tabel = $this->tabel();

        foreach ([
            'nisn',                                  // tidak terdaftar
            'p.nama_sekolah',                        // ekspresi SQL, bukan kunci
            'nama_sekolah; DROP TABLE pendaftaran',  // suntikan
            '(SELECT sandi FROM admin)',
            'aksi',                                  // terdaftar tanpa SQL
            'tidak_urut',                            // terdaftar, tidak bisa diurutkan
        ] as $kunci) {
            $p = $tabel->parameter(self::permintaanDataTables(0, $kunci));
            $this->assertSame([['p.id', 'DESC']], $p['urut'], $kunci);
        }

        // Nomor kolom tanpa pasangan `columns[n][data]` juga diabaikan.
        $p = $tabel->parameter(['order' => [['column' => '7', 'dir' => 'asc']], 'columns' => [0 => ['data' => 'total']]]);
        $this->assertSame([['p.id', 'DESC']], $p['urut']);

        // Bentuk yang bukan larik tidak membuat galat.
        $p = $tabel->parameter(['order' => 'nama_sekolah', 'columns' => 'x', 'search' => 'y', 'urut' => ['total']]);
        $this->assertSame([['p.id', 'DESC']], $p['urut']);
        $this->assertSame('', $p['cari']);
    }

    public function testArahUrutHanyaAscAtauDesc(): void
    {
        $tabel = $this->tabel();

        $this->assertSame([['p.total', 'ASC'], ['p.id', 'DESC']], $tabel->parameter(self::permintaanDataTables(2, 'total', 'DESC; DROP'))['urut']);
        $this->assertSame([['p.total', 'DESC'], ['p.id', 'DESC']], $tabel->parameter(self::permintaanDataTables(2, 'total', 'DESC'))['urut']);
    }

    public function testUrutBawaanTidakDigandakan(): void
    {
        $tabel = new TabelSisiServer(['id' => 'p.id', 'nama' => 'p.nama'], [], ['p.id' => 'desc']);

        $this->assertSame([['p.id', 'ASC']], $tabel->parameter(self::permintaanDataTables(0, 'id'))['urut']);
    }

    public function testBentukPendekUntukExcel(): void
    {
        $p = $this->tabel()->parameter(['cari' => 'maju', 'urut' => 'total', 'arah' => 'desc', 'status' => 'lunas']);

        $this->assertSame('maju', $p['cari']);
        $this->assertSame([['p.total', 'DESC'], ['p.id', 'DESC']], $p['urut']);
        $this->assertSame(['status' => 'lunas'], $p['saringan']);
    }

    public function testParameterExcelMengikutiPermintaanTabel(): void
    {
        $masukan = self::permintaanDataTables(1, 'nama_sekolah', 'desc', ['search' => ['value' => 'maju'], 'status' => 'lunas', 'kabupaten' => 'Kudus']);

        $this->assertSame(
            ['status' => 'lunas', 'kabupaten' => 'Kudus', 'cari' => 'maju', 'urut' => 'nama_sekolah', 'arah' => 'desc'],
            $this->tabel()->parameterExcel($masukan),
        );

        // Bentuk pendek menghasilkan dirinya sendiri.
        $pendek = ['status' => 'lunas', 'kabupaten' => 'Kudus', 'cari' => 'maju', 'urut' => 'nama_sekolah', 'arah' => 'desc'];
        $this->assertSame($pendek, $this->tabel()->parameterExcel($pendek));
    }

    public function testSaringanHanyaYangTerdaftarDanNilaiDiizinkan(): void
    {
        $p = $this->tabel()->parameter([
            'status'    => 'batal',          // di luar daftar nilai
            'kabupaten' => '  Kudus ',
            'jenis'     => '',               // kosong: tidak menyaring
            'nisn'      => '9900000001',     // tidak terdaftar
            'sandi'     => 'x',
        ]);
        $this->assertSame(['kabupaten' => 'Kudus'], $p['saringan']);

        $p = $this->tabel()->parameter(['status' => 'lunas', 'kabupaten' => ['Kudus'], 'jenis' => 'L']);
        $this->assertSame(['status' => 'lunas', 'jenis' => 'L'], $p['saringan']);

        // Nilai yang terlalu panjang diabaikan.
        $p = $this->tabel()->parameter(['kabupaten' => str_repeat('a', 101)]);
        $this->assertSame([], $p['saringan']);
    }

    public function testPanjangDanMulaiDibatasi(): void
    {
        $tabel = $this->tabel();

        $this->assertSame(100, $tabel->parameter(['length' => '100000'])['panjang']);
        $this->assertSame(25, $tabel->parameter(['length' => '-1'])['panjang']);
        $this->assertSame(25, $tabel->parameter(['length' => '0'])['panjang']);
        $this->assertSame(25, $tabel->parameter(['length' => 'semua'])['panjang']);
        $this->assertSame(10, $tabel->parameter(['length' => '10'])['panjang']);
        $this->assertSame(0, $tabel->parameter(['start' => '-20'])['mulai']);
        $this->assertSame(0, $tabel->parameter(['start' => '1e9'])['mulai']);
        $this->assertSame(0, $tabel->parameter(['draw' => '<script>'])['draw']);
    }

    public function testCariDipotong(): void
    {
        $p = $this->tabel()->parameter(['search' => ['value' => str_repeat('é', 150)]]);

        $this->assertSame(100, mb_strlen($p['cari']));
    }

    public function testNamaSaringanYangBentrokDitolak(): void
    {
        foreach (['search', 'order', 'columns', 'cari', 'urut', 'arah', 'draw', 'start', 'length'] as $nama) {
            try {
                new TabelSisiServer(['a' => 'a'], [$nama => 'a']);
                $this->fail("Nama saringan {$nama} seharusnya ditolak.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
