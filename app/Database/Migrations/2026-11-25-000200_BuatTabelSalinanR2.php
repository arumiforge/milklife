<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Salinan cadangan ke Cloudflare R2 (F11-21, K187): satu baris (`id` = 1)
 * berisi pengaturan yang diisi admin di Pengaturan > Cadangan, dan hasil
 * kiriman terakhir `cadangan:buat`.
 *
 * - `rahasia`: Secret Access Key yang dienkripsi dengan `encryption.key`
 *   (base64), tidak pernah ditampilkan lagi.
 * - Tabel ini tidak ikut cadangan lengkap panel dan tidak diubah impor
 *   (`CadanganLengkap::TABEL_DILEWATI`), jadi pengaturan R2 tetap milik server.
 *
 * Hanya menambah tabel (aturan Tahap 9 butir 1).
 */
class BuatTabelSalinanR2 extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
            ],
            'id_akun' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'bucket' => [
                'type'       => 'VARCHAR',
                'constraint' => 63,
            ],
            'kunci_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
            ],
            'rahasia' => [
                'type'    => 'TEXT',
                'comment' => 'Secret Access Key terenkripsi (base64)',
            ],
            'folder' => [
                'type'       => 'VARCHAR',
                'constraint' => 63,
                'comment'    => 'Awalan nama objek di bucket',
            ],
            'kirim_waktu' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'kirim_berhasil' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'kirim_pesan' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'diubah_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('salinan_r2', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('salinan_r2', true);
    }
}
