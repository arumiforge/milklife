<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `nomor_lomba`: nomor individu dan beregu per kelompok umur.
 *
 * Kolom tim (`jumlah_inti`, `batas_anggota`, `jumlah_putra`, `jumlah_putri`,
 * `biaya_beregu`) kosong untuk nomor individu. `jumlah_putra` dan
 * `jumlah_putri` hanya dipakai nomor mix (K09).
 */
class BuatTabelNomorLomba extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kelompok_umur_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'jenis' => [
                'type'       => 'ENUM',
                'constraint' => ['individu', 'beregu'],
            ],
            'kategori' => [
                'type'       => 'ENUM',
                'constraint' => ['putra', 'putri', 'mix'],
            ],
            'jumlah_inti' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'batas_anggota' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'jumlah_putra' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'jumlah_putri' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            // Rupiah per tim.
            'biaya_beregu' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'urutan' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'diubah_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('kelompok_umur_id', 'kelompok_umur', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('nomor_lomba', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('nomor_lomba', true);
    }
}
