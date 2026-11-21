<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `faq`: tanya-jawab halaman Bantuan (F9-04), dikelola admin di
 * Pengaturan > Bantuan.
 *
 * - `kategori`: kunci kategori di `App\Models\FaqModel::KATEGORI`
 *   (`sebelum`, `mengisi`, `pembayaran`, `cek`, `hari_lomba`, `hasil`).
 *   Teks biasa, bukan ENUM, supaya kategori baru tidak butuh migrasi.
 * - `jawaban`: Markdown sederhana (`App\Libraries\MarkdownSederhana`) dengan
 *   isian otomatis seperti `{tutup_pendaftaran}` (`App\Libraries\Bantuan\JawabanFaq`).
 * - `urutan`: kecil tampil lebih dulu di kategorinya; `aktif` 0 = disembunyikan.
 */
class BuatTabelFaq extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kategori' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'pertanyaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'jawaban' => [
                'type' => 'TEXT',
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
        $this->forge->addKey(['kategori', 'urutan']);
        $this->forge->createTable('faq', false, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('faq', true);
    }
}
