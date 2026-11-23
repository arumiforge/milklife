<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rekor kejuaraan (F11-06, K172 butir 3), dicatat mulai Seri 2.
 *
 * - `nomor_lomba.berat_alat`: spesifikasi alat berupa teks bebas per nomor,
 *   misalnya "Peluru 3 kg" (CR 25.2). Rekor dengan alat berbeda tidak saling
 *   memecahkan.
 * - Tabel `rekor`: satu baris satu rekor yang pernah tercatat untuk satu
 *   nomor lomba (kelompok umur dan jenis kelamin ikut nomornya), berat alat,
 *   dan cara pencatatan waktu. Rekor yang berlaku = prestasi terbaik di antara
 *   baris dengan kunci yang sama.
 *   - `hasil_id` terisi untuk rekor yang dibuat otomatis saat hasil Final
 *     diterbitkan; baris itu dihapus lagi saat terbitan ditarik, jadi rekor
 *     lama berlaku kembali. Kosong untuk rekor yang diisi admin.
 *   - Nama nomor, kelompok umur, kategori, atlet, dan sekolah disalin,
 *     supaya rekor tetap terbaca walau nomor atau atletnya sudah dihapus.
 *
 * Hanya menambah kolom dan tabel (aturan Tahap 9 butir 1).
 */
class BuatTabelRekor extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'berat_alat' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'cara_waktu',
                'comment'    => 'Spesifikasi alat, teks bebas (F11-06)',
            ],
        ]);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nomor_lomba_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'kelompok_umur' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'nomor' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'comment'    => 'Nama nomor lengkap saat rekor dicatat',
            ],
            'kategori' => [
                'type'       => 'ENUM',
                'constraint' => ['putra', 'putri', 'mix'],
            ],
            'jenis_hasil' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
            ],
            'cara_waktu' => [
                'type'       => 'ENUM',
                'constraint' => ['elektronik', 'tangan'],
                'null'       => true,
                'comment'    => 'Hanya untuk jenis hasil waktu',
            ],
            'berat_alat' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            'prestasi' => [
                'type'    => 'INT',
                'comment' => 'Satuan sama dengan hasil.prestasi',
            ],
            'atlet' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Nama atlet, atau anggota regu',
            ],
            'sekolah' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'seri' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'tanggal' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'hasil_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'Terisi untuk rekor dari hasil terbit',
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
        $this->forge->addForeignKey('nomor_lomba_id', 'nomor_lomba', 'id', 'RESTRICT', 'SET NULL', 'rekor_nomor_lomba');
        $this->forge->addForeignKey('hasil_id', 'hasil', 'id', 'RESTRICT', 'SET NULL', 'rekor_hasil');

        $this->forge->createTable('rekor', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('rekor', true);
        $this->forge->dropColumn('nomor_lomba', ['berat_alat']);
    }
}
