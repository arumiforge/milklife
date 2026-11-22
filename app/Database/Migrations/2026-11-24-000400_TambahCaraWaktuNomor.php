<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cara pencatatan waktu per nomor (F11-04, K172 butir 5).
 *
 * - `nomor_lomba.cara_waktu`: `elektronik` (bawaan) atau `tangan`. Waktu
 *   tangan dibulatkan ke atas ke 0,1 detik saat disimpan dan ditulis dengan
 *   satu angka desimal (TR 19). Hanya berarti untuk jenis hasil waktu.
 *
 * Hanya menambah kolom (aturan Tahap 9 butir 1). Nomor lama menjadi
 * elektronik, jadi perilakunya tetap; prestasi tersimpan tidak diubah.
 */
class TambahCaraWaktuNomor extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'cara_waktu' => [
                'type'       => 'ENUM',
                'constraint' => ['elektronik', 'tangan'],
                'null'       => false,
                'default'    => 'elektronik',
                'after'      => 'pemecah_seri',
                'comment'    => 'Cara pencatatan waktu: elektronik atau tangan',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('nomor_lomba', ['cara_waktu']);
    }
}
