<?php

use CodeIgniter\Database\Exceptions\DatabaseException;
use Tests\Support\UjiBasisData;

/**
 * Migrasi Tahap 2: tabel `kelompok_umur_jenjang` (F2-01) dan batas kelompok
 * umur menurut tahun lahir (F2-03, K36).
 *
 * @internal
 */
final class MigrasiTahap2Test extends UjiBasisData
{
    public function testStrukturTabelKelompokUmurJenjang(): void
    {
        $tabel = $this->db->query(
            'SELECT ENGINE AS mesin, TABLE_COLLATION AS kolasi FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->db->getDatabase(), 'kelompok_umur_jenjang'],
        )->getRowArray();

        $this->assertSame(['mesin' => 'InnoDB', 'kolasi' => 'utf8mb4_unicode_ci'], $tabel);

        $kolom = $this->db->query(
            'SELECT COLUMN_NAME AS nama, COLUMN_TYPE AS tipe, IS_NULLABLE AS kosong, EXTRA AS ekstra
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$this->db->getDatabase(), 'kelompok_umur_jenjang'],
        )->getResultArray();

        $this->assertSame([
            ['nama' => 'id', 'tipe' => 'int unsigned', 'kosong' => 'NO', 'ekstra' => 'auto_increment'],
            ['nama' => 'kelompok_umur_id', 'tipe' => 'int unsigned', 'kosong' => 'NO', 'ekstra' => ''],
            ['nama' => 'jenjang', 'tipe' => "enum('A','B','C')", 'kosong' => 'NO', 'ekstra' => ''],
            ['nama' => 'dibuat_pada', 'tipe' => 'datetime', 'kosong' => 'YES', 'ekstra' => ''],
            ['nama' => 'diubah_pada', 'tipe' => 'datetime', 'kosong' => 'YES', 'ekstra' => ''],
        ], $kolom);
    }

    public function testKunciTamuHapusBerantai(): void
    {
        $aturan = $this->db->query(
            'SELECT k.COLUMN_NAME AS kolom, k.REFERENCED_TABLE_NAME AS rujukan, r.DELETE_RULE AS hapus, r.UPDATE_RULE AS ubah
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ?',
            [$this->db->getDatabase(), 'kelompok_umur_jenjang'],
        )->getResultArray();

        $this->assertSame([['kolom' => 'kelompok_umur_id', 'rujukan' => 'kelompok_umur', 'hapus' => 'CASCADE', 'ubah' => 'RESTRICT']], $aturan);

        $ku = $this->kelompokUmur('KU Karangan');
        $this->db->table('kelompok_umur_jenjang')->insertBatch([
            ['kelompok_umur_id' => $ku, 'jenjang' => 'A'],
            ['kelompok_umur_id' => $ku, 'jenjang' => 'B'],
        ]);
        $this->seeNumRecords(2, 'kelompok_umur_jenjang', ['kelompok_umur_id' => $ku]);

        $this->db->table('kelompok_umur')->delete(['id' => $ku]);

        $this->seeNumRecords(0, 'kelompok_umur_jenjang', []);
    }

    public function testSatuJenjangSekaliPerKelompokUmur(): void
    {
        $ku  = $this->kelompokUmur('KU Karangan');
        $ku2 = $this->kelompokUmur('KU Lain');
        $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku, 'jenjang' => 'A']);

        // Jenjang yang sama di kelompok umur lain boleh.
        $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku2, 'jenjang' => 'A']);

        $this->expectException(DatabaseException::class);
        $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku, 'jenjang' => 'A']);
    }

    public function testJenjangDiLuarDaftarDitolak(): void
    {
        $ku = $this->kelompokUmur('KU Karangan');

        $this->expectException(DatabaseException::class);
        $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku, 'jenjang' => 'D']);
    }

    public function testKelompokUmurHarusAda(): void
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => 999999, 'jenjang' => 'A']);
    }

    public function testGulungBalikBersihDanBisaDiulang(): void
    {
        $this->assertTrue($this->db->tableExists('kelompok_umur_jenjang', false));

        $this->regressDatabase();

        $sisa = array_values(array_diff($this->db->listTables(false), ['migrations']));
        $this->assertSame([], $sisa, 'Gulung balik semua migrasi menyisakan hanya tabel migrations.');

        $this->migrateDatabase();
        $this->assertTrue($this->db->tableExists('kelompok_umur_jenjang', false));
    }

    public function testBatasKelompokUmurMenurutTahunLahir(): void
    {
        // K36: `usia_minimal`/`usia_maksimal` diganti tahun lahir, boleh kosong.
        $kolom = $this->kolomKelompokUmur();

        $this->assertNotContains('usia_minimal', array_keys($kolom));
        $this->assertNotContains('usia_maksimal', array_keys($kolom));
        $this->assertSame(['tipe' => 'smallint unsigned', 'kosong' => 'YES'], $kolom['tahun_lahir_awal']);
        $this->assertSame(['tipe' => 'smallint unsigned', 'kosong' => 'YES'], $kolom['tahun_lahir_akhir']);
        $this->assertSame(['id', 'nama', 'tahun_lahir_awal', 'tahun_lahir_akhir'], array_slice(array_keys($kolom), 0, 4));

        $this->db->table('kelompok_umur')->insert(['nama' => 'KU Karangan', 'tahun_lahir_awal' => 2019]);
        $baris = $this->db->table('kelompok_umur')->get()->getRowArray();
        $this->assertSame('2019', $baris['tahun_lahir_awal']);
        $this->assertNull($baris['tahun_lahir_akhir']);
    }

    public function testGulungBalikTahunLahirMengembalikanBatasUsia(): void
    {
        // Migrasi tahun lahir dipindah ke batch tersendiri, lalu hanya batch
        // itu yang digulung balik.
        $batch = (int) $this->db->table('migrations')->selectMax('batch')->get()->getRow()->batch;
        $this->db->table('migrations')
            ->where('version', '2026-11-12-000200')
            ->update(['batch' => $batch + 1]);

        $migrasi = $this->migrations;
        $migrasi->setNamespace(null);
        $this->assertTrue($migrasi->regress($batch, 'tests'));

        $kolom = $this->kolomKelompokUmur();
        $this->assertSame(['tipe' => 'tinyint unsigned', 'kosong' => 'YES'], $kolom['usia_minimal']);
        $this->assertSame(['tipe' => 'tinyint unsigned', 'kosong' => 'YES'], $kolom['usia_maksimal']);
        $this->assertNotContains('tahun_lahir_awal', array_keys($kolom));
        $this->assertNotContains('tahun_lahir_akhir', array_keys($kolom));
        $this->assertSame(['id', 'nama', 'usia_minimal', 'usia_maksimal'], array_slice(array_keys($kolom), 0, 4));

        $migrasi->latest('tests');
        $this->assertArrayHasKey('tahun_lahir_awal', $this->kolomKelompokUmur());
    }

    /**
     * Kolom `kelompok_umur` berurutan: nama => [tipe, boleh kosong].
     *
     * @return array<string, array{tipe: string, kosong: string}>
     */
    private function kolomKelompokUmur(): array
    {
        $baris = $this->db->query(
            'SELECT COLUMN_NAME AS nama, COLUMN_TYPE AS tipe, IS_NULLABLE AS kosong
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$this->db->getDatabase(), 'kelompok_umur'],
        )->getResultArray();

        $kolom = [];

        foreach ($baris as $b) {
            $kolom[$b['nama']] = ['tipe' => $b['tipe'], 'kosong' => $b['kosong']];
        }

        return $kolom;
    }

    private function kelompokUmur(string $nama): int
    {
        $this->db->table('kelompok_umur')->insert(['nama' => $nama]);

        return (int) $this->db->insertID();
    }
}
