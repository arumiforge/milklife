<?php

use App\Libraries\KelengkapanPengaturan;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan kelengkapan pengaturan (rancangan 6.1 revisi 3), kelas murni (F2-09).
 *
 * @internal
 */
final class KelengkapanPengaturanTest extends CIUnitTestCase
{
    public function testPotretKosongSemuaButirBelumLengkap(): void
    {
        $kelengkapan = new KelengkapanPengaturan([]);

        $this->assertFalse($kelengkapan->lengkap());
        $this->assertSame(0, $kelengkapan->jumlahLengkap());
        $this->assertSame(
            ['acara', 'pendaftaran', 'kelompok-umur', 'nomor-lomba', 'pembayaran'],
            array_column($kelengkapan->butir(), 'kunci'),
        );
        $this->assertCount(5, $kelengkapan->belumLengkap());

        $this->assertSame(
            'Belum diisi: nama acara, seri, tahun, tempat, tanggal mulai acara, tanggal selesai acara.',
            $kelengkapan->untukHalaman('acara')['keterangan'],
        );
        $this->assertSame(
            'Belum diisi: tanggal mulai pendaftaran, tanggal akhir pendaftaran.',
            $kelengkapan->untukHalaman('pendaftaran')['keterangan'],
        );
        $this->assertSame(
            'Belum ada kelompok umur. Tambahkan minimal satu kelompok umur.',
            $kelengkapan->untukHalaman('kelompok-umur')['keterangan'],
        );
        $this->assertSame(
            'Belum ada nomor lomba aktif, karena belum ada kelompok umur yang aktif.',
            $kelengkapan->untukHalaman('nomor-lomba')['keterangan'],
        );
        $this->assertSame(
            'Belum diisi: nama bank, nomor rekening, nama pemilik rekening, nomor WA pembayaran.',
            $kelengkapan->untukHalaman('pembayaran')['keterangan'],
        );
    }

    public function testTeksKosongSamaDenganNull(): void
    {
        // Pengaturan kosong disimpan sebagai '' (F2-05) atau null; keduanya belum diisi.
        $kelengkapan = new KelengkapanPengaturan([
            'acara'       => ['nama' => '', 'seri' => '', 'tahun' => null, 'tempat' => '', 'tanggal_mulai' => '', 'tanggal_selesai' => null],
            'pendaftaran' => ['mulai' => '', 'akhir' => null],
            'pembayaran'  => ['bank' => '', 'nomor_rekening' => null, 'pemilik_rekening' => '', 'nomor_wa' => ''],
        ]);

        $this->assertSame(0, $kelengkapan->jumlahLengkap());
        $this->assertSame(
            'Belum diisi: nama acara, seri, tahun, tempat, tanggal mulai acara, tanggal selesai acara.',
            $kelengkapan->untukHalaman('acara')['keterangan'],
        );
        $this->assertSame(
            'Belum diisi: nama bank, nomor rekening, nama pemilik rekening, nomor WA pembayaran.',
            $kelengkapan->untukHalaman('pembayaran')['keterangan'],
        );
    }

    public function testPotretLengkapSemuaButirLengkap(): void
    {
        $kelengkapan = new KelengkapanPengaturan(self::potretLengkap());

        $this->assertTrue($kelengkapan->lengkap());
        $this->assertSame(5, $kelengkapan->jumlahLengkap());
        $this->assertSame([], $kelengkapan->belumLengkap());
        $this->assertSame('2 kelompok umur aktif sudah lengkap.', $kelengkapan->untukHalaman('kelompok-umur')['keterangan']);
        $this->assertSame('3 nomor lomba aktif; nomor beregu sudah lengkap.', $kelengkapan->untukHalaman('nomor-lomba')['keterangan']);
    }

    public function testSetiapButirPunyaLabelDanAlamatHalaman(): void
    {
        $alamat = [];

        foreach ((new KelengkapanPengaturan([]))->butir() as $butir) {
            $this->assertSame(['kunci', 'label', 'lengkap', 'keterangan', 'alamat'], array_keys($butir));
            $this->assertNotSame('', $butir['label']);
            $alamat[$butir['kunci']] = $butir['alamat'];
        }

        $this->assertSame([
            'acara'         => 'admin/pengaturan/acara',
            'pendaftaran'   => 'admin/pengaturan/pendaftaran',
            'kelompok-umur' => 'admin/pengaturan/kelompok-umur',
            'nomor-lomba'   => 'admin/pengaturan/nomor-lomba',
            'pembayaran'    => 'admin/pengaturan/pembayaran',
        ], $alamat);
    }

    public function testUntukHalamanTanpaButir(): void
    {
        $kelengkapan = new KelengkapanPengaturan([]);

        $this->assertNull($kelengkapan->untukHalaman('jadwal'));
        $this->assertNull($kelengkapan->untukHalaman('akun'));
    }

    // ---------- Acara, pendaftaran, pembayaran ----------

    public function testAcaraMenyebutYangKurang(): void
    {
        $potret                           = self::potretLengkap();
        $potret['acara']['tempat']        = '   ';
        $potret['acara']['seri']          = 0;
        $potret['acara']['tanggal_selesai'] = '2026-13-40';

        $butir = (new KelengkapanPengaturan($potret))->untukHalaman('acara');

        $this->assertFalse($butir['lengkap']);
        $this->assertSame('Belum diisi: seri, tempat, tanggal selesai acara.', $butir['keterangan']);
    }

    public function testAcaraMenerimaAngkaBerupaTeks(): void
    {
        $potret                   = self::potretLengkap();
        $potret['acara']['seri']  = '2';
        $potret['acara']['tahun'] = '2026';

        $this->assertTrue((new KelengkapanPengaturan($potret))->untukHalaman('acara')['lengkap']);
    }

    public function testPendaftaranButuhMulaiDanAkhir(): void
    {
        $potret                         = self::potretLengkap();
        $potret['pendaftaran']['akhir'] = null;

        $butir = (new KelengkapanPengaturan($potret))->untukHalaman('pendaftaran');

        $this->assertFalse($butir['lengkap']);
        $this->assertSame('Belum diisi: tanggal akhir pendaftaran.', $butir['keterangan']);
    }

    public function testTanggalPatokanUsiaBukanButir(): void
    {
        // K37: tanggal patokan usia dihapus dari kelengkapan.
        $potret = self::potretLengkap();
        unset($potret['pendaftaran']['tanggal_patokan_usia']);

        $this->assertTrue((new KelengkapanPengaturan($potret))->lengkap());
    }

    public function testPembayaranMenyebutYangKurang(): void
    {
        $potret                           = self::potretLengkap();
        $potret['pembayaran']['nomor_wa'] = '';
        $potret['pembayaran']['bank']     = '';

        $butir = (new KelengkapanPengaturan($potret))->untukHalaman('pembayaran');

        $this->assertFalse($butir['lengkap']);
        $this->assertSame('Belum diisi: nama bank, nomor WA pembayaran.', $butir['keterangan']);
        $this->assertFalse((new KelengkapanPengaturan($potret))->lengkap());
    }

    // ---------- Kelompok umur ----------

    public function testKelompokUmurSemuaMatiBelumLengkap(): void
    {
        $potret = self::potretLengkap();

        foreach ($potret['kelompok_umur'] as $i => $ku) {
            $potret['kelompok_umur'][$i]['aktif'] = '0';
        }

        $kelengkapan = new KelengkapanPengaturan($potret);

        $this->assertSame(
            'Belum ada kelompok umur yang aktif. Aktifkan minimal satu kelompok umur.',
            $kelengkapan->untukHalaman('kelompok-umur')['keterangan'],
        );
        $this->assertFalse($kelengkapan->untukHalaman('nomor-lomba')['lengkap']);
    }

    public function testKelompokUmurMenyebutNamaYangKurang(): void
    {
        $potret                                         = self::potretLengkap();
        $potret['kelompok_umur'][0]['jenjang']           = [];
        $potret['kelompok_umur'][1]['jenjang']           = ['X'];
        $potret['kelompok_umur'][1]['biaya_individu']    = null;
        $potret['kelompok_umur'][0]['awalan']            = [];
        $potret['kelompok_umur'][1]['tahun_lahir_awal']  = null;
        $potret['kelompok_umur'][1]['tahun_lahir_akhir'] = null;

        $butir = (new KelengkapanPengaturan($potret))->untukHalaman('kelompok-umur');

        $this->assertFalse($butir['lengkap']);
        $this->assertSame(
            'KU 12 belum punya batas tahun lahir. KU 10, KU 12 belum punya jenjang sekolah. '
            . 'KU 12 belum punya biaya individu. KU 10 belum punya awalan nomor dada.',
            $butir['keterangan'],
        );
    }

    public function testBatasTahunLahirCukupSalahSatu(): void
    {
        $potret                                         = self::potretLengkap();
        $potret['kelompok_umur'][0]['tahun_lahir_awal']  = null;
        $potret['kelompok_umur'][1]['tahun_lahir_akhir'] = '';

        $this->assertTrue((new KelengkapanPengaturan($potret))->untukHalaman('kelompok-umur')['lengkap']);
    }

    public function testBiayaNolTetapTerisi(): void
    {
        $potret                                      = self::potretLengkap();
        $potret['kelompok_umur'][0]['biaya_individu'] = '0';
        $potret['nomor_lomba'][1]['biaya_beregu']     = 0;

        $this->assertTrue((new KelengkapanPengaturan($potret))->lengkap());
    }

    public function testKelompokUmurMatiTidakDinilai(): void
    {
        $potret                    = self::potretLengkap();
        $potret['kelompok_umur'][] = [
            'id' => 9, 'nama' => 'KU Lama', 'aktif' => 0, 'tahun_lahir_awal' => null, 'tahun_lahir_akhir' => null,
            'biaya_individu' => null, 'jenjang' => [], 'awalan' => [],
        ];
        $potret['nomor_lomba'][] = self::nomor(9, 'Estafet Lama', 'beregu', 'mix', ['batas_anggota' => null, 'jumlah_putra' => null]);

        $this->assertTrue((new KelengkapanPengaturan($potret))->lengkap());
    }

    public function testDaftarNamaPanjangDipendekkan(): void
    {
        $potret = self::potretLengkap();
        $potret['kelompok_umur'] = [];

        foreach (range(1, 7) as $i) {
            $potret['kelompok_umur'][] = [
                'id' => $i, 'nama' => 'KU ' . $i, 'aktif' => 1, 'tahun_lahir_awal' => 2000 + $i, 'tahun_lahir_akhir' => 2000 + $i,
                'biaya_individu' => 1000, 'jenjang' => [], 'awalan' => [(string) $i],
            ];
        }

        $this->assertSame(
            'KU 1, KU 2, KU 3, dan 4 kelompok umur lainnya belum punya jenjang sekolah.',
            (new KelengkapanPengaturan($potret))->untukHalaman('kelompok-umur')['keterangan'],
        );
    }

    // ---------- Nomor lomba ----------

    public function testNomorLombaHarusAdaYangAktifPadaKelompokUmurAktif(): void
    {
        $potret = self::potretLengkap();

        foreach ($potret['nomor_lomba'] as $i => $nomor) {
            $potret['nomor_lomba'][$i]['aktif'] = 0;
        }

        $butir = (new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba');

        $this->assertFalse($butir['lengkap']);
        $this->assertSame(
            'Belum ada nomor lomba aktif pada kelompok umur yang aktif. Tambahkan atau aktifkan minimal satu nomor lomba.',
            $butir['keterangan'],
        );

        // Nomor aktif pada kelompok umur yang mati tidak dihitung.
        $potret                                = self::potretLengkap();
        $potret['kelompok_umur'][0]['aktif']   = 0;
        $potret['nomor_lomba']                 = [self::nomor(1, 'Lari 60 m', 'individu', 'putra')];

        $this->assertFalse((new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba')['lengkap']);
    }

    public function testNomorIndividuSajaSudahCukup(): void
    {
        $potret                = self::potretLengkap();
        $potret['nomor_lomba'] = [self::nomor(1, 'Lari 60 m', 'individu', 'putri')];

        $this->assertTrue((new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba')['lengkap']);
    }

    public function testNomorBereguMenyebutYangKurang(): void
    {
        $potret                = self::potretLengkap();
        $potret['nomor_lomba'] = [
            self::nomor(1, 'Lari 60 m', 'individu', 'putra'),
            self::nomor(1, 'Formula 1', 'beregu', 'putra', ['batas_anggota' => null]),
            self::nomor(1, 'Formula 1', 'beregu', 'putri', ['batas_anggota' => null, 'biaya_beregu' => null]),
            self::nomor(2, 'Estafet 8 × 50 m', 'beregu', 'mix', ['jumlah_inti' => null, 'jumlah_putri' => null]),
            // Nomor mati tidak dinilai.
            self::nomor(2, 'Estafet Lama', 'beregu', 'putra', ['aktif' => 0, 'batas_anggota' => null]),
        ];

        $butir = (new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba');

        $this->assertFalse($butir['lengkap']);
        $this->assertSame(
            'Estafet 8 × 50 m mix (KU 12) belum punya jumlah inti. '
            . 'Formula 1 putra (KU 10), Formula 1 putri (KU 10) belum punya batas anggota. '
            . 'Formula 1 putri (KU 10) belum punya biaya beregu. '
            . 'Estafet 8 × 50 m mix (KU 12) belum punya jumlah putra dan putri minimal.',
            $butir['keterangan'],
        );
    }

    public function testKosongNomorBereguSamaDenganAturanF204(): void
    {
        // Seperti `NomorLombaModel::kekuranganTim()`: hanya null dan '' yang kosong.
        $potret                = self::potretLengkap();
        $potret['nomor_lomba'] = [
            self::nomor(1, 'Formula 1', 'beregu', 'putra', ['jumlah_inti' => 0, 'batas_anggota' => '0', 'biaya_beregu' => 0]),
        ];

        $this->assertTrue((new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba')['lengkap']);

        $potret['nomor_lomba'] = [
            self::nomor(2, 'Estafet 8 × 50 m', 'beregu', 'mix', ['batas_anggota' => '', 'jumlah_putra' => '']),
        ];

        $this->assertSame(
            'Estafet 8 × 50 m mix (KU 12) belum punya batas anggota. Estafet 8 × 50 m mix (KU 12) belum punya jumlah putra dan putri minimal.',
            (new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba')['keterangan'],
        );
    }

    public function testJumlahPutraPutriHanyaUntukMix(): void
    {
        $potret                = self::potretLengkap();
        $potret['nomor_lomba'] = [self::nomor(1, 'Formula 1', 'beregu', 'putra', ['jumlah_putra' => null, 'jumlah_putri' => null])];

        $this->assertTrue((new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba')['lengkap']);
    }

    public function testDaftarNomorPanjangDipendekkan(): void
    {
        $potret                = self::potretLengkap();
        $potret['nomor_lomba'] = [];

        foreach (range(1, 8) as $i) {
            $potret['nomor_lomba'][] = self::nomor(1, 'Estafet ' . $i, 'beregu', 'putra', ['batas_anggota' => null]);
        }

        $this->assertSame(
            'Estafet 1 putra (KU 10), Estafet 2 putra (KU 10), Estafet 3 putra (KU 10), dan 5 nomor lainnya belum punya batas anggota.',
            (new KelengkapanPengaturan($potret))->untukHalaman('nomor-lomba')['keterangan'],
        );
    }

    // ---------- Bantuan ----------

    /**
     * @return array<string, mixed>
     */
    private static function potretLengkap(): array
    {
        return [
            'acara' => [
                'nama'            => 'Lomba Lari Karangan',
                'seri'            => 2,
                'tahun'           => 2026,
                'tempat'          => 'Lapangan Karangan',
                'tanggal_mulai'   => '2026-12-09',
                'tanggal_selesai' => '2026-12-12',
            ],
            'pendaftaran' => ['mulai' => '2026-10-01 00:00:00', 'akhir' => '2026-11-30 23:59:00'],
            'pembayaran'  => [
                'bank'             => 'Bank Karangan',
                'nomor_rekening'   => '1234567890',
                'pemilik_rekening' => 'Panitia Karangan',
                'nomor_wa'         => '6281234567890',
            ],
            'kelompok_umur' => [
                [
                    'id' => 1, 'nama' => 'KU 10', 'aktif' => '1', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018',
                    'biaya_individu' => '75000', 'jenjang' => ['A'], 'awalan' => ['2'],
                ],
                [
                    'id' => 2, 'nama' => 'KU 12', 'aktif' => 1, 'tahun_lahir_awal' => 2015, 'tahun_lahir_akhir' => 2015,
                    'biaya_individu' => 75000, 'jenjang' => ['A', 'B'], 'awalan' => ['3', '6'],
                ],
            ],
            'nomor_lomba' => [
                self::nomor(1, 'Lari 60 m', 'individu', 'putra'),
                self::nomor(1, 'Formula 1', 'beregu', 'putra'),
                self::nomor(2, 'Estafet 8 × 50 m', 'beregu', 'mix'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $ubah
     *
     * @return array<string, mixed>
     */
    private static function nomor(int $kelompokUmurId, string $nama, string $jenis, string $kategori, array $ubah = []): array
    {
        $beregu = $jenis === 'beregu';
        $mix    = $kategori === 'mix';

        return $ubah + [
            'id'               => random_int(1, 100000),
            'kelompok_umur_id' => (string) $kelompokUmurId,
            'nama'             => $nama,
            'jenis'            => $jenis,
            'kategori'         => $kategori,
            'aktif'            => '1',
            'jumlah_inti'      => $beregu ? '4' : null,
            'batas_anggota'    => $beregu ? '6' : null,
            'jumlah_putra'     => $mix ? '4' : null,
            'jumlah_putri'     => $mix ? '4' : null,
            'biaya_beregu'     => $beregu ? '100000' : null,
        ];
    }
}
