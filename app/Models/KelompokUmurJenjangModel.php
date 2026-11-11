<?php

namespace App\Models;

use App\Libraries\Jenjang;
use CodeIgniter\Model;
use RuntimeException;

/**
 * Jenjang sekolah yang boleh ikut tiap kelompok umur (tabel
 * `kelompok_umur_jenjang`, K23, K24).
 *
 * Satu baris berarti jenjang itu boleh ikut kelompok umur tersebut; tanpa
 * baris berarti belum ada jenjang yang dibuka. Kode jenjang (`A`, `B`, `C`)
 * dan labelnya dari `App\Libraries\Jenjang`.
 *
 * Tidak membuka transaksi sendiri: `sinkronkan()` dipanggil
 * `KelompokUmurModel::simpanLengkap()` di dalam transaksinya.
 */
class KelompokUmurJenjangModel extends Model
{
    protected $table            = 'kelompok_umur_jenjang';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['kelompok_umur_id', 'jenjang'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * Kode jenjang satu kelompok umur, berurutan seperti `Jenjang::semua()`.
     *
     * @return list<string>
     */
    public function kodeUntuk(int $kelompokUmurId): array
    {
        return Jenjang::rapikan(array_column(
            $this->where('kelompok_umur_id', $kelompokUmurId)->findAll(),
            'jenjang',
        ));
    }

    /**
     * Kode jenjang semua kelompok umur: id kelompok umur => daftar kode.
     * Kelompok umur tanpa jenjang tidak muncul.
     *
     * @return array<int, list<string>>
     */
    public function kodePerKelompokUmur(): array
    {
        $kumpulan = [];

        foreach ($this->findAll() as $baris) {
            $kumpulan[(int) $baris['kelompok_umur_id']][] = $baris['jenjang'];
        }

        return array_map(static fn (array $kode): array => Jenjang::rapikan($kode), $kumpulan);
    }

    /**
     * Samakan jenjang satu kelompok umur dengan `$kode`: yang tidak dicentang
     * dihapus, yang baru ditambahkan, yang tetap tidak disentuh.
     *
     * @param list<string> $kode Kode yang sudah dirapikan (`Jenjang::rapikan()`).
     *
     * @throws RuntimeException Bila basis data gagal menyimpan.
     */
    public function sinkronkan(int $kelompokUmurId, array $kode): void
    {
        $kode = Jenjang::rapikan($kode);
        $ada  = $this->kodeUntuk($kelompokUmurId);

        $dibuang = array_values(array_diff($ada, $kode));

        if ($dibuang !== [] && $this->where('kelompok_umur_id', $kelompokUmurId)->whereIn('jenjang', $dibuang)->delete() === false) {
            throw new RuntimeException('Jenjang kelompok umur gagal dihapus.');
        }

        foreach (array_diff($kode, $ada) as $baru) {
            if ($this->insert(['kelompok_umur_id' => $kelompokUmurId, 'jenjang' => $baru]) === false) {
                throw new RuntimeException('Jenjang kelompok umur gagal disimpan.');
            }
        }
    }
}
