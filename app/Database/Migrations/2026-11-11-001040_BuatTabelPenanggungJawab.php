<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `penanggung_jawab`: 1 sampai 5 orang per pendaftaran.
 *
 * `foto` berisi nama berkas foto, boleh kosong (ditampilkan sebagai ikon
 * "tanpa gambar"). Ikut terhapus bersama pendaftarannya.
 */
class BuatTabelPenanggungJawab extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pendaftaran_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'nomor_wa' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'foto' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('penanggung_jawab', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('penanggung_jawab', true);
    }
}
