<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Nama regu boleh lebih dari satu huruf (K106): nama dari daftar admin
 * ("Putra 1") atau diketik official ("Merah"), paling panjang 30 huruf.
 */
class PerpanjangNamaRegu extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('atlet_nomor', [
            'regu' => [
                'name'       => 'regu',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'comment'    => 'Nama regu, hanya nomor beregu',
            ],
        ]);

        $this->forge->modifyColumn('pendaftaran_beregu', [
            'regu' => [
                'name'       => 'regu',
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'A',
                'comment'    => 'Nama regu',
            ],
        ]);
    }

    public function down(): void
    {
        // Nama yang lebih dari satu huruf dipotong; hanya untuk membatalkan di lingkungan uji.
        $this->db->query('UPDATE ' . $this->db->prefixTable('atlet_nomor') . ' SET regu = LEFT(regu, 1) WHERE regu IS NOT NULL');
        $this->db->query('UPDATE ' . $this->db->prefixTable('pendaftaran_beregu') . ' SET regu = LEFT(regu, 1)');

        $this->forge->modifyColumn('atlet_nomor', [
            'regu' => ['name' => 'regu', 'type' => 'CHAR', 'constraint' => 1, 'null' => true, 'comment' => 'Label regu (A–Z), hanya nomor beregu'],
        ]);
        $this->forge->modifyColumn('pendaftaran_beregu', [
            'regu' => ['name' => 'regu', 'type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'A', 'comment' => 'Label regu (A–Z)'],
        ]);
    }
}
