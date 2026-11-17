<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `atlet_ditolak` (K116): atlet yang ditolak admin dari sebuah
 * pendaftaran, misalnya karena tahun lahirnya tidak cocok dengan kelompok
 * umur yang didaftarkan.
 *
 * Saat ditolak, baris `atlet` (beserta nomor lombanya) dihapus supaya NISN-nya
 * bisa didaftarkan lagi, dan salinan datanya disimpan di sini untuk
 * ditampilkan di Cek Pendaftaran: kelompok umur dan nomor lomba lama, biaya,
 * alasan, kelompok umur yang sesuai, serta total pendaftaran sebelum dan
 * sesudah penolakan. Begitu NISN itu terdaftar lagi, keterangannya tidak
 * ditampilkan lagi di Cek Pendaftaran (barisnya tetap ada untuk admin).
 *
 * Bila pendaftarannya dihapus, catatannya ikut terhapus; bila digabung (K109),
 * catatannya ikut pindah ke pendaftaran gabungan.
 */
class BuatTabelAtletDitolak extends Migration
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
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'nisn' => [
                'type'       => 'CHAR',
                'constraint' => 10,
            ],
            'tanggal_lahir' => [
                'type' => 'DATE',
            ],
            'jenis_kelamin' => [
                'type'       => 'ENUM',
                'constraint' => ['L', 'P'],
            ],
            'kelompok_umur' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Nama kelompok umur saat didaftarkan',
            ],
            'nomor_lomba' => [
                'type'    => 'TEXT',
                'comment' => 'JSON: nomor lomba yang diikuti saat ditolak (nama, jenis, biaya, regu, peran)',
            ],
            'biaya_individu' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'kelompok_umur_sesuai_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'Kelompok umur yang cocok menurut pengaturan saat ditolak; tanpa kunci tamu',
            ],
            'kelompok_umur_sesuai' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Nama dan rentang tahun lahirnya, atau kalimat bila tidak ada yang cocok',
            ],
            'alasan' => [
                'type' => 'TEXT',
            ],
            'total_sebelum' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'total_sesudah' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'ditolak_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('nisn', false, false, 'atlet_ditolak_nisn');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE', 'atlet_ditolak_pendaftaran');

        $this->forge->createTable('atlet_ditolak', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('atlet_ditolak', true);
    }
}
