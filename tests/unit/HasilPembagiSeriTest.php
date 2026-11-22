<?php

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\PembagiSeri;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pembagi seri dan lintasan (F9-08, K140 butir 8): rata, sekolah sama dipisah
 * sebisanya, acak berbenih yang bisa diulang, unggulan zig-zag dengan urutan
 * lintasan World Athletics, dan aturan lolos Q/q.
 *
 * @internal
 */
final class HasilPembagiSeriTest extends CIUnitTestCase
{
    /**
     * @param array<string, int> $sekolah kunci sekolah => banyak peserta
     *
     * @return list<array{kunci: string, sekolah: string}>
     */
    private static function peserta(array $sekolah): array
    {
        $hasil = [];
        $i     = 1;

        foreach ($sekolah as $s => $jumlah) {
            for ($j = 0; $j < $jumlah; $j++) {
                $hasil[] = ['kunci' => 'a:' . $i++, 'sekolah' => $s];
            }
        }

        return $hasil;
    }

    // ---------- Banyak dan ukuran seri ----------

    public function testJumlahDanUkuranSeriRata(): void
    {
        $this->assertSame(0, PembagiSeri::jumlahSeri(0, 8));
        $this->assertSame(1, PembagiSeri::jumlahSeri(8, 8));
        $this->assertSame(2, PembagiSeri::jumlahSeri(9, 8));
        $this->assertSame(3, PembagiSeri::jumlahSeri(20, 8));
        $this->assertSame(1, PembagiSeri::jumlahSeri(40, 0), 'Tanpa batas kelompok: satu seri.');

        $this->assertSame([7, 7, 6], PembagiSeri::ukuranSeri(20, 3));
        $this->assertSame([5, 4], PembagiSeri::ukuranSeri(9, 2));
        $this->assertSame([], PembagiSeri::ukuranSeri(0, 2));
    }

    public function testTempatBawaanMenurutJenisHasil(): void
    {
        $this->assertSame(8, PembagiSeri::tempatBawaan(JenisHasil::WAKTU));
        $this->assertSame(0, PembagiSeri::tempatBawaan(JenisHasil::JARAK));
        $this->assertTrue(PembagiSeri::pakaiLintasan(JenisHasil::WAKTU));
        $this->assertFalse(PembagiSeri::pakaiLintasan(JenisHasil::MULTI));
    }

    // ---------- Urutan lintasan ----------

    public function testUrutanLintasanBawaanDanPeriksa(): void
    {
        $this->assertSame('3,4,5,6;7,8;1,2', PembagiSeri::urutanBawaan(8));
        $this->assertSame('3,4;2,5;1,6', PembagiSeri::urutanBawaan(6));
        $this->assertSame('3;2,4;1,5', PembagiSeri::urutanBawaan(5));
        $this->assertSame([[3, 4, 5, 6], [7, 8], [1, 2]], PembagiSeri::kelompokLintasan(null, 8));
        $this->assertSame([[4], [5], [3], [6], [2], [7], [1], [8]], PembagiSeri::kelompokLintasan('4;5;3;6;2;7;1;8', 8));

        $this->assertNull(PembagiSeri::periksaUrutan('', 8));
        $this->assertNull(PembagiSeri::periksaUrutan(' 4, 5 ;3,6;7,8; 1,2 ', 8));
        $this->assertStringContainsString('tepat sekali', (string) PembagiSeri::periksaUrutan('3,4,5,6;7,8;1', 8));
        $this->assertStringContainsString('tepat sekali', (string) PembagiSeri::periksaUrutan('3,4,5,6;7,8;1,1,2', 8));
        $this->assertStringContainsString('angka', (string) PembagiSeri::periksaUrutan('3-6', 8));
        $this->assertSame('4,5;3,6;7,8;1,2', PembagiSeri::rapikanUrutan(' 4, 5 ;3,6;7,8; 1,2 '));

        // Isian salah tidak dipakai: kembali ke bawaan.
        $this->assertSame([[3, 4, 5, 6], [7, 8], [1, 2]], PembagiSeri::kelompokLintasan('1,2,3', 8));
    }

    // ---------- Babak pertama: acak ----------

    public function testBagiAcakRataDanLintasanUnik(): void
    {
        $peserta = self::peserta(['s1' => 4, 's2' => 4, 's3' => 4, 's4' => 4, 's5' => 4]);
        $bagi    = PembagiSeri::bagiAcak($peserta, 8, true, 2026);

        $this->assertCount(20, $bagi);
        $perSeri = [];

        foreach ($bagi as $kunci => $t) {
            $perSeri[$t['seri']][] = $t['lintasan'];
        }

        ksort($perSeri);
        $this->assertSame([1, 2, 3], array_keys($perSeri));
        $this->assertSame([7, 7, 6], array_map('count', array_values($perSeri)));

        foreach ($perSeri as $lintasan) {
            $this->assertSame(count($lintasan), count(array_unique($lintasan)), 'Lintasan dalam satu seri tidak boleh sama.');
            // Seri tidak penuh memakai lintasan tengah lebih dulu: 1 atau 2 yang kosong.
            $this->assertEmpty(array_diff($lintasan, range(1, 8)));
        }

        sort($perSeri[3]);
        $this->assertSame([3, 4, 5, 6, 7, 8], $perSeri[3], 'Seri berisi 6 memakai lintasan 3–8.');
    }

    public function testSeriBerisiEnamMemakaiLintasanTengah(): void
    {
        $bagi = PembagiSeri::bagiAcak(self::peserta(['s1' => 6]), 8, true, 7);
        $lintasan = array_column($bagi, 'lintasan');
        sort($lintasan);

        $this->assertSame([3, 4, 5, 6, 7, 8], $lintasan);
    }

    public function testSekolahSamaDipisahSebisanya(): void
    {
        // Sekolah s1 punya 3 atlet, ada 3 seri: tiap seri satu atlet s1.
        $peserta = self::peserta(['s1' => 3, 's2' => 2, 's3' => 5, 's4' => 6, 's5' => 4]);

        foreach ([1, 2, 3, 99, 123456] as $benih) {
            $bagi  = PembagiSeri::bagiAcak($peserta, 8, true, $benih);
            $hitung = [];

            foreach ($peserta as $p) {
                $hitung[$p['sekolah']][$bagi[$p['kunci']]['seri']] = ($hitung[$p['sekolah']][$bagi[$p['kunci']]['seri']] ?? 0) + 1;
            }

            $this->assertSame([1, 1, 1], array_values($hitung['s1']), 'Benih ' . $benih);
            $this->assertSame(1, max($hitung['s2']));
            $this->assertSame(2, max($hitung['s4']), 'Enam atlet di tiga seri: dua per seri.');
            $this->assertSame(2, max($hitung['s3']));
        }
    }

    public function testBenihSamaHasilSamaBenihLainBerbeda(): void
    {
        $peserta = self::peserta(['s1' => 5, 's2' => 5, 's3' => 6]);

        $this->assertSame(PembagiSeri::bagiAcak($peserta, 8, true, 42), PembagiSeri::bagiAcak($peserta, 8, true, 42));
        $this->assertNotSame(PembagiSeri::bagiAcak($peserta, 8, true, 42), PembagiSeri::bagiAcak($peserta, 8, true, 43));
    }

    public function testJumlahSeriDimintaAdmin(): void
    {
        $peserta = self::peserta(['s1' => 3, 's2' => 3, 's3' => 3, 's4' => 3]);

        $tiga = PembagiSeri::bagiAcak($peserta, 8, true, 5, 3);
        $seri = array_count_values(array_column($tiga, 'seri'));
        ksort($seri);
        $this->assertSame([1 => 4, 2 => 4, 3 => 4], $seri);

        // Lebih sedikit dari yang muat: dipakai paling sedikit (2 seri untuk 12 peserta).
        $this->assertSame(2, max(array_column(PembagiSeri::bagiAcak($peserta, 8, true, 5, 1), 'seri')));
    }

    public function testTanpaLintasanUrutanTampil(): void
    {
        $bagi = PembagiSeri::bagiAcak(self::peserta(['s1' => 4, 's2' => 7]), 0, false, 11);
        $urut = array_column($bagi, 'lintasan');
        sort($urut);

        $this->assertSame([1], array_values(array_unique(array_column($bagi, 'seri'))));
        $this->assertSame(range(1, 11), $urut);

        // Kelompok 5 peserta: 3 kelompok.
        $this->assertSame(3, max(array_column(PembagiSeri::bagiAcak(self::peserta(['s1' => 11]), 5, false, 11), 'seri')));
        $this->assertSame([], PembagiSeri::bagiAcak([], 8, true, 1));
    }

    // ---------- Babak lanjutan: unggulan ----------

    public function testUnggulanSatuSeriMemakaiKelompokLintasanWorldAthletics(): void
    {
        $peringkat = ['a:1', 'a:2', 'a:3', 'a:4', 'a:5', 'a:6', 'a:7', 'a:8'];

        foreach ([1, 2, 3, 4] as $benih) {
            $bagi = PembagiSeri::bagiUnggulan($peringkat, 8, true, $benih);
            $lajur = array_map(static fn (string $k): int => $bagi[$k]['lintasan'], $peringkat);

            $empat = array_slice($lajur, 0, 4);
            sort($empat);
            $this->assertSame([3, 4, 5, 6], $empat, 'Empat terbaik di lintasan 3–6.');
            $dua = array_slice($lajur, 4, 2);
            sort($dua);
            $this->assertSame([7, 8], $dua);
            $akhir = array_slice($lajur, 6, 2);
            sort($akhir);
            $this->assertSame([1, 2], $akhir);
        }

        // Urutan tetap tanpa undian.
        $tetap = PembagiSeri::bagiUnggulan($peringkat, 8, true, 9, null, '4;5;3;6;2;7;1;8');
        $this->assertSame([4, 5, 3, 6, 2, 7, 1, 8], array_map(static fn (string $k): int => $tetap[$k]['lintasan'], $peringkat));
    }

    public function testUnggulanDuaSeriZigzag(): void
    {
        $peringkat = array_map(static fn (int $i): string => 'a:' . $i, range(1, 16));
        $bagi      = PembagiSeri::bagiUnggulan($peringkat, 8, true, 3, 2);

        // 1→S1, 2→S2, 3→S2, 4→S1, 5→S1, 6→S2, …
        $this->assertSame([1, 2, 2, 1, 1, 2, 2, 1], array_map(static fn (string $k): int => $bagi[$k]['seri'], array_slice($peringkat, 0, 8)));
        $this->assertSame(8, count(array_filter($bagi, static fn (array $t): bool => $t['seri'] === 1)));

        // Lima peserta: kelompok lintasan pertama penuh, peserta kelima diundi di 7–8.
        $lima = PembagiSeri::bagiUnggulan(array_slice($peringkat, 0, 5), 8, true, 3);
        $this->assertContains($lima['a:5']['lintasan'], [7, 8]);
    }

    // ---------- Lolos Q/q ----------

    public function testLolosQDanQKecil(): void
    {
        $baris = [
            ['kunci' => 'a:1', 'seri' => 1, 'prestasi' => 900, 'keterangan' => null],
            ['kunci' => 'a:2', 'seri' => 1, 'prestasi' => 950, 'keterangan' => null],
            ['kunci' => 'a:3', 'seri' => 1, 'prestasi' => 980, 'keterangan' => null],
            ['kunci' => 'a:4', 'seri' => 1, 'prestasi' => null, 'keterangan' => 'DNS'],
            ['kunci' => 'a:5', 'seri' => 2, 'prestasi' => 1000, 'keterangan' => null],
            ['kunci' => 'a:6', 'seri' => 2, 'prestasi' => 1010, 'keterangan' => null],
            ['kunci' => 'a:7', 'seri' => 2, 'prestasi' => 960, 'keterangan' => 'DQ'],
            ['kunci' => 'a:8', 'seri' => 2, 'prestasi' => 1020, 'keterangan' => null],
        ];

        // Q = 1 terbaik tiap seri, q = 2 waktu terbaik berikutnya.
        $this->assertSame(['a:1' => 'Q', 'a:2' => 'q', 'a:3' => 'q', 'a:5' => 'Q'], PembagiSeri::lolos($baris, 1, 2, JenisHasil::WAKTU));

        // Q = 2, q = 0.
        $this->assertSame(['a:1' => 'Q', 'a:2' => 'Q', 'a:5' => 'Q', 'a:6' => 'Q'], PembagiSeri::lolos($baris, 2, 0, JenisHasil::WAKTU));

        // Jarak: besar lebih baik, peringkat sama di batas ikut lolos.
        $jarak = [
            ['kunci' => 'a:1', 'seri' => 1, 'prestasi' => 300, 'keterangan' => null],
            ['kunci' => 'a:2', 'seri' => 1, 'prestasi' => 320, 'keterangan' => null],
            ['kunci' => 'a:3', 'seri' => 1, 'prestasi' => 320, 'keterangan' => null],
        ];
        $this->assertSame(['a:2' => 'Q', 'a:3' => 'Q'], PembagiSeri::lolos($jarak, 1, 0, JenisHasil::JARAK));

        // F11-05: r (mundur) dinilai dari prestasinya, NM tidak lolos.
        $kode = [
            ['kunci' => 'a:1', 'seri' => 1, 'prestasi' => 300, 'keterangan' => null],
            ['kunci' => 'a:2', 'seri' => 1, 'prestasi' => 350, 'keterangan' => 'r'],
            ['kunci' => 'a:3', 'seri' => 2, 'prestasi' => null, 'keterangan' => 'NM'],
            ['kunci' => 'a:4', 'seri' => 2, 'prestasi' => 310, 'keterangan' => null],
        ];
        $this->assertSame(['a:2' => 'Q', 'a:4' => 'Q', 'a:1' => 'q'], PembagiSeri::lolos($kode, 1, 1, JenisHasil::JARAK));
    }

    public function testAturanOtomatisMengisiPenuh(): void
    {
        $this->assertSame(['q' => 2, 'q_kecil' => 2], PembagiSeri::aturanOtomatis(3, 8));
        $this->assertSame(['q' => 8, 'q_kecil' => 0], PembagiSeri::aturanOtomatis(1, 8));
        $this->assertSame(['q' => 4, 'q_kecil' => 0], PembagiSeri::aturanOtomatis(4, 16));
        $this->assertSame(['q' => 0, 'q_kecil' => 8], PembagiSeri::aturanOtomatis(10, 8));
        $this->assertSame(['q' => 0, 'q_kecil' => 0], PembagiSeri::aturanOtomatis(0, 8));
    }
}
