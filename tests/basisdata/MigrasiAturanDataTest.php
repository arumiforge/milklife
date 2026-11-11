<?php

use CodeIgniter\Database\Exceptions\DatabaseException;
use Tests\Support\UjiBasisData;

/**
 * Aturan data yang dijaga basis data: keunikan, hapus berantai, larangan hapus,
 * nilai awal, dan uang tak bertanda. Semua nama dan NISN di sini karangan.
 *
 * @internal
 */
final class MigrasiAturanDataTest extends UjiBasisData
{
    // ---------------------------------------------------------------
    // Pembantu pembuat baris
    // ---------------------------------------------------------------

    private function buatKelompokUmur(array $isi = []): int
    {
        $this->db->table('kelompok_umur')->insert($isi + ['nama' => 'KU 10']);

        return (int) $this->db->insertID();
    }

    private function buatNomorLomba(int $kelompokUmurId, array $isi = []): int
    {
        $this->db->table('nomor_lomba')->insert($isi + [
            'kelompok_umur_id' => $kelompokUmurId,
            'nama'             => 'Lari 60 m',
            'jenis'            => 'individu',
            'kategori'         => 'putra',
        ]);

        return (int) $this->db->insertID();
    }

    private function buatPendaftaran(array $isi = []): int
    {
        $this->db->table('pendaftaran')->insert($isi + [
            'jenjang'           => 'A',
            'npsn'              => '99999901',
            'nama_sekolah'      => 'SD KARANGAN SATU',
            'kabupaten'         => 'Kudus',
            'subtotal_individu' => 150000,
            'subtotal_beregu'   => 100000,
            'total'             => 250000,
        ]);

        return (int) $this->db->insertID();
    }

    private function buatAtlet(int $pendaftaranId, int $kelompokUmurId, string $nisn, ?string $nomorDada = null): int
    {
        $this->db->table('atlet')->insert([
            'pendaftaran_id'   => $pendaftaranId,
            'nama'             => 'ATLET KARANGAN ' . $nisn,
            'nisn'             => $nisn,
            'tanggal_lahir'    => '2016-05-17',
            'jenis_kelamin'    => 'L',
            'kelompok_umur_id' => $kelompokUmurId,
            'nomor_dada'       => $nomorDada,
        ]);

        return (int) $this->db->insertID();
    }

    private function jumlah(string $tabel): int
    {
        return $this->db->table($tabel)->countAllResults();
    }

    // ---------------------------------------------------------------
    // Keunikan
    // ---------------------------------------------------------------

    public function testNisnGandaDitolak(): void
    {
        $ku          = $this->buatKelompokUmur();
        $pendaftaran = $this->buatPendaftaran();
        $this->buatAtlet($pendaftaran, $ku, '9900000001');

        // Juga dari pendaftaran lain (susulan).
        $susulan = $this->buatPendaftaran();

        $this->expectException(DatabaseException::class);
        $this->buatAtlet($susulan, $ku, '9900000001');
    }

    public function testNomorDadaGandaDitolak(): void
    {
        $ku          = $this->buatKelompokUmur();
        $pendaftaran = $this->buatPendaftaran();
        $this->buatAtlet($pendaftaran, $ku, '9900000001', '1001');

        $this->expectException(DatabaseException::class);
        $this->buatAtlet($pendaftaran, $ku, '9900000002', '1001');
    }

    public function testBanyakNomorDadaKosongDiterima(): void
    {
        $ku          = $this->buatKelompokUmur();
        $pendaftaran = $this->buatPendaftaran();

        $this->buatAtlet($pendaftaran, $ku, '9900000001');
        $this->buatAtlet($pendaftaran, $ku, '9900000002');
        $this->buatAtlet($pendaftaran, $ku, '9900000003');

        $this->assertSame(3, $this->db->table('atlet')->where('nomor_dada', null)->countAllResults());
    }

    public function testIdPendaftaranGandaDitolak(): void
    {
        $this->buatPendaftaran(['id_pendaftaran' => 'MAC2A0000126']);

        $this->expectException(DatabaseException::class);
        $this->buatPendaftaran(['id_pendaftaran' => 'MAC2A0000126']);
    }

    public function testBanyakIdPendaftaranKosongDiterima(): void
    {
        $this->buatPendaftaran();
        $this->buatPendaftaran();

        $this->assertSame(2, $this->db->table('pendaftaran')->where('id_pendaftaran', null)->countAllResults());
    }

    public function testAwalanGandaDitolak(): void
    {
        $ku10 = $this->buatKelompokUmur(['nama' => 'KU 10']);
        $ku12 = $this->buatKelompokUmur(['nama' => 'KU 12']);
        $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $ku10, 'awalan' => '1']);

        $this->expectException(DatabaseException::class);
        $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $ku12, 'awalan' => '1']);
    }

    public function testPasanganAtletNomorGandaDitolak(): void
    {
        $ku          = $this->buatKelompokUmur();
        $nomor       = $this->buatNomorLomba($ku);
        $pendaftaran = $this->buatPendaftaran();
        $atlet       = $this->buatAtlet($pendaftaran, $ku, '9900000001');
        $this->db->table('atlet_nomor')->insert(['atlet_id' => $atlet, 'nomor_lomba_id' => $nomor, 'biaya' => 75000]);

        $this->expectException(DatabaseException::class);
        $this->db->table('atlet_nomor')->insert(['atlet_id' => $atlet, 'nomor_lomba_id' => $nomor, 'biaya' => 75000]);
    }

    public function testPasanganPendaftaranNomorGandaDitolak(): void
    {
        $ku          = $this->buatKelompokUmur();
        $nomor       = $this->buatNomorLomba($ku, ['nama' => 'Formula 1', 'jenis' => 'beregu', 'jumlah_inti' => 4]);
        $pendaftaran = $this->buatPendaftaran();
        $this->db->table('pendaftaran_beregu')->insert(['pendaftaran_id' => $pendaftaran, 'nomor_lomba_id' => $nomor, 'biaya_tim' => 100000]);

        $this->expectException(DatabaseException::class);
        $this->db->table('pendaftaran_beregu')->insert(['pendaftaran_id' => $pendaftaran, 'nomor_lomba_id' => $nomor, 'biaya_tim' => 100000]);
    }

    // ---------------------------------------------------------------
    // Kunci tamu
    // ---------------------------------------------------------------

    public function testHapusPendaftaranIkutMenghapusMiliknya(): void
    {
        $ku     = $this->buatKelompokUmur();
        $nomor  = $this->buatNomorLomba($ku);
        $beregu = $this->buatNomorLomba($ku, ['nama' => 'Formula 1', 'jenis' => 'beregu', 'jumlah_inti' => 4]);

        $hapus = $this->buatPendaftaran();
        $tetap = $this->buatPendaftaran(['npsn' => '99999902', 'nama_sekolah' => 'SD KARANGAN DUA']);

        foreach ([$hapus => '99000000', $tetap => '99100000'] as $pendaftaran => $awalNisn) {
            $this->db->table('penanggung_jawab')->insert([
                'pendaftaran_id' => $pendaftaran,
                'nama'           => 'GURU KARANGAN',
                'nomor_wa'       => '081200000000',
            ]);
            $atlet1 = $this->buatAtlet($pendaftaran, $ku, $awalNisn . '01');
            $atlet2 = $this->buatAtlet($pendaftaran, $ku, $awalNisn . '02');
            $this->db->table('atlet_nomor')->insertBatch([
                ['atlet_id' => $atlet1, 'nomor_lomba_id' => $nomor, 'biaya' => 75000],
                ['atlet_id' => $atlet1, 'nomor_lomba_id' => $beregu, 'biaya' => null],
                ['atlet_id' => $atlet2, 'nomor_lomba_id' => $beregu, 'biaya' => null],
            ]);
            $this->db->table('pendaftaran_beregu')->insert([
                'pendaftaran_id' => $pendaftaran,
                'nomor_lomba_id' => $beregu,
                'biaya_tim'      => 100000,
            ]);
        }

        $this->db->table('pendaftaran')->where('id', $hapus)->delete();

        $this->assertSame(1, $this->jumlah('pendaftaran'));
        $this->assertSame(1, $this->jumlah('penanggung_jawab'));
        $this->assertSame(2, $this->jumlah('atlet'));
        $this->assertSame(3, $this->jumlah('atlet_nomor'));
        $this->assertSame(1, $this->jumlah('pendaftaran_beregu'));
        $this->dontSeeInDatabase('atlet', ['pendaftaran_id' => $hapus]);
        $this->seeInDatabase('atlet', ['pendaftaran_id' => $tetap]);

        // Data induk tidak ikut terhapus.
        $this->assertSame(1, $this->jumlah('kelompok_umur'));
        $this->assertSame(2, $this->jumlah('nomor_lomba'));
    }

    public function testHapusAtletIkutMenghapusNomornya(): void
    {
        $ku          = $this->buatKelompokUmur();
        $nomor       = $this->buatNomorLomba($ku);
        $pendaftaran = $this->buatPendaftaran();
        $atlet       = $this->buatAtlet($pendaftaran, $ku, '9900000001');
        $this->db->table('atlet_nomor')->insert(['atlet_id' => $atlet, 'nomor_lomba_id' => $nomor, 'biaya' => 75000]);

        $this->db->table('atlet')->where('id', $atlet)->delete();

        $this->assertSame(0, $this->jumlah('atlet_nomor'));
    }

    public function testKelompokUmurYangDipakaiAtletTidakBisaDihapus(): void
    {
        $ku = $this->buatKelompokUmur();
        $this->buatAtlet($this->buatPendaftaran(), $ku, '9900000001');

        $this->expectException(DatabaseException::class);
        $this->db->table('kelompok_umur')->where('id', $ku)->delete();
    }

    public function testKelompokUmurYangPunyaNomorLombaTidakBisaDihapus(): void
    {
        $ku = $this->buatKelompokUmur();
        $this->buatNomorLomba($ku);

        $this->expectException(DatabaseException::class);
        $this->db->table('kelompok_umur')->where('id', $ku)->delete();
    }

    public function testKelompokUmurYangPunyaAwalanTidakBisaDihapus(): void
    {
        $ku = $this->buatKelompokUmur();
        $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $ku, 'awalan' => '1']);

        $this->expectException(DatabaseException::class);
        $this->db->table('kelompok_umur')->where('id', $ku)->delete();
    }

    public function testKelompokUmurTanpaPemakaiBisaDihapus(): void
    {
        $ku = $this->buatKelompokUmur();

        $this->db->table('kelompok_umur')->where('id', $ku)->delete();

        $this->assertSame(0, $this->jumlah('kelompok_umur'));
    }

    public function testNomorLombaYangDipakaiAtletTidakBisaDihapus(): void
    {
        $ku    = $this->buatKelompokUmur();
        $nomor = $this->buatNomorLomba($ku);
        $atlet = $this->buatAtlet($this->buatPendaftaran(), $ku, '9900000001');
        $this->db->table('atlet_nomor')->insert(['atlet_id' => $atlet, 'nomor_lomba_id' => $nomor, 'biaya' => 75000]);

        $this->expectException(DatabaseException::class);
        $this->db->table('nomor_lomba')->where('id', $nomor)->delete();
    }

    public function testNomorLombaYangDipakaiTimTidakBisaDihapus(): void
    {
        $ku    = $this->buatKelompokUmur();
        $nomor = $this->buatNomorLomba($ku, ['nama' => 'Formula 1', 'jenis' => 'beregu', 'jumlah_inti' => 4]);
        $this->db->table('pendaftaran_beregu')->insert([
            'pendaftaran_id' => $this->buatPendaftaran(),
            'nomor_lomba_id' => $nomor,
            'biaya_tim'      => 100000,
        ]);

        $this->expectException(DatabaseException::class);
        $this->db->table('nomor_lomba')->where('id', $nomor)->delete();
    }

    public function testAtletDenganPendaftaranYangTidakAdaDitolak(): void
    {
        $ku = $this->buatKelompokUmur();

        $this->expectException(DatabaseException::class);
        $this->buatAtlet(999999, $ku, '9900000001');
    }

    // ---------------------------------------------------------------
    // Nilai awal dan nilai kosong
    // ---------------------------------------------------------------

    public function testNilaiAwalKolom(): void
    {
        $ku = $this->buatKelompokUmur();
        $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $ku, 'awalan' => '1']);
        $nomor       = $this->buatNomorLomba($ku);
        $pendaftaran = $this->buatPendaftaran();
        $this->db->table('jadwal')->insert(['judul' => 'Technical meeting']);
        $this->db->table('unduhan')->insert(['judul' => 'Juknis', 'berkas' => 'juknis.pdf']);
        $this->db->table('tautan')->insert(['jenis' => 'narahubung', 'label' => 'Panitia', 'nilai' => '081200000000']);

        $kelompokUmur = $this->db->table('kelompok_umur')->where('id', $ku)->get()->getRowArray();
        $this->assertSame('0', $kelompokUmur['urutan']);
        $this->assertSame('1', $kelompokUmur['aktif']);

        $awalan = $this->db->table('awalan_nomor_dada')->get()->getRowArray();
        $this->assertSame('0', $awalan['urutan']);
        $this->assertSame('0', $awalan['nomor_terakhir']);

        $nomorLomba = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();
        $this->assertSame('0', $nomorLomba['urutan']);
        $this->assertSame('1', $nomorLomba['aktif']);

        $baris = $this->db->table('pendaftaran')->where('id', $pendaftaran)->get()->getRowArray();
        $this->assertSame('menunggu', $baris['status']);
        $this->assertNull($baris['id_pendaftaran']);
        $this->assertNull($baris['waktu_lunas']);

        foreach (['jadwal', 'unduhan'] as $tabel) {
            $isi = $this->db->table($tabel)->get()->getRowArray();
            $this->assertSame('0', $isi['urutan'], "urutan {$tabel}");
            $this->assertSame('1', $isi['tampil'], "tampil {$tabel}");
        }

        $this->assertSame('0', $this->db->table('tautan')->get()->getRowArray()['urutan']);
    }

    public function testBatasTahunLahirDanBiayaIndividuBolehKosong(): void
    {
        $ku = $this->buatKelompokUmur([
            'tahun_lahir_awal'  => null,
            'tahun_lahir_akhir' => null,
            'biaya_individu'    => null,
        ]);

        $baris = $this->db->table('kelompok_umur')->where('id', $ku)->get()->getRowArray();
        $this->assertNull($baris['tahun_lahir_awal']);
        $this->assertNull($baris['tahun_lahir_akhir']);
        $this->assertNull($baris['biaya_individu']);
    }

    // ---------------------------------------------------------------
    // Uang dan pilihan tetap
    // ---------------------------------------------------------------

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function kolomUangPendaftaran(): iterable
    {
        yield 'subtotal_individu' => ['subtotal_individu'];

        yield 'subtotal_beregu' => ['subtotal_beregu'];

        yield 'total' => ['total'];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('kolomUangPendaftaran')]
    public function testUangPendaftaranMenolakNilaiNegatif(string $kolom): void
    {
        $this->expectException(DatabaseException::class);
        $this->buatPendaftaran([$kolom => -1]);
    }

    public function testBiayaIndividuMenolakNilaiNegatif(): void
    {
        $this->expectException(DatabaseException::class);
        $this->buatKelompokUmur(['biaya_individu' => -50000]);
    }

    public function testBiayaBereguMenolakNilaiNegatif(): void
    {
        $ku = $this->buatKelompokUmur();

        $this->expectException(DatabaseException::class);
        $this->buatNomorLomba($ku, ['jenis' => 'beregu', 'biaya_beregu' => -100000]);
    }

    public function testBiayaAtletNomorMenolakNilaiNegatif(): void
    {
        $ku    = $this->buatKelompokUmur();
        $nomor = $this->buatNomorLomba($ku);
        $atlet = $this->buatAtlet($this->buatPendaftaran(), $ku, '9900000001');

        $this->expectException(DatabaseException::class);
        $this->db->table('atlet_nomor')->insert(['atlet_id' => $atlet, 'nomor_lomba_id' => $nomor, 'biaya' => -75000]);
    }

    public function testBiayaTimMenolakNilaiNegatif(): void
    {
        $ku    = $this->buatKelompokUmur();
        $nomor = $this->buatNomorLomba($ku, ['jenis' => 'beregu']);

        $this->expectException(DatabaseException::class);
        $this->db->table('pendaftaran_beregu')->insert([
            'pendaftaran_id' => $this->buatPendaftaran(),
            'nomor_lomba_id' => $nomor,
            'biaya_tim'      => -100000,
        ]);
    }

    public function testStatusDiLuarPilihanDitolak(): void
    {
        $this->expectException(DatabaseException::class);
        $this->buatPendaftaran(['status' => 'dibatalkan']);
    }

    public function testJenisKelaminDiLuarPilihanDitolak(): void
    {
        $ku = $this->buatKelompokUmur();

        $this->expectException(DatabaseException::class);
        $this->db->table('atlet')->insert([
            'pendaftaran_id'   => $this->buatPendaftaran(),
            'nama'             => 'ATLET KARANGAN',
            'nisn'             => '9900000001',
            'tanggal_lahir'    => '2016-05-17',
            'jenis_kelamin'    => 'X',
            'kelompok_umur_id' => $ku,
        ]);
    }

    public function testNisnLebihDariSepuluhKarakterDitolak(): void
    {
        $ku = $this->buatKelompokUmur();

        $this->expectException(DatabaseException::class);
        $this->buatAtlet($this->buatPendaftaran(), $ku, '99000000011');
    }
}
