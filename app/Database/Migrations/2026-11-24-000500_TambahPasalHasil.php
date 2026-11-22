<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kode NM, r, dan pasal diskualifikasi (F11-05, temuan M7, CR 25).
 *
 * - `hasil.pasal`: pasal pendek alasan diskualifikasi, misalnya `TR16.8`;
 *   diisi hanya bila keterangan DQ.
 * - Keterangan NM dan r muat di kolom `keterangan` yang sudah ada
 *   (`VARCHAR(3)`), jadi kolom itu tidak diubah.
 *
 * Hanya menambah kolom (K139). Hasil lama tanpa pasal terbaca sama.
 */
class TambahPasalHasil extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('hasil', [
            'pasal' => [
                'type'       => 'VARCHAR',
                'constraint' => 12,
                'null'       => true,
                'after'      => 'keterangan',
                'comment'    => 'Pasal diskualifikasi, misalnya TR16.8',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('hasil', 'pasal');
    }
}
