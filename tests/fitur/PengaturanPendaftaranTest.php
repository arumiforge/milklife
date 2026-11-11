<?php

use CodeIgniter\I18n\Time;
use Config\Pendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman pengaturan Pendaftaran (`/admin/pengaturan/pendaftaran`, F2-02).
 *
 * Waktu "sekarang" dibekukan pada 11 November 2026 pukul 10.00 WIB supaya
 * pemeriksaan batas buka manual tidak bergantung pada jam komputer.
 *
 * @internal
 */
final class PengaturanPendaftaranTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/pendaftaran';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-11-11 10:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    /**
     * Isian benar seperti flyer Seri 2, dengan kotak `datetime-local`.
     *
     * @return array<string, string>
     */
    private static function isianBenar(): array
    {
        return [
            'mulai'                     => '2026-10-01T00:00',
            'akhir'                     => '2026-11-30T23:59',
            'buka_manual_sampai'        => '',
            'tampilan_usia'             => 'tahun',
            'penanggung_jawab_minimal'  => '1',
            'penanggung_jawab_maksimal' => '5',
            'nomor_individu_maksimal'   => '2',
        ];
    }

    // ---------- Kunci pengaturan ----------

    public function testTanggalPatokanUsiaSudahDihapus(): void
    {
        // K37: usia hanya keterangan, dihitung pada tanggal mulai acara.
        $this->assertFalse(property_exists(Pendaftaran::class, 'tanggal_patokan_usia'));
    }

    // ---------- Halaman ----------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
    }

    public function testFormulirBerisiNilaiAwal(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Pendaftaran', trim($xpath->query('//header//h1')->item(0)?->textContent ?? ''));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Pendaftaran")]')->length);

        // Waktu memakai kotak tanggal dan jam peramban, kosong sebelum diisi.
        foreach (['mulai', 'akhir', 'buka_manual_sampai'] as $kotak) {
            $masukan = $xpath->query('//input[@name="' . $kotak . '"]')->item(0);
            $this->assertNotNull($masukan, $kotak);
            $this->assertSame('datetime-local', $masukan->getAttribute('type'), $kotak);
            $this->assertSame('', $masukan->getAttribute('value'), $kotak);
        }

        // Nilai awal dari `Config\Pendaftaran`.
        $this->assertSame('1', $this->nilai($xpath, 'penanggung_jawab_minimal'));
        $this->assertSame('5', $this->nilai($xpath, 'penanggung_jawab_maksimal'));
        $this->assertSame('2', $this->nilai($xpath, 'nomor_individu_maksimal'));
        $this->assertSame(0, $xpath->query('//input[@name="buka_manual"][@checked]')->length);
        $this->assertSame(['tahun'], $this->nilaiTercentang($xpath, 'tampilan_usia'));
        $this->assertSame(2, $xpath->query('//input[@type="radio"][@name="tampilan_usia"]')->length);

        // Tanpa tanggal patokan usia (K37); keterangan usia menyebut tanggal mulai acara.
        $this->assertSame(0, $xpath->query('//input[contains(@name, "patokan")]')->length);
        $hasil->assertDontSee('patokan');
        $hasil->assertSee('dihitung pada tanggal mulai acara');
        $hasil->assertSee('belum diisi di');
        $this->assertSame(1, $xpath->query('//*[@id="tampilan_usia-keterangan"]//a[@href="' . site_url('admin/pengaturan/acara') . '"]')->length);
    }

    public function testKeteranganUsiaMenyebutTanggalMulaiAcara(): void
    {
        setting('Acara.tanggal_mulai', '2026-12-09');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertSee('dihitung pada tanggal mulai acara');
        $hasil->assertSee('(9 Desember 2026)');
    }

    public function testFormulirBerisiNilaiTersimpan(): void
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, [
            'buka_manual'        => '1',
            'buka_manual_sampai' => '2026-12-03T12:00',
            'tampilan_usia'      => 'tahun_bulan',
        ] + self::isianBenar());

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));

        $this->assertSame('2026-10-01T00:00', $this->nilai($xpath, 'mulai'));
        $this->assertSame('2026-11-30T23:59', $this->nilai($xpath, 'akhir'));
        $this->assertSame('2026-12-03T12:00', $this->nilai($xpath, 'buka_manual_sampai'));
        $this->assertSame(1, $xpath->query('//input[@name="buka_manual"][@checked]')->length);
        $this->assertSame(['tahun_bulan'], $this->nilaiTercentang($xpath, 'tampilan_usia'));
    }

    public function testSimpanBerhasil(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'penanggung_jawab_minimal'  => '2',
            'penanggung_jawab_maksimal' => '4',
            'nomor_individu_maksimal'   => '3',
            'tampilan_usia'             => 'tahun_bulan',
        ] + self::isianBenar());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Pengaturan pendaftaran tersimpan.');

        // Waktu disimpan `Y-m-d H:i:s` (WIB), teliti sampai menit.
        $this->assertSame('2026-10-01 00:00:00', setting('Pendaftaran.mulai'));
        $this->assertSame('2026-11-30 23:59:00', setting('Pendaftaran.akhir'));
        $this->assertFalse(setting('Pendaftaran.buka_manual'));
        $this->assertNull(setting('Pendaftaran.buka_manual_sampai'));
        $this->assertSame('tahun_bulan', setting('Pendaftaran.tampilan_usia'));
        $this->assertSame(2, setting('Pendaftaran.penanggung_jawab_minimal'));
        $this->assertSame(4, setting('Pendaftaran.penanggung_jawab_maksimal'));
        $this->assertSame(3, setting('Pendaftaran.nomor_individu_maksimal'));
    }

    public function testDetikDibuang(): void
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, ['mulai' => '2026-10-01T08:15:42'] + self::isianBenar());

        $this->assertSame('2026-10-01 08:15:00', setting('Pendaftaran.mulai'));
    }

    public function testBukaManual(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'buka_manual'        => '1',
            'buka_manual_sampai' => '2026-12-03T12:00',
        ] + self::isianBenar());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Pengaturan pendaftaran tersimpan.');
        $this->assertTrue(setting('Pendaftaran.buka_manual'));
        $this->assertSame('2026-12-03 12:00:00', setting('Pendaftaran.buka_manual_sampai'));

        // Centang dihilangkan: kembali mengikuti jadwal; batas yang terisi tetap disimpan.
        $this->kirimSebagaiAdmin(self::ALAMAT, ['buka_manual_sampai' => '2026-12-03T12:00'] + self::isianBenar());
        $this->assertFalse(setting('Pendaftaran.buka_manual'));
        $this->assertSame('2026-12-03 12:00:00', setting('Pendaftaran.buka_manual_sampai'));
    }

    public function testBukaManualTanpaBatasDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['buka_manual' => '1'] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['buka_manual_sampai']);
        $hasil->assertSee('Batas buka manual belum diisi.');
        $this->assertFalse(setting('Pendaftaran.buka_manual'));

        // Centang tetap seperti yang tadi dipilih.
        $this->assertSame(1, $this->xpathDari($hasil)->query('//input[@name="buka_manual"][@checked]')->length);
    }

    public function testBatasBukaManualYangSudahLewatDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'buka_manual'        => '1',
            'buka_manual_sampai' => '2026-11-11T10:00',
        ] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['buka_manual_sampai']);
        $hasil->assertSee('Batas buka manual sudah lewat.');
    }

    public function testIsianKosongDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, array_fill_keys(array_keys(self::isianBenar()), ''));

        $this->assertIsianSalahDitampilkanUlang($hasil, [
            'mulai', 'akhir', 'tampilan_usia',
            'penanggung_jawab_minimal', 'penanggung_jawab_maksimal', 'nomor_individu_maksimal',
        ]);
        $hasil->assertSee('Waktu buka belum diisi.');
        $hasil->assertSee('Waktu tutup belum diisi.');
        $hasil->assertSee('Tampilan usia belum dipilih.');
        $this->assertNull(setting('Pendaftaran.mulai'));
    }

    public function testWaktuTutupHarusSetelahWaktuBuka(): void
    {
        foreach (['2026-10-01T00:00', '2026-09-30T23:59'] as $akhir) {
            $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['akhir' => $akhir] + self::isianBenar());

            $this->assertIsianSalahDitampilkanUlang($hasil, ['akhir']);
            $hasil->assertSee('Waktu tutup harus setelah waktu buka (1 Oktober 2026 pukul 00.00).');
        }

        $this->assertNull(setting('Pendaftaran.akhir'));
    }

    public function testWaktuTidakSahDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'mulai'              => '2026-02-30T08:00',
            'akhir'              => 'besok',
            'buka_manual_sampai' => '2026-12-03T25:00',
        ] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['mulai', 'akhir', 'buka_manual_sampai']);
        $hasil->assertSee('Waktu buka belum benar.');
        $hasil->assertSee('Waktu tutup belum benar.');
        $hasil->assertSee('Batas buka manual belum benar.');

        // Isian yang tadi diketik tetap ada.
        $this->assertSame('besok', $this->nilai($this->xpathDari($hasil), 'akhir'));
    }

    public function testTampilanUsiaHanyaDariPilihan(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['tampilan_usia' => 'bulan'] + self::isianBenar());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['tampilan_usia']);
        $this->assertSame('tahun', setting('Pendaftaran.tampilan_usia'));
    }

    public function testBatasPenanggungJawab(): void
    {
        // Paling sedikit 1.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['penanggung_jawab_minimal' => '0'] + self::isianBenar());
        $this->assertIsianSalahDitampilkanUlang($hasil, ['penanggung_jawab_minimal']);
        $hasil->assertSee('Jumlah penanggung jawab paling sedikit harus angka 1 sampai 20');

        // Paling banyak tidak kurang dari paling sedikit.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'penanggung_jawab_minimal'  => '3',
            'penanggung_jawab_maksimal' => '2',
        ] + self::isianBenar());
        $this->assertIsianSalahDitampilkanUlang($hasil, ['penanggung_jawab_maksimal']);
        $hasil->assertSee('Jumlah paling banyak tidak boleh lebih kecil dari jumlah paling sedikit (3).');

        // Bukan angka bulat, atau terlalu besar.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'penanggung_jawab_minimal'  => '1,5',
            'penanggung_jawab_maksimal' => '21',
        ] + self::isianBenar());
        $this->assertIsianSalahDitampilkanUlang($hasil, ['penanggung_jawab_minimal', 'penanggung_jawab_maksimal']);

        // Sama besar boleh.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'penanggung_jawab_minimal'  => '3',
            'penanggung_jawab_maksimal' => '3',
        ] + self::isianBenar());
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->assertSame(3, setting('Pendaftaran.penanggung_jawab_maksimal'));
    }

    public function testBatasNomorIndividu(): void
    {
        foreach (['0', '11', '-1', 'dua'] as $nilai) {
            $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['nomor_individu_maksimal' => $nilai] + self::isianBenar());

            $this->assertIsianSalahDitampilkanUlang($hasil, ['nomor_individu_maksimal']);
            $hasil->assertSee('Batas nomor individu harus angka 1 sampai 10, misalnya 2.');
        }

        $this->assertSame(2, setting('Pendaftaran.nomor_individu_maksimal'));
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, self::isianBenar());
        $this->assertNull(setting('Pendaftaran.mulai'));
    }

    // ---------- Bantuan ----------

    private function nilai(DOMXPath $xpath, string $kotak): ?string
    {
        return $xpath->query('//input[@name="' . $kotak . '"]')->item(0)?->getAttribute('value');
    }

    /**
     * @return list<string>
     */
    private function nilaiTercentang(DOMXPath $xpath, string $kotak): array
    {
        $nilai = [];

        foreach ($xpath->query('//input[@name="' . $kotak . '"][@checked]') as $masukan) {
            $nilai[] = $masukan->getAttribute('value');
        }

        return $nilai;
    }
}
