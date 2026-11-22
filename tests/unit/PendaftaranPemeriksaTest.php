<?php

use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\PendaftaranContoh;

/**
 * Pemeriksaan seluruh kiriman wizard (F4-01): rancangan bagian 6 dan
 * K23, K28, K36, K50, K51, tanpa basis data.
 *
 * @internal
 */
final class PendaftaranPemeriksaTest extends CIUnitTestCase
{
    use PendaftaranContoh;

    private const HARI_INI = '2026-11-12';

    private const ADA_KOSONG = ['referensi' => null, 'nisn_terdaftar' => [], 'tim' => [], 'individu' => []];

    /**
     * @param array<string, mixed> $kiriman
     * @param array<string, mixed> $ada
     * @param array<string, mixed> $induk
     *
     * @return array<string, mixed>
     */
    private static function periksa(array $kiriman, array $ada = [], array $induk = [], bool $periksaFoto = true): array
    {
        return (new PemeriksaPendaftaran(array_replace_recursive(self::indukContoh(), $induk), $ada + self::ADA_KOSONG, self::HARI_INI))
            ->periksa($kiriman, $periksaFoto);
    }

    /**
     * @param array<string, mixed> $hasil
     *
     * @return array<string, string> jalur => pesan (pesan pertama tiap jalur)
     */
    private static function galat(array $hasil): array
    {
        $peta = [];

        foreach ($hasil['galat'] as $g) {
            $peta[$g['jalur']] ??= $g['pesan'];
        }

        return $peta;
    }

    /**
     * @param array<string, mixed> $hasil
     */
    private function assertGalat(array $hasil, string $jalur, string $potongan = ''): void
    {
        $galat = self::galat($hasil);

        $this->assertArrayHasKey($jalur, $galat, 'Galat yang ada: ' . json_encode($galat, JSON_UNESCAPED_UNICODE));
        $this->assertNull($hasil['data']);
        $this->assertNull($hasil['rincian']);

        if ($potongan !== '') {
            $this->assertStringContainsString($potongan, $galat[$jalur]);
        }
    }

    // Kiriman sah

    public function testKirimanSahMenghasilkanDataDanRincian(): void
    {
        $hasil = self::periksa(self::kirimanContoh());

        $this->assertSame([], $hasil['galat']);

        $data = $hasil['data'];
        $this->assertSame(['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => null, 'nama_sekolah' => 'SD Karangan Maju', 'nama_usulan' => null, 'nama_resmi' => null], $data['sekolah']);
        $this->assertSame('6281200000001', $data['penanggung_jawab'][0]['nomor_wa']);
        $this->assertStringStartsWith("\xFF\xD8", $data['penanggung_jawab'][0]['foto']);
        $this->assertSame('BUDI KARANGAN 01', $data['atlet'][0]['nama']);
        $this->assertSame(2, $data['atlet'][0]['kelompok_umur_id']);
        $this->assertSame([['nomor_lomba_id' => 10, 'biaya' => 150000], ['nomor_lomba_id' => 12, 'biaya' => 150000]], $data['atlet'][0]['nomor']);
        $this->assertSame([], $data['beregu']);
        $this->assertSame(450000, $data['subtotal_individu']);
        $this->assertSame(0, $data['subtotal_beregu']);
        $this->assertSame(450000, $data['total']);

        $rincian = $hasil['rincian'];
        $this->assertSame('SD/MI', $rincian['sekolah']['jenjang_label']);
        $this->assertCount(3, $rincian['individu']);
        $this->assertSame(['nama' => 'BUDI KARANGAN 01', 'nisn' => '9900000001', 'kelompok_umur' => 'KU 10', 'nomor' => 'Lari 60 m putra', 'biaya' => 150000], $rincian['individu'][0]);
        $this->assertSame(450000, $rincian['total']);
    }

    public function testKirimanKosongMenyebutSemuaLangkah(): void
    {
        $hasil = self::periksa([]);
        $galat = self::galat($hasil);

        $this->assertArrayHasKey('sekolah.nama', $galat);
        $this->assertArrayHasKey('sekolah.jenjang', $galat);
        $this->assertArrayHasKey('sekolah.kabupaten', $galat);
        $this->assertArrayHasKey('penanggung_jawab', $galat);
        $this->assertArrayHasKey('atlet', $galat);
        $this->assertSame([1, 2, 3], array_values(array_unique(array_column($hasil['galat'], 'langkah'))));
    }

    // Sekolah

    public function testNpsnReferensiMenentukanNamaKabupatenDanJenjang(): void
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'B', 'kabupaten' => 'Blora', 'npsn' => ' 99999911 ', 'nama' => 'nama ketikan'];

        $hasil = self::periksa($kiriman, ['referensi' => ['npsn' => '99999911', 'nama' => 'SD KARANGAN  MAJU', 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kab. Kudus']]);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama_sekolah' => 'SD KARANGAN MAJU', 'nama_usulan' => null, 'nama_resmi' => null], $hasil['data']['sekolah']);
    }

    public function testUsulanNamaDipakaiSementaraUntukSekolahReferensi(): void
    {
        $referensi          = ['npsn' => '99999911', 'nama' => 'MIS KARANGAN', 'bentuk' => 'MI', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'];
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => '', 'nama_usulan' => '  MIS KARANGAN   REKAAN '];

        $hasil = self::periksa($kiriman, ['referensi' => $referensi]);

        // K99: pendaftaran memakai nama usulan; nama resmi ikut dikirim untuk usulan ke admin.
        $this->assertSame([], $hasil['galat']);
        $this->assertSame('MIS KARANGAN REKAAN', $hasil['data']['sekolah']['nama_sekolah']);
        $this->assertSame('MIS KARANGAN REKAAN', $hasil['data']['sekolah']['nama_usulan']);
        $this->assertSame('MIS KARANGAN', $hasil['data']['sekolah']['nama_resmi']);
        $this->assertSame('MIS KARANGAN', $hasil['rincian']['sekolah']['nama_resmi']);

        // Usulan yang sama dengan nama resmi (huruf besar-kecil diabaikan) bukan usulan.
        $kiriman['sekolah']['nama_usulan'] = 'mis karangan';
        $hasil                             = self::periksa($kiriman, ['referensi' => $referensi]);
        $this->assertNull($hasil['data']['sekolah']['nama_usulan']);
        $this->assertSame('MIS KARANGAN', $hasil['data']['sekolah']['nama_sekolah']);
    }

    public function testUsulanNamaDiperiksa(): void
    {
        $referensi          = ['npsn' => '99999911', 'nama' => 'MIS KARANGAN', 'bentuk' => 'MI', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'];
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => '', 'nama_usulan' => str_repeat('A', 151)];

        $this->assertGalat(self::periksa($kiriman, ['referensi' => $referensi]), 'sekolah.nama_usulan', 'terlalu panjang');

        $kiriman['sekolah']['nama_usulan'] = 'SLB KARANGAN';
        $this->assertGalat(self::periksa($kiriman, ['referensi' => $referensi]), 'sekolah.nama_usulan');

        // Tanpa sekolah referensi, nama ditulis sendiri; usulan diabaikan.
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '', 'nama' => 'SD Karangan', 'nama_usulan' => 'SD Lain'];
        $hasil              = self::periksa($kiriman);
        $this->assertSame('SD Karangan', $hasil['data']['sekolah']['nama_sekolah']);
        $this->assertNull($hasil['data']['sekolah']['nama_usulan']);
    }

    public function testSlbDitolak(): void
    {
        $kiriman                    = self::kirimanContoh();
        $kiriman['sekolah']['npsn'] = '99999912';

        $hasil = self::periksa($kiriman, ['referensi' => ['npsn' => '99999912', 'nama' => 'SLB KARANGAN', 'bentuk' => 'SLB', 'jenjang' => 'SMA', 'kabupaten' => 'Pati']]);

        $this->assertGalat($hasil, 'sekolah.npsn', 'SLB KARANGAN adalah Sekolah Luar Biasa (SLB)');
    }

    public function testNamaManualSlbDitolak(): void
    {
        $kiriman                    = self::kirimanContoh();
        $kiriman['sekolah']['nama'] = 'Sekolah Luar Biasa Karangan';

        $this->assertGalat(self::periksa($kiriman), 'sekolah.nama', 'SLB tidak termasuk peserta');

        $kiriman['sekolah']['nama'] = 'slb negeri karangan';
        $this->assertGalat(self::periksa($kiriman), 'sekolah.nama');
    }

    public function testNpsnTidakSah(): void
    {
        $kiriman                    = self::kirimanContoh();
        $kiriman['sekolah']['npsn'] = '1234567';

        $this->assertGalat(self::periksa($kiriman), 'sekolah.npsn', 'NPSN harus 8 angka');
    }

    public function testNpsnTidakDikenalWajibNamaManual(): void
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Pati', 'npsn' => '88888888', 'nama' => ''];

        $this->assertGalat(self::periksa($kiriman), 'sekolah.nama', 'NPSN ini belum ada di data sekolah');

        $kiriman['sekolah']['nama'] = 'MI Karangan';
        $hasil                      = self::periksa($kiriman);
        $this->assertSame([], $hasil['galat']);
        $this->assertSame('88888888', $hasil['data']['sekolah']['npsn']);
    }

    public function testKabupatenDiLuarLimaDitolak(): void
    {
        $kiriman                         = self::kirimanContoh();
        $kiriman['sekolah']['kabupaten'] = 'Demak';

        $this->assertGalat(self::periksa($kiriman), 'sekolah.kabupaten', 'Blora, Rembang, Pati, Kudus, Jepara');
    }

    // Penanggung jawab

    public function testJumlahPenanggungJawab(): void
    {
        $kiriman                     = self::kirimanContoh();
        $kiriman['penanggung_jawab'] = array_fill(0, 3, $kiriman['penanggung_jawab'][0]);

        $this->assertGalat(self::periksa($kiriman, [], ['aturan' => ['penanggung_jawab_maksimal' => 2]]), 'penanggung_jawab', 'paling banyak 2 orang');
        $this->assertGalat(self::periksa($kiriman, [], ['aturan' => ['penanggung_jawab_minimal' => 4, 'penanggung_jawab_maksimal' => 5]]), 'penanggung_jawab', 'minimal 4');
        $this->assertSame([], self::periksa($kiriman)['galat']);
    }

    public function testIsianPenanggungJawab(): void
    {
        $kiriman                     = self::kirimanContoh();
        $kiriman['penanggung_jawab'] = [['nama' => ' ', 'nomor_wa' => '021-555-1234', 'foto' => '']];

        $hasil = self::periksa($kiriman);

        $this->assertGalat($hasil, 'penanggung_jawab.0.nama', 'Ofisial 1: nama belum diisi');
        $this->assertGalat($hasil, 'penanggung_jawab.0.nomor_wa', 'nomor WA belum benar');
        $this->assertGalat($hasil, 'penanggung_jawab.0.foto', 'foto belum diisi');
    }

    public function testFotoWajibDanHarusJpegSah(): void
    {
        $kiriman = self::kirimanContoh();

        unset($kiriman['penanggung_jawab'][0]['foto']);
        $this->assertGalat(self::periksa($kiriman), 'penanggung_jawab.0.foto', 'foto belum diisi');

        $kiriman['penanggung_jawab'][0]['foto'] = 'data:image/jpeg;base64,' . base64_encode('bukan gambar');
        $this->assertGalat(self::periksa($kiriman), 'penanggung_jawab.0.foto', 'Ambil ulang fotonya');

        // Tanpa pemeriksaan isi, foto cukup ada.
        $kiriman['penanggung_jawab'][0]['foto'] = 'ada';
        $this->assertSame([], self::periksa($kiriman, [], [], false)['galat']);
    }

    // Atlet

    public function testIsianAtletTidakSah(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [['nama' => '', 'nisn' => '12345', 'tanggal_lahir' => '2017-02-30', 'jenis_kelamin' => 'X', 'nomor' => []]];

        $hasil = self::periksa($kiriman);

        $this->assertGalat($hasil, 'atlet.0.nama', 'nama belum diisi');
        $this->assertGalat($hasil, 'atlet.0.nisn', 'tepat 10 angka');
        $this->assertGalat($hasil, 'atlet.0.tanggal_lahir', 'tanggal lahir belum benar');
        $this->assertGalat($hasil, 'atlet.0.jenis_kelamin', 'jenis kelamin');
        $this->assertGalat($hasil, 'atlet.0.nomor', 'Pilih minimal satu nomor');
    }

    public function testTanggalLahirDiMasaDepanDitolak(): void
    {
        $kiriman                            = self::kirimanContoh();
        $kiriman['atlet'][0]['tanggal_lahir'] = '2026-11-13';

        $this->assertGalat(self::periksa($kiriman), 'atlet.0.tanggal_lahir', 'Periksa lagi tahunnya');
    }

    public function testNisnKembarDiKirimanDitolak(): void
    {
        $kiriman                      = self::kirimanContoh();
        $kiriman['atlet'][1]['nisn'] = '9900000001';

        $this->assertGalat(self::periksa($kiriman), 'atlet.1.nisn', 'sudah dipakai BUDI KARANGAN 01 di pendaftaran ini');
    }

    public function testNisnTerdaftarDitolak(): void
    {
        $hasil = self::periksa(self::kirimanContoh(), ['nisn_terdaftar' => ['9900000002']]);

        $this->assertGalat($hasil, 'atlet.1.nisn', 'NISN 9900000002 sudah terdaftar');
        $this->assertSame(3, $hasil['galat'][0]['langkah']);
    }

    public function testKelompokUmurDibatasiJenjang(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [self::atletContoh('9900000003', [40], 'P', '2012-01-01')];

        $this->assertGalat(self::periksa($kiriman), 'atlet.0.tanggal_lahir', 'masuk KU 15, yang hanya untuk SMP/MTs');
    }

    public function testJenjangMenentukanKelompokUmurSaatRentangBeririsan(): void
    {
        // K95: KU 12 (SD) dan KU 15 (SMP) sama-sama menerima kelahiran 2012.
        $induk                     = self::indukContoh();
        $induk['kelompok_umur'][1] = ['id' => 3, 'nama' => 'KU 12', 'tahun_lahir_awal' => 2012, 'tahun_lahir_akhir' => 2015, 'biaya_individu' => 175000, 'jenjang' => ['A']];
        $periksa                   = static fn (array $kiriman): array => (new PemeriksaPendaftaran($induk, self::ADA_KOSONG, self::HARI_INI))->periksa($kiriman, true);

        // Dari SD: KU 12.
        $sd          = self::kirimanContoh();
        $sd['atlet'] = [self::atletContoh('9900000003', [30], 'L', '2012-05-01')];
        $this->assertSame([], $periksa($sd)['galat']);

        // Dari SMP: KU 15, karena KU 12 tidak dibuka untuk SMP.
        $smp            = self::kirimanContoh();
        $smp['sekolah'] = ['jenjang' => 'B', 'kabupaten' => 'Kudus', 'npsn' => '', 'nama' => 'SMP Karangan Maju'];
        $smp['atlet']   = [self::atletContoh('9900000004', [40], 'P', '2012-05-01')];
        $this->assertSame([], $periksa($smp)['galat']);

        $smp['atlet'] = [self::atletContoh('9900000004', [30], 'L', '2012-05-01')];
        $galat        = self::galat($periksa($smp));
        $this->assertStringContainsString('Lari 60 m putra untuk KU 12, sedangkan atlet ini KU 15', $galat['atlet.0.nomor'] ?? '');
    }

    public function testPendaftarMemilihKelompokUmurSaatRentangBeririsanDalamSatuJenjang(): void
    {
        // K134: KU 10 lahir 2016–2018 dan KU 12 lahir 2014–2016, keduanya SD/MI.
        $induk                     = self::indukContoh();
        $induk['kelompok_umur'][1] = ['id' => 3, 'nama' => 'KU 12', 'tahun_lahir_awal' => 2014, 'tahun_lahir_akhir' => 2016, 'biaya_individu' => 175000, 'jenjang' => ['A']];
        $periksa                   = static fn (array $kiriman): array => (new PemeriksaPendaftaran($induk, self::ADA_KOSONG, self::HARI_INI))->periksa($kiriman, true);

        // Tanpa pilihan: galat di kotak pilihan kelompok umur.
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [self::atletContoh('9900000003', [30], 'L', '2016-05-01')];
        $galat            = self::galat($periksa($kiriman));
        $this->assertStringContainsString('boleh ikut KU 10 atau KU 12. Pilih salah satu', $galat['atlet.0.kelompok_umur_id'] ?? '');

        // Memilih KU 12: nomor KU 12 diterima, biaya KU 12.
        $kiriman['atlet'][0]['kelompok_umur_id'] = 3;
        $hasil                                   = $periksa($kiriman);
        $this->assertSame([], $hasil['galat']);
        $this->assertSame(3, $hasil['data']['atlet'][0]['kelompok_umur_id']);
        $this->assertSame('KU 12', $hasil['rincian']['individu'][0]['kelompok_umur']);
        $this->assertSame(175000, $hasil['rincian']['individu'][0]['biaya']);

        // Memilih KU 10 (pilihan dari formulir berupa teks): nomor KU 12 ditolak.
        $kiriman['atlet'][0]['kelompok_umur_id'] = '2';
        $galat                                   = self::galat($periksa($kiriman));
        $this->assertStringContainsString('untuk KU 12, sedangkan atlet ini KU 10', $galat['atlet.0.nomor'] ?? '');

        $kiriman['atlet'][0]['nomor'] = [10];
        $this->assertSame([], $periksa($kiriman)['galat']);

        // Di luar irisan, pilihan diabaikan.
        $kiriman['atlet'] = [self::atletContoh('9900000004', [10], 'L', '2017-05-01') + ['kelompok_umur_id' => 3]];
        $this->assertSame(2, $periksa($kiriman)['data']['atlet'][0]['kelompok_umur_id']);
    }

    public function testNomorHarusMilikKelompokUmurAtlet(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [self::atletContoh('9900000003', [30])];

        $this->assertGalat(self::periksa($kiriman), 'atlet.0.nomor', 'Lari 60 m putra untuk KU 12, sedangkan atlet ini KU 10');
    }

    public function testNomorHarusSesuaiJenisKelamin(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [self::atletContoh('9900000003', [10], 'P')];

        $this->assertGalat(self::periksa($kiriman), 'atlet.0.nomor', 'hanya untuk atlet laki-laki');
    }

    public function testNomorTidakAktifAtauTidakDikenal(): void
    {
        $kiriman                       = self::kirimanContoh();
        $kiriman['atlet'][0]['nomor'] = [999];
        $this->assertGalat(self::periksa($kiriman), 'atlet.0.nomor', 'sudah tidak dibuka');

        $kiriman['atlet'][0]['nomor'] = ['abc'];
        $this->assertGalat(self::periksa($kiriman), 'atlet.0.nomor', 'tidak dikenal');
    }

    public function testNomorIndividuPalingBanyak(): void
    {
        $kiriman                       = self::kirimanContoh();
        $kiriman['atlet'][0]['nomor'] = [10, 12, 13];

        $this->assertGalat(self::periksa($kiriman), 'atlet.0.nomor', 'ikut 3 nomor individu. Batasnya 2 nomor');
        $this->assertSame([], self::periksa($kiriman, [], ['aturan' => ['nomor_individu_maksimal' => 3, 'atlet_per_nomor_maksimal' => null]])['galat']);
    }

    // Regu (K105)

    /**
     * Atlet Kanga's Escape di satu regu: `$inti` atlet pertama inti (bawaan:
     * sampai 4), sisanya cadangan.
     *
     * @return list<array<string, mixed>>
     */
    private static function timKanga(int $jumlah, int $mulai = 10, string $regu = 'A', ?int $inti = null): array
    {
        $inti ??= min($jumlah, 4);
        $atlet  = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $atlet[] = self::atletContoh('99000000' . ($mulai + $i), [20]) + [
                'regu' => [20 => ['regu' => $regu, 'peran' => $i < $inti ? 'inti' : 'cadangan']],
            ];
        }

        return $atlet;
    }

    /**
     * Regu lama dari pendaftaran sebelumnya: `$inti` inti lalu cadangan.
     *
     * @return list<array{id: int, nama: string, jenis_kelamin: string, peran: string}>
     */
    private static function reguLama(int $jumlah, int $inti = 4, int $idAwal = 100): array
    {
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $hasil[] = ['id' => $idAwal + $i, 'nama' => 'LAMA ' . $i, 'jenis_kelamin' => 'L', 'peran' => $i < $inti ? 'inti' : 'cadangan'];
        }

        return $hasil;
    }

    public function testTimBaruLengkapDibayarPerTim(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(5);

        $hasil = self::periksa($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'regu' => 'A', 'biaya_tim' => 100000]], $hasil['data']['beregu']);
        $this->assertSame([['nomor_lomba_id' => 20, 'biaya' => null, 'regu' => 'A', 'peran' => 'inti']], $hasil['data']['atlet'][0]['nomor']);
        $this->assertSame('cadangan', $hasil['data']['atlet'][4]['nomor'][0]['peran']);
        $this->assertSame(100000, $hasil['data']['subtotal_beregu']);
        $this->assertSame(0, $hasil['data']['subtotal_individu']);
        $this->assertSame([], $hasil['data']['turun_cadangan']);
        $this->assertSame(
            [['nomor' => "Kanga's Escape putra", 'nomor_lomba_id' => 20, 'regu' => 'A', 'regu_tunggal' => true, 'kelompok_umur' => 'KU 10', 'biaya' => 100000, 'susulan' => false,
                'anggota' => 5, 'inti' => 4, 'cadangan' => 1, 'anggota_lama' => null, 'turun' => 0]],
            $hasil['rincian']['beregu'],
        );
    }

    public function testDuaReguDibayarPerRegu(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = array_merge(self::timKanga(4, 10, 'B'), self::timKanga(6, 20, 'A'));

        $hasil = self::periksa($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([
            ['nomor_lomba_id' => 20, 'regu' => 'A', 'biaya_tim' => 100000],
            ['nomor_lomba_id' => 20, 'regu' => 'B', 'biaya_tim' => 100000],
        ], $hasil['data']['beregu']);
        $this->assertSame(200000, $hasil['data']['total']);
        $this->assertSame(['A', 'B'], array_column($hasil['rincian']['beregu'], 'regu'));
        $this->assertSame([6, 4], array_column($hasil['rincian']['beregu'], 'anggota'));
        // Dua regu di satu nomor: nama regu tetap ditulis (K108).
        $this->assertSame([false, false], array_column($hasil['rincian']['beregu'], 'regu_tunggal'));
    }

    public function testReguTunggalTanpaNamaRegu(): void
    {
        // Satu-satunya regu sekolah di nomor ini: pesan menulis nama nomor saja (K108).
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(3);

        $hasil = self::periksa($kiriman);
        $this->assertGalat($hasil, 'beregu.20:A', "Kanga's Escape putra baru punya 3 atlet");
        $this->assertStringNotContainsString('Regu A', self::galat($hasil)['beregu.20:A']);

        // Regu lama sekolah ini (NPSN sama) ikut dihitung: regu baru B membuat nama regu ditulis.
        [$susulan, $ada]   = self::susulanKanga();
        $susulan['atlet']  = self::timKanga(3, 10, 'B');
        $this->assertGalat(self::periksa($susulan, $ada), 'beregu.20:B', "Kanga's Escape putra Regu B baru punya 3 atlet");

        // Admin (longgar) tetap melihat nama regu.
        $induk  = self::indukContoh();
        $longgar = new PemeriksaPendaftaran($induk, self::ADA_KOSONG, self::HARI_INI, [], true);
        $kiriman['atlet'] = self::timKanga(9, 10, 'A', 5);
        $galat = self::galat($longgar->periksa($kiriman));
        $this->assertStringContainsString("Kanga's Escape putra Regu A", (string) ($galat['beregu.20:A'] ?? ''));
    }

    public function testSetiapReguDiperiksaSendiri(): void
    {
        // 8 atlet: Regu A lengkap, Regu B baru 3 atlet.
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = array_merge(self::timKanga(5, 10, 'A'), self::timKanga(3, 20, 'B'));

        $hasil = self::periksa($kiriman);

        $this->assertGalat($hasil, 'beregu.20:B', "Kanga's Escape putra Regu B baru punya 3 atlet");
        $this->assertArrayNotHasKey('beregu.20:A', self::galat($hasil));
    }

    public function testPilihanReguDanPeranWajib(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(4);
        unset($kiriman['atlet'][0]['regu']);
        $kiriman['atlet'][1]['regu'][20]['peran'] = '';

        $hasil = self::periksa($kiriman);

        $this->assertGalat($hasil, 'atlet.0.regu', 'belum memilih regu');
        $this->assertGalat($hasil, 'atlet.1.regu', 'belum ditandai Inti atau Cadangan');
    }

    public function testBatasReguPerNomor(): void
    {
        $induk = ['nomor_lomba' => [4 => ['regu_maksimal' => 2]]];

        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(4, 10, 'C');

        $this->assertGalat(self::periksa($kiriman, [], $induk), 'atlet.0.regu', 'tidak punya Regu C. Satu sekolah paling banyak 2 regu di nomor ini. Pilih Regu A atau B.');

        $kiriman['atlet'] = self::timKanga(4, 10, 'b');
        $this->assertSame([], self::periksa($kiriman, [], $induk)['galat'], 'Huruf kecil dirapikan menjadi B.');

        $kiriman['atlet'] = self::timKanga(4, 10, 'AA');
        $this->assertGalat(self::periksa($kiriman, [], $induk), 'atlet.0.regu', 'tidak punya Regu Aa');
    }

    public function testKetentuanAdminMemakaiDaftarNamaRegu(): void
    {
        // K106: daftar dari admin, dipotong batas regu nomor (2).
        $induk = ['aturan' => ['regu_mode' => 'ketentuan', 'regu_label' => ['Putra 1', 'Putra 2', 'Putra 3']], 'nomor_lomba' => [4 => ['regu_maksimal' => 2]]];

        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(4, 10, 'regu putra 2');
        $hasil            = self::periksa($kiriman, [], $induk);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame('Putra 2', $hasil['data']['beregu'][0]['regu']);

        $kiriman['atlet'] = self::timKanga(4, 10, 'Putra 3');
        $this->assertGalat(self::periksa($kiriman, [], $induk), 'atlet.0.regu', 'Pilih Regu Putra 1 atau Putra 2.');

        $kiriman['atlet'] = self::timKanga(4, 10, 'A');
        $this->assertGalat(self::periksa($kiriman, [], $induk), 'atlet.0.regu', 'tidak punya Regu A');
    }

    public function testBebasMenerimaNamaReguOfficial(): void
    {
        $induk = ['aturan' => ['regu_mode' => 'bebas', 'regu_label' => ['A', 'B', 'C']]];

        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = array_merge(self::timKanga(4, 10, 'merah'), self::timKanga(4, 20, 'MERAH PUTIH'));
        $hasil            = self::periksa($kiriman, [], $induk);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(['Merah', 'Merah Putih'], array_column($hasil['data']['beregu'], 'regu'));
        $this->assertSame(200000, $hasil['data']['total']);

        // "merah" dan "Regu Merah" satu regu: 8 atlet, inti 8 > 4.
        $kiriman['atlet'] = array_merge(self::timKanga(4, 10, 'merah'), self::timKanga(4, 20, 'Regu Merah'));
        // Satu-satunya regu di nomor ini, jadi pesannya tanpa nama regu (K108).
        $this->assertGalat(self::periksa($kiriman, [], $induk), 'beregu.20:Merah', "Kanga's Escape putra punya 8 atlet");

        $kiriman['atlet'] = self::timKanga(4, 10, '');
        $this->assertGalat(self::periksa($kiriman, [], $induk), 'atlet.0.regu', 'belum diberi nama regu');

        $kiriman['atlet'] = self::timKanga(4, 10, 'Merah!');
        $this->assertGalat(self::periksa($kiriman, [], $induk), 'atlet.0.regu', 'hanya boleh berisi huruf');
    }

    public function testBebasTetapDibatasiReguPerNomor(): void
    {
        // Batas 2 regu: sekolah sudah punya Regu Merah, kiriman membuat dua regu baru.
        [$kiriman, $ada] = self::susulanKanga('Merah');
        $induk           = ['aturan' => ['regu_mode' => 'bebas'], 'nomor_lomba' => [4 => ['regu_maksimal' => 2]]];
        $kiriman['atlet'] = array_merge(self::timKanga(4, 10, 'Biru'), self::timKanga(4, 20, 'Hijau'));

        $hasil = self::periksa($kiriman, $ada, $induk);
        $this->assertGalat($hasil, 'beregu.20:Biru', 'paling banyak 2 regu di nomor ini, sekarang 3 regu (termasuk Regu Merah yang sudah terdaftar)');
        $this->assertGalat($hasil, 'beregu.20:Hijau');

        // Satu regu baru masih muat.
        $kiriman['atlet'] = self::timKanga(4, 10, 'Biru');
        $this->assertSame([], self::periksa($kiriman, $ada, $induk)['galat']);
    }

    public function testReguLamaTetapBolehWalauBatasDiturunkan(): void
    {
        [$kiriman, $ada] = self::susulanKanga('C');
        $induk           = ['nomor_lomba' => [4 => ['regu_maksimal' => 1]]];

        $this->assertSame([], self::periksa($kiriman, $ada, $induk)['galat']);
    }

    public function testTimBaruBelumLengkapDitolak(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(3);

        $hasil = self::periksa($kiriman);

        $this->assertGalat($hasil, 'beregu.20:A', 'baru punya 3 atlet');
    }

    public function testTimSusulanTanpaBiayaTim(): void
    {
        [$kiriman, $ada] = self::susulanKanga();

        $hasil = self::periksa($kiriman, $ada);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'regu' => 'A', 'biaya_tim' => 0]], $hasil['data']['beregu']);
        $this->assertTrue($hasil['rincian']['beregu'][0]['susulan']);
        $this->assertSame(4, $hasil['rincian']['beregu'][0]['anggota_lama']);
        $this->assertSame(0, $hasil['data']['total']);

        // Melebihi batas anggota bersama regu lama.
        $kiriman['atlet'] = self::timKanga(3, 10, 'A', 0);
        $this->assertGalat(self::periksa($kiriman, $ada), 'beregu.20:A', 'sudah punya 4 atlet dari pendaftaran sebelumnya');
    }

    public function testSusulanBolehMembuatReguBaruDenganBiaya(): void
    {
        [$kiriman, $ada]  = self::susulanKanga();
        $kiriman['atlet'] = self::timKanga(4, 10, 'B');

        $hasil = self::periksa($kiriman, $ada);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'regu' => 'B', 'biaya_tim' => 100000]], $hasil['data']['beregu']);
        $this->assertFalse($hasil['rincian']['beregu'][0]['susulan']);
    }

    public function testSusulanIntiBaruMenurunkanIntiLama(): void
    {
        [$kiriman, $ada]  = self::susulanKanga();
        $kiriman['atlet'] = self::timKanga(1, 10, 'A', 1);

        // Tanpa menurunkan inti lama: inti menjadi 5.
        $this->assertGalat(self::periksa($kiriman, $ada), 'beregu.20:A', 'punya 5 atlet inti (4 di antaranya dari pendaftaran sebelumnya)');

        $kiriman['turun_cadangan'] = ['20:102'];
        $hasil                     = self::periksa($kiriman, $ada);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['atlet_id' => 102, 'nomor_lomba_id' => 20]], $hasil['data']['turun_cadangan']);
        $this->assertSame(1, $hasil['rincian']['beregu'][0]['turun']);
    }

    public function testTurunCadanganHarusIntiLamaDiReguYangDitambah(): void
    {
        [$kiriman, $ada]  = self::susulanKanga();
        $kiriman['atlet'] = self::timKanga(1, 10, 'A', 1);

        foreach (['20:999', '20:abc', '21:102', 'x'] as $turun) {
            $kiriman['turun_cadangan'] = [$turun];
            $this->assertNotSame([], self::periksa($kiriman, $ada)['galat'], $turun);
        }

        // Atlet lama cadangan tidak bisa "diturunkan".
        $ada['tim'][20]['A'][] = ['id' => 150, 'nama' => 'CADANG', 'jenis_kelamin' => 'L', 'peran' => 'cadangan'];
        $kiriman['turun_cadangan'] = ['20:150'];
        $this->assertGalat(self::periksa($kiriman, $ada), 'turun.20');

        // Regu B sekolah ini tidak ditambah kiriman ini.
        $ada['tim'][20]['B']       = self::reguLama(4, 4, 200);
        $kiriman['turun_cadangan'] = ['20:200'];
        $this->assertGalat(self::periksa($kiriman, $ada), 'turun.20');
    }

    // Konfirmasi regu susulan (K57)

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} kiriman susulan satu cadangan Kanga's Escape, data ada
     */
    private static function susulanKanga(string $regu = 'A'): array
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $kiriman['atlet']   = self::timKanga(1, 10, $regu, 0);
        $referensi          = ['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU', 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'];

        return [$kiriman, ['referensi' => $referensi, 'tim' => [20 => [$regu => self::reguLama(4)]]]];
    }

    public function testTimSusulanTanpaKonfirmasiDitolak(): void
    {
        [$kiriman, $ada]               = self::susulanKanga();
        $kiriman['konfirmasi_susulan'] = [];

        $hasil = self::periksa($kiriman, $ada);

        // Sekolah hanya punya satu regu di nomor ini: tanpa nama regu (K108).
        $this->assertGalat($hasil, 'konfirmasi.20:A', "Kanga's Escape putra: sekolah ini sudah punya regu ini (4 atlet dari pendaftaran sebelumnya)");
        $this->assertStringContainsString('Centang kotak konfirmasi', $hasil['galat'][0]['pesan']);
        $this->assertSame(3, $hasil['galat'][0]['langkah']);
        $this->assertCount(1, $hasil['galat']);

        // Konfirmasi untuk nomor atau regu lain tidak berlaku.
        $kiriman['konfirmasi_susulan'] = ['21:A', '20:B', 20, 'bukan', null];
        $this->assertGalat(self::periksa($kiriman, $ada), 'konfirmasi.20:A');
    }

    public function testTimSusulanDenganKonfirmasiDiterima(): void
    {
        [$kiriman, $ada] = self::susulanKanga();

        foreach ([['20:A'], ['21:A', '20:A']] as $konfirmasi) {
            $kiriman['konfirmasi_susulan'] = $konfirmasi;
            $hasil                         = self::periksa($kiriman, $ada);

            $this->assertSame([], $hasil['galat'], json_encode($konfirmasi));
            $this->assertSame([['nomor_lomba_id' => 20, 'regu' => 'A', 'biaya_tim' => 0]], $hasil['data']['beregu']);
            $this->assertTrue($hasil['rincian']['beregu'][0]['susulan']);
        }
    }

    public function testTanpaKunciKonfirmasiPerilakuLama(): void
    {
        // Pemanggil admin (F6-04) tidak membawa kunci konfirmasi: regu susulan diterima seperti sebelumnya.
        [$kiriman, $ada] = self::susulanKanga();

        $hasil = self::periksa($kiriman, $ada);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'regu' => 'A', 'biaya_tim' => 0]], $hasil['data']['beregu']);
    }

    public function testKonfirmasiTidakMemengaruhiTimBaru(): void
    {
        // Pendaftaran tanpa regu susulan tidak perlu konfirmasi apa pun.
        $kiriman                       = self::kirimanContoh();
        $kiriman['atlet']              = self::timKanga(4);
        $kiriman['konfirmasi_susulan'] = [];

        $hasil = self::periksa($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'regu' => 'A', 'biaya_tim' => 100000]], $hasil['data']['beregu']);

        // Tanpa NPSN, data regu sekolah lain tidak membuat regu menjadi susulan.
        $this->assertSame([], self::periksa($kiriman, ['tim' => [20 => ['A' => self::reguLama(4)]]])['galat']);
    }

    public function testWajibKonfirmasiSusulanMerapikanKiriman(): void
    {
        $this->assertSame([], PemeriksaPendaftaran::wajibKonfirmasiSusulan(['atlet' => []])['konfirmasi_susulan']);
        $this->assertSame([], PemeriksaPendaftaran::wajibKonfirmasiSusulan(['konfirmasi_susulan' => 'semua'])['konfirmasi_susulan']);
        $this->assertSame(
            ['20:A', '21:B', '20:a', '20:AB'],   // nama regu boleh lebih dari satu huruf (K106)
            PemeriksaPendaftaran::wajibKonfirmasiSusulan(['konfirmasi_susulan' => ['20:A', '21:B', '20:A', 20, '0:A', '20:a', '20:AB', null, ['x']]])['konfirmasi_susulan'],
        );
    }

    public function testTimSekolahLainTidakDihitungTanpaNpsn(): void
    {
        // Data regu hanya berlaku bila NPSN diisi; tanpa NPSN pendaftaran berdiri sendiri.
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(1);

        $this->assertGalat(self::periksa($kiriman, ['tim' => [20 => ['A' => self::reguLama(4)]]]), 'beregu.20:A', 'baru punya 1 atlet');
    }

    public function testLonggarUntukReguTersimpan(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(3);
        $induk            = self::indukContoh();

        // Admin: regu A tersimpan di pendaftaran ini boleh kurang inti; regu baru tidak.
        $longgar = new PemeriksaPendaftaran($induk, ['regu_tersimpan' => ['20:A']] + self::ADA_KOSONG, self::HARI_INI, [], true);
        $this->assertSame([], $longgar->periksa($kiriman)['galat']);

        $baru = new PemeriksaPendaftaran($induk, self::ADA_KOSONG, self::HARI_INI, [], true);
        $this->assertNotSame([], $baru->periksa($kiriman)['galat']);
    }

    public function testTimMixDiperiksaPutraPutri(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [];

        for ($i = 0; $i < 8; $i++) {
            $kiriman['atlet'][] = self::atletContoh('99000001' . $i . '0', [21], $i < 6 ? 'L' : 'P') + ['regu' => [21 => ['regu' => 'A', 'peran' => 'inti']]];
        }

        $this->assertGalat(self::periksa($kiriman), 'beregu.21:A', 'Jadikan 2 putri sebagai inti');
    }

    // Kuota per nomor individu (K51)

    public function testKuotaNomorIndividu(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [
            self::atletContoh('9900000011', [10]),
            self::atletContoh('9900000012', [10]),
            self::atletContoh('9900000013', [10]),
        ];

        $this->assertGalat(self::periksa($kiriman), 'individu.10', 'Lari 60 m putra: paling banyak 2 atlet per sekolah, sekarang 3. Hapus 1 atlet');
        $this->assertSame([], self::periksa($kiriman, [], ['aturan' => ['atlet_per_nomor_maksimal' => 3]])['galat']);
    }

    public function testKuotaTanpaBatas(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = array_map(static fn (int $i): array => self::atletContoh('99000000' . (20 + $i), [10]), range(0, 5));

        $induk                                       = self::indukContoh();
        $induk['aturan']['atlet_per_nomor_maksimal'] = null;

        $hasil = (new PemeriksaPendaftaran($induk, self::ADA_KOSONG, self::HARI_INI))->periksa($kiriman);

        $this->assertSame([], $hasil['galat']);
    }

    public function testKuotaDihitungBersamaPendaftaranSebelumnya(): void
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $referensi          = ['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU', 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'];

        // Sudah 1 atlet Lari 60 m putra: kiriman contoh menambah 1, jadi 2 (boleh).
        $this->assertSame([], self::periksa($kiriman, ['referensi' => $referensi, 'individu' => [10 => 1]])['galat']);

        // Sudah 2: penuh.
        $this->assertGalat(
            self::periksa($kiriman, ['referensi' => $referensi, 'individu' => [10 => 2]]),
            'individu.10',
            'sekolah ini sudah mendaftarkan 2 atlet sebelumnya, dan paling banyak 2 atlet per sekolah. Nomor ini sudah penuh',
        );
    }

    // Bantu

    public function testNpsnDanNisnKiriman(): void
    {
        $kiriman                    = self::kirimanContoh();
        $kiriman['sekolah']['npsn'] = ' 99999911 ';
        $kiriman['atlet'][]         = self::atletContoh('salah', [10]);

        $this->assertSame('99999911', PemeriksaPendaftaran::npsnKiriman($kiriman));
        $this->assertSame(['9900000001', '9900000002'], PemeriksaPendaftaran::nisnKiriman($kiriman));
        $this->assertNull(PemeriksaPendaftaran::npsnKiriman(['sekolah' => ['npsn' => '123']]));
        $this->assertSame([], PemeriksaPendaftaran::nisnKiriman(['atlet' => 'bukan daftar']));

        // Spasi di tengah dibuang seperti saat memeriksa sekolah, supaya
        // susulan, kuota, dan SLB tetap terbaca untuk NPSN yang sama.
        $this->assertSame('99999911', PemeriksaPendaftaran::npsnKiriman(['sekolah' => ['npsn' => "9999 99\t11"]]));
    }
}
