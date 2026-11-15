<?php

namespace App\Libraries\Panel;

use App\Libraries\Pendaftaran\Regu;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Tim beregu tanpa pembayar biaya tim (K68; rencana Tahap 7, "Tim tanpa
 * pembayar").
 *
 * Satu tim adalah satu regu sekolah (NPSN) di satu nomor beregu, sama dengan
 * cara hitung `Rekap` (K105): anggota dari pendaftaran susulan ber-NPSN sama
 * di regu yang sama ikut regu itu, dan setiap pendaftaran anggota regu punya
 * satu baris `pendaftaran_beregu` (biaya regu 0 untuk susulan). Kunci tim
 * adalah "nomor:regu" (`Regu::kunci()`). Tim **tanpa pembayar**
 * bila tidak ada satu pun baris itu yang `biaya_tim`-nya lebih dari 0,
 * sementara tarif beregu nomor itu saat ini lebih dari 0. Sekolah tanpa NPSN
 * tidak bisa punya tim susulan dan tidak pernah ditandai.
 *
 * Biaya tim tidak dipindah otomatis (K68). Rincian pendaftaran, dasbor, dan
 * rekap keuangan memakai pustaka ini supaya angkanya selalu sama; admin
 * menagih lewat Ubah ("Bebankan biaya tim ke pendaftaran ini").
 *
 *     $tim = new TimTanpaPembayar();
 *     $tim->daftar();               // semua tim tanpa pembayar
 *     $tim->jumlah();               // count($tim->daftar())
 *     $tim->untukPendaftaran($id);  // nomor_lomba_id => tim, hanya tim yang memuat pendaftaran ini
 *     $tim->ditinggalkan($id);      // tim yang kehilangan pembayar bila pendaftaran ini dihapus
 *     $tim->ditinggalkan($id, '20317000', ['12:A']); // … bila NPSN dan regunya menjadi ini (Ubah)
 *     $tim->tarifBebankan($id, '20317000', 12, 'A');  // tarif bila boleh dibebankan ke pendaftaran ini, atau null
 *
 * Bentuk satu tim:
 *
 *     [
 *         'npsn' => '20317000', 'nomor_lomba_id' => 12, 'regu' => 'A', 'kunci' => '12:A',
 *         'nomor_lomba' => "Kanga's Escape putra Regu A",
 *         'kelompok_umur' => 'KU 10', 'nama_sekolah' => 'SD …', 'kabupaten' => 'Kudus',
 *         'tarif' => 100000, 'jumlah_anggota' => 5,
 *         'pendaftaran' => [['id' => 7, 'id_pendaftaran' => 'MAC2A0000726', 'status' => 'menunggu', 'jumlah_anggota' => 2], …],
 *     ]
 */
final class TimTanpaPembayar
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Semua tim tanpa pembayar, berurutan menurut kelompok umur, nomor lomba,
     * lalu nama sekolah.
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(): array
    {
        return array_values(array_map(self::bentuk(...), array_filter($this->tim(), self::tanpaPembayar(...))));
    }

    public function jumlah(): int
    {
        return count($this->daftar());
    }

    /**
     * Tim tanpa pembayar yang memuat pendaftaran ini, menurut kunci "nomor:regu".
     *
     * @return array<int, array<string, mixed>>
     */
    public function untukPendaftaran(int $pendaftaranId): array
    {
        $npsn = $this->npsnPendaftaran($pendaftaranId);

        if ($npsn === null) {
            return [];
        }

        $hasil = [];

        foreach ($this->tim($npsn) as $tim) {
            if (self::tanpaPembayar($tim) && isset($tim['baris'][$pendaftaranId])) {
                $hasil[$tim['kunci']] = self::bentuk($tim);
            }
        }

        return $hasil;
    }

    /**
     * Tim yang kehilangan satu-satunya pembayar biaya timnya bila pendaftaran
     * ini keluar dari tim: dihapus (bawaan), atau diubah sehingga NPSN-nya
     * menjadi `$npsnBaru` dan regunya `$reguBaru`. Hanya tim yang masih punya
     * anggota dari pendaftaran lain; daftar pendaftarannya tanpa pendaftaran
     * ini.
     *
     * @param list<string> $reguBaru kunci "nomor:regu"
     *
     * @return list<array<string, mixed>>
     */
    public function ditinggalkan(int $pendaftaranId, ?string $npsnBaru = null, array $reguBaru = []): array
    {
        $npsn = $this->npsnPendaftaran($pendaftaranId);

        if ($npsn === null) {
            return [];
        }

        $hasil = [];

        foreach ($this->tim($npsn) as $tim) {
            $sendiri = $tim['baris'][$pendaftaranId] ?? null;

            if ($sendiri === null || $sendiri['biaya_tim'] <= 0) {
                continue;
            }

            if ($npsnBaru === $npsn && in_array($tim['kunci'], $reguBaru, true)) {
                continue;
            }

            unset($tim['baris'][$pendaftaranId]);

            if ($tim['baris'] !== [] && self::tanpaPembayar($tim)) {
                $hasil[] = self::bentuk($tim);
            }
        }

        return $hasil;
    }

    /**
     * Tarif beregu saat ini bila biaya regu (NPSN, nomor, regu) boleh
     * dibebankan ke pendaftaran ini: sekolah ber-NPSN, tarif lebih dari 0, dan
     * tidak ada pendaftaran lain regu itu yang sudah membayar biaya regu.
     * Selain itu `null`.
     */
    public function tarifBebankan(int $pendaftaranId, ?string $npsn, int $nomorLombaId, string $regu = 'A'): ?int
    {
        if ($npsn === null || $npsn === '') {
            return null;
        }

        $nomor = $this->db->table('nomor_lomba')->select('biaya_beregu')
            ->where('id', $nomorLombaId)->where('jenis', 'beregu')
            ->get()->getRowArray();
        $tarif = is_array($nomor) ? (int) $nomor['biaya_beregu'] : 0;

        if ($tarif <= 0) {
            return null;
        }

        $dibayar = $this->db->table('pendaftaran_beregu pb')
            ->join('pendaftaran p', 'p.id = pb.pendaftaran_id')
            ->where('p.npsn', $npsn)
            ->where('pb.nomor_lomba_id', $nomorLombaId)
            ->where('pb.regu', $regu)
            ->where('pb.pendaftaran_id !=', $pendaftaranId)
            ->where('pb.biaya_tim >', 0)
            ->countAllResults();

        return $dibayar > 0 ? null : $tarif;
    }

    /**
     * Nama regu ("Kanga's Escape putra Regu A"), untuk pesan galat.
     */
    public function namaNomor(int $nomorLombaId, string $regu = 'A'): string
    {
        $baris = $this->db->table('nomor_lomba')->select('nama, kategori')->where('id', $nomorLombaId)->get()->getRowArray();

        return Regu::nama(is_array($baris) ? NomorLombaModel::namaLengkap($baris) : 'nomor ' . $nomorLombaId, $regu);
    }

    /**
     * Kalimat peringatan untuk jendela hapus dan jendela simpan Ubah, atau
     * '' bila tidak ada tim yang ditinggalkan.
     *
     * @param list<array<string, mixed>> $tim hasil `ditinggalkan()`
     */
    public static function kalimatDitinggalkan(array $tim): string
    {
        if ($tim === []) {
            return '';
        }

        $bagian = array_map(static fn (array $t): string => $t['nomor_lomba'] . ' (anggota lain di pendaftaran '
            . implode(', ', array_column($t['pendaftaran'], 'id_pendaftaran')) . ')', $tim);

        return 'Perhatian: pendaftaran ini membayar biaya regu ' . implode('; ', $bagian) . '. '
            . 'Setelah ini ' . (count($tim) > 1 ? 'regu-regu itu' : 'regu itu') . ' tidak punya pembayar biaya regu, dan biayanya tidak dipindah otomatis. '
            . 'Tagihkan lewat Ubah di salah satu pendaftaran anggotanya dengan mencentang "Bebankan biaya regu ke pendaftaran ini".';
    }

    /**
     * Kalimat pesan kilat sesudah hapus atau simpan Ubah, atau '' bila tidak
     * ada tim yang ditinggalkan.
     *
     * @param list<array<string, mixed>> $tim hasil `ditinggalkan()` sebelum tindakan
     */
    public static function kalimatSesudah(array $tim): string
    {
        if ($tim === []) {
            return '';
        }

        return implode(', ', array_column($tim, 'nomor_lomba')) . ' kini tanpa pembayar biaya regu. '
            . 'Tagihkan lewat Ubah di salah satu pendaftaran anggotanya ('
            . implode(', ', array_unique(array_merge(...array_map(static fn (array $t): array => array_column($t['pendaftaran'], 'id_pendaftaran'), $tim)))) . ').';
    }

    /**
     * Semua tim bertarif di atas 0 dari sekolah ber-NPSN (atau satu NPSN),
     * dengan baris `pendaftaran_beregu` per pendaftaran.
     *
     * @return list<array<string, mixed>>
     */
    private function tim(?string $npsn = null): array
    {
        $an = $this->db->prefixTable('atlet_nomor');
        $a  = $this->db->prefixTable('atlet');

        $pembuat = $this->db->table('pendaftaran_beregu pb')
            ->select('p.id, p.id_pendaftaran, p.status, p.npsn, p.nama_sekolah, p.kabupaten, pb.nomor_lomba_id, pb.regu, pb.biaya_tim,'
                . ' nl.nama, nl.kategori, nl.biaya_beregu, nl.urutan AS urutan_nomor, ku.nama AS kelompok_umur, ku.urutan AS urutan_ku, ku.id AS ku_id')
            ->select("(SELECT COUNT(*) FROM {$an} an JOIN {$a} a ON a.id = an.atlet_id WHERE a.pendaftaran_id = p.id AND an.nomor_lomba_id = pb.nomor_lomba_id AND an.regu = pb.regu) AS anggota", false)
            ->join('pendaftaran p', 'p.id = pb.pendaftaran_id')
            ->join('nomor_lomba nl', 'nl.id = pb.nomor_lomba_id')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->where('p.npsn IS NOT NULL', null, false)
            ->where('p.npsn !=', '')
            ->where('nl.biaya_beregu >', 0)
            ->orderBy('p.id');

        if ($npsn !== null) {
            $pembuat->where('p.npsn', $npsn);
        }

        $tim = [];

        foreach ($pembuat->get()->getResultArray() as $b) {
            $kunci = $b['npsn'] . '|' . $b['nomor_lomba_id'] . '|' . $b['regu'];

            $tim[$kunci] ??= [
                'npsn'           => (string) $b['npsn'],
                'nomor_lomba_id' => (int) $b['nomor_lomba_id'],
                'regu'           => (string) $b['regu'],
                'kunci'          => Regu::kunci((int) $b['nomor_lomba_id'], (string) $b['regu']),
                'nomor_lomba'    => Regu::nama(NomorLombaModel::namaLengkap($b), (string) $b['regu']),
                'kelompok_umur'  => (string) $b['kelompok_umur'],
                'nama_sekolah'   => (string) $b['nama_sekolah'],
                'kabupaten'      => (string) $b['kabupaten'],
                'tarif'          => (int) $b['biaya_beregu'],
                'urutan'         => [(int) $b['urutan_ku'], (int) $b['ku_id'], (int) $b['urutan_nomor'], (int) $b['nomor_lomba_id'], (string) $b['nama_sekolah'], (string) $b['regu']],
                'baris'          => [],
            ];
            $tim[$kunci]['baris'][(int) $b['id']] = [
                'id'             => (int) $b['id'],
                'id_pendaftaran' => (string) $b['id_pendaftaran'],
                'status'         => (string) $b['status'],
                'jumlah_anggota' => (int) $b['anggota'],
                'biaya_tim'      => (int) $b['biaya_tim'],
            ];
        }

        usort($tim, static fn (array $x, array $y): int => $x['urutan'] <=> $y['urutan']);

        return $tim;
    }

    /**
     * @param array<string, mixed> $tim
     */
    private static function tanpaPembayar(array $tim): bool
    {
        foreach ($tim['baris'] as $b) {
            if ($b['biaya_tim'] > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $tim
     *
     * @return array<string, mixed>
     */
    private static function bentuk(array $tim): array
    {
        $pendaftaran = array_map(static function (array $b): array {
            unset($b['biaya_tim']);

            return $b;
        }, array_values($tim['baris']));

        unset($tim['baris'], $tim['urutan']);

        return $tim + [
            'jumlah_anggota' => array_sum(array_column($pendaftaran, 'jumlah_anggota')),
            'pendaftaran'    => $pendaftaran,
        ];
    }

    private function npsnPendaftaran(int $pendaftaranId): ?string
    {
        $baris = $this->db->table('pendaftaran')->select('npsn')->where('id', $pendaftaranId)->get()->getRowArray();
        $npsn  = is_array($baris) ? (string) ($baris['npsn'] ?? '') : '';

        return $npsn === '' ? null : $npsn;
    }
}
