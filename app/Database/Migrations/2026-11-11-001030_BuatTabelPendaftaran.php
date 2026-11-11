<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `pendaftaran`: satu kali pendaftaran dari satu sekolah.
 *
 * `id_pendaftaran` (contoh `MAC2A0000126`) baru diisi dari `id` setelah baris
 * dibuat, jadi boleh kosong; MySQL mengizinkan banyak NULL pada kolom unik.
 * `npsn` sengaja bukan kunci tamu ke `sekolah`: sekolah yang tidak ada di data
 * referensi tetap bisa mendaftar, dan pendaftaran tanpa NPSN berdiri sendiri.
 * Biaya disalin saat pendaftaran dibuat, dalam rupiah.
 */
class BuatTabelPendaftaran extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pendaftaran' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            // A = SD/MI, B = SMP/MTs, C = SMA/SMK/MA.
            'jenjang' => [
                'type'       => 'ENUM',
                'constraint' => ['A', 'B', 'C'],
            ],
            'npsn' => [
                'type'       => 'CHAR',
                'constraint' => 8,
                'null'       => true,
            ],
            'nama_sekolah' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'kabupaten' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'subtotal_individu' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'subtotal_beregu' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'total' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu', 'lunas'],
                'default'    => 'menunggu',
            ],
            'waktu_lunas' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey('id_pendaftaran');
        $this->forge->addKey('npsn');
        $this->forge->addKey('status');
        $this->forge->createTable('pendaftaran', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('pendaftaran', true);
    }
}
