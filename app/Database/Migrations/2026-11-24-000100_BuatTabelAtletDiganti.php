<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `atlet_diganti` (F11-01, K172): catatan atlet yang diganti admin
 * dengan atlet lain dari sekolah yang sama, misalnya karena cedera menjelang
 * lomba.
 *
 * Saat diganti, baris `atlet` lama dihapus (NISN-nya bisa didaftarkan lagi),
 * dan atlet baru mewarisi nomor lomba, regu, dan peran inti atau cadangannya.
 * Salinan data atlet lama dan siapa penggantinya disimpan di sini untuk
 * rincian pendaftaran di panel admin.
 *
 * Tabel ini terpisah dari `atlet_ditolak` (K116) supaya atlet yang diganti
 * tidak tampil sebagai "ditolak" di Cek Pendaftaran dan bukti. Bila
 * pendaftarannya dihapus, catatannya ikut terhapus; bila digabung (K109),
 * catatannya ikut pindah.
 */
class BuatTabelAtletDiganti extends Migration
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
                'comment'    => 'Nama atlet lama',
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
            ],
            'nomor_dada' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
                'comment'    => 'Nomor atlet lama; tidak dipakai ulang (K11)',
            ],
            'nomor_lomba' => [
                'type'    => 'TEXT',
                'comment' => 'JSON: nomor lomba yang diwariskan (nama, jenis, biaya, regu, peran)',
            ],
            'pengganti_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'pengganti_nisn' => [
                'type'       => 'CHAR',
                'constraint' => 10,
            ],
            'pengganti_nomor_dada' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'alasan' => [
                'type' => 'TEXT',
            ],
            'diganti_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('nisn', false, false, 'atlet_diganti_nisn');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE', 'atlet_diganti_pendaftaran');

        $this->forge->createTable('atlet_diganti', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('atlet_diganti', true);
    }
}
