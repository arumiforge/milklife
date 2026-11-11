<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `kelompok_umur_jenjang`: jenjang sekolah yang boleh ikut tiap
 * kelompok umur (K23, K24).
 *
 * Satu baris berarti jenjang itu boleh ikut kelompok umur tersebut; tanpa
 * baris berarti belum ada jenjang yang dibuka. Kode jenjang (`A`, `B`, `C`)
 * dan labelnya ditulis di `App\Libraries\Jenjang`.
 *
 * Baris ikut terhapus bila kelompok umurnya dihapus (CASCADE), karena tidak
 * menyimpan catatan yang perlu dijaga.
 */
class BuatTabelKelompokUmurJenjang extends Migration
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
            'jenjang' => [
                'type'       => 'ENUM',
                'constraint' => ['A', 'B', 'C'],
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
        $this->forge->addUniqueKey(['kelompok_umur_id', 'jenjang']);
        $this->forge->addForeignKey('kelompok_umur_id', 'kelompok_umur', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('kelompok_umur_jenjang', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('kelompok_umur_jenjang', true);
    }
}
