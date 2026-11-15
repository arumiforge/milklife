<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Regu per sekolah tidak dibatasi secara bawaan (K107).
 *
 * - Nomor beregu yang masih memakai batas awal 3 dari `TambahReguBeregu`
 *   menjadi tidak dibatasi; batas lain yang sudah diubah admin dibiarkan.
 * - Daftar nama regu yang masih bawaan lama "A, B, C" dihapus dari pengaturan,
 *   sehingga official memilih huruf A, B, C, … berurutan tanpa batas.
 */
class ReguTanpaBatas extends Migration
{
    public function up(): void
    {
        $this->db->table('nomor_lomba')
            ->where('jenis', 'beregu')
            ->where('regu_maksimal', 3)
            ->update(['regu_maksimal' => null]);

        if ($this->db->tableExists('settings')) {
            $this->db->table('settings')
                ->where('class', 'Config\Pendaftaran')
                ->where('key', 'label_regu')
                ->where('value', 'A, B, C')
                ->delete();
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: batas yang kosong tidak bisa dibedakan dari yang dikosongkan admin.
    }
}
