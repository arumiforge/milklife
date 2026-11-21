<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel skor multi-lomba (F9-10, K140 butir 7b). Hanya menambah tabel dan kolom.
 *
 * - `tabel_skor`: satu tabel skor (Trilomba O2SN SMP, Pancalomba SMA/SMK, atau
 *   tabel buatan admin). `kode` unik untuk pemasangan ulang dan paket pindah
 *   server; `komponen` JSON `[{kode, nama, jenis}]`, jenis `waktu` atau `jarak`.
 * - `tabel_skor_baris`: baris prestasi → poin per komponen dan kategori
 *   (putra/putri). Prestasi dalam satuan `hasil.prestasi` (perseratus detik
 *   atau sentimeter). Ikut terhapus bila tabelnya dihapus.
 * - `nomor_lomba.tabel_skor_id` dan `nomor_lomba.komponen_skor`: tabel dan
 *   komponen (kode dipisah koma, urut tampil) untuk nomor berjenis hasil
 *   `multi`. Tabel yang dipakai nomor tidak bisa dihapus.
 * - `hasil.rincian`: JSON prestasi dan poin per komponen untuk nomor `multi`;
 *   `hasil.prestasi` tetap berisi total poin, jadi peringkat, klasemen, dan
 *   unduhan lama tetap jalan.
 */
class BuatTabelSkor extends Migration
{
    public function up(): void
    {
        $tabel = ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode'        => ['type' => 'VARCHAR', 'constraint' => 60],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'keterangan'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'komponen'    => ['type' => 'TEXT', 'comment' => 'JSON [{kode, nama, jenis}]'],
            'dibuat_pada' => ['type' => 'DATETIME', 'null' => true],
            'diubah_pada' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode', 'tabel_skor_kode');
        $this->forge->createTable('tabel_skor', false, $tabel);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tabel_skor_id' => ['type' => 'INT', 'unsigned' => true],
            'komponen'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'kategori'      => ['type' => 'VARCHAR', 'constraint' => 5, 'comment' => 'putra atau putri'],
            'prestasi'      => ['type' => 'INT', 'unsigned' => true, 'comment' => 'Perseratus detik atau sentimeter'],
            'poin'          => ['type' => 'SMALLINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tabel_skor_id', 'komponen', 'kategori', 'prestasi'], 'tabel_skor_baris_unik');
        $this->forge->addForeignKey('tabel_skor_id', 'tabel_skor', 'id', 'RESTRICT', 'CASCADE', 'tabel_skor_baris_tabel');
        $this->forge->createTable('tabel_skor_baris', false, $tabel);

        $this->forge->addColumn('nomor_lomba', [
            'tabel_skor_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'jenis_hasil',
                'comment'  => 'Tabel skor untuk jenis hasil multi',
            ],
            'komponen_skor' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'tabel_skor_id',
                'comment'    => 'Kode komponen dipisah koma, untuk jenis hasil multi',
            ],
        ]);
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('nomor_lomba') . ' ADD CONSTRAINT nomor_lomba_tabel_skor FOREIGN KEY (tabel_skor_id) REFERENCES ' . $this->db->prefixTable('tabel_skor') . ' (id) ON UPDATE RESTRICT ON DELETE RESTRICT');

        $this->forge->addColumn('hasil', [
            'rincian' => [
                'type'    => 'TEXT',
                'null'    => true,
                'after'   => 'prestasi',
                'comment' => 'JSON prestasi dan poin per komponen (jenis hasil multi)',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('rincian', 'hasil')) {
            $this->forge->dropColumn('hasil', 'rincian');
        }

        if ($this->db->fieldExists('tabel_skor_id', 'nomor_lomba')) {
            $this->forge->dropForeignKey('nomor_lomba', 'nomor_lomba_tabel_skor');
            $this->forge->dropColumn('nomor_lomba', ['tabel_skor_id', 'komponen_skor']);
        }

        $this->forge->dropTable('tabel_skor_baris', true);
        $this->forge->dropTable('tabel_skor', true);
    }
}
