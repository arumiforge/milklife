<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `atlet_nomor`: nomor lomba yang diikuti tiap atlet.
 *
 * `biaya` terisi untuk nomor individu (rupiah, disalin saat mendaftar) dan
 * kosong untuk nomor beregu, yang biayanya per tim di `pendaftaran_beregu`.
 * Satu atlet tidak bisa didaftarkan dua kali pada nomor yang sama.
 */
class BuatTabelAtletNomor extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'atlet_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nomor_lomba_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'biaya' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
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
        $this->forge->addUniqueKey(['atlet_id', 'nomor_lomba_id']);
        $this->forge->addForeignKey('atlet_id', 'atlet', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('nomor_lomba_id', 'nomor_lomba', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('atlet_nomor', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('atlet_nomor', true);
    }
}
