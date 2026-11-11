<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `awalan_nomor_dada`: digit pertama nomor dada per kelompok umur.
 *
 * Satu kelompok umur boleh punya banyak awalan (K05); awalan dipakai
 * berurutan menurut `urutan`. `nomor_terakhir` adalah tiga digit urut
 * terakhir yang sudah dibagikan (0 = belum ada, paling banyak 999).
 *
 * Kelompok umur yang masih punya awalan tidak bisa dihapus (RESTRICT), supaya
 * catatan nomor yang sudah dibagikan tidak hilang tanpa sengaja.
 */
class BuatTabelAwalanNomorDada extends Migration
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
            'awalan' => [
                'type'       => 'CHAR',
                'constraint' => 1,
            ],
            'urutan' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'nomor_terakhir' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
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
        $this->forge->addUniqueKey('awalan');
        $this->forge->addForeignKey('kelompok_umur_id', 'kelompok_umur', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('awalan_nomor_dada', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('awalan_nomor_dada', true);
    }
}
