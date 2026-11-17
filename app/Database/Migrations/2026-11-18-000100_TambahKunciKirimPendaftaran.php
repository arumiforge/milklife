<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom `pendaftaran.kunci_kirim` (K118, B2): kunci acak yang dibuat wizard
 * untuk satu kiriman. Bila ofisial menekan Kirim Pendaftaran lagi setelah
 * jawaban server tidak sampai, kunci yang sama membuat server menjawab dengan
 * pendaftaran yang sudah tersimpan, bukan galat "NISN sudah terdaftar".
 *
 * Boleh kosong: pendaftaran lama, pendaftaran dari halaman wizard yang belum
 * dimuat ulang, dan pendaftaran yang dibuat admin tidak punya kunci.
 */
class TambahKunciKirimPendaftaran extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pendaftaran', [
            'kunci_kirim' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'kode_bukti',
            ],
        ]);

        $this->db->query('ALTER TABLE pendaftaran ADD UNIQUE KEY pendaftaran_kunci_kirim (kunci_kirim)');
    }

    public function down(): void
    {
        $this->forge->dropColumn('pendaftaran', 'kunci_kirim');
    }
}
