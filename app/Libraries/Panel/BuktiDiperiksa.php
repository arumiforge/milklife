<?php

namespace App\Libraries\Panel;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Penanda bukti bayar baru yang belum diperiksa admin (F8-02, K124).
 *
 * Bukti dari ofisial (wizard, Cek Pendaftaran) masuk dengan
 * `bukti_bayar.diperiksa_pada` kosong. Bukti dianggap sudah diperiksa:
 *
 * - saat pendaftarannya ditandai Lunas (`StatusPembayaran`, memanggil
 *   `tandai()` di transaksinya);
 * - saat admin menekan "Sudah diperiksa" di rincian (`tandaiDanCatat()`,
 *   dicatat di riwayat tindakan admin);
 * - langsung saat admin sendiri yang mengunggah (`BerkasBuktiBayar::unggahKe()`).
 *
 * Membuka rincian saja tidak menandai apa pun.
 *
 *     (new BuktiDiperiksa())->tandaiDanCatat($id);   // jumlah bukti yang ditandai, null bila pendaftaran tidak ada
 */
final class BuktiDiperiksa
{
    /**
     * Kondisi SQL "pendaftaran `p` punya bukti baru yang belum diperiksa".
     */
    public const SQL_ADA_BUKTI_BARU = 'EXISTS (SELECT 1 FROM bukti_bayar bb WHERE bb.pendaftaran_id = p.id AND bb.diperiksa_pada IS NULL)';

    /**
     * Kondisi SQL "pendaftaran `p` bukan 'Semua atlet ditolak'" (K117,
     * sama dengan `DataPanelPendaftaran::ditolakSemua()`).
     */
    public const SQL_BUKAN_DITOLAK_SEMUA = "NOT (p.status <> 'lunas' AND NOT EXISTS (SELECT 1 FROM atlet ba WHERE ba.pendaftaran_id = p.id)"
        . ' AND EXISTS (SELECT 1 FROM atlet_ditolak bd WHERE bd.pendaftaran_id = p.id))';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Tandai semua bukti baru satu pendaftaran sudah diperiksa, tanpa
     * transaksi sendiri. Hasilnya jumlah bukti yang ditandai.
     *
     * @throws DatabaseException Bila gagal ditulis.
     */
    public function tandai(int $pendaftaranId, ?string $namaAdmin = null): int
    {
        $hasil = $this->db->table('bukti_bayar')->where('pendaftaran_id', $pendaftaranId)->where('diperiksa_pada', null)->update([
            'diperiksa_pada' => self::sekarang(),
            'diperiksa_oleh' => mb_substr($namaAdmin ?? RiwayatAdmin::pelakuSesi()['nama'], 0, 100),
        ]);

        if ($hasil === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Bukti bayar gagal ditandai sudah diperiksa: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }

        return $this->db->affectedRows();
    }

    /**
     * Tombol "Sudah diperiksa": tandai bukti baru dan catat di riwayat, dalam
     * satu transaksi. `null` bila pendaftaran tidak ada; 0 bila tidak ada
     * bukti baru (tidak dicatat).
     */
    public function tandaiDanCatat(int $pendaftaranId): ?int
    {
        $this->db->resetTransStatus();
        $this->db->transBegin();

        try {
            $baris = $this->db->query('SELECT id, id_pendaftaran FROM pendaftaran WHERE id = ? FOR UPDATE', [$pendaftaranId]);
            $baris = is_object($baris) ? $baris->getRowArray() : null;

            if (! is_array($baris)) {
                $this->db->transRollback();

                return null;
            }

            $jumlah = $this->tandai($pendaftaranId);

            if ($jumlah > 0) {
                (new RiwayatAdmin($this->db))->catat($pendaftaranId, (string) $baris['id_pendaftaran'], RiwayatAdmin::PERIKSA_BUKTI, $jumlah . ' bukti bayar baru ditandai sudah diperiksa.');
            }

            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Transaksi tandai bukti bayar gagal.');
            }

            $this->db->transCommit();

            return $jumlah;
        } catch (Throwable $e) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            throw $e;
        }
    }

    private static function sekarang(): string
    {
        return Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');
    }
}
