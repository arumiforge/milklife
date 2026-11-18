<?php

namespace App\Libraries\Hasil;

use App\Libraries\Pendaftaran\Regu;
use CodeIgniter\Database\BaseConnection;

/**
 * Peserta satu nomor lomba untuk hasil lomba (F8-04): atlet untuk nomor
 * individu, regu untuk nomor beregu.
 *
 * - Kontingen = sekolah per NPSN; pendaftaran tanpa NPSN menjadi kontingen
 *   sendiri dengan kunci `p<id pendaftaran>` (K124 butir 6, sejalan K109).
 * - Satu regu = kontingen + huruf regu di nomor itu. Nama regu = nama sekolah,
 *   ditambah "Regu A" bila sekolah itu punya lebih dari satu regu di nomor
 *   itu (sejalan K108).
 * - `lunas`: pendaftarannya sudah Lunas (regu: salah satu anggotanya). Hanya
 *   peserta Lunas yang bisa ditambahkan ke babak; peserta yang sudah ada di
 *   babak tetap tampil walau statusnya berubah.
 *
 * Kunci peserta: `a<id atlet>` atau `r<kontingen>|<regu>`, dipakai di
 * formulir dan untuk mencocokkan baris `hasil`.
 */
final class PesertaNomor
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Kunci kontingen dari NPSN dan ID pendaftaran.
     */
    public static function kontingen(?string $npsn, int $pendaftaranId): string
    {
        $npsn = trim((string) $npsn);

        return $npsn !== '' ? $npsn : 'p' . $pendaftaranId;
    }

    public static function kunciAtlet(int $atletId): string
    {
        return 'a' . $atletId;
    }

    public static function kunciRegu(string $kontingen, string $regu): string
    {
        return 'r' . $kontingen . '|' . $regu;
    }

    /**
     * Kunci peserta dari satu baris `hasil`.
     *
     * @param array<string, mixed> $hasil
     */
    public static function kunciHasil(array $hasil): string
    {
        return ($hasil['atlet_id'] ?? null) !== null
            ? self::kunciAtlet((int) $hasil['atlet_id'])
            : self::kunciRegu((string) $hasil['kontingen'], (string) $hasil['regu']);
    }

    /**
     * Semua peserta nomor itu, urut nama sekolah lalu nama (atau regu).
     *
     * @param array<string, mixed> $nomor Baris `nomor_lomba`.
     *
     * @return array<string, array{kunci: string, atlet_id: int|null, kontingen: string, regu: string|null, nama: string, nomor_dada: string, sekolah: string, jenjang: string, lunas: bool, anggota: list<array{nama: string, nomor_dada: string, peran: string}>}>
     */
    public function semua(array $nomor): array
    {
        $baris = $this->db->table('atlet_nomor an')
            ->select('a.id AS atlet_id, a.nama, a.nomor_dada, an.regu, an.peran, p.id AS pendaftaran_id, p.npsn, p.nama_sekolah, p.jenjang, p.status')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('an.nomor_lomba_id', (int) $nomor['id'])
            ->orderBy('p.nama_sekolah', 'ASC')
            ->orderBy('an.regu', 'ASC')
            ->orderBy('an.peran', 'DESC')
            ->orderBy('a.nomor_dada', 'ASC')
            ->orderBy('a.nama', 'ASC')
            ->get()
            ->getResultArray();

        return ($nomor['jenis'] ?? '') === 'beregu' ? $this->regu($baris) : $this->individu($baris);
    }

    /**
     * Peserta Lunas yang belum ada di daftar kunci itu (pilihan "Tambah peserta").
     *
     * @param array<string, mixed> $nomor
     * @param list<string>         $sudahAda
     *
     * @return array<string, array<string, mixed>>
     */
    public function bisaDitambah(array $nomor, array $sudahAda): array
    {
        $ada = array_flip($sudahAda);

        return array_filter($this->semua($nomor), static fn (array $p): bool => $p['lunas'] && ! isset($ada[$p['kunci']]));
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return array<string, array<string, mixed>>
     */
    private function individu(array $baris): array
    {
        $hasil = [];

        foreach ($baris as $b) {
            $kunci         = self::kunciAtlet((int) $b['atlet_id']);
            $hasil[$kunci] = [
                'kunci'      => $kunci,
                'atlet_id'   => (int) $b['atlet_id'],
                'kontingen'  => self::kontingen($b['npsn'], (int) $b['pendaftaran_id']),
                'regu'       => null,
                'nama'       => (string) $b['nama'],
                'nomor_dada' => (string) ($b['nomor_dada'] ?? ''),
                'sekolah'    => (string) $b['nama_sekolah'],
                'jenjang'    => (string) $b['jenjang'],
                'lunas'      => $b['status'] === 'lunas',
                'anggota'    => [],
            ];
        }

        return $hasil;
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return array<string, array<string, mixed>>
     */
    private function regu(array $baris): array
    {
        $hasil     = [];
        $reguPerKt = [];

        foreach ($baris as $b) {
            $regu      = (string) ($b['regu'] ?? '') !== '' ? (string) $b['regu'] : 'A';
            $kontingen = self::kontingen($b['npsn'], (int) $b['pendaftaran_id']);
            $kunci     = self::kunciRegu($kontingen, $regu);

            $hasil[$kunci] ??= [
                'kunci'          => $kunci,
                'atlet_id'       => null,
                'kontingen'      => $kontingen,
                'regu'           => $regu,
                'nama'           => '',
                'nomor_dada'     => '',
                'sekolah'        => (string) $b['nama_sekolah'],
                'jenjang'        => (string) $b['jenjang'],
                'lunas'          => false,
                'anggota'        => [],
                'pendaftaran_id' => (int) $b['pendaftaran_id'],
            ];

            // Nama sekolah dan jenjang dari pendaftaran terbaru sekolah itu.
            if ((int) $b['pendaftaran_id'] > $hasil[$kunci]['pendaftaran_id']) {
                $hasil[$kunci]['sekolah']        = (string) $b['nama_sekolah'];
                $hasil[$kunci]['jenjang']        = (string) $b['jenjang'];
                $hasil[$kunci]['pendaftaran_id'] = (int) $b['pendaftaran_id'];
            }

            $hasil[$kunci]['lunas'] = $hasil[$kunci]['lunas'] || $b['status'] === 'lunas';
            $hasil[$kunci]['anggota'][] = [
                'nama'       => (string) $b['nama'],
                'nomor_dada' => (string) ($b['nomor_dada'] ?? ''),
                'peran'      => (string) ($b['peran'] ?? ''),
            ];
            $reguPerKt[$kontingen][$regu] = true;
        }

        foreach ($hasil as $kunci => $regu) {
            $tunggal               = count($reguPerKt[$regu['kontingen']]) === 1;
            $hasil[$kunci]['nama'] = $tunggal ? $regu['sekolah'] : $regu['sekolah'] . ' ' . Regu::label((string) $regu['regu']);
            unset($hasil[$kunci]['pendaftaran_id']);
        }

        return $hasil;
    }
}
