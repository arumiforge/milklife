<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Nomor lomba tiap atlet (tabel `atlet_nomor`). `biaya` terisi untuk nomor
 * individu (rupiah, disalin saat mendaftar) dan kosong untuk nomor beregu.
 */
class AtletNomorModel extends Model
{
    protected $table            = 'atlet_nomor';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['atlet_id', 'nomor_lomba_id', 'biaya'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * Jumlah atlet sebuah sekolah (NPSN) per nomor lomba, dari semua
     * pendaftarannya: untuk kuota nomor individu (K51) dan anggota tim
     * susulan (6.6).
     *
     * @return array<int, int> nomor_lomba_id => jumlah atlet
     */
    public function jumlahPerNomorUntukNpsn(string $npsn, ?string $jenis = null): array
    {
        $kueri = $this->db->table('atlet_nomor')
            ->select('atlet_nomor.nomor_lomba_id, COUNT(*) AS jumlah')
            ->join('atlet', 'atlet.id = atlet_nomor.atlet_id')
            ->join('pendaftaran', 'pendaftaran.id = atlet.pendaftaran_id')
            ->where('pendaftaran.npsn', $npsn)
            ->groupBy('atlet_nomor.nomor_lomba_id');

        if ($jenis !== null) {
            $kueri->join('nomor_lomba', 'nomor_lomba.id = atlet_nomor.nomor_lomba_id')->where('nomor_lomba.jenis', $jenis);
        }

        $hasil = [];

        foreach ($kueri->get()->getResultArray() as $baris) {
            $hasil[(int) $baris['nomor_lomba_id']] = (int) $baris['jumlah'];
        }

        return $hasil;
    }
}
