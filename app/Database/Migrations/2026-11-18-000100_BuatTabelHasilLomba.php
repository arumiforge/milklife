<?php

namespace App\Database\Migrations;

use App\Libraries\Hasil\JenisHasil;
use CodeIgniter\Database\Migration;

/**
 * Hasil lomba (F8-04, K124).
 *
 * - `nomor_lomba.jenis_hasil`: `waktu`, `jarak`, atau `poin`. Nomor yang sudah
 *   ada diisi tebakan dari namanya (`JenisHasil::dariNama`, sesuai juknis).
 * - `nomor_lomba.hasil_terbit_pada`: kapan hasil nomor itu diterbitkan ke
 *   halaman depan; kosong = belum terbit.
 * - `babak`: babak per nomor lomba (Penyisihan seri 1, …, Final); satu Final
 *   per nomor dijaga aplikasi (`App\Libraries\Hasil\HasilLomba`).
 * - `hasil`: satu baris per peserta per babak. Nomor individu menunjuk
 *   `atlet_id`; nomor beregu memakai `kontingen` (NPSN, atau `p<id>` untuk
 *   pendaftaran tanpa NPSN, K124 butir 6) dan huruf `regu`. Prestasi bilangan
 *   bulat: perseratus detik, sentimeter, atau poin.
 */
class BuatTabelHasilLomba extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'jenis_hasil' => [
                'type'       => 'ENUM',
                'constraint' => array_keys(JenisHasil::LABEL),
                'null'       => false,
                'default'    => JenisHasil::WAKTU,
                'after'      => 'kategori',
            ],
            'hasil_terbit_pada' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'aktif',
            ],
        ]);

        foreach ($this->db->table('nomor_lomba')->select('id, nama')->get()->getResultArray() as $nomor) {
            $jenis = JenisHasil::dariNama((string) $nomor['nama']);

            if ($jenis !== JenisHasil::WAKTU) {
                $this->db->table('nomor_lomba')->where('id', $nomor['id'])->update(['jenis_hasil' => $jenis]);
            }
        }

        $tabel = [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ];

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
        $this->forge->addForeignKey('nomor_lomba_id', 'nomor_lomba', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('babak', false, $tabel);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'babak_id'         => ['type' => 'INT', 'unsigned' => true],
            'atlet_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'kontingen'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'regu'             => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'prestasi'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'keterangan'       => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
            'peringkat'        => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'peringkat_manual' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'dibuat_pada'      => ['type' => 'DATETIME', 'null' => true],
            'diubah_pada'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['babak_id', 'atlet_id']);
        $this->forge->addUniqueKey(['babak_id', 'kontingen', 'regu']);
        $this->forge->addForeignKey('babak_id', 'babak', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('atlet_id', 'atlet', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('hasil', false, $tabel);
    }

    public function down(): void
    {
        $this->forge->dropTable('hasil', true);
        $this->forge->dropTable('babak', true);

        $ada = array_values(array_filter(['jenis_hasil', 'hasil_terbit_pada'], fn (string $kolom): bool => $this->db->query('SHOW COLUMNS FROM ' . $this->db->prefixTable('nomor_lomba') . ' LIKE ?', [$kolom])->getNumRows() > 0));

        if ($ada !== []) {
            $this->forge->dropColumn('nomor_lomba', $ada);
        }
    }
}
