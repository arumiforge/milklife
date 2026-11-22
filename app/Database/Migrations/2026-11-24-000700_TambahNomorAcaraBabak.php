<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Nomor acara per babak (F11-07, K172 butir 7).
 *
 * - `babak.nomor_acara`: nomor panggil babak untuk pembawa acara, juri, dan
 *   lembar hasil (Acara 101, 102, dan seterusnya, seperti "Event" di lembar
 *   Seri 1). Kosong = belum diberi nomor; babak tetap tampil tanpa nomor.
 *
 * Hanya menambah kolom, jadi aman dipasang di produksi (K139). Kolom ikut
 * paket pindah server (K76) lewat `PaketPendaftaran`.
 */
class TambahNomorAcaraBabak extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('babak', [
            'nomor_acara' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'lokasi',
                'comment'    => 'Nomor acara (Acara 101); kosong = belum diberi nomor',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('nomor_acara', 'babak')) {
            $this->forge->dropColumn('babak', 'nomor_acara');
        }
    }
}
