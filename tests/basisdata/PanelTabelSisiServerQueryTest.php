<?php

use App\Libraries\Panel\TabelSisiServer;
use CodeIgniter\Database\BaseBuilder;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * `TabelSisiServer` pada basis data uji: jumlah, pencarian, saringan, urutan,
 * halaman, dan percobaan menyuntik kolom.
 *
 * @internal
 */
final class PanelTabelSisiServerQueryTest extends UjiBasisData
{
    use PanelDataContoh;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $daftar = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();

        $this->daftar['maju'] = $this->buatPendaftaranPanel(['dibuat_pada' => '2026-11-01 08:00:00']);
        $this->daftar['jaya'] = $this->buatPendaftaranPanel([
            'sekolah'     => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
            'status'      => 'lunas',
            'atlet'       => [$this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
            'dibuat_pada' => '2026-11-02 08:00:00',
        ]);
        $this->daftar['tim'] = $this->buatPendaftaranPanel([
            'sekolah'     => ['nama' => 'SD KARANGAN 100% "TIM"', 'npsn' => null, 'jenjang' => 'A', 'kabupaten' => 'Pati'],
            'atlet'       => $this->timPanel('kanga', 4),
            'dibuat_pada' => '2026-11-03 08:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    private function tabel(): TabelSisiServer
    {
        return new TabelSisiServer(
            kolom: [
                'id_pendaftaran' => ['sql' => 'p.id_pendaftaran', 'cari' => true],
                'nama_sekolah'   => ['sql' => 'p.nama_sekolah', 'cari' => true],
                'kabupaten'      => ['sql' => 'p.kabupaten', 'cari' => true],
                'jumlah_atlet'   => 'jumlah_atlet',
                'total'          => 'p.total',
                'status'         => 'p.status',
            ],
            saringan: [
                'status'    => ['sql' => 'p.status', 'nilai' => ['lunas', 'menunggu']],
                'jenjang'   => ['sql' => 'p.jenjang', 'nilai' => ['A', 'B', 'C']],
                'kabupaten' => 'p.kabupaten',
                // Saringan lewat tabel lain.
                'ku' => static fn (BaseBuilder $b, string $nilai): BaseBuilder => $b->where('EXISTS (SELECT 1 FROM atlet x WHERE x.pendaftaran_id = p.id AND x.kelompok_umur_id = ' . (int) $nilai . ')', null, false),
            ],
            urutBawaan: ['p.id' => 'desc'],
        );
    }

    private function pembuat(): Closure
    {
        return fn (): BaseBuilder => $this->db->table('pendaftaran p')
            ->select('p.id, p.id_pendaftaran, p.nama_sekolah, p.kabupaten, p.total, p.status, COUNT(a.id) AS jumlah_atlet')
            ->join('atlet a', 'a.pendaftaran_id = p.id', 'left')
            ->groupBy('p.id');
    }

    /**
     * @param array<string, mixed> $masukan
     *
     * @return list<string>
     */
    private function sekolah(array $masukan): array
    {
        return array_column($this->tabel()->jawab($this->pembuat(), $masukan)['data'], 'nama_sekolah');
    }

    public function testJawabanBentukDataTables(): void
    {
        $jawaban = $this->tabel()->jawab($this->pembuat(), ['draw' => '7']);

        $this->assertSame(['draw', 'recordsTotal', 'recordsFiltered', 'data'], array_keys($jawaban));
        $this->assertSame(7, $jawaban['draw']);
        $this->assertSame(3, $jawaban['recordsTotal']);
        $this->assertSame(3, $jawaban['recordsFiltered']);
        // Urutan bawaan: terbaru dulu. Jumlah atlet dihitung per pendaftaran (GROUP BY).
        $this->assertSame(['SD KARANGAN 100% "TIM"', 'SMP KARANGAN JAYA', 'SD KARANGAN MAJU'], array_column($jawaban['data'], 'nama_sekolah'));
        $this->assertSame(['4', '1', '2'], array_map('strval', array_column($jawaban['data'], 'jumlah_atlet')));
    }

    public function testCariDiKolomYangDicari(): void
    {
        $jawaban = $this->tabel()->jawab($this->pembuat(), ['search' => ['value' => 'jaya']]);
        $this->assertSame(3, $jawaban['recordsTotal']);
        $this->assertSame(1, $jawaban['recordsFiltered']);
        $this->assertSame(['SMP KARANGAN JAYA'], array_column($jawaban['data'], 'nama_sekolah'));

        $this->assertSame(['SD KARANGAN MAJU'], $this->sekolah(['search' => ['value' => 'kudus']]));
        $this->assertSame(['SD KARANGAN MAJU'], $this->sekolah(['search' => ['value' => $this->daftar['maju']['id_pendaftaran']]]));

        // Karakter LIKE dicari apa adanya, bukan sebagai pola.
        $this->assertSame(['SD KARANGAN 100% "TIM"'], $this->sekolah(['search' => ['value' => '100%']]));
        $this->assertSame([], $this->sekolah(['search' => ['value' => '_%_']]));
        $this->assertSame([], $this->sekolah(['search' => ['value' => "' OR 1=1 -- "]]));
    }

    public function testSaringan(): void
    {
        $this->assertSame(['SMP KARANGAN JAYA'], $this->sekolah(['status' => 'lunas']));
        $this->assertSame(['SD KARANGAN 100% "TIM"', 'SD KARANGAN MAJU'], $this->sekolah(['status' => 'menunggu']));
        $this->assertSame(['SD KARANGAN 100% "TIM"', 'SD KARANGAN MAJU'], $this->sekolah(['jenjang' => 'A']));
        $this->assertSame(['SD KARANGAN MAJU'], $this->sekolah(['jenjang' => 'A', 'kabupaten' => 'Kudus']));
        $this->assertSame(['SMP KARANGAN JAYA'], $this->sekolah(['ku' => (string) $this->idInduk['ku15']]));

        // Nilai di luar daftar diabaikan; nilai bebas yang tidak cocok menghasilkan kosong.
        $this->assertCount(3, $this->sekolah(['status' => "lunas' OR '1'='1"]));
        $this->assertSame([], $this->sekolah(['kabupaten' => "Kudus' OR '1'='1"]));

        $jawaban = $this->tabel()->jawab($this->pembuat(), ['status' => 'menunggu', 'search' => ['value' => 'maju']]);
        $this->assertSame(3, $jawaban['recordsTotal']);
        $this->assertSame(1, $jawaban['recordsFiltered']);
    }

    public function testUrutan(): void
    {
        $permintaan = static fn (int $kolom, string $kunci, string $arah): array => [
            'order' => [['column' => (string) $kolom, 'dir' => $arah]], 'columns' => [$kolom => ['data' => $kunci]],
        ];

        $this->assertSame(['SD KARANGAN 100% "TIM"', 'SD KARANGAN MAJU', 'SMP KARANGAN JAYA'], $this->sekolah($permintaan(1, 'nama_sekolah', 'asc')));
        $this->assertSame(['SMP KARANGAN JAYA', 'SD KARANGAN MAJU', 'SD KARANGAN 100% "TIM"'], $this->sekolah($permintaan(1, 'nama_sekolah', 'desc')));

        // Total: tim 100.000, Jaya 200.000, Maju 450.000.
        $this->assertSame(['SD KARANGAN 100% "TIM"', 'SMP KARANGAN JAYA', 'SD KARANGAN MAJU'], $this->sekolah($permintaan(4, 'total', 'asc')));
        $this->assertSame(['SD KARANGAN 100% "TIM"', 'SD KARANGAN MAJU', 'SMP KARANGAN JAYA'], $this->sekolah($permintaan(3, 'jumlah_atlet', 'desc')));
    }

    public function testKolomSuntikanDiabaikan(): void
    {
        $bawaan = $this->sekolah([]);

        foreach ([
            'nama_sekolah; DROP TABLE pendaftaran',
            '(SELECT nama FROM admin LIMIT 1)',
            'p.nama_sekolah',
            'IF(1=1,p.total,p.id)',
            'sandi',
        ] as $kunci) {
            $this->assertSame($bawaan, $this->sekolah(['order' => [['column' => '0', 'dir' => 'asc']], 'columns' => [['data' => $kunci]]]), $kunci);
            $this->assertSame($bawaan, $this->sekolah(['urut' => $kunci, 'arah' => 'asc']), $kunci);
        }

        $this->assertTrue($this->db->tableExists('pendaftaran', false));
        $this->assertSame(3, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testHalaman(): void
    {
        $jawaban = $this->tabel()->jawab($this->pembuat(), ['start' => '1', 'length' => '1']);

        $this->assertSame(3, $jawaban['recordsFiltered']);
        $this->assertSame(['SMP KARANGAN JAYA'], array_column($jawaban['data'], 'nama_sekolah'));

        $jawaban = $this->tabel()->jawab($this->pembuat(), ['start' => '10', 'length' => '25']);
        $this->assertSame([], $jawaban['data']);
        $this->assertSame(3, $jawaban['recordsFiltered']);
    }

    public function testSemuaBarisTanpaBatasHalamanDenganSaringanSama(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        }

        $masukan = ['status' => 'menunggu', 'length' => '2', 'start' => '2', 'urut' => 'total', 'arah' => 'desc'];
        $baris   = $this->tabel()->semuaBaris($this->pembuat(), $masukan);

        $this->assertCount(5, $baris);
        $this->assertSame('SD KARANGAN MAJU', $baris[0]['nama_sekolah']);
        $this->assertSame(450000, (int) $baris[0]['total']);
        $this->assertNotContains('lunas', array_column($baris, 'status'));

        $jawaban = $this->tabel()->jawab($this->pembuat(), $masukan);
        $this->assertSame(5, $jawaban['recordsFiltered']);
        $this->assertCount(2, $jawaban['data']);
    }

    public function testBentukBaris(): void
    {
        $tabel = $this->tabel()->bentukBaris(static fn (array $b): array => [
            'sekolah' => $b['nama_sekolah'],
            'tautan'  => ['alamat' => site_url('admin/pendaftaran/' . $b['id']), 'teks' => $b['id_pendaftaran']],
        ]);

        $jawaban = $tabel->jawab($this->pembuat(), ['status' => 'lunas']);

        $this->assertSame([[
            'sekolah' => 'SMP KARANGAN JAYA',
            'tautan'  => ['alamat' => site_url('admin/pendaftaran/' . $this->daftar['jaya']['id']), 'teks' => $this->daftar['jaya']['id_pendaftaran']],
        ]], $jawaban['data']);
        $this->assertSame('SMP KARANGAN JAYA', $tabel->semuaBaris($this->pembuat(), ['status' => 'lunas'])[0]['sekolah']);
    }
}
