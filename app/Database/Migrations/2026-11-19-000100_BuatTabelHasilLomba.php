<?php

namespace App\Database\Migrations;

use App\Libraries\Hasil\JenisHasil;
use CodeIgniter\Database\Migration;

/**
 * Hasil lomba semua babak (F8-04, K124, asumsi 6 K125).
 *
 * - `nomor_lomba.jenis_hasil`: `waktu`, `jarak`, atau `poin`; nomor yang sudah
 *   ada diisi menurut namanya (`JenisHasil::tebak()`), admin bisa menggantinya.
 * - `nomor_lomba.hasil_terbit_pada`: waktu admin menekan Terbitkan; kosong =
 *   hasil nomor itu belum tampil di halaman publik `hasil`.
 * - `babak`: babak per nomor lomba (Penyisihan seri 1, Final, …); satu
 *   Final per nomor dijaga aplikasi (`HasilLomba`). Ikut terhapus bila
 *   nomornya dihapus.
 * - `hasil`: satu baris per peserta per babak. Nomor individu menunjuk
 *   `atlet_id`; nomor beregu menunjuk `pendaftaran_id` + `regu` (nama regu, K106).
 *   `prestasi` bilangan bulat (perseratus detik, sentimeter, atau poin),
 *   `keterangan` DNS/DNF/DQ, `peringkat` dihitung otomatis kecuali
 *   `peringkat_manual` = 1 (koreksi admin di Final). Baris ikut terhapus bila
 *   atlet, pendaftaran, atau babaknya dihapus; saat pendaftaran digabung (K109)
 *   `pendaftaran_id` dipindah ke pendaftaran tujuan.
 */
class BuatTabelHasilLomba extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'jenis_hasil' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => false,
                'default'    => JenisHasil::WAKTU,
                'after'      => 'kategori',
                'comment'    => 'waktu, jarak, atau poin',
            ],
            'hasil_terbit_pada' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'aktif',
                'comment' => 'Waktu hasil diterbitkan; kosong = belum tampil di halaman publik',
            ],
        ]);

        foreach ($this->db->table('nomor_lomba')->select('id, nama')->get()->getResultArray() as $n) {
            $this->db->table('nomor_lomba')->where('id', $n['id'])->update(['jenis_hasil' => JenisHasil::tebak((string) $n['nama'])]);
        }

        $tabel = ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_lomba_id' => ['type' => 'INT', 'unsigned' => true],
            'nama'           => ['type' => 'VARCHAR', 'constraint' => 60],
            'final'          => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'urutan'         => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'dibuat_pada'    => ['type' => 'DATETIME', 'null' => true],
            'diubah_pada'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('nomor_lomba_id', 'nomor_lomba', 'id', 'RESTRICT', 'CASCADE', 'babak_nomor_lomba');
        $this->forge->createTable('babak', false, $tabel);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'babak_id'         => ['type' => 'INT', 'unsigned' => true],
            'atlet_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Peserta nomor individu'],
            'pendaftaran_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Peserta nomor beregu, bersama regu'],
            'regu'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => 'Nama regu (K106), nomor beregu'],
            'prestasi'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Perseratus detik, sentimeter, atau poin'],
            'keterangan'       => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true, 'comment' => 'DNS, DNF, atau DQ'],
            'peringkat'        => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'peringkat_manual' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0, 'comment' => '1 = peringkat dikoreksi admin'],
            'dibuat_pada'      => ['type' => 'DATETIME', 'null' => true],
            'diubah_pada'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['babak_id', 'atlet_id'], 'hasil_babak_atlet');
        $this->forge->addUniqueKey(['babak_id', 'pendaftaran_id', 'regu'], 'hasil_babak_regu');
        $this->forge->addForeignKey('babak_id', 'babak', 'id', 'RESTRICT', 'CASCADE', 'hasil_babak');
        $this->forge->addForeignKey('atlet_id', 'atlet', 'id', 'RESTRICT', 'CASCADE', 'hasil_atlet');
        $this->forge->addForeignKey('pendaftaran_id', 'pendaftaran', 'id', 'RESTRICT', 'CASCADE', 'hasil_pendaftaran');
        $this->forge->createTable('hasil', false, $tabel);
    }

    public function down(): void
    {
        $this->forge->dropTable('hasil', true);
        $this->forge->dropTable('babak', true);

        if ($this->db->fieldExists('jenis_hasil', 'nomor_lomba')) {
            $this->forge->dropColumn('nomor_lomba', ['jenis_hasil', 'hasil_terbit_pada']);
        }
    }
}
