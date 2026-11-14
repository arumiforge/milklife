<?php

use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\SimpanPendaftaran;
use App\Models\AtletModel;
use App\Models\PendaftaranModel;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Pengumpul data dan penyimpanan pendaftaran dalam satu transaksi (F4-01):
 * ID pendaftaran, biaya tersalin, foto di luar `public/`, NISN unik, satu tim
 * per nomor beregu per sekolah, susulan, dan kuota (K51).
 *
 * @internal
 */
final class PendaftaranSimpanTest extends UjiBasisData
{
    use PendaftaranContoh;

    private const HARI_INI = '2026-11-12';

    /**
     * @var array<string, int>
     */
    private array $id = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pakaiFolderFotoSementara();
        $this->id = $this->isiDataIndukPendaftaran();
    }

    protected function tearDown(): void
    {
        $this->hapusFolderFotoSementara();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function simpan(array $kiriman): array
    {
        return (new SimpanPendaftaran())->simpan(self::kirimanUntukBasisData($kiriman, $this->id), self::HARI_INI);
    }

    /**
     * @param int $jumlah Banyak atlet Kanga's Escape (putra, KU 10).
     *
     * @return array<string, mixed>
     */
    private static function kirimanTimKanga(int $jumlah, int $mulai, string $npsn = '99999911'): array
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => $npsn, 'nama' => ''];
        $kiriman['atlet']   = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $kiriman['atlet'][] = self::atletContoh(sprintf('99%08d', $mulai + $i), [20]);
        }

        return $kiriman;
    }

    // Pengumpul data

    public function testIndukHanyaDataAktif(): void
    {
        $this->db->table('nomor_lomba')->where('id', $this->id['turbo'])->update(['aktif' => 0]);
        $this->db->table('kelompok_umur')->where('id', $this->id['ku15'])->update(['aktif' => 0]);
        setting('Pendaftaran.atlet_per_nomor_maksimal', null);

        $induk = (new DataIndukPendaftaran())->induk();

        $this->assertSame(['KU 10', 'KU 12'], array_column($induk['kelompok_umur'], 'nama'));
        $this->assertSame(['A'], $induk['kelompok_umur'][0]['jenjang']);
        $this->assertSame(150000, $induk['kelompok_umur'][0]['biaya_individu']);
        $this->assertNotContains($this->id['turbo'], array_column($induk['nomor_lomba'], 'id'));
        $this->assertNotContains($this->id['lari80putri'], array_column($induk['nomor_lomba'], 'id'));
        $this->assertSame(
            ['penanggung_jawab_minimal' => 1, 'penanggung_jawab_maksimal' => 5, 'nomor_individu_maksimal' => 2, 'atlet_per_nomor_maksimal' => null],
            $induk['aturan'],
        );
    }

    public function testKuotaBawaanTidakDibatasi(): void
    {
        // K53: kuota atlet per sekolah per nomor individu bawaannya tidak dibatasi.
        $this->assertNull(DataIndukPendaftaran::aturan()['atlet_per_nomor_maksimal']);
    }

    // Simpan

    public function testSimpanPendaftaranLengkap(): void
    {
        $kiriman = self::kirimanContoh();

        // Tim Kanga's Escape lengkap: 4 atlet putra.
        foreach ([3, 4, 5, 6] as $n) {
            $kiriman['atlet'][] = self::atletContoh('990000000' . $n, [20]);
        }

        $hasil = $this->simpan($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame('MAC2A' . str_pad((string) $hasil['pendaftaran']['id'], 5, '0', STR_PAD_LEFT) . '26', $hasil['pendaftaran']['id_pendaftaran']);

        $pendaftaran = model(PendaftaranModel::class)->cariIdPendaftaran(strtolower($hasil['pendaftaran']['id_pendaftaran']));
        $this->assertSame('SD Karangan Maju', $pendaftaran['nama_sekolah']);
        $this->assertNull($pendaftaran['npsn']);
        $this->assertSame('Kudus', $pendaftaran['kabupaten']);
        $this->assertSame('menunggu', $pendaftaran['status']);
        $this->assertSame(450000, (int) $pendaftaran['subtotal_individu']);
        $this->assertSame(100000, (int) $pendaftaran['subtotal_beregu']);
        $this->assertSame(550000, (int) $pendaftaran['total']);
        $this->assertNotNull($pendaftaran['dibuat_pada']);

        $pj = $this->db->table('penanggung_jawab')->get()->getResultArray();
        $this->assertCount(1, $pj);
        $this->assertSame('6281200000001', $pj[0]['nomor_wa']);
        $this->assertFileExists($this->folderFoto . $pj[0]['foto']);
        $this->assertCount(1, $this->berkasFoto());

        $atlet = model(AtletModel::class)->milikPendaftaran((int) $pendaftaran['id']);
        $this->assertCount(6, $atlet);
        $this->assertSame('BUDI KARANGAN 01', $atlet[0]['nama']);
        $this->assertSame((string) $this->id['ku10'], (string) $atlet[0]['kelompok_umur_id']);
        $this->assertNull($atlet[0]['nomor_dada']);

        $nomor = $this->db->table('atlet_nomor')->where('atlet_id', $atlet[0]['id'])->orderBy('id')->get()->getResultArray();
        $this->assertSame([150000, 150000], array_map('intval', array_column($nomor, 'biaya')));

        $tim = $this->db->table('pendaftaran_beregu')->get()->getResultArray();
        $this->assertCount(1, $tim);
        $this->assertSame(100000, (int) $tim[0]['biaya_tim']);

        $this->assertSame(550000, $hasil['rincian']['total']);
    }

    public function testBiayaTersalinTidakBerubahSaatTarifDiubah(): void
    {
        $hasil = $this->simpan(self::kirimanContoh());
        $this->db->table('kelompok_umur')->where('id', $this->id['ku10'])->update(['biaya_individu' => 999000]);

        $pendaftaran = model(PendaftaranModel::class)->find($hasil['pendaftaran']['id']);
        $this->assertSame(450000, (int) $pendaftaran['total']);
    }

    public function testNomorUrutIdBerlakuGlobal(): void
    {
        $pertama            = $this->simpan(self::kirimanContoh());
        $kedua              = self::kirimanContoh();
        $kedua['sekolah']   = ['jenjang' => 'B', 'kabupaten' => 'Jepara', 'npsn' => '99999913', 'nama' => ''];
        $kedua['atlet']     = [self::atletContoh('9900000031', [30], 'L', '2015-02-02')];
        $hasilKedua         = $this->simpan($kedua);

        $this->assertSame([], $hasilKedua['galat']);
        $this->assertSame($pertama['pendaftaran']['id'] + 1, $hasilKedua['pendaftaran']['id']);
        $this->assertStringStartsWith('MAC2B', $hasilKedua['pendaftaran']['id_pendaftaran']);

        $baris = model(PendaftaranModel::class)->find($hasilKedua['pendaftaran']['id']);
        $this->assertSame('SMP KARANGAN JAYA', $baris['nama_sekolah']);
        $this->assertSame('Jepara', $baris['kabupaten']);
        $this->assertSame('B', $baris['jenjang']);
    }

    public function testNisnTerdaftarDitolakTanpaMenulisApaPun(): void
    {
        $this->simpan(self::kirimanContoh());
        $jumlahFoto = count($this->berkasFoto());

        $kiriman                    = self::kirimanContoh();
        $kiriman['sekolah']['nama'] = 'SD Karangan Lain';
        $hasil                      = $this->simpan($kiriman);

        $this->assertSame('atlet.0.nisn', $hasil['galat'][0]['jalur']);
        $this->assertStringContainsString('sudah terdaftar', $hasil['galat'][0]['pesan']);
        $this->assertNull($hasil['pendaftaran']);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
        $this->assertCount($jumlahFoto, $this->berkasFoto());
    }

    public function testTransaksiGagalTidakMeninggalkanBarisAtauFoto(): void
    {
        // NISN yang lolos pemeriksaan pertama tetapi ditolak kunci unik
        // basis data (seperti kiriman lain yang tersimpan pada detik yang
        // sama): dilaporkan sebagai galat NISN, tanpa sisa baris atau foto.
        $data = new class () extends DataIndukPendaftaran {
            public int $panggilan = 0;

            public function ada(?string $npsn, array $nisn): array
            {
                $ada = parent::ada($npsn, $nisn);

                if ($this->panggilan++ === 0) {
                    $ada['nisn_terdaftar'] = [];
                }

                return $ada;
            }
        };

        $this->simpan(self::kirimanContoh());
        $sebelum = count($this->berkasFoto());

        $kiriman                    = self::kirimanUntukBasisData(self::kirimanContoh(), $this->id);
        $kiriman['sekolah']['nama'] = 'SD Karangan Lain';
        $hasil                      = (new SimpanPendaftaran(null, $data))->simpan($kiriman, self::HARI_INI);

        $this->assertSame(2, $data->panggilan);
        $this->assertStringContainsString('sudah terdaftar', $hasil['galat'][0]['pesan']);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame(1, $this->db->table('penanggung_jawab')->countAllResults());
        $this->assertSame(2, $this->db->table('atlet')->countAllResults());
        $this->assertCount($sebelum, $this->berkasFoto());
    }

    public function testSlbDitolak(): void
    {
        $kiriman                    = self::kirimanContoh();
        $kiriman['sekolah']['npsn'] = '99999912';

        $hasil = $this->simpan($kiriman);

        $this->assertSame('sekolah.npsn', $hasil['galat'][0]['jalur']);
        $this->assertStringContainsString('SLB', $hasil['galat'][0]['pesan']);
        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testUsulanNamaSekolahTercatatDanDipakaiPendaftaran(): void
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => '', 'nama_usulan' => 'SD KARANGAN MAJU REKAAN'];

        $hasil = $this->simpan($kiriman);

        // K99: pendaftaran memakai nama usulan, usulannya menunggu keputusan admin.
        $this->assertSame([], $hasil['galat']);
        $this->seeInDatabase('pendaftaran', ['id' => $hasil['pendaftaran']['id'], 'nama_sekolah' => 'SD KARANGAN MAJU REKAAN']);
        $this->seeInDatabase('usulan_nama_sekolah', [
            'npsn'           => '99999911',
            'pendaftaran_id' => $hasil['pendaftaran']['id'],
            'nama_lama'      => 'SD KARANGAN MAJU',
            'nama_usulan'    => 'SD KARANGAN MAJU REKAAN',
            'status'         => 'menunggu',
        ]);
        // Data sekolah belum berubah sampai admin menyetujui.
        $this->seeInDatabase('sekolah', ['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU']);
    }

    public function testTanpaUsulanTidakAdaBarisUsulan(): void
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => '', 'nama_usulan' => ''];

        $this->assertSame([], $this->simpan($kiriman)['galat']);
        $this->assertSame(0, $this->db->table('usulan_nama_sekolah')->countAllResults());
    }

    // Satu tim per nomor beregu per sekolah (6.6)

    public function testSusulanMenambahAnggotaTanpaBiayaTim(): void
    {
        $pertama = $this->simpan(self::kirimanTimKanga(4, 100));
        $this->assertSame([], $pertama['galat']);

        $kedua = $this->simpan(self::kirimanTimKanga(2, 200));
        $this->assertSame([], $kedua['galat']);
        $this->assertTrue($kedua['rincian']['beregu'][0]['susulan']);

        $tim = $this->db->table('pendaftaran_beregu')->orderBy('id')->get()->getResultArray();
        $this->assertSame([100000, 0], array_map('intval', array_column($tim, 'biaya_tim')));
        $this->assertSame(0, (int) model(PendaftaranModel::class)->find($kedua['pendaftaran']['id'])['total']);

        $data = (new DataIndukPendaftaran())->dataSekolah('99999911');
        $this->assertSame([$this->id['kanga'] => 6], $data['tim']);

        // Tim sudah 6 = batas anggota.
        $ketiga = $this->simpan(self::kirimanTimKanga(1, 300));
        $this->assertSame('beregu.' . $this->id['kanga'], $ketiga['galat'][0]['jalur']);
        $this->assertStringContainsString('Tim ini sudah penuh', $ketiga['galat'][0]['pesan']);
    }

    public function testSusulanTanpaKonfirmasiTidakDisimpan(): void
    {
        // K57: kiriman wizard membawa daftar konfirmasi; tim susulan yang tidak
        // ada di daftar itu ditolak dan tidak ada yang tersimpan.
        $this->assertSame([], $this->simpan(self::kirimanTimKanga(4, 100))['galat']);

        $kiriman                       = self::kirimanTimKanga(2, 200);
        $kiriman['konfirmasi_susulan'] = [];
        $hasil                         = $this->simpan($kiriman);

        $this->assertSame([['langkah' => 3, 'jalur' => 'konfirmasi.' . $this->id['kanga']]], array_map(
            static fn (array $g): array => ['langkah' => $g['langkah'], 'jalur' => $g['jalur']],
            $hasil['galat'],
        ));
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());

        $kiriman['konfirmasi_susulan'] = [20];
        $hasil                         = $this->simpan($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['rincian']['beregu'][0]['susulan']);
        $this->assertSame(0, $hasil['rincian']['beregu'][0]['biaya']);
    }

    public function testSusulanTimHanyaBerlakuUntukNpsnYangSama(): void
    {
        $this->simpan(self::kirimanTimKanga(4, 100));

        // Sekolah lain (tanpa NPSN) harus membentuk tim lengkap sendiri.
        $kiriman                    = self::kirimanTimKanga(2, 200, '');
        $kiriman['sekolah']['nama'] = 'SD Karangan Tanpa NPSN';
        $hasil                      = $this->simpan($kiriman);
        $this->assertSame('beregu.' . $this->id['kanga'], $hasil['galat'][0]['jalur']);
        $this->assertStringContainsString('baru punya 2 atlet', $hasil['galat'][0]['pesan']);
    }

    // Kuota (K51)

    public function testKuotaDihitungLintasPendaftaranNpsnYangSama(): void
    {
        setting('Pendaftaran.atlet_per_nomor_maksimal', 2);
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $kiriman['atlet']   = [self::atletContoh('9900000041', [10]), self::atletContoh('9900000042', [10])];
        $this->assertSame([], $this->simpan($kiriman)['galat']);

        $kiriman['atlet'] = [self::atletContoh('9900000043', [10])];
        $hasil            = $this->simpan($kiriman);
        $this->assertSame('individu.' . $this->id['lari60putra'], $hasil['galat'][0]['jalur']);

        setting('Pendaftaran.atlet_per_nomor_maksimal', null);
        $this->assertSame([], $this->simpan($kiriman)['galat']);
        $this->assertSame([$this->id['lari60putra'] => 3], (new DataIndukPendaftaran())->dataSekolah('99999911')['individu']);
    }

    public function testFotoTersimpanSatuPerPenanggungJawab(): void
    {
        $kiriman                       = self::kirimanContoh();
        $kiriman['penanggung_jawab'][] = ['nama' => 'Bapak Karangan', 'nomor_wa' => '+62 812 0000 0002', 'foto' => self::fotoContoh(20, 20)];

        $hasil = $this->simpan($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertCount(2, $this->berkasFoto());
        $this->assertSame(2, $this->db->table('penanggung_jawab')->countAllResults());
        $this->assertStringStartsWith($this->folderFoto, (string) (new FotoPenanggungJawab())->jalurPenuh(
            $this->db->table('penanggung_jawab')->get()->getRowArray()['foto'],
        ));
    }
}
