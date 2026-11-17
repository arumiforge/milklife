<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penanda bukti bayar baru yang belum diperiksa admin (F8-02, K124).
 *
 * - `bukti_bayar.diperiksa_pada` kosong = bukti baru dari ofisial (wizard atau
 *   Cek Pendaftaran) yang belum diperiksa. Diisi saat pendaftaran ditandai
 *   Lunas, saat admin menekan "Sudah diperiksa", atau langsung saat admin
 *   sendiri yang mengunggah.
 * - `diperiksa_oleh`: nama admin saat itu.
 * - Data lama: bukti milik pendaftaran yang sudah Lunas dianggap sudah
 *   diperiksa (waktu Lunas-nya), supaya penanda hanya muncul untuk bukti yang
 *   memang belum ditangani.
 */
class TambahDiperiksaBuktiBayar extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bukti_bayar', [
            'diperiksa_pada' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'waktu_unggah',
            ],
            'diperiksa_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'diperiksa_pada',
            ],
        ]);

        $this->db->query(
            "UPDATE bukti_bayar b JOIN pendaftaran p ON p.id = b.pendaftaran_id
             SET b.diperiksa_pada = COALESCE(p.waktu_lunas, b.waktu_unggah, NOW()), b.diperiksa_oleh = 'Data lama'
             WHERE p.status = 'lunas' AND b.diperiksa_pada IS NULL",
        );
    }

    public function down(): void
    {
        $this->forge->dropColumn('bukti_bayar', ['diperiksa_pada', 'diperiksa_oleh']);
    }
}
