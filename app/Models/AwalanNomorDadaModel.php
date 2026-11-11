<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Awalan nomor dada (tabel `awalan_nomor_dada`).
 *
 * Nomor dada 4 digit: 1 digit awalan + 3 digit urut (rancangan 6.9). Satu
 * kelompok umur boleh punya banyak awalan (K05), dipakai berurutan menurut
 * `urutan`. Awalan berupa satu angka 1–9 (angka 0 tidak dipakai supaya nomor
 * dada tidak kehilangan angka depan di Excel) dan unik di semua kelompok umur
 * (dijaga juga oleh indeks unik).
 *
 * `nomor_terakhir` adalah tiga digit urut terakhir yang sudah dibagikan.
 * Awalan dengan `nomor_terakhir > 0` sudah dipakai dan tidak bisa dihapus.
 *
 * Tidak membuka transaksi sendiri: `sinkronkan()` dipanggil
 * `KelompokUmurModel::simpanLengkap()` di dalam transaksinya.
 */
class AwalanNomorDadaModel extends Model
{
    protected $table            = 'awalan_nomor_dada';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['kelompok_umur_id', 'awalan', 'urutan', 'nomor_terakhir'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * Urai isian awalan dari formulir menjadi daftar awalan, urut sesuai
     * ketikan.
     *
     *     AwalanNomorDadaModel::urai('4, 6');  // [['4', '6'], null]
     *     AwalanNomorDadaModel::urai('4 6');   // [['4', '6'], null]
     *     AwalanNomorDadaModel::urai('');      // [[], null]
     *     AwalanNomorDadaModel::urai('46');    // [[], 'Awalan "46" belum benar. …']
     *
     * Pemisah: koma, titik koma, garis miring, atau spasi.
     *
     * @return array{0: list<string>, 1: string|null} [daftar awalan, pesan galat atau null]
     */
    public static function urai(?string $isian): array
    {
        $potongan = preg_split('/[\s,;\/]+/u', trim((string) $isian), -1, PREG_SPLIT_NO_EMPTY);
        $awalan   = [];

        foreach ($potongan === false ? [] : $potongan as $satu) {
            if (preg_match('/^[1-9]$/D', $satu) !== 1) {
                return [[], 'Awalan "' . $satu . '" belum benar. Awalan berupa satu angka 1 sampai 9; pisahkan beberapa awalan dengan koma, misalnya 4, 6.'];
            }

            if (in_array($satu, $awalan, true)) {
                return [[], 'Awalan ' . $satu . ' tertulis lebih dari sekali. Tulis setiap awalan sekali saja.'];
            }

            $awalan[] = $satu;
        }

        return [$awalan, null];
    }

    /**
     * Awalan satu kelompok umur, berurutan menurut `urutan`.
     *
     * @return list<array<string, mixed>>
     */
    public function milikKelompokUmur(int $kelompokUmurId): array
    {
        return $this->where('kelompok_umur_id', $kelompokUmurId)->orderBy('urutan')->orderBy('id')->findAll();
    }

    /**
     * Awalan semua kelompok umur: id kelompok umur => baris awalan
     * berurutan menurut `urutan`.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    public function perKelompokUmur(): array
    {
        $kumpulan = [];

        foreach ($this->orderBy('urutan')->orderBy('id')->findAll() as $baris) {
            $kumpulan[(int) $baris['kelompok_umur_id']][] = $baris;
        }

        return $kumpulan;
    }

    /**
     * Awalan dari `$awalan` yang sudah dimiliki kelompok umur lain:
     * awalan => nama kelompok umur pemiliknya.
     *
     * @param list<string> $awalan
     *
     * @return array<string, string>
     */
    public function dipakaiKelompokUmurLain(array $awalan, ?int $kecualiKelompokUmurId = null): array
    {
        if ($awalan === []) {
            return [];
        }

        $kueri = $this->db->table($this->table . ' a')
            ->select('a.awalan, k.nama')
            ->join('kelompok_umur k', 'k.id = a.kelompok_umur_id')
            ->whereIn('a.awalan', $awalan);

        if ($kecualiKelompokUmurId !== null) {
            $kueri->where('a.kelompok_umur_id !=', $kecualiKelompokUmurId);
        }

        $hasil = [];

        foreach ($kueri->get()->getResultArray() as $baris) {
            $hasil[$baris['awalan']] = $baris['nama'];
        }

        return $hasil;
    }

    /**
     * Samakan awalan satu kelompok umur dengan `$awalan`: awalan yang tidak
     * ditulis lagi dihapus, yang tetap diberi `urutan` baru sesuai ketikan
     * (`nomor_terakhir` tetap), yang baru ditambahkan dengan `nomor_terakhir` 0.
     *
     * Pemanggil sudah memastikan awalan yang dihapus belum pernah dipakai dan
     * awalan baru tidak dimiliki kelompok umur lain; sebagai pengaman,
     * awalan yang sudah dipakai tidak pernah dihapus di sini.
     *
     * @param list<string> $awalan Urut sesuai ketikan.
     *
     * @throws RuntimeException Bila awalan terpakai akan dihapus atau basis data gagal menyimpan.
     */
    public function sinkronkan(int $kelompokUmurId, array $awalan): void
    {
        $ada = [];

        foreach ($this->milikKelompokUmur($kelompokUmurId) as $baris) {
            $ada[$baris['awalan']] = $baris;
        }

        foreach ($ada as $kode => $baris) {
            if (in_array((string) $kode, $awalan, true)) {
                continue;
            }

            if ((int) $baris['nomor_terakhir'] > 0) {
                throw new RuntimeException('Awalan ' . $kode . ' sudah dipakai dan tidak bisa dihapus.');
            }

            if ($this->delete($baris['id']) === false) {
                throw new RuntimeException('Awalan ' . $kode . ' gagal dihapus.');
            }
        }

        foreach ($awalan as $indeks => $kode) {
            $urutan = $indeks + 1;

            if (isset($ada[$kode])) {
                if ((int) $ada[$kode]['urutan'] !== $urutan && $this->update($ada[$kode]['id'], ['urutan' => $urutan]) === false) {
                    throw new RuntimeException('Urutan awalan ' . $kode . ' gagal disimpan.');
                }

                continue;
            }

            if ($this->insert([
                'kelompok_umur_id' => $kelompokUmurId,
                'awalan'           => $kode,
                'urutan'           => $urutan,
                'nomor_terakhir'   => 0,
            ]) === false) {
                throw new RuntimeException('Awalan ' . $kode . ' gagal disimpan.');
            }
        }
    }
}
