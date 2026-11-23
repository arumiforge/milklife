<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Peran akun di tabel `admin` (F11-16, K181).
 *
 * - `admin`: bisa membuka semua halaman panel (bawaan, termasuk semua akun
 *   yang sudah ada).
 * - `juri`: hanya Lembar juri dan halaman Akun miliknya sendiri. Dibuat admin
 *   di Pengaturan > Akun juri atau lewat `php spark admin:buat --peran juri`.
 *
 * Hanya menambah kolom dengan nilai awal `admin`, jadi aman dipasang di
 * produksi (K139). Tabel `admin` tidak ikut cadangan (K176 butir 10).
 */
class TambahPeranAdmin extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('admin', [
            'peran' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
                'default'    => 'admin',
                'after'      => 'nama_pengguna',
                'comment'    => 'admin = semua halaman; juri = hanya Lembar juri dan Akun',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('peran', 'admin')) {
            $this->forge->dropColumn('admin', 'peran');
        }
    }
}
