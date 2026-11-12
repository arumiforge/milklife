<?php

use Tests\Support\UjiBasisData;

/**
 * Struktur tabel domain dari migrasi F1-02: tabel, kolom, tipe, kunci tamu,
 * indeks, dan rollback.
 *
 * @internal
 */
final class MigrasiStrukturTest extends UjiBasisData
{
    /**
     * Kolom yang diharapkan per tabel: [tipe kolom, boleh kosong, nilai awal].
     *
     * @var array<string, array<string, array{0: string, 1: bool, 2: string|null}>>
     */
    private const KOLOM = [
        'kelompok_umur' => [
            'id'                => ['int unsigned', false, null],
            'nama'              => ['varchar(50)', false, null],
            // Batas tahun lahir (K36, migrasi 2026-11-12-000200) menggantikan batas usia.
            'tahun_lahir_awal'  => ['smallint unsigned', true, null],
            'tahun_lahir_akhir' => ['smallint unsigned', true, null],
            'biaya_individu'    => ['int unsigned', true, null],
            'urutan'            => ['smallint unsigned', false, '0'],
            'aktif'             => ['tinyint unsigned', false, '1'],
            'dibuat_pada'       => ['datetime', true, null],
            'diubah_pada'       => ['datetime', true, null],
        ],
        'awalan_nomor_dada' => [
            'id'               => ['int unsigned', false, null],
            'kelompok_umur_id' => ['int unsigned', false, null],
            'awalan'           => ['char(1)', false, null],
            'urutan'           => ['smallint unsigned', false, '0'],
            'nomor_terakhir'   => ['smallint unsigned', false, '0'],
            'dibuat_pada'      => ['datetime', true, null],
            'diubah_pada'      => ['datetime', true, null],
        ],
        'nomor_lomba' => [
            'id'               => ['int unsigned', false, null],
            'kelompok_umur_id' => ['int unsigned', false, null],
            'nama'             => ['varchar(100)', false, null],
            'jenis'            => ["enum('individu','beregu')", false, null],
            'kategori'         => ["enum('putra','putri','mix')", false, null],
            'jumlah_inti'      => ['tinyint unsigned', true, null],
            'batas_anggota'    => ['tinyint unsigned', true, null],
            'jumlah_putra'     => ['tinyint unsigned', true, null],
            'jumlah_putri'     => ['tinyint unsigned', true, null],
            'biaya_beregu'     => ['int unsigned', true, null],
            'urutan'           => ['smallint unsigned', false, '0'],
            'aktif'            => ['tinyint unsigned', false, '1'],
            'dibuat_pada'      => ['datetime', true, null],
            'diubah_pada'      => ['datetime', true, null],
        ],
        'pendaftaran' => [
            'id'                => ['int unsigned', false, null],
            'id_pendaftaran'    => ['varchar(20)', true, null],
            'kode_bukti'        => ['char(24)', true, null],
            'jenjang'           => ["enum('A','B','C')", false, null],
            'npsn'              => ['char(8)', true, null],
            'nama_sekolah'      => ['varchar(150)', false, null],
            'kabupaten'         => ['varchar(50)', false, null],
            'subtotal_individu' => ['int unsigned', false, null],
            'subtotal_beregu'   => ['int unsigned', false, null],
            'total'             => ['int unsigned', false, null],
            'status'            => ["enum('menunggu','lunas')", false, 'menunggu'],
            'waktu_lunas'       => ['datetime', true, null],
            'dibuat_pada'       => ['datetime', true, null],
            'diubah_pada'       => ['datetime', true, null],
        ],
        'penanggung_jawab' => [
            'id'             => ['int unsigned', false, null],
            'pendaftaran_id' => ['int unsigned', false, null],
            'nama'           => ['varchar(100)', false, null],
            'nomor_wa'       => ['varchar(20)', false, null],
            'foto'           => ['varchar(255)', true, null],
            'dibuat_pada'    => ['datetime', true, null],
            'diubah_pada'    => ['datetime', true, null],
        ],
        'atlet' => [
            'id'               => ['int unsigned', false, null],
            'pendaftaran_id'   => ['int unsigned', false, null],
            'nama'             => ['varchar(100)', false, null],
            'nisn'             => ['char(10)', false, null],
            'tanggal_lahir'    => ['date', false, null],
            'jenis_kelamin'    => ["enum('L','P')", false, null],
            'kelompok_umur_id' => ['int unsigned', false, null],
            'nomor_dada'       => ['char(4)', true, null],
            'dibuat_pada'      => ['datetime', true, null],
            'diubah_pada'      => ['datetime', true, null],
        ],
        'atlet_nomor' => [
            'id'             => ['int unsigned', false, null],
            'atlet_id'       => ['int unsigned', false, null],
            'nomor_lomba_id' => ['int unsigned', false, null],
            'biaya'          => ['int unsigned', true, null],
            'dibuat_pada'    => ['datetime', true, null],
            'diubah_pada'    => ['datetime', true, null],
        ],
        'pendaftaran_beregu' => [
            'id'             => ['int unsigned', false, null],
            'pendaftaran_id' => ['int unsigned', false, null],
            'nomor_lomba_id' => ['int unsigned', false, null],
            'biaya_tim'      => ['int unsigned', false, null],
            'dibuat_pada'    => ['datetime', true, null],
            'diubah_pada'    => ['datetime', true, null],
        ],
        'jadwal' => [
            'id'          => ['int unsigned', false, null],
            'judul'       => ['varchar(150)', false, null],
            'keterangan'  => ['text', true, null],
            'waktu'       => ['datetime', true, null],
            'tempat'      => ['varchar(150)', true, null],
            'tautan'      => ['varchar(255)', true, null],
            'urutan'      => ['smallint unsigned', false, '0'],
            'tampil'      => ['tinyint unsigned', false, '1'],
            'dibuat_pada' => ['datetime', true, null],
            'diubah_pada' => ['datetime', true, null],
        ],
        'unduhan' => [
            'id'          => ['int unsigned', false, null],
            'judul'       => ['varchar(150)', false, null],
            'keterangan'  => ['text', true, null],
            'berkas'      => ['varchar(255)', false, null],
            'urutan'      => ['smallint unsigned', false, '0'],
            'tampil'      => ['tinyint unsigned', false, '1'],
            'dibuat_pada' => ['datetime', true, null],
            'diubah_pada' => ['datetime', true, null],
        ],
        // Tahap 6 (F6-01): bukti bayar yang diunggah admin (K34).
        'bukti_bayar' => [
            'id'             => ['int unsigned', false, null],
            'pendaftaran_id' => ['int unsigned', false, null],
            'berkas'         => ['varchar(255)', false, null],
            'ukuran'         => ['int unsigned', false, null],
            'waktu_unggah'   => ['datetime', true, null],
        ],
        'tautan' => [
            'id'          => ['int unsigned', false, null],
            'jenis'       => ["enum('narahubung','media_sosial')", false, null],
            'label'       => ['varchar(100)', false, null],
            'nilai'       => ['varchar(255)', false, null],
            'urutan'      => ['smallint unsigned', false, '0'],
            'dibuat_pada' => ['datetime', true, null],
            'diubah_pada' => ['datetime', true, null],
        ],
    ];

    /**
     * Kunci tamu yang diharapkan: "tabel.kolom" => [tabel rujukan, aturan hapus].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const KUNCI_TAMU = [
        'awalan_nomor_dada.kelompok_umur_id' => ['kelompok_umur', 'RESTRICT'],
        'nomor_lomba.kelompok_umur_id'       => ['kelompok_umur', 'RESTRICT'],
        'penanggung_jawab.pendaftaran_id'    => ['pendaftaran', 'CASCADE'],
        'atlet.pendaftaran_id'               => ['pendaftaran', 'CASCADE'],
        'atlet.kelompok_umur_id'             => ['kelompok_umur', 'RESTRICT'],
        'atlet_nomor.atlet_id'               => ['atlet', 'CASCADE'],
        'atlet_nomor.nomor_lomba_id'         => ['nomor_lomba', 'RESTRICT'],
        'pendaftaran_beregu.pendaftaran_id'  => ['pendaftaran', 'CASCADE'],
        'pendaftaran_beregu.nomor_lomba_id'  => ['nomor_lomba', 'RESTRICT'],
        // Tahap 2 (F2-01): jenjang ikut terhapus bersama kelompok umurnya.
        'kelompok_umur_jenjang.kelompok_umur_id' => ['kelompok_umur', 'CASCADE'],
        // Tahap 6 (F6-01): bukti bayar ikut terhapus bersama pendaftarannya.
        'bukti_bayar.pendaftaran_id' => ['pendaftaran', 'CASCADE'],
    ];

    public function testSemuaTabelDomainAdaDenganInnoDBDanUtf8mb4(): void
    {
        $baris = $this->db->query(
            'SELECT TABLE_NAME AS tabel, ENGINE AS mesin, TABLE_COLLATION AS kolasi
             FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?',
            [$this->db->getDatabase()],
        )->getResultArray();
        $tabel = array_column($baris, null, 'tabel');

        foreach (array_keys(self::KOLOM) as $nama) {
            $this->assertArrayHasKey($nama, $tabel, "Tabel {$nama} tidak ada.");
            $this->assertSame('InnoDB', $tabel[$nama]['mesin'], "Mesin tabel {$nama}.");
            $this->assertSame('utf8mb4_unicode_ci', $tabel[$nama]['kolasi'], "Kolasi tabel {$nama}.");
        }
    }

    public function testMigrasiSemuaMembuatTabelSettings(): void
    {
        $this->assertTrue($this->db->tableExists('settings'));
    }

    public function testKolomSesuaiRancangan(): void
    {
        foreach (self::KOLOM as $tabel => $harapan) {
            $baris = $this->db->query(
                'SELECT COLUMN_NAME AS kolom, COLUMN_TYPE AS tipe, IS_NULLABLE AS kosong,
                        COLUMN_DEFAULT AS awal, EXTRA AS ekstra
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION',
                [$this->db->getDatabase(), $tabel],
            )->getResultArray();
            $kolom = array_column($baris, null, 'kolom');

            $this->assertSame(array_keys($harapan), array_keys($kolom), "Daftar kolom tabel {$tabel}.");

            foreach ($harapan as $nama => [$tipe, $bolehKosong, $nilaiAwal]) {
                $info = $kolom[$nama];
                $this->assertSame($tipe, $info['tipe'], "Tipe {$tabel}.{$nama}.");
                $this->assertSame($bolehKosong ? 'YES' : 'NO', $info['kosong'], "Boleh kosong {$tabel}.{$nama}.");
                $this->assertSame($nilaiAwal, $info['awal'], "Nilai awal {$tabel}.{$nama}.");
            }

            $this->assertSame('auto_increment', $kolom['id']['ekstra'], "Kolom id tabel {$tabel} naik otomatis.");
        }
    }

    public function testTidakAdaKolomDesimal(): void
    {
        $jumlah = $this->db->query(
            "SELECT COUNT(*) AS jumlah FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ('decimal', 'float', 'double')",
            [$this->db->getDatabase()],
        )->getRow()->jumlah;

        $this->assertSame(0, (int) $jumlah);
    }

    public function testKunciTamuDanAturanHapus(): void
    {
        $baris = $this->db->query(
            'SELECT k.TABLE_NAME AS tabel, k.COLUMN_NAME AS kolom,
                    k.REFERENCED_TABLE_NAME AS rujukan, k.REFERENCED_COLUMN_NAME AS kolom_rujukan,
                    r.DELETE_RULE AS aturan_hapus
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            [$this->db->getDatabase()],
        )->getResultArray();

        $ada = [];

        foreach ($baris as $b) {
            $ada[$b['tabel'] . '.' . $b['kolom']] = [$b['rujukan'], $b['aturan_hapus']];
            $this->assertSame('id', $b['kolom_rujukan']);
        }

        ksort($ada);
        $harapan = self::KUNCI_TAMU;
        ksort($harapan);

        // Termasuk memastikan pendaftaran.npsn bukan kunci tamu.
        $this->assertSame($harapan, $ada);
    }

    public function testIndeksDanKunciUnik(): void
    {
        $baris = $this->db->query(
            'SELECT TABLE_NAME AS tabel, INDEX_NAME AS indeks, NON_UNIQUE AS tidak_unik,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS kolom
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND INDEX_NAME <> \'PRIMARY\'
             GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE',
            [$this->db->getDatabase()],
        )->getResultArray();

        $unik      = [];
        $tidakUnik = [];

        foreach ($baris as $b) {
            $kunci = $b['tabel'] . ':' . $b['kolom'];

            if ((int) $b['tidak_unik'] === 0) {
                $unik[] = $kunci;
            } else {
                $tidakUnik[] = $kunci;
            }
        }

        foreach ([
            'awalan_nomor_dada:awalan',
            'pendaftaran:id_pendaftaran',
            'atlet:nisn',
            'atlet:nomor_dada',
            'atlet_nomor:atlet_id,nomor_lomba_id',
            'pendaftaran_beregu:pendaftaran_id,nomor_lomba_id',
        ] as $harapan) {
            $this->assertContains($harapan, $unik, "Kunci unik {$harapan}.");
        }

        foreach (['pendaftaran:npsn', 'pendaftaran:status', 'atlet:nama'] as $harapan) {
            $this->assertContains($harapan, $tidakUnik, "Indeks {$harapan}.");
        }
    }

    public function testRollbackPenuhMenghapusSemuaTabelDomain(): void
    {
        $this->regressDatabase();

        foreach (array_keys(self::KOLOM) as $tabel) {
            $this->assertFalse($this->db->tableExists($tabel, false), "Tabel {$tabel} masih ada setelah rollback.");
        }

        $this->assertFalse($this->db->tableExists('settings', false));

        // Migrasi ulang setelah rollback tetap bisa berjalan.
        $this->migrateDatabase();

        foreach (array_keys(self::KOLOM) as $tabel) {
            $this->assertTrue($this->db->tableExists($tabel, false), "Tabel {$tabel} tidak dibuat ulang.");
        }
    }
}
