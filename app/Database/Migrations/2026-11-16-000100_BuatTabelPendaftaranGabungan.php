<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `pendaftaran_gabungan` (K109): ID pendaftaran dan kode bukti lama
 * dari pendaftaran yang sudah digabung ke pendaftaran Lunas lain dengan NPSN
 * yang sama.
 *
 * Pendaftaran lamanya dihapus setelah isinya dipindah, tetapi ID dan kode
 * buktinya tetap dikenali: kode QR di bukti PDF lama (`/bukti/<kode lama>/<ID
 * lama>`) diarahkan ke bukti pendaftaran gabungan, dan Cek Pendaftaran dengan
 * ID lama menampilkan pendaftaran gabungan. Bila pendaftaran gabungan
 * dihapus, catatannya ikut terhapus.
 */
class BuatTabelPendaftaranGabungan extends Migration
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
                'comment'  => 'Pendaftaran gabungan (tujuan)',
            ],
            'id_pendaftaran_lama' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'kode_bukti_lama' => [
                'type'       => 'CHAR',
                'constraint' => 24,
                'null'       => true,
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('id_pendaftaran_lama', 'pendaftaran_gabungan_id_lama');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE', 'pendaftaran_gabungan_pendaftaran');

        $this->forge->createTable('pendaftaran_gabungan', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('pendaftaran_gabungan', true);
    }
}
