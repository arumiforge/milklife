<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `atlet`.
 *
 * NISN unik di seluruh basis data (K06). `nomor_dada` baru diisi saat
 * pendaftaran ditandai Lunas (K11), jadi boleh kosong; MySQL mengizinkan banyak
 * NULL pada kolom unik. Atlet ikut terhapus bersama pendaftarannya, sedangkan
 * kelompok umur yang masih dipakai atlet tidak bisa dihapus.
 */
class BuatTabelAtlet extends Migration
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
            'nisn' => [
                'type'       => 'CHAR',
                'constraint' => 10,
            ],
            'tanggal_lahir' => [
                'type' => 'DATE',
            ],
            // L = laki-laki, P = perempuan.
            'jenis_kelamin' => [
                'type'       => 'ENUM',
                'constraint' => ['L', 'P'],
            ],
            'kelompok_umur_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'nomor_dada' => [
                'type'       => 'CHAR',
                'constraint' => 4,
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
        $this->forge->addUniqueKey('nisn');
        $this->forge->addUniqueKey('nomor_dada');
        $this->forge->addKey('nama');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('kelompok_umur_id', 'kelompok_umur', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('atlet', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('atlet', true);
    }
}
