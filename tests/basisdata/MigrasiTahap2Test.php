<?php

use CodeIgniter\Database\Exceptions\DatabaseException;
use Tests\Support\UjiBasisData;

/**
 * Migrasi Tahap 2 (F2-01): tabel `kelompok_umur_jenjang`.
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

    private function kelompokUmur(string $nama): int
    {
        $this->db->table('kelompok_umur')->insert(['nama' => $nama]);

        return (int) $this->db->insertID();
    }
}
