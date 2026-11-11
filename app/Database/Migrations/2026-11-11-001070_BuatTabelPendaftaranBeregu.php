<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `pendaftaran_beregu`: tim beregu dalam satu pendaftaran.
 *
 * `biaya_tim` dalam rupiah, disalin saat mendaftar (0 untuk susulan yang hanya
 * menambah anggota, K07). Satu pendaftaran hanya punya satu tim per nomor.
 * Aturan "satu tim per nomor per sekolah" lintas pendaftaran dijaga aplikasi,
 * karena sekolah dikenali dari NPSN yang boleh kosong.
 */
class BuatTabelPendaftaranBeregu extends Migration
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
            'nomor_lomba_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'biaya_tim' => [
                'type'     => 'INT',
                'unsigned' => true,
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
        $this->forge->addUniqueKey(['pendaftaran_id', 'nomor_lomba_id']);
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('nomor_lomba_id', 'nomor_lomba', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('pendaftaran_beregu', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('pendaftaran_beregu', true);
    }
}
