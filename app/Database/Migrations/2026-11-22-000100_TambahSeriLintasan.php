<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seri, lintasan, dan alur babak (F9-08, K140 butir 8).
 *
 * - `nomor_lomba.tempat_per_seri`: lintasan per seri (nomor lari) atau
 *   peserta per kelompok (lompat/lempar, urutan tampil tanpa lintasan);
 *   kosong = bawaan (8 untuk nomor waktu, satu kelompok untuk lainnya).
 * - `nomor_lomba.urutan_lintasan`: urutan lintasan unggulan untuk babak
 *   lanjutan, kelompok undian dipisah titik koma (`3,4,5,6;7,8;1,2`);
 *   kosong = bawaan World Athletics untuk 8 lintasan.
 * - `babak.jumlah_seri`: banyak seri hasil pembagian terakhir.
 * - `babak.lolos_q` dan `babak.lolos_q_kecil`: aturan lolos ke babak
 *   berikutnya, Q = n terbaik tiap seri, q = m prestasi terbaik berikutnya;
 *   kosong = dihitung otomatis dari kapasitas babak berikutnya.
 * - `babak.benih`: benih acak pembagian terakhir, supaya bisa diulang.
 * - `babak.seri_dikunci_pada`: daftar seri dikunci (tidak bisa dibagi atau
 *   digeser lagi) dan tampil di halaman publik; kosong = belum dikunci.
 * - `hasil.seri` dan `hasil.lintasan`: seri dan lintasan (atau urutan tampil)
 *   peserta di babak itu; kosong = belum dibagi.
 *
 * Hanya menambah kolom (aturan Tahap 9 butir 1). Kolom ikut paket pindah
 * server (K76) lewat `PaketPendaftaran`.
 */
class TambahSeriLintasan extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'tempat_per_seri' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'komponen_skor',
                'comment'    => 'Lintasan per seri atau peserta per kelompok; kosong = bawaan',
            ],
            'urutan_lintasan' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'tempat_per_seri',
                'comment'    => 'Urutan lintasan unggulan, kelompok undian dipisah titik koma; kosong = bawaan',
            ],
        ]);

        $this->forge->addColumn('babak', [
            'jumlah_seri' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'lokasi',
                'comment'  => 'Banyak seri hasil pembagian; kosong = belum dibagi',
            ],
            'lolos_q' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'jumlah_seri',
                'comment'  => 'Q: n terbaik tiap seri lolos; kosong = otomatis',
            ],
            'lolos_q_kecil' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'lolos_q',
                'comment'  => 'q: m prestasi terbaik berikutnya lolos; kosong = otomatis',
            ],
            'benih' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'lolos_q_kecil',
                'comment'  => 'Benih acak pembagian seri terakhir',
            ],
            'seri_dikunci_pada' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'benih',
                'comment' => 'Daftar seri dikunci dan tampil untuk umum; kosong = belum',
            ],
        ]);

        $this->forge->addColumn('hasil', [
            'seri' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'regu',
                'comment'  => 'Seri peserta di babak ini; kosong = belum dibagi',
            ],
            'lintasan' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'seri',
                'comment'  => 'Lintasan, atau urutan tampil untuk lompat/lempar',
            ],
        ]);

        $this->db->query('CREATE INDEX hasil_seri ON ' . $this->db->prefixTable('hasil') . ' (babak_id, seri, lintasan)');
    }

    public function down(): void
    {
        if ($this->db->fieldExists('seri', 'hasil')) {
            $this->db->query('DROP INDEX hasil_seri ON ' . $this->db->prefixTable('hasil'));
            $this->forge->dropColumn('hasil', ['seri', 'lintasan']);
        }

        if ($this->db->fieldExists('jumlah_seri', 'babak')) {
            $this->forge->dropColumn('babak', ['jumlah_seri', 'lolos_q', 'lolos_q_kecil', 'benih', 'seri_dikunci_pada']);
        }

        if ($this->db->fieldExists('tempat_per_seri', 'nomor_lomba')) {
            $this->forge->dropColumn('nomor_lomba', ['tempat_per_seri', 'urutan_lintasan']);
        }
    }
}
