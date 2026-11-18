<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tanda hadir atlet saat daftar ulang hari lomba (F8-03, K124, asumsi 4 K125).
 *
 * - `atlet.hadir_pada`: waktu admin menandai atlet hadir; kosong = belum hadir.
 * - `atlet.hadir_oleh`: nama admin yang menandai (salinan saat itu).
 *
 * Kolom ikut pindah bersama barisnya saat pendaftaran digabung (K109) dan
 * ikut dibawa ekspor/impor pindah server (K76).
 */
class TambahHadirAtlet extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('atlet', [
            'hadir_pada' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'nomor_dada',
                'comment' => 'Waktu ditandai hadir saat daftar ulang; kosong = belum hadir',
            ],
            'hadir_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'hadir_pada',
                'comment'    => 'Nama admin yang menandai hadir',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('hadir_pada', 'atlet')) {
            $this->forge->dropColumn('atlet', ['hadir_pada', 'hadir_oleh']);
        }
    }
}
