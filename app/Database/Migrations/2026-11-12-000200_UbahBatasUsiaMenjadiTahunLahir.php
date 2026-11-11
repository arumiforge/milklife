<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Batas kelompok umur menurut tahun lahir, bukan usia (K36).
 *
 * `usia_minimal` dan `usia_maksimal` diganti `tahun_lahir_awal` (tahun lahir
 * paling awal, atlet tertua) dan `tahun_lahir_akhir` (tahun lahir paling
 * akhir, atlet termuda). Keduanya boleh kosong: kosong berarti tidak dibatasi
 * di sisi itu, misalnya KU 8 "lahir 2019 ke atas" punya awal 2019 dan akhir
 * kosong. Bulan dan tanggal lahir tidak diperhatikan.
 *
 * Isi kolom lama tidak dipindahkan: usia tidak bisa diubah menjadi tahun lahir
 * tanpa tanggal patokan (K37), dan sampai migrasi ini kolom lama belum pernah
 * diisi (`data:awal` mengosongkannya). `down()` mengembalikan kolom lama dalam
 * keadaan kosong.
 */
class UbahBatasUsiaMenjadiTahunLahir extends Migration
{
    public function up(): void
    {
        $this->forge->dropColumn('kelompok_umur', ['usia_minimal', 'usia_maksimal']);
        $this->forge->addColumn('kelompok_umur', [
            'tahun_lahir_awal' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'nama',
            ],
            'tahun_lahir_akhir' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'tahun_lahir_awal',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('kelompok_umur', ['tahun_lahir_awal', 'tahun_lahir_akhir']);
        $this->forge->addColumn('kelompok_umur', [
            'usia_minimal' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'nama',
            ],
            'usia_maksimal' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'usia_minimal',
            ],
        ]);
    }
}
