<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `usulan_nama_sekolah` (K99): nama sekolah yang lebih lengkap yang
 * diusulkan pendaftar di wizard, untuk diputuskan admin di menu Sekolah.
 *
 * Pendaftaran yang mengusulkan langsung memakai nama usulan. Disetujui: nama
 * di data sekolah dan semua pendaftaran ber-NPSN itu diganti. Ditolak:
 * pendaftaran pengusul kembali memakai nama dari data sekolah.
 * `pendaftaran_id` dikosongkan bila pendaftarannya dihapus; usulannya tetap
 * ada supaya admin masih bisa memutuskan nama sekolahnya.
 */
class BuatTabelUsulanNamaSekolah extends Migration
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
            'pendaftaran_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'nama_lama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'comment'    => 'Nama di data sekolah saat diusulkan',
            ],
            'nama_usulan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu', 'disetujui', 'ditolak'],
                'default'    => 'menunggu',
            ],
            'nama_disetujui' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'comment'    => 'Nama yang akhirnya dipakai; admin boleh merapikan usulan',
            ],
            'alasan' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'diputuskan_pada' => [
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
        $this->forge->addKey(['status', 'npsn'], false, false, 'usulan_nama_sekolah_status_npsn');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'SET NULL', 'usulan_nama_sekolah_pendaftaran');

        $this->forge->createTable('usulan_nama_sekolah', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('usulan_nama_sekolah', true);
    }
}
