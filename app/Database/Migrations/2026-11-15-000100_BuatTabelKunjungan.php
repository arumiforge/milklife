<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel `kunjungan`: catatan pengunjung halaman publik (K102), satu baris
 * per halaman yang dibuka. Diisi filter `CatatPengunjung`, dibaca
 * `App\Libraries\Pengunjung` untuk dasbor dan `public/arumi.php`.
 *
 * Alamat IP tidak disimpan. `sidik` adalah potongan HMAC dari IP, peramban,
 * dan tanggal, jadi satu pengunjung hanya bisa dikenali dalam satu hari
 * (untuk menghitung pengunjung unik) dan tidak bisa dilacak antar-hari.
 */
class BuatTabelKunjungan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'waktu' => [
                'type' => 'DATETIME',
            ],
            'sidik' => [
                'type'       => 'CHAR',
                'constraint' => 16,
                'comment'    => 'Pengenal pengunjung dalam satu hari, bukan IP',
            ],
            'halaman' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'ponsel' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('waktu', false, false, 'kunjungan_waktu');

        $this->forge->createTable('kunjungan', false, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('kunjungan', true);
    }
}
