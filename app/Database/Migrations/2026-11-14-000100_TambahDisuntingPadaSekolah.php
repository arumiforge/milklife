<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Data sekolah bisa disunting admin (K97).
 *
 * - `disunting_pada`: waktu terakhir admin menyimpan sekolah ini di panel.
 *   Sekolah yang pernah disunting tidak ditimpa `php spark sekolah:impor`,
 *   supaya nama pembeda yang ditulis admin (misalnya "MIS MUHAMMADIYAH JATI")
 *   tidak kembali ke nama umum dari data resmi.
 * - Indeks `nama`: daftar sekolah di panel menghitung sekolah lain yang
 *   namanya sama.
 */
class TambahDisuntingPadaSekolah extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('sekolah', [
            'disunting_pada' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'kabupaten',
                'comment' => 'Terakhir disunting admin; impor tidak menimpa baris ini',
            ],
        ]);

        $this->forge->addKey('nama', false, false, 'sekolah_nama');
        $this->forge->processIndexes('sekolah');
    }

    public function down(): void
    {
        if (! $this->db->tableExists('sekolah', false)) {
            return;
        }

        $this->forge->dropKey('sekolah', 'sekolah_nama', false);

        if ($this->db->fieldExists('disunting_pada', 'sekolah')) {
            $this->forge->dropColumn('sekolah', 'disunting_pada');
        }
    }
}
