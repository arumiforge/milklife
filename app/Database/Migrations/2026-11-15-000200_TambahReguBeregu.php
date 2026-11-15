<?php

namespace App\Database\Migrations;

use App\Libraries\Pendaftaran\PeranAwalRegu;
use CodeIgniter\Database\Migration;

/**
 * Lebih dari satu regu per nomor beregu per sekolah (K105).
 *
 * - `nomor_lomba.regu_maksimal`: regu per sekolah paling banyak; kosong =
 *   tidak dibatasi. Nomor beregu yang sudah ada diberi 3 (Regu A, B, C).
 * - `atlet_nomor.regu` (huruf A–Z) dan `atlet_nomor.peran` (`inti` atau
 *   `cadangan`): hanya terisi untuk nomor beregu.
 * - `pendaftaran_beregu.regu`: satu baris per pendaftaran per nomor per regu
 *   (biaya regu 0 untuk susulan yang hanya menambah anggota regu itu).
 *
 * Data lama: semua tim menjadi Regu A. Atlet yang paling awal didaftarkan di
 * tim itu (satu tim = satu NPSN di satu nomor, atau satu pendaftaran bila
 * tanpa NPSN) sebanyak jumlah inti menjadi inti, sisanya cadangan.
 */
class TambahReguBeregu extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nomor_lomba', [
            'regu_maksimal' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'batas_anggota',
                'comment'    => 'Regu per sekolah paling banyak (beregu); kosong = tidak dibatasi',
            ],
        ]);
        $this->db->table('nomor_lomba')->where('jenis', 'beregu')->update(['regu_maksimal' => 3]);

        $this->forge->addColumn('atlet_nomor', [
            'regu' => [
                'type'       => 'CHAR',
                'constraint' => 1,
                'null'       => true,
                'after'      => 'biaya',
                'comment'    => 'Label regu (A–Z), hanya nomor beregu',
            ],
            'peran' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'null'       => true,
                'after'      => 'regu',
                'comment'    => 'inti atau cadangan, hanya nomor beregu',
            ],
        ]);

        $this->forge->addColumn('pendaftaran_beregu', [
            'regu' => [
                'type'       => 'CHAR',
                'constraint' => 1,
                'null'       => false,
                'default'    => 'A',
                'after'      => 'nomor_lomba_id',
                'comment'    => 'Label regu (A–Z)',
            ],
        ]);

        // Kunci unik baru dulu, supaya kunci asing pendaftaran_id tetap punya indeks.
        $this->forge->addUniqueKey(['pendaftaran_id', 'nomor_lomba_id', 'regu'], 'pendaftaran_beregu_regu');
        $this->forge->processIndexes('pendaftaran_beregu');
        $this->forge->dropKey('pendaftaran_beregu', 'pendaftaran_id_nomor_lomba_id', false);

        // Tim lama menjadi Regu A; inti menurut urutan daftar (K105).
        PeranAwalRegu::isi($this->db);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('regu', 'pendaftaran_beregu')) {
            // Hanya bisa kembali bila tiap pendaftaran punya satu regu per nomor.
            $this->db->table('pendaftaran_beregu')->where('regu !=', 'A')->delete();
            $this->forge->addUniqueKey(['pendaftaran_id', 'nomor_lomba_id'], 'pendaftaran_id_nomor_lomba_id');
            $this->forge->processIndexes('pendaftaran_beregu');
            $this->forge->dropKey('pendaftaran_beregu', 'pendaftaran_beregu_regu', false);
            $this->forge->dropColumn('pendaftaran_beregu', 'regu');
        }

        if ($this->db->fieldExists('regu', 'atlet_nomor')) {
            $this->forge->dropColumn('atlet_nomor', ['regu', 'peran']);
        }

        if ($this->db->fieldExists('regu_maksimal', 'nomor_lomba')) {
            $this->forge->dropColumn('nomor_lomba', 'regu_maksimal');
        }
    }
}
