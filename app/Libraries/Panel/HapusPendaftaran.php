<?php

namespace App\Libraries\Panel;

use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Throwable;

/**
 * Hapus satu pendaftaran beserta isinya (rancangan 6.9; rencana Tahap 6,
 * "Hapus pendaftaran").
 *
 * - Baris `penanggung_jawab`, `atlet`, `atlet_nomor`, `pendaftaran_beregu`,
 *   dan `bukti_bayar` ikut terhapus lewat kunci asing `CASCADE`, dalam satu
 *   transaksi. NISN atletnya bisa didaftarkan lagi.
 * - Berkas foto penanggung jawab (`FotoPenanggungJawab::hapus()`) dan bukti
 *   bayar (`BerkasBuktiBayar::hapusBerkas()`) dihapus setelah transaksi
 *   berhasil, jadi kegagalan transaksi tidak menghilangkan berkas.
 * - Baris pendaftaran dikunci (`SELECT … FOR UPDATE`) sebelum daftar berkas
 *   dibaca, supaya bukti bayar yang diunggah bersamaan menunggu dan tidak
 *   tertinggal sebagai berkas yatim.
 * - Nomor dada tidak dipakai ulang: `awalan_nomor_dada.nomor_terakhir` tidak
 *   diturunkan.
 *
 *     $hasil = (new HapusPendaftaran())->hapus($id);
 *     // null bila tidak ditemukan; selain itu
 *     // ['id_pendaftaran' => 'MAC2A0000126', 'nama_sekolah' => 'SD …', 'jumlah_atlet' => 3]
 */
final class HapusPendaftaran
{
    private BaseConnection $db;

    private FotoPenanggungJawab $foto;

    private BerkasBuktiBayar $bukti;

    public function __construct(?BaseConnection $db = null, ?FotoPenanggungJawab $foto = null, ?BerkasBuktiBayar $bukti = null)
    {
        $this->db    = $db ?? db_connect();
        $this->foto  = $foto ?? new FotoPenanggungJawab();
        $this->bukti = $bukti ?? new BerkasBuktiBayar();
    }

    /**
     * @return array{id_pendaftaran: string, nama_sekolah: string, jumlah_atlet: int}|null
     */
    public function hapus(int $pendaftaranId): ?array
    {
        $this->db->resetTransStatus();
        $this->db->transBegin();

        try {
            $baris = $this->baca('SELECT id, id_pendaftaran, nama_sekolah FROM pendaftaran WHERE id = ? FOR UPDATE', [$pendaftaranId])[0] ?? null;

            if ($baris === null) {
                $this->db->transRollback();

                return null;
            }

            $foto        = array_values(array_filter(array_column($this->baca('SELECT foto FROM penanggung_jawab WHERE pendaftaran_id = ?', [$pendaftaranId]), 'foto')));
            $bukti       = array_column($this->baca('SELECT berkas FROM bukti_bayar WHERE pendaftaran_id = ?', [$pendaftaranId]), 'berkas');
            $jumlahAtlet = (int) ($this->baca('SELECT COUNT(*) AS jumlah FROM atlet WHERE pendaftaran_id = ?', [$pendaftaranId])[0]['jumlah'] ?? 0);

            if ($this->db->table('pendaftaran')->where('id', $pendaftaranId)->delete() === false || $this->db->transStatus() === false) {
                throw $this->galat('Pendaftaran gagal dihapus');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            throw $e;
        }

        foreach ($foto as $jalur) {
            $this->foto->hapus((string) $jalur);
        }

        $this->bukti->hapusBerkas(array_map('strval', $bukti));

        return [
            'id_pendaftaran' => (string) $baris['id_pendaftaran'],
            'nama_sekolah'   => (string) $baris['nama_sekolah'],
            'jumlah_atlet'   => $jumlahAtlet,
        ];
    }

    /**
     * Kueri baca yang selalu melempar `DatabaseException` bila gagal (di dalam
     * transaksi CodeIgniter mengembalikan `false`).
     *
     * @param list<int|string> $ikat
     *
     * @return list<array<string, mixed>>
     */
    private function baca(string $sql, array $ikat): array
    {
        $hasil = $this->db->query($sql, $ikat);

        if (! is_object($hasil)) {
            throw $this->galat('Data pendaftaran gagal dibaca');
        }

        return $hasil->getResultArray();
    }

    private function galat(string $awal): DatabaseException
    {
        $galat = $this->db->error();

        return new DatabaseException($awal . ': ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
    }
}
