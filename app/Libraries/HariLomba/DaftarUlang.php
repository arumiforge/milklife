<?php

namespace App\Libraries\HariLomba;

use App\Libraries\DataPendaftaran;
use App\Libraries\Pendaftaran\Regu;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Daftar ulang hari lomba (F8-03, K124): cari pendaftaran dari kode QR bukti
 * atau ID, lalu tandai atletnya hadir.
 *
 * - `cari()`: pendaftaran (bentuk `DataPendaftaran::cariId()`) ditambah daftar
 *   atlet dengan nomor atlet (BIB), nomor lomba, regu, dan tanda hadir. ID
 *   lama yang sudah digabung (K109) memberi pendaftaran gabungannya.
 * - `tandaiHadir()` dan `batalHadir()`: hanya atlet milik pendaftaran itu.
 *   Atlet yang sudah hadir tidak diubah waktunya.
 *
 * Pengendali memastikan pendaftarannya Lunas sebelum menandai hadir.
 *
 *     $daftarUlang = new DaftarUlang();
 *     $hasil = $daftarUlang->cari('MAC2A0000126'); // null bila tidak ada
 *     $hasil['pendaftaran']['status'];             // 'lunas'
 *     $hasil['atlet'][0]['hadir_pada'];            // null atau '2026-12-09 07:15:00'
 *     $hasil['digabung_dari'];                     // ID yang dicari bila sudah digabung, selain itu null
 *     $daftarUlang->tandaiHadir($id, null, 'Admin Satu');  // semua atlet; jumlah yang baru ditandai
 */
final class DaftarUlang
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return array{pendaftaran: array<string, mixed>, id: int, atlet: list<array<string, mixed>>, digabung_dari: string|null}|null
     */
    public function cari(string $idPendaftaran): ?array
    {
        $pendaftaran = (new DataPendaftaran($this->db))->cariId($idPendaftaran);

        if ($pendaftaran === null) {
            return null;
        }

        $baris = $this->db->table('pendaftaran')->select('id')->where('id_pendaftaran', $pendaftaran['id_pendaftaran'])->get()->getRow();
        $id    = (int) ($baris->id ?? 0);

        return [
            'pendaftaran'   => $pendaftaran,
            'id'            => $id,
            'atlet'         => $this->atlet($id),
            'digabung_dari' => strcasecmp((string) $pendaftaran['id_pendaftaran'], trim($idPendaftaran)) !== 0 ? strtoupper(trim($idPendaftaran)) : null,
        ];
    }

    /**
     * Atlet satu pendaftaran, urut nomor atlet (BIB; yang belum bernomor di
     * akhir) lalu nama. `nomor_lomba`: daftar teks, nomor beregu ditulis
     * dengan regunya dan "(cadangan)" bila cadangan.
     *
     * @return list<array{id: int, nama: string, nisn: string, jenis_kelamin: string, kelompok_umur: string, nomor_dada: string|null, hadir_pada: string|null, hadir_oleh: string|null, nomor_lomba: list<string>}>
     */
    public function atlet(int $pendaftaranId): array
    {
        $atlet = $this->db->table('atlet a')
            ->select('a.id, a.nama, a.nisn, a.jenis_kelamin, a.nomor_dada, a.hadir_pada, a.hadir_oleh, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('a.pendaftaran_id', $pendaftaranId)
            ->orderBy('a.nomor_dada IS NULL', '', false)->orderBy('a.nomor_dada')->orderBy('a.nama')->orderBy('a.id')
            ->get()->getResultArray();

        if ($atlet === []) {
            return [];
        }

        $nomor = [];

        foreach ($this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.regu, an.peran, nl.nama, nl.kategori, nl.jenis')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->whereIn('an.atlet_id', array_map(static fn (array $a): int => (int) $a['id'], $atlet))
            ->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray() as $n) {
            $nama = NomorLombaModel::namaLengkap($n);

            if ($n['jenis'] === 'beregu') {
                $nama = Regu::nama($nama, (string) ($n['regu'] ?? 'A')) . ($n['peran'] === Regu::CADANGAN ? ' (cadangan)' : '');
            }

            $nomor[(int) $n['atlet_id']][] = $nama;
        }

        return array_map(static fn (array $a): array => [
            'id'            => (int) $a['id'],
            'nama'          => (string) $a['nama'],
            'nisn'          => (string) $a['nisn'],
            'jenis_kelamin' => (string) $a['jenis_kelamin'],
            'kelompok_umur' => (string) $a['kelompok_umur'],
            'nomor_dada'    => $a['nomor_dada'],
            'hadir_pada'    => $a['hadir_pada'],
            'hadir_oleh'    => $a['hadir_oleh'],
            'nomor_lomba'   => $nomor[(int) $a['id']] ?? [],
        ], $atlet);
    }

    /**
     * Tandai hadir atlet pendaftaran ini: `$atletId` kosong (`null`) berarti
     * semua atletnya. Atlet yang sudah hadir dilewati.
     *
     * @param list<int>|null $atletId
     *
     * @return int banyak atlet yang baru ditandai hadir
     */
    public function tandaiHadir(int $pendaftaranId, ?array $atletId, string $oleh): int
    {
        if ($atletId === []) {
            return 0;
        }

        $kueri = $this->db->table('atlet')->where('pendaftaran_id', $pendaftaranId)->where('hadir_pada', null);

        if ($atletId !== null) {
            $kueri->whereIn('id', $atletId);
        }

        $kueri->update(['hadir_pada' => date('Y-m-d H:i:s'), 'hadir_oleh' => mb_substr($oleh, 0, 100)]);

        return $this->db->affectedRows();
    }

    /**
     * Batalkan tanda hadir satu atlet (salah tekan).
     */
    public function batalHadir(int $pendaftaranId, int $atletId): bool
    {
        $this->db->table('atlet')
            ->where('pendaftaran_id', $pendaftaranId)->where('id', $atletId)->where('hadir_pada IS NOT NULL', null, false)
            ->update(['hadir_pada' => null, 'hadir_oleh' => null]);

        return $this->db->affectedRows() > 0;
    }
}
