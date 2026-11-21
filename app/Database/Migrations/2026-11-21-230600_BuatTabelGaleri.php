<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `galeri`: album foto acara berupa tautan luar (F9-06, K140 butir 11),
 * dikelola admin di Pengaturan > Galeri dan tampil di `/galeri`.
 *
 * - `tautan`: alamat `https://` album di Instagram atau Google Drive panitia;
 *   `NULL` = belum diisi (album tidak tampil untuk umum).
 * - `sampul`: jalur gambar `unggahan/galeri/<acak>.webp` (`App\Libraries\Unggahan`);
 *   `NULL` = tanpa sampul.
 * - `urutan`: kecil tampil lebih dulu; `aktif` 0 = disembunyikan.
 *
 * Hanya menambah tabel (rencana Tahap 9, "Aturan untuk semua sesi" butir 1).
 */
class BuatTabelGaleri extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'judul' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'default'    => '',
            ],
            'tautan' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'sampul' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'urutan' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'diubah_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('urutan');
        $this->forge->createTable('galeri', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('galeri', true);
    }
}
