<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel akun admin. Semua admin punya peran yang sama.
 *
 * Kata sandi disimpan sebagai hash (`password_hash`), tidak pernah teks asli.
 * Akun dibuat lewat `php spark admin:buat`; tidak ada akun bawaan.
 */
class BuatTabelAdmin extends Migration
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
                'constraint' => 100,
            ],
            'nama_pengguna' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'sandi' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addUniqueKey('nama_pengguna', 'admin_nama_pengguna_unik');

        $this->forge->createTable('admin', true, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('admin', true);
    }
}
