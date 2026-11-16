<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * KU 12 menerima kelahiran 2014–2015 sesuai juknis ("batas usia maksimal
 * kelahiran 2014"), bukan hanya 2015 seperti data awal K36 (K112).
 *
 * Hanya mengubah baris KU 12 yang rentangnya masih bawaan (2015–2015). Bila
 * admin sudah mengubahnya sendiri di panel, nilai admin tidak disentuh.
 * Kelompok umur lain jenjang SD/MI yang memakai tahun 2014 tidak ada di data
 * awal; bila ada, baris ini juga dilewati supaya tidak terjadi irisan dalam
 * satu jenjang (K95).
 */
class KelompokUmur12SesuaiJuknis extends Migration
{
    public function up(): void
    {
        if (! $this->adaKolomTahunLahir()) {
            return;
        }

        $ku = $this->db->table('kelompok_umur')
            ->where('nama', 'KU 12')->where('tahun_lahir_awal', 2015)->where('tahun_lahir_akhir', 2015)
            ->get()->getRowArray();

        if ($ku === null) {
            return;
        }

        $jenjang = array_column($this->db->table('kelompok_umur_jenjang')->select('jenjang')->where('kelompok_umur_id', $ku['id'])->get()->getResultArray(), 'jenjang');

        // Kelompok umur aktif lain di jenjang yang sama yang sudah menerima 2014.
        $bentrok = $jenjang === [] ? 0 : $this->db->table('kelompok_umur k')
            ->join('kelompok_umur_jenjang j', 'j.kelompok_umur_id = k.id')
            ->where('k.id <>', $ku['id'])->where('k.aktif', 1)->whereIn('j.jenjang', $jenjang)
            ->groupStart()->where('k.tahun_lahir_awal', null)->orWhere('k.tahun_lahir_awal <=', 2014)->groupEnd()
            ->groupStart()->where('k.tahun_lahir_akhir', null)->orWhere('k.tahun_lahir_akhir >=', 2014)->groupEnd()
            ->countAllResults();

        if ($bentrok > 0) {
            return;
        }

        $this->db->table('kelompok_umur')->where('id', $ku['id'])->update([
            'tahun_lahir_awal' => 2014,
            'diubah_pada'      => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        // Saat semua migrasi dibatalkan, kolom ini bisa sudah tidak ada.
        if (! $this->adaKolomTahunLahir()) {
            return;
        }

        $this->db->table('kelompok_umur')
            ->where('nama', 'KU 12')->where('tahun_lahir_awal', 2014)->where('tahun_lahir_akhir', 2015)
            ->update(['tahun_lahir_awal' => 2015]);
    }

    /**
     * Kolom batas tahun lahir ada. Dibaca langsung dari basis data, bukan
     * tembolok nama kolom CodeIgniter, yang bisa usang saat semua migrasi
     * dibatalkan lalu dijalankan lagi dalam satu proses.
     */
    private function adaKolomTahunLahir(): bool
    {
        return $this->db->query(
            "SELECT COUNT(*) AS ada FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'tahun_lahir_awal'",
            [$this->db->prefixTable('kelompok_umur')],
        )->getRow()->ada > 0;
    }
}
