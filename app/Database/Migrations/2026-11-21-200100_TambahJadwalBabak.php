<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jadwal pertandingan per babak (F9-07, K140 butir 9).
 *
 * - `babak.tanggal`: hari babak dipertandingkan; kosong = belum dijadwalkan
 *   dan tidak tampil di halaman publik `jadwal`.
 * - `babak.jam_mulai`: jam mulai (WIB); kosong = jam belum pasti.
 * - `babak.lokasi`: lapangan atau tempat, misalnya "Lintasan utama".
 *
 * Hanya menambah kolom (aturan Tahap 9 butir 1). Kolom ikut paket pindah
 * server (K76) lewat `PaketPendaftaran`.
 */
class TambahJadwalBabak extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('babak', [
            'tanggal' => [
                'type'    => 'DATE',
                'null'    => true,
                'after'   => 'urutan',
                'comment' => 'Hari babak dipertandingkan; kosong = belum dijadwalkan',
            ],
            'jam_mulai' => [
                'type'    => 'TIME',
                'null'    => true,
                'after'   => 'tanggal',
                'comment' => 'Jam mulai WIB; kosong = belum pasti',
            ],
            'lokasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'jam_mulai',
                'comment'    => 'Lapangan atau tempat babak',
            ],
        ]);

        $this->db->query('CREATE INDEX babak_tanggal ON ' . $this->db->prefixTable('babak') . ' (tanggal, jam_mulai)');
    }

    public function down(): void
    {
        if ($this->db->fieldExists('tanggal', 'babak')) {
            $this->db->query('DROP INDEX babak_tanggal ON ' . $this->db->prefixTable('babak'));
            $this->forge->dropColumn('babak', ['tanggal', 'jam_mulai', 'lokasi']);
        }
    }
}
