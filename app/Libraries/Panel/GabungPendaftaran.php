<?php

namespace App\Libraries\Panel;

use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Gabungkan pendaftaran Lunas dengan NPSN yang sama menjadi satu (K109).
 *
 * Satu sekolah (NPSN) paling banyak punya satu pendaftaran Lunas. Begitu
 * pendaftaran kedua ditandai Lunas, isinya pindah ke pendaftaran yang **Lunas
 * paling dulu** (`waktu_lunas` terawal, lalu `id` terkecil), lalu
 * pendaftaran itu dihapus:
 *
 * - `atlet` (beserta `atlet_nomor`), `bukti_bayar`, dan `usulan_nama_sekolah`
 *   pindah ke pendaftaran tujuan. Nomor dada tidak berubah.
 * - `penanggung_jawab` pindah, kecuali orang dengan nomor WA yang sudah ada
 *   di pendaftaran tujuan: barisnya dihapus (fotonya dipakai tujuan bila
 *   tujuan belum punya foto; selain itu berkas fotonya dikembalikan di `foto`
 *   untuk dihapus pemanggil setelah transaksi berhasil).
 * - `pendaftaran_beregu` pindah; regu yang sudah ada di tujuan (baris susulan
 *   berbiaya 0) dijumlahkan biayanya ke baris tujuan.
 * - Subtotal dan total dijumlahkan. Nama sekolah, jenjang, kabupaten, ID,
 *   kode bukti, dan `waktu_lunas` tujuan tetap.
 * - Riwayat tindakan admin (F8-01) pindah ke tujuan, dan tujuan mencatat
 *   "Digabung dari MAC…".
 * - ID dan kode bukti lama dicatat di `pendaftaran_gabungan`, termasuk catatan
 *   lama milik pendaftaran yang ikut digabung, supaya kode QR bukti lama dan
 *   Cek Pendaftaran dengan ID lama tetap sampai ke pendaftaran gabungan.
 *
 * Pendaftaran tanpa NPSN tidak pernah digabung. Penggabungan tidak bisa
 * dipisah lagi.
 *
 * Berjalan di dalam transaksi pemanggil bila ada (transaksi bersarang
 * CodeIgniter hanya menambah kedalaman), selain itu di transaksinya sendiri.
 * Baris pendaftaran ber-NPSN itu dikunci `SELECT … FOR UPDATE` urut `id`.
 *
 *     $hasil = (new GabungPendaftaran())->gabungNpsn('20300001');
 *     // null bila tidak ada yang digabung; selain itu
 *     // ['tujuan' => ['id' => 4, 'id_pendaftaran' => 'MAC2A0000426'],
 *     //  'digabung' => ['MAC2A0000926'], 'foto' => ['penanggung-jawab/….jpg']]
 *     (new GabungPendaftaran())->hapusFoto($hasil['foto']);   // setelah transaksi selesai
 */
final class GabungPendaftaran
{
    private BaseConnection $db;

    private FotoPenanggungJawab $foto;

    public function __construct(?BaseConnection $db = null, ?FotoPenanggungJawab $foto = null)
    {
        $this->db   = $db ?? db_connect();
        $this->foto = $foto ?? new FotoPenanggungJawab();
    }

    /**
     * Gabungkan semua pendaftaran Lunas NPSN ini ke yang Lunas paling dulu.
     *
     * @return array{tujuan: array{id: int, id_pendaftaran: string}, digabung: list<string>, foto: list<string>}|null
     *         `null` bila NPSN kosong atau kurang dari dua pendaftaran Lunas.
     */
    public function gabungNpsn(?string $npsn): ?array
    {
        $npsn = trim((string) $npsn);

        if ($npsn === '') {
            return null;
        }

        $this->mulai();

        try {
            $lunas = $this->baca(
                "SELECT id, id_pendaftaran, kode_bukti, subtotal_individu, subtotal_beregu, total, waktu_lunas FROM pendaftaran WHERE npsn = ? AND status = 'lunas' ORDER BY id FOR UPDATE",
                [$npsn],
            );

            if (count($lunas) < 2) {
                $this->selesai();

                return null;
            }

            usort($lunas, static fn (array $a, array $b): int => [(string) ($a['waktu_lunas'] ?? '9999'), (int) $a['id']] <=> [(string) ($b['waktu_lunas'] ?? '9999'), (int) $b['id']]);

            $tujuan   = array_shift($lunas);
            $sekarang = self::sekarang();
            $foto     = [];
            $digabung = [];

            foreach ($lunas as $sumber) {
                $foto       = array_merge($foto, $this->pindahkan($sumber, $tujuan, $sekarang));
                $digabung[] = (string) $sumber['id_pendaftaran'];

                $tujuan['subtotal_individu'] = (int) $tujuan['subtotal_individu'] + (int) $sumber['subtotal_individu'];
                $tujuan['subtotal_beregu']   = (int) $tujuan['subtotal_beregu'] + (int) $sumber['subtotal_beregu'];
                $tujuan['total']             = (int) $tujuan['total'] + (int) $sumber['total'];
            }

            $this->pastikan($this->db->table('pendaftaran')->where('id', (int) $tujuan['id'])->update([
                'subtotal_individu' => $tujuan['subtotal_individu'],
                'subtotal_beregu'   => $tujuan['subtotal_beregu'],
                'total'             => $tujuan['total'],
                'diubah_pada'       => $sekarang,
            ]));

            $this->selesai();

            return [
                'tujuan'   => ['id' => (int) $tujuan['id'], 'id_pendaftaran' => (string) $tujuan['id_pendaftaran']],
                'digabung' => $digabung,
                'foto'     => $foto,
            ];
        } catch (Throwable $e) {
            $this->batal();

            throw $e;
        }
    }

    /**
     * Hapus berkas foto penanggung jawab ganda. Panggil setelah transaksi
     * penggabungan berhasil.
     *
     * @param list<string> $foto
     */
    public function hapusFoto(array $foto): void
    {
        foreach ($foto as $jalur) {
            $this->foto->hapus($jalur);
        }
    }

    /**
     * Kalimat untuk admin, contoh "Pendaftaran MAC2A0000926 digabung ke
     * MAC2A0000426 karena NPSN-nya sama; ID lama tetap bisa dipakai di Cek
     * Pendaftaran dan kode QR bukti lama."
     *
     * @param array{tujuan: array{id: int, id_pendaftaran: string}, digabung: list<string>} $hasil
     */
    public static function kalimat(array $hasil): string
    {
        return 'Pendaftaran ' . implode(', ', $hasil['digabung']) . ' digabung ke ' . $hasil['tujuan']['id_pendaftaran']
            . ' karena NPSN-nya sama. ID lama tetap bisa dipakai di Cek Pendaftaran, dan kode QR bukti lama membuka bukti gabungan.';
    }

    /**
     * Pindahkan isi satu pendaftaran ke tujuan, catat ID lamanya, lalu hapus.
     *
     * @param array<string, mixed> $sumber
     * @param array<string, mixed> $tujuan
     *
     * @return list<string> Berkas foto penanggung jawab ganda yang perlu dihapus.
     */
    private function pindahkan(array $sumber, array $tujuan, string $sekarang): array
    {
        $dari = (int) $sumber['id'];
        $ke   = (int) $tujuan['id'];

        // Catatan atlet yang ditolak (K116) ikut pindah supaya tetap tampil di Cek Pendaftaran.
        foreach (['atlet', 'bukti_bayar', 'usulan_nama_sekolah', 'atlet_ditolak'] as $tabel) {
            $this->pastikan($this->db->table($tabel)->where('pendaftaran_id', $dari)->update(['pendaftaran_id' => $ke]));
        }

        $foto = $this->pindahkanPenanggungJawab($dari, $ke, $sekarang);
        $this->pindahkanRegu($dari, $ke, $sekarang);

        // Riwayat tindakan admin (F8-01) ikut pindah; tujuan mencatat asal gabungannya.
        $riwayat = new RiwayatAdmin($this->db);
        $riwayat->pindahkan($dari, $ke);
        $riwayat->catat($ke, (string) $tujuan['id_pendaftaran'], RiwayatAdmin::GABUNG, 'Digabung dari ' . $sumber['id_pendaftaran']
            . ' karena NPSN-nya sama. Atlet, ofisial, regu, dan bukti bayarnya pindah ke sini; total ditambah ' . rupiah((int) $sumber['total']) . '.');

        // Catatan lama milik sumber ikut menunjuk tujuan, lalu sumber sendiri dicatat.
        $this->pastikan($this->db->table('pendaftaran_gabungan')->where('pendaftaran_id', $dari)->update(['pendaftaran_id' => $ke]));
        $this->pastikan($this->db->table('pendaftaran_gabungan')->insert([
            'pendaftaran_id'      => $ke,
            'id_pendaftaran_lama' => (string) $sumber['id_pendaftaran'],
            'kode_bukti_lama'     => $sumber['kode_bukti'] ?? null,
            'dibuat_pada'         => $sekarang,
        ]));

        $this->pastikan($this->db->table('pendaftaran')->where('id', $dari)->delete());

        return $foto;
    }

    /**
     * @return list<string>
     */
    private function pindahkanPenanggungJawab(int $dari, int $ke, string $sekarang): array
    {
        $ada = [];

        foreach ($this->baca('SELECT id, nomor_wa, foto FROM penanggung_jawab WHERE pendaftaran_id = ? ORDER BY id', [$ke]) as $baris) {
            $ada[(string) $baris['nomor_wa']] = $baris;
        }

        $sisa = [];

        foreach ($this->baca('SELECT id, nomor_wa, foto FROM penanggung_jawab WHERE pendaftaran_id = ? ORDER BY id', [$dari]) as $baris) {
            $wa   = (string) $baris['nomor_wa'];
            $sama = $ada[$wa] ?? null;

            if ($sama === null) {
                $this->pastikan($this->db->table('penanggung_jawab')->where('id', (int) $baris['id'])->update(['pendaftaran_id' => $ke, 'diubah_pada' => $sekarang]));
                $ada[$wa] = $baris;

                continue;
            }

            $this->pastikan($this->db->table('penanggung_jawab')->where('id', (int) $baris['id'])->delete());

            if ((string) ($baris['foto'] ?? '') === '') {
                continue;
            }

            if ((string) ($sama['foto'] ?? '') === '') {
                $this->pastikan($this->db->table('penanggung_jawab')->where('id', (int) $sama['id'])->update(['foto' => $baris['foto'], 'diubah_pada' => $sekarang]));
                $ada[$wa]['foto'] = $baris['foto'];
            } else {
                $sisa[] = (string) $baris['foto'];
            }
        }

        return $sisa;
    }

    private function pindahkanRegu(int $dari, int $ke, string $sekarang): void
    {
        $ada = [];

        foreach ($this->baca('SELECT id, nomor_lomba_id, regu, biaya_tim FROM pendaftaran_beregu WHERE pendaftaran_id = ?', [$ke]) as $baris) {
            $ada[$baris['nomor_lomba_id'] . ':' . $baris['regu']] = $baris;
        }

        foreach ($this->baca('SELECT id, nomor_lomba_id, regu, biaya_tim FROM pendaftaran_beregu WHERE pendaftaran_id = ? ORDER BY id', [$dari]) as $baris) {
            $sama = $ada[$baris['nomor_lomba_id'] . ':' . $baris['regu']] ?? null;

            if ($sama === null) {
                $this->pastikan($this->db->table('pendaftaran_beregu')->where('id', (int) $baris['id'])->update(['pendaftaran_id' => $ke, 'diubah_pada' => $sekarang]));

                continue;
            }

            $this->pastikan($this->db->table('pendaftaran_beregu')->where('id', (int) $baris['id'])->delete());

            if ((int) $baris['biaya_tim'] > 0) {
                $this->pastikan($this->db->table('pendaftaran_beregu')->where('id', (int) $sama['id'])->update([
                    'biaya_tim'   => (int) $sama['biaya_tim'] + (int) $baris['biaya_tim'],
                    'diubah_pada' => $sekarang,
                ]));
            }
        }
    }

    private function mulai(): void
    {
        if ($this->db->transDepth === 0) {
            // Penanda gagal transaksi CodeIgniter melekat dari kegagalan sebelumnya.
            $this->db->resetTransStatus();
        }

        if (! $this->db->transBegin()) {
            throw $this->galat('Transaksi penggabungan pendaftaran tidak bisa dimulai');
        }
    }

    private function selesai(): void
    {
        if ($this->db->transStatus() === false) {
            throw $this->galat('Transaksi penggabungan pendaftaran gagal');
        }

        $this->db->transCommit();
    }

    private function batal(): void
    {
        if ($this->db->transDepth > 0) {
            $this->db->transRollback();
        }
    }

    /**
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

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            throw $this->galat('Data pendaftaran gagal digabung');
        }
    }

    private function galat(string $awal): DatabaseException
    {
        $galat = $this->db->error();

        return new DatabaseException($awal . ': ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
    }

    private static function sekarang(): string
    {
        return Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');
    }
}
