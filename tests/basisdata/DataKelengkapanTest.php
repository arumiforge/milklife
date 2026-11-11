<?php

use App\Libraries\BukaTutupPendaftaran;
use App\Libraries\DataKelengkapan;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\UjiBasisData;

/**
 * Pengumpul potret kelengkapan dari pengaturan dan tabel (F2-09), pada basis
 * data kosong, setelah `data:awal`, dan setelah semua halaman diisi.
 *
 * Kolom `tahun_lahir_awal`/`tahun_lahir_akhir` (K36) dibuat migrasi F2-03 yang
 * dikerjakan bersamaan; selama migrasi itu belum ada, uji ini menambahkannya
 * sendiri di persiapan.
 *
 * @internal
 */
final class DataKelengkapanTest extends UjiBasisData
{
    use StreamFilterTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::siapkanKolomTahunLahir($this->db);
    }

    public function testBasisDataKosongSemuaBelumLengkap(): void
    {
        $data        = new DataKelengkapan();
        $kelengkapan = $data->kelengkapan();

        $this->assertFalse($kelengkapan->lengkap());
        $this->assertSame(0, $kelengkapan->jumlahLengkap());
        $this->assertSame(
            ['acara' => false, 'pendaftaran' => false, 'kelompok-umur' => false, 'nomor-lomba' => false, 'pembayaran' => false],
            array_column($kelengkapan->butir(), 'lengkap', 'kunci'),
        );
        $this->assertSame([], $data->potret()['kelompok_umur']);
        $this->assertSame([], $data->potret()['nomor_lomba']);

        $this->assertSame(BukaTutupPendaftaran::DISIAPKAN, $data->bukaTutup(self::wib('2026-11-11 10:00:00'))->keadaan());
    }

    public function testSetelahDataAwalSebagianLengkap(): void
    {
        $this->jalankanDataAwal();

        $kelengkapan = (new DataKelengkapan())->kelengkapan();

        $this->assertSame(
            ['acara' => true, 'pendaftaran' => true, 'kelompok-umur' => false, 'nomor-lomba' => false, 'pembayaran' => false],
            array_column($kelengkapan->butir(), 'lengkap', 'kunci'),
        );

        // Awalan nomor dada sudah diisi data awal (K33), jenjang dan biaya belum.
        $ku = $kelengkapan->untukHalaman('kelompok-umur')['keterangan'];
        $this->assertStringContainsString('KU 8 (Seneng Playon), KU 10, KU 12, KU 15, KU 18 belum punya jenjang sekolah.', $ku);
        $this->assertStringContainsString('KU 8 (Seneng Playon), KU 10, KU 12, KU 15, KU 18 belum punya biaya individu.', $ku);
        $this->assertStringNotContainsString('awalan', $ku);

        // Nomor beregu dari data awal sudah punya jumlah inti dan biaya, belum batas anggota.
        $this->assertSame(
            "Kanga's Escape putra (KU 10), Kanga's Escape putri (KU 10), Formula 1 putra (KU 10), dan 8 nomor lainnya belum punya batas anggota. "
            . 'Estafet 8 × 50 m mix (KU 10), Estafet 8 × 50 m mix (KU 12), Estafet 4 × 400 m mix (KU 18) belum punya jumlah putra dan putri.',
            $kelengkapan->untukHalaman('nomor-lomba')['keterangan'],
        );
    }

    public function testSetelahSemuaDiisiLengkapDanKeadaanSesuaiWaktu(): void
    {
        $this->jalankanDataAwal();
        self::lengkapiSemua($this->db);

        $data = new DataKelengkapan();

        $this->assertTrue($data->kelengkapan()->lengkap(), implode(' ', array_column($data->kelengkapan()->belumLengkap(), 'keterangan')));

        $this->assertSame(BukaTutupPendaftaran::BELUM_DIBUKA, $data->bukaTutup(self::wib('2026-09-30 23:59:59'))->keadaan());
        $this->assertSame(BukaTutupPendaftaran::TERBUKA, $data->bukaTutup(self::wib('2026-10-01 00:00:00'))->keadaan());
        $this->assertSame(BukaTutupPendaftaran::TERBUKA, $data->bukaTutup(self::wib('2026-11-30 23:59:59'))->keadaan());
        $this->assertSame(BukaTutupPendaftaran::DITUTUP, $data->bukaTutup(self::wib('2026-12-01 00:00:00'))->keadaan());
    }

    public function testBukaManualDibacaDariPengaturan(): void
    {
        $this->jalankanDataAwal();
        self::lengkapiSemua($this->db);
        setting('Pendaftaran.buka_manual', true);
        setting('Pendaftaran.buka_manual_sampai', '2026-12-03 12:00:00');

        $data    = new DataKelengkapan();
        $terbuka = $data->bukaTutup(self::wib('2026-12-02 08:00:00'));

        $this->assertTrue($terbuka->terbuka());
        $this->assertTrue($terbuka->lewatManual());
        $this->assertSame('Pendaftaran dibuka sampai 3 Desember 2026 pukul 12.00.', $terbuka->kalimat());
        $this->assertSame(BukaTutupPendaftaran::DITUTUP, $data->bukaTutup(self::wib('2026-12-03 12:01:00'))->keadaan());
    }

    public function testPotretMembacaJenjangAwalanDanUrutan(): void
    {
        $this->db->table('kelompok_umur')->insertBatch([
            ['id' => 1, 'nama' => 'KU Kedua', 'urutan' => 2, 'aktif' => 1, 'biaya_individu' => 50000, 'tahun_lahir_awal' => 2014],
            ['id' => 2, 'nama' => 'KU Pertama', 'urutan' => 1, 'aktif' => 0, 'biaya_individu' => null, 'tahun_lahir_awal' => null],
        ]);
        $this->db->table('kelompok_umur_jenjang')->insertBatch([
            ['kelompok_umur_id' => 1, 'jenjang' => 'C'],
            ['kelompok_umur_id' => 1, 'jenjang' => 'B'],
        ]);
        $this->db->table('awalan_nomor_dada')->insertBatch([
            ['kelompok_umur_id' => 1, 'awalan' => '7', 'urutan' => 2],
            ['kelompok_umur_id' => 1, 'awalan' => '4', 'urutan' => 1],
        ]);
        $this->db->table('nomor_lomba')->insertBatch([
            ['kelompok_umur_id' => 1, 'nama' => 'Nomor B', 'jenis' => 'individu', 'kategori' => 'putra', 'urutan' => 1],
            ['kelompok_umur_id' => 2, 'nama' => 'Nomor A', 'jenis' => 'individu', 'kategori' => 'putri', 'urutan' => 5],
        ]);

        $potret = (new DataKelengkapan())->potret();

        $this->assertSame(['KU Pertama', 'KU Kedua'], array_column($potret['kelompok_umur'], 'nama'));
        $this->assertSame(0, $potret['kelompok_umur'][0]['aktif']);
        $this->assertSame([], $potret['kelompok_umur'][0]['jenjang']);
        $this->assertEqualsCanonicalizing(['B', 'C'], $potret['kelompok_umur'][1]['jenjang']);
        $this->assertSame(['4', '7'], $potret['kelompok_umur'][1]['awalan']);
        $this->assertSame('2014', (string) $potret['kelompok_umur'][1]['tahun_lahir_awal']);
        $this->assertNull($potret['kelompok_umur'][1]['tahun_lahir_akhir']);
        // Nomor lomba berurutan menurut kelompok umurnya.
        $this->assertSame(['Nomor A', 'Nomor B'], array_column($potret['nomor_lomba'], 'nama'));
    }

    public function testTabelTakTerbacaDianggapKosong(): void
    {
        $this->jalankanDataAwal();
        $this->db->query('RENAME TABLE nomor_lomba TO nomor_lomba_sementara');

        try {
            $kelengkapan = (new DataKelengkapan())->kelengkapan();
        } finally {
            $this->db->query('RENAME TABLE nomor_lomba_sementara TO nomor_lomba');
        }

        $this->assertFalse($kelengkapan->untukHalaman('kelompok-umur')['lengkap']);
        $this->assertSame('Belum ada kelompok umur. Tambahkan minimal satu kelompok umur.', $kelengkapan->untukHalaman('kelompok-umur')['keterangan']);
        // Pengaturan tetap terbaca.
        $this->assertTrue($kelengkapan->untukHalaman('acara')['lengkap']);
    }

    // ---------- Bantuan (salinannya ada di uji fitur dasbor) ----------

    /**
     * Tambahkan kolom batas tahun lahir bila migrasi F2-03 belum ada.
     * `SHOW COLUMNS` dipakai karena `fieldExists()` menyimpan hasil lama.
     */
    private static function siapkanKolomTahunLahir(CodeIgniter\Database\BaseConnection $db): void
    {
        if ($db->query("SHOW COLUMNS FROM kelompok_umur LIKE 'tahun_lahir_awal'")->getNumRows() === 0) {
            $db->query('ALTER TABLE kelompok_umur ADD tahun_lahir_awal SMALLINT UNSIGNED NULL AFTER nama, ADD tahun_lahir_akhir SMALLINT UNSIGNED NULL AFTER tahun_lahir_awal');
        }
    }

    /**
     * Isi semua yang dikosongkan `data:awal`, seperti admin mengisi semua halaman.
     */
    private static function lengkapiSemua(CodeIgniter\Database\BaseConnection $db): void
    {
        $db->query('UPDATE kelompok_umur SET tahun_lahir_awal = COALESCE(tahun_lahir_awal, 2000 + id), biaya_individu = 50000');
        $db->query("UPDATE nomor_lomba SET batas_anggota = jumlah_inti + 2 WHERE jenis = 'beregu'");
        $db->query("UPDATE nomor_lomba SET jumlah_putra = jumlah_inti DIV 2, jumlah_putri = jumlah_inti DIV 2 WHERE kategori = 'mix'");

        foreach ($db->table('kelompok_umur')->select('id')->get()->getResultArray() as $ku) {
            $db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku['id'], 'jenjang' => 'A']);
        }

        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '1234567890');
        setting('Pembayaran.pemilik_rekening', 'Panitia Karangan');
        setting('Pembayaran.nomor_wa', '6281234567890');
    }

    private function jalankanDataAwal(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));
    }

    private static function wib(string $teks): DateTimeImmutable
    {
        return new DateTimeImmutable($teks, new DateTimeZone('Asia/Jakarta'));
    }
}
