<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Status nomor dan batas peserta (F11-03, K172 butir 4).
 *
 * - `nomor_lomba.tidak_dilombakan`: 1 = nomor batal dilombakan (misalnya
 *   peserta kurang). Nomor itu keluar dari jadwal, seri, klasemen, dan
 *   videotron; halaman hasil menulis keterangannya. Bawaan 0.
 * - `nomor_lomba.alasan_tidak_dilombakan`: alasan singkat yang ditulis di
 *   halaman hasil.
 * - `nomor_lomba.peserta_minimal`: batas peserta paling sedikit; kosong =
 *   tanpa batas. Bila peserta Lunas atau hadir kurang dari batas, halaman
 *   Hasil dan Seri admin memberi peringatan. Medali tidak dikurangi.
 *
 * Hanya menambah kolom; bawaan menjaga perilaku lama (K139).
 */
class TambahStatusNomorBatasPeserta extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'tidak_dilombakan' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
                'after'      => 'pemecah_seri',
                'comment'    => '1 = nomor tidak dilombakan',
            ],
            'alasan_tidak_dilombakan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'tidak_dilombakan',
                'comment'    => 'Alasan singkat, tampil di halaman hasil',
            ],
            'peserta_minimal' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'alasan_tidak_dilombakan',
                'comment'  => 'Batas peserta paling sedikit; kosong = tanpa batas',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('nomor_lomba', ['tidak_dilombakan', 'alasan_tidak_dilombakan', 'peserta_minimal']);
    }
}
