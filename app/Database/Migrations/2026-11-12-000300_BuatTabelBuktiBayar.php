<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `bukti_bayar` (rancangan 11; K34; Tahap 6, F6-01).
 *
 * Gambar bukti bayar yang diunggah admin, paling banyak 5 per pendaftaran
 * (dijaga aplikasi, `BuktiBayarModel::BATAS`). `berkas` jalur relatif di
 * folder unggahan di luar `public/` (`App\Libraries\Panel\BerkasBuktiBayar`),
 * `ukuran` dalam bita. Baris ikut terhapus bersama pendaftarannya; berkasnya
 * dihapus aplikasi.
 */
class BuatTabelBuktiBayar extends Migration
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
            'berkas' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'ukuran' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'waktu_unggah' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('bukti_bayar', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('bukti_bayar', true);
    }
}
