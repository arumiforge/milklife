<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom `pendaftaran.kode_bukti` (K77): kode acak 24 huruf heksadesimal di
 * alamat bukti PDF (`/bukti/<kode>/<ID pendaftaran>`), supaya bukti tidak bisa
 * dibuka hanya dengan menebak ID pendaftaran yang berurutan. Pendaftaran yang
 * sudah ada langsung diberi kode.
 */
class TambahKodeBuktiPendaftaran extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pendaftaran', [
            'kode_bukti' => [
                'type'       => 'CHAR',
                'constraint' => 24,
                'null'       => true,
                'after'      => 'id_pendaftaran',
            ],
        ]);

        foreach ($this->db->table('pendaftaran')->select('id')->get()->getResultArray() as $baris) {
            $this->db->table('pendaftaran')->where('id', $baris['id'])->update(['kode_bukti' => bin2hex(random_bytes(12))]);
        }

        $this->db->query('ALTER TABLE pendaftaran ADD UNIQUE KEY pendaftaran_kode_bukti (kode_bukti)');
    }

    public function down(): void
    {
        $this->forge->dropColumn('pendaftaran', 'kode_bukti');
    }
}
