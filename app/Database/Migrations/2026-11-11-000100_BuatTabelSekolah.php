<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `sekolah`: data referensi sekolah dari `data/sekolah/sekolah.json`.
 *
 * Diisi dengan `php spark sekolah:impor`. Panjang kolom diambil dari panjang
 * terpanjang di data (10 November 2026) ditambah kelonggaran:
 * nama 90 → 150, bentuk 10 → 20, status 6 → 10, alamat 80 → 200,
 * desa/kelurahan 17 → 60, kecamatan 14 → 60, kabupaten 7 → 40.
 */
class BuatTabelSekolah extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'npsn' => [
                'type'       => 'CHAR',
                'constraint' => 8,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'bentuk' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'jenjang' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'comment'    => 'SD, SMP, atau SMA',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'alamat' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'desa_kelurahan' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            'kecamatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            'kabupaten' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'comment'    => 'Tanpa awalan "Kab." atau "Kota", misalnya Blora',
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
        $this->forge->addUniqueKey('npsn', 'sekolah_npsn_unik');
        $this->forge->addKey(['jenjang', 'kabupaten'], false, false, 'sekolah_jenjang_kabupaten');

        $this->forge->createTable('sekolah', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('sekolah', true);
    }
}
