<?php

use App\Libraries\Panel\NamaDesaSekolah;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan murni nama desa untuk sekolah bernama kembar (K114):
 * `NamaDesaSekolah::rencanakan()`. Semua sekolah karangan.
 *
 * @internal
 */
final class PanelNamaDesaSekolahTest extends CIUnitTestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function sekolah(int $id, string $nama, ?string $desa, ?string $kecamatan = 'Anggitan', ?string $kabupaten = 'Kudus'): array
    {
        return [
            'id'             => $id,
            'npsn'           => (string) (99200000 + $id),
            'nama'           => $nama,
            'desa_kelurahan' => $desa,
            'kecamatan'      => $kecamatan,
            'kabupaten'      => $kabupaten,
        ];
    }

    /**
     * @param list<array<string, mixed>> $daftar
     *
     * @return array<int, string|null> id => nama baru (null bila dilewati)
     */
    private static function namaBaru(array $daftar, array $namaLain = []): array
    {
        $hasil = NamaDesaSekolah::rencanakan($daftar, [...array_column($daftar, 'nama'), ...$namaLain]);
        $per   = [];

        foreach ($hasil['usulan'] as $s) {
            $per[$s['id']] = $s['nama_baru'];
        }

        foreach ($hasil['lewati'] as $s) {
            $per[$s['id']] = null;
        }

        ksort($per);

        return $per;
    }

    public function testNamaDesaDitambahkanDenganHurufBesar(): void
    {
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, 'MIS KARANGAN', 'Rekaan'),
            self::sekolah(2, 'MIS KARANGAN', 'Khayalan  Kulon'),
        ], ['MIS KARANGAN', 'MIS KARANGAN']);

        $this->assertSame([], $hasil['lewati']);
        $this->assertSame(['MIS KARANGAN REKAAN', 'MIS KARANGAN KHAYALAN KULON'], array_column($hasil['usulan'], 'nama_baru'));
        $this->assertSame(['desa_kelurahan', 'desa_kelurahan'], array_column($hasil['usulan'], 'pembeda'));
        // Data asli tetap terbawa.
        $this->assertSame('99200001', $hasil['usulan'][0]['npsn']);
    }

    public function testNamaHurufCampuranMemakaiEjaanDesaApaAdanya(): void
    {
        $this->assertSame(
            [1 => 'SDN 1 Karangan Rekaan', 2 => 'SDN 1 KARANGAN KHAYALAN'],
            self::namaBaru([self::sekolah(1, 'SDN 1 Karangan', 'Rekaan'), self::sekolah(2, 'SDN 1 KARANGAN', 'Khayalan')]),
        );
    }

    public function testDesaYangSudahTertulisDiNamaMemakaiKecamatan(): void
    {
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, 'SD NEGERI 1 REKAAN', 'Rekaan', 'Anggitan', 'Rembang'),
            self::sekolah(2, 'SD NEGERI 1 REKAAN', 'Rekaan', 'Contoh', 'Jepara'),
        ], []);

        $this->assertSame(['SD NEGERI 1 REKAAN ANGGITAN', 'SD NEGERI 1 REKAAN CONTOH'], array_column($hasil['usulan'], 'nama_baru'));
        $this->assertSame(['kecamatan', 'kecamatan'], array_column($hasil['usulan'], 'pembeda'));
    }

    public function testDesaYangSamaDenganSekolahLainBernamaSamaMemakaiKecamatan(): void
    {
        $this->assertSame(
            [1 => 'MIS KARANGAN ANGGITAN', 2 => 'MIS KARANGAN CONTOH', 3 => 'MIS KARANGAN KHAYALAN'],
            self::namaBaru([
                self::sekolah(1, 'MIS KARANGAN', 'Rekaan', 'Anggitan'),
                self::sekolah(2, 'MIS KARANGAN', 'Rekaan', 'Contoh'),
                self::sekolah(3, 'MIS KARANGAN', 'Khayalan', 'Contoh'),
            ]),
        );
    }

    public function testKecamatanTertulisDiNamaMemakaiKabupaten(): void
    {
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, 'SD NEGERI 1 REKAAN', 'Rekaan', 'Sale', 'Rembang'),
            self::sekolah(2, 'SD NEGERI 1 REKAAN', 'Rekaan', 'Rekaan', 'Jepara'),
        ], []);

        $this->assertSame(['SD NEGERI 1 REKAAN SALE', 'SD NEGERI 1 REKAAN JEPARA'], array_column($hasil['usulan'], 'nama_baru'));
        $this->assertSame(['kecamatan', 'kabupaten'], array_column($hasil['usulan'], 'pembeda'));
    }

    public function testKataYangHanyaBagianKataLainTidakDianggapTertulis(): void
    {
        // "JATI" bukan kata utuh di "JATISARI", jadi desa Jati tetap dipakai.
        $this->assertSame(
            [1 => 'MI JATISARI JATI', 2 => 'MI JATISARI REKAAN'],
            self::namaBaru([self::sekolah(1, 'MI JATISARI', 'Jati'), self::sekolah(2, 'MI JATISARI', 'Rekaan')]),
        );
    }

    public function testLetakYangTidakMembedakanDilewati(): void
    {
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, 'MI KARANGAN', 'Rekaan'),
            self::sekolah(2, 'MI KARANGAN', 'Rekaan'),
            self::sekolah(3, 'MTS KARANGAN', null, null, 'Kudus'),
            self::sekolah(4, 'MTS KARANGAN', '', ' ', 'Kudus'),
            self::sekolah(5, 'MTS KARANGAN', 'Khayalan', null, 'Kudus'),
        ], []);

        $this->assertSame(['MTS KARANGAN KHAYALAN'], array_column($hasil['usulan'], 'nama_baru'));
        $this->assertSame([1, 2, 3, 4], array_column($hasil['lewati'], 'id'));
        $this->assertSame(NamaDesaSekolah::ALASAN_LETAK, $hasil['lewati'][0]['alasan']);
        $this->assertArrayNotHasKey('nama_baru', $hasil['lewati'][0]);
    }

    public function testSekolahTanpaDesaYangTersisaMemakaiKecamatan(): void
    {
        // Sekolah 2 tanpa desa; sekolah 1 sudah dibedakan desanya, jadi
        // kecamatan sekolah 2 hanya dibandingkan dengan sekolah yang tersisa.
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, 'MI KARANGAN', 'Rekaan', 'Anggitan'),
            self::sekolah(2, 'MI KARANGAN', null, 'Anggitan'),
        ], []);

        $this->assertSame(['MI KARANGAN REKAAN', 'MI KARANGAN ANGGITAN'], array_column($hasil['usulan'], 'nama_baru'));
        $this->assertSame(['desa_kelurahan', 'kecamatan'], array_column($hasil['usulan'], 'pembeda'));
    }

    public function testNamaBaruYangSudahDipakaiSekolahLainDilewati(): void
    {
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, 'MIS KARANGAN', 'Rekaan'),
            self::sekolah(2, 'MIS KARANGAN', 'Khayalan'),
        ], ['MIS KARANGAN', 'MIS KARANGAN', 'mis karangan rekaan']);

        $this->assertSame(['MIS KARANGAN KHAYALAN'], array_column($hasil['usulan'], 'nama_baru'));
        $this->assertSame([1], array_column($hasil['lewati'], 'id'));
        $this->assertSame(NamaDesaSekolah::ALASAN_BENTROK, $hasil['lewati'][0]['alasan']);
    }

    public function testNamaBaruYangSamaDenganNamaBaruSekolahLainDilewati(): void
    {
        // Dua kelompok berbeda bisa menghasilkan nama baru yang sama; keduanya
        // dilewati supaya admin memilih sendiri.
        $this->assertSame(
            [1 => null, 2 => 'MI KARANGAN CONTOH', 3 => null, 4 => 'MI KARANGAN REKAAN KHAYALAN'],
            self::namaBaru([
                self::sekolah(1, 'MI KARANGAN', 'Rekaan Jaya'),
                self::sekolah(2, 'MI KARANGAN', 'Contoh'),
                self::sekolah(3, 'MI KARANGAN REKAAN', 'Jaya'),
                self::sekolah(4, 'MI KARANGAN REKAAN', 'Khayalan'),
            ]),
        );
    }

    public function testNamaBaruTerlaluPanjangDilewati(): void
    {
        $hasil = NamaDesaSekolah::rencanakan([
            self::sekolah(1, str_repeat('A', 145), 'Rekaan'),
            self::sekolah(2, str_repeat('A', 145), 'Desa'),
        ], []);

        $this->assertSame([2], array_column($hasil['usulan'], 'id'));
        $this->assertSame(NamaDesaSekolah::ALASAN_PANJANG, $hasil['lewati'][0]['alasan']);
    }
}
