<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `kelompok_umur`: KU 8, KU 10, dan seterusnya.
 *
 * Batas usia dan biaya individu boleh kosong karena admin baru mengisinya
 * dari juknis (K12). Usia dalam tahun penuh pada tanggal patokan usia.
 */
class BuatTabelKelompokUmur extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'usia_minimal' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'usia_maksimal' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            // Rupiah per atlet per nomor individu.
            'biaya_individu' => [
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
        $this->forge->createTable('kelompok_umur', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('kelompok_umur', true);
    }
}
