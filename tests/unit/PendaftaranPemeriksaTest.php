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

        $this->assertGalat(self::periksa($kiriman), 'sekolah.nama', 'NPSN ini tidak ada di data sekolah kami');

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

        $this->assertGalat($hasil, 'penanggung_jawab.0.nama', 'Penanggung jawab 1: nama belum diisi');
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

        $this->assertGalat(self::periksa($kiriman), 'atlet.0.nomor', 'ikut 3 nomor individu, padahal paling banyak 2');
        $this->assertSame([], self::periksa($kiriman, [], ['aturan' => ['nomor_individu_maksimal' => 3, 'atlet_per_nomor_maksimal' => null]])['galat']);
    }

    // Tim

    /**
     * @return list<array<string, mixed>>
     */
    private static function timKanga(int $jumlah, int $mulai = 10): array
    {
        $atlet = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $atlet[] = self::atletContoh('99000000' . ($mulai + $i), [20]);
        }

        return $atlet;
    }

    public function testTimBaruLengkapDibayarPerTim(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(4);

        $hasil = self::periksa($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'biaya_tim' => 100000]], $hasil['data']['beregu']);
        $this->assertSame([['nomor_lomba_id' => 20, 'biaya' => null]], $hasil['data']['atlet'][0]['nomor']);
        $this->assertSame(100000, $hasil['data']['subtotal_beregu']);
        $this->assertSame(0, $hasil['data']['subtotal_individu']);
        $this->assertSame(
            [['nomor' => "Kanga's Escape putra", 'kelompok_umur' => 'KU 10', 'biaya' => 100000, 'susulan' => false, 'anggota' => 4, 'anggota_lama' => null]],
            $hasil['rincian']['beregu'],
        );
    }

    public function testTimBaruBelumLengkapDitolak(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(3);

        $hasil = self::periksa($kiriman);

        $this->assertGalat($hasil, 'beregu.20', 'baru punya 3 atlet');
    }

    public function testTimSusulanTanpaBiayaTim(): void
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $kiriman['atlet']   = self::timKanga(1);
        $referensi          = ['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU', 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'];

        $hasil = self::periksa($kiriman, ['referensi' => $referensi, 'tim' => [20 => 4]]);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'biaya_tim' => 0]], $hasil['data']['beregu']);
        $this->assertTrue($hasil['rincian']['beregu'][0]['susulan']);
        $this->assertSame(4, $hasil['rincian']['beregu'][0]['anggota_lama']);
        $this->assertSame(0, $hasil['data']['total']);

        // Melebihi batas anggota bersama tim lama.
        $kiriman['atlet'] = self::timKanga(3);
        $this->assertGalat(self::periksa($kiriman, ['referensi' => $referensi, 'tim' => [20 => 4]]), 'beregu.20', 'sudah punya 4 atlet dari pendaftaran sebelumnya');
    }

    // Konfirmasi tim susulan (K57)

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} kiriman susulan satu atlet Kanga's Escape, data ada
     */
    private static function susulanKanga(): array
    {
        $kiriman            = self::kirimanContoh();
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $kiriman['atlet']   = self::timKanga(1);
        $referensi          = ['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU', 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'];

        return [$kiriman, ['referensi' => $referensi, 'tim' => [20 => 4]]];
    }

    public function testTimSusulanTanpaKonfirmasiDitolak(): void
    {
        [$kiriman, $ada]               = self::susulanKanga();
        $kiriman['konfirmasi_susulan'] = [];

        $hasil = self::periksa($kiriman, $ada);

        $this->assertGalat($hasil, 'konfirmasi.20', "Tim Kanga's Escape putra: sekolah ini sudah punya tim di nomor ini (4 atlet dari pendaftaran sebelumnya)");
        $this->assertStringContainsString('Centang kotak konfirmasi', $hasil['galat'][0]['pesan']);
        $this->assertSame(3, $hasil['galat'][0]['langkah']);
        $this->assertCount(1, $hasil['galat']);

        // Konfirmasi untuk nomor lain tidak berlaku.
        $kiriman['konfirmasi_susulan'] = [21, 'bukan', -20, null];
        $this->assertGalat(self::periksa($kiriman, $ada), 'konfirmasi.20');
    }

    public function testTimSusulanDenganKonfirmasiDiterima(): void
    {
        [$kiriman, $ada] = self::susulanKanga();

        foreach ([[20], ['20'], [21, 20]] as $konfirmasi) {
            $kiriman['konfirmasi_susulan'] = $konfirmasi;
            $hasil                         = self::periksa($kiriman, $ada);

            $this->assertSame([], $hasil['galat'], json_encode($konfirmasi));
            $this->assertSame([['nomor_lomba_id' => 20, 'biaya_tim' => 0]], $hasil['data']['beregu']);
            $this->assertTrue($hasil['rincian']['beregu'][0]['susulan']);
        }
    }

    public function testTanpaKunciKonfirmasiPerilakuLama(): void
    {
        // Pemanggil admin (F6-04) tidak membawa kunci konfirmasi: tim susulan diterima seperti sebelumnya.
        [$kiriman, $ada] = self::susulanKanga();

        $hasil = self::periksa($kiriman, $ada);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'biaya_tim' => 0]], $hasil['data']['beregu']);
    }

    public function testKonfirmasiTidakMemengaruhiTimBaru(): void
    {
        // Pendaftaran tanpa tim susulan tidak perlu konfirmasi apa pun.
        $kiriman                       = self::kirimanContoh();
        $kiriman['atlet']              = self::timKanga(4);
        $kiriman['konfirmasi_susulan'] = [];

        $hasil = self::periksa($kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([['nomor_lomba_id' => 20, 'biaya_tim' => 100000]], $hasil['data']['beregu']);

        // Tanpa NPSN, data tim sekolah lain tidak membuat tim menjadi susulan.
        $this->assertSame([], self::periksa($kiriman, ['tim' => [20 => 4]])['galat']);
    }

    public function testWajibKonfirmasiSusulanMerapikanKiriman(): void
    {
        $this->assertSame([], PemeriksaPendaftaran::wajibKonfirmasiSusulan(['atlet' => []])['konfirmasi_susulan']);
        $this->assertSame([], PemeriksaPendaftaran::wajibKonfirmasiSusulan(['konfirmasi_susulan' => 'semua'])['konfirmasi_susulan']);
        $this->assertSame(
            [20, 21],
            PemeriksaPendaftaran::wajibKonfirmasiSusulan(['konfirmasi_susulan' => [20, '21', '20', 0, -3, '4a', 1.5, null, ['x']]])['konfirmasi_susulan'],
        );
    }

    public function testTimSekolahLainTidakDihitungTanpaNpsn(): void
    {
        // Data tim hanya berlaku bila NPSN diisi; tanpa NPSN pendaftaran berdiri sendiri.
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = self::timKanga(1);

        $this->assertGalat(self::periksa($kiriman, ['tim' => [20 => 4]]), 'beregu.20', 'baru punya 1 atlet');
    }

    public function testTimMixDiperiksaPutraPutri(): void
    {
        $kiriman          = self::kirimanContoh();
        $kiriman['atlet'] = [];

        for ($i = 0; $i < 8; $i++) {
            $kiriman['atlet'][] = self::atletContoh('99000001' . $i . '0', [21], $i < 6 ? 'L' : 'P');
        }

        $this->assertGalat(self::periksa($kiriman), 'beregu.21', 'Tambah 2 putri');
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
