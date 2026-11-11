<?php

use Tests\Support\UjiBasisData;

/**
 * Sambungan ke basis data uji dan migrasi semua namespace.
 *
 * @internal
 */
final class SambunganBasisDataTest extends UjiBasisData
{
    public function testTersambungKeBasisDataUji(): void
    {
        $this->assertSame('MySQLi', $this->db->DBDriver);
        $this->assertSame('', $this->db->DBPrefix);
        $this->assertSame(config('Database')->tests['database'], $this->db->getDatabase());
        $this->assertNotSame(config('Database')->default['database'], $this->db->getDatabase());
        $this->assertSame(1, (int) $this->db->query('SELECT 1 AS satu')->getRow()->satu);
    }

    public function testMigrasiSemuaMembuatTabelSettings(): void
    {
        $this->assertTrue($this->db->tableExists('settings'));
        $this->assertTrue($this->db->tableExists('migrations'));
    }

    public function testPengaturanBisaDisimpanDanDibaca(): void
    {
        service('settings')->set('Aplikasi.contohUji', 'nilai karangan');

        $this->assertSame('nilai karangan', service('settings')->get('Aplikasi.contohUji'));
        $this->seeInDatabase('settings', ['class' => 'Config\Aplikasi', 'key' => 'contohUji']);
    }
}
