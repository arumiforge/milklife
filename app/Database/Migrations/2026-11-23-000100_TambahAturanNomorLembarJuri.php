<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Aturan nomor dan lembar juri (F9-09, K140 butir 7c dan 7d).
 *
 * - `nomor_lomba.jumlah_percobaan`: banyak percobaan nomor lompat/lempar
 *   (jenis hasil jarak); kosong = bawaan 3.
 * - `nomor_lomba.pemecah_seri`: prestasi sama di nomor jarak dipisah dengan
 *   prestasi terbaik kedua (lalu ketiga, dst.) bila percobaannya dicatat;
 *   bawaan menyala.
 * - `hasil.percobaan`: prestasi tiap percobaan (JSON: sentimeter, `"X"`
 *   gagal, `"-"` lewat, `null` belum diisi); kosong = hanya prestasi terbaik
 *   yang dicatat (data lama).
 * - `hasil.seperseribu`: waktu lari dalam seperseribu detik bila diisi
 *   (foto finis), pemecah waktu yang sama di perseratus detik.
 * - `hasil.versi`: naik setiap kali baris disimpan; lembar juri menolak
 *   simpanan dari baris yang sudah diubah admin lain.
 *
 * Hanya menambah kolom (aturan Tahap 9 butir 1). Data lama tanpa percobaan
 * dan tanpa seperseribu tetap berperingkat sama seperti sebelumnya.
 */
class TambahAturanNomorLembarJuri extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'jumlah_percobaan' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'urutan_lintasan',
                'comment'  => 'Percobaan nomor lompat/lempar; kosong = 3',
            ],
            'pemecah_seri' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
                'after'      => 'jumlah_percobaan',
                'comment'    => '1 = prestasi sama dipisah prestasi terbaik kedua',
            ],
        ]);

        $this->forge->addColumn('hasil', [
            'percobaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'rincian',
                'comment'    => 'JSON prestasi tiap percobaan (sentimeter, X, -)',
            ],
            'seperseribu' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'percobaan',
                'comment'  => 'Waktu dalam seperseribu detik bila diisi',
            ],
            'versi' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 0,
                'after'    => 'peringkat_manual',
                'comment'  => 'Naik setiap kali baris disimpan',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('hasil', ['percobaan', 'seperseribu', 'versi']);
        $this->forge->dropColumn('nomor_lomba', ['jumlah_percobaan', 'pemecah_seri']);
    }
}
