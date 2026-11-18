<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `riwayat_admin` (F8-01, K124): siapa melakukan apa dan kapan pada
 * sebuah pendaftaran.
 *
 * - `pendaftaran_id` menjadi kosong saat pendaftarannya dihapus (kunci asing
 *   `SET NULL`), sedangkan `id_pendaftaran` (teks) tetap, jadi riwayat
 *   pendaftaran yang sudah dihapus masih bisa dicari di `admin/riwayat`.
 * - Saat digabung (K109) barisnya pindah ke pendaftaran tujuan; `id_pendaftaran`
 *   tetap ID lamanya, sehingga asal tiap baris masih terbaca.
 * - `admin_id` tanpa kunci asing dan `nama_admin` salinan nama saat itu, supaya
 *   riwayat tetap utuh walau akun admin dihapus atau diganti namanya.
 * - `tindakan`: kode pendek (`RiwayatAdmin::LABEL`); `ringkasan`: kalimat
 *   berbahasa Indonesia untuk dibaca panitia.
 */
class BuatTabelRiwayatAdmin extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pendaftaran_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'id_pendaftaran' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'admin_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'nama_admin' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'tindakan' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'ringkasan' => [
                'type' => 'TEXT',
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('id_pendaftaran', false, false, 'riwayat_admin_id_pendaftaran');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'CASCADE', 'SET NULL', 'riwayat_admin_pendaftaran');

        $this->forge->createTable('riwayat_admin', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('riwayat_admin', true);
    }
}
