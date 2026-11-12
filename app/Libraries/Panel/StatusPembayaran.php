<?php

namespace App\Libraries\Panel;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Status pembayaran pendaftaran dan pemberian nomor dada (rancangan 6.9;
 * K11, K32, K59; rencana Tahap 6, "Nomor dada").
 *
 * - `tandaiLunas()`: status menjadi Lunas, `waktu_lunas` diisi, dan setiap
 *   atlet yang belum punya nomor dada mendapat nomor. Bila awalan satu
 *   kelompok umur saja tidak cukup, seluruh penandaan dibatalkan: status
 *   tetap Menunggu dan tidak ada nomor yang dibagikan.
 * - `kembaliMenunggu()`: status kembali ke Menunggu, `waktu_lunas` dikosongkan.
 *   Nomor dada tidak dihapus; saat ditandai Lunas lagi nomornya tetap.
 * - `beriNomorDada()`: beri nomor kepada atlet satu pendaftaran yang belum
 *   punya, tanpa mengubah status. Dipakai `tandaiLunas()` dan F6-04 (K59:
 *   atlet baru di pendaftaran Lunas yang totalnya tetap).
 *
 * Nomor dada tidak pernah dihapus atau dipakai ulang: `nomor_terakhir` awalan
 * hanya naik, dan nomor baru selalu di atas nomor tertinggi yang pernah
 * tersimpan dengan awalan yang sama.
 *
 * Kunci: baris `pendaftaran` (pada `tandaiLunas()`), baris `atlet` yang butuh
 * nomor, dan baris `awalan_nomor_dada` kelompok umurnya dikunci dengan
 * `SELECT … FOR UPDATE` dalam satu transaksi, berurutan menurut `id`, jadi
 * dua penandaan bersamaan menunggu giliran dan tidak pernah memberi nomor yang
 * sama. Keunikan `atlet.nomor_dada` juga dijaga basis data.
 *
 * Pemakaian:
 *
 *     $status = new StatusPembayaran();
 *     $hasil  = $status->tandaiLunas($id);
 *     // ['berhasil' => true, 'pesan' => null, 'nomor' => [atlet_id => '2001', …]]
 *     // ['berhasil' => false, 'pesan' => 'Pendaftaran belum ditandai Lunas karena …', 'nomor' => []]
 *
 *     $status->kembaliMenunggu($id);        // ['berhasil' => true, 'pesan' => null]
 *
 * F6-04, di dalam transaksinya sendiri (transaksi bersarang CodeIgniter hanya
 * menambah kedalaman, jadi kunci bertahan sampai transaksi luar selesai):
 *
 *     $db->transBegin();
 *     // … simpan atlet baru …
 *     $nomor = (new StatusPembayaran($db))->beriNomorDada($id);
 *     if ($nomor['penuh'] !== []) {
 *         $db->transRollback();               // tidak ada nomor yang ditulis; batalkan perubahan lain
 *         // tampilkan $nomor['pesan']
 *     }
 *     $db->transCommit();
 *
 * Galat basis data (misalnya menunggu kunci terlalu lama) dilempar setelah
 * transaksi dibatalkan.
 */
final class StatusPembayaran
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Tandai Lunas dan beri nomor dada kepada atlet yang belum punya.
     *
     * @return array{berhasil: bool, pesan: string|null, nomor: array<int, string>}
     *         `nomor`: id atlet => nomor dada yang baru dibagikan.
     */
    public function tandaiLunas(int $pendaftaranId): array
    {
        $this->mulai();

        try {
            $baris = $this->kunciPendaftaran($pendaftaranId);

            if ($baris === null) {
                $this->db->transRollback();

                return ['berhasil' => false, 'pesan' => 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.', 'nomor' => []];
            }

            if ($baris['status'] === 'lunas') {
                $this->db->transRollback();

                return ['berhasil' => false, 'pesan' => 'Pendaftaran ini sudah Lunas.', 'nomor' => []];
            }

            $nomor = $this->beriNomorDada($pendaftaranId);

            if ($nomor['penuh'] !== []) {
                $this->db->transRollback();

                return ['berhasil' => false, 'pesan' => $nomor['pesan'], 'nomor' => []];
            }

            $sekarang = self::sekarang();
            $this->pastikan($this->db->table('pendaftaran')->where('id', $pendaftaranId)->update([
                'status'      => 'lunas',
                'waktu_lunas' => $sekarang,
                'diubah_pada' => $sekarang,
            ]));

            $this->selesai();

            return ['berhasil' => true, 'pesan' => null, 'nomor' => $nomor['nomor']];
        } catch (Throwable $e) {
            $this->batal();

            throw $e;
        }
    }

    /**
     * Kembalikan status ke Menunggu Konfirmasi Pembayaran. Nomor dada tetap.
     *
     * @return array{berhasil: bool, pesan: string|null}
     */
    public function kembaliMenunggu(int $pendaftaranId): array
    {
        $this->mulai();

        try {
            $baris = $this->kunciPendaftaran($pendaftaranId);

            if ($baris === null) {
                $this->db->transRollback();

                return ['berhasil' => false, 'pesan' => 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.'];
            }

            if ($baris['status'] !== 'lunas') {
                $this->db->transRollback();

                return ['berhasil' => false, 'pesan' => 'Pendaftaran ini memang masih Menunggu Konfirmasi Pembayaran.'];
            }

            $this->pastikan($this->db->table('pendaftaran')->where('id', $pendaftaranId)->update([
                'status'      => 'menunggu',
                'waktu_lunas' => null,
                'diubah_pada' => self::sekarang(),
            ]));

            $this->selesai();

            return ['berhasil' => true, 'pesan' => null];
        } catch (Throwable $e) {
            $this->batal();

            throw $e;
        }
    }

    /**
     * Beri nomor dada kepada atlet satu pendaftaran yang belum punya, menurut
     * urutan simpan (`atlet.id`), dari awalan kelompok umur masing-masing.
     * Status pendaftaran tidak diubah.
     *
     * Bila ada kelompok umur yang awalannya tidak cukup, tidak ada nomor yang
     * ditulis; `penuh` menyebut kelompok umurnya dan `pesan` kalimat untuk
     * admin. Pemanggil yang punya transaksi sendiri membatalkannya.
     *
     * @return array{nomor: array<int, string>, penuh: list<string>, pesan: string|null}
     *         `nomor`: id atlet => nomor dada baru.
     */
    public function beriNomorDada(int $pendaftaranId): array
    {
        $this->mulai();

        try {
            $atlet = $this->baca(
                'SELECT id, kelompok_umur_id FROM atlet WHERE pendaftaran_id = ? AND nomor_dada IS NULL ORDER BY id FOR UPDATE',
                [$pendaftaranId],
            );

            if ($atlet === []) {
                $this->selesai();

                return ['nomor' => [], 'penuh' => [], 'pesan' => null];
            }

            $perKu = [];

            foreach ($atlet as $baris) {
                $perKu[(int) $baris['kelompok_umur_id']][] = (int) $baris['id'];
            }

            $awalan    = $this->kunciAwalan(array_keys($perKu));
            $tertinggi = $this->urutTertinggi(array_merge(...array_values(array_map(
                static fn (array $daftar): array => array_column($daftar, 'awalan'),
                $awalan,
            ))));

            $nomor    = [];
            $terakhir = [];
            $penuh    = [];

            foreach ($perKu as $kuId => $daftarAtlet) {
                $milik = array_map(static fn (array $a): array => [
                    'awalan'         => (string) $a['awalan'],
                    'nomor_terakhir' => max((int) $a['nomor_terakhir'], $tertinggi[(string) $a['awalan']] ?? 0),
                ], $awalan[$kuId] ?? []);

                $hasil = NomorDada::bagikan($milik, count($daftarAtlet));

                if ($hasil === null) {
                    $penuh[$kuId] = $awalan[$kuId] ?? [];

                    continue;
                }

                foreach ($daftarAtlet as $i => $atletId) {
                    $nomor[$atletId] = $hasil['nomor'][$i];
                }

                $terakhir += $hasil['nomor_terakhir'];
            }

            if ($penuh !== []) {
                $this->db->transRollback();

                $nama = $this->namaKelompokUmur(array_keys($penuh));

                return [
                    'nomor' => [],
                    'penuh' => array_values($nama),
                    'pesan' => self::pesanPenuh($penuh, $nama),
                ];
            }

            $sekarang = self::sekarang();

            foreach ($nomor as $atletId => $nomorDada) {
                $this->pastikan($this->db->table('atlet')->where('id', $atletId)->where('nomor_dada', null)->update([
                    'nomor_dada'  => $nomorDada,
                    'diubah_pada' => $sekarang,
                ]));
            }

            foreach ($terakhir as $kode => $urut) {
                $this->pastikan($this->db->table('awalan_nomor_dada')->where('awalan', (string) $kode)->update([
                    'nomor_terakhir' => $urut,
                    'diubah_pada'    => $sekarang,
                ]));
            }

            $this->selesai();

            ksort($nomor);

            return ['nomor' => $nomor, 'penuh' => [], 'pesan' => null];
        } catch (Throwable $e) {
            $this->batal();

            throw $e;
        }
    }

    /**
     * Kalimat untuk admin bila awalan tidak cukup. Menyebut kelompok umur
     * yang perlu ditambah awalannya dan tempat menambahkannya.
     *
     * @param array<int, list<array<string, mixed>>> $penuh id kelompok umur => awalannya
     * @param array<int, string>                     $nama  id kelompok umur => nama
     */
    public static function pesanPenuh(array $penuh, array $nama): string
    {
        $bagian = [];

        foreach ($penuh as $kuId => $awalan) {
            $namaKu   = $nama[$kuId] ?? 'kelompok umur ini';
            $bagian[] = $awalan === []
                ? $namaKu . ' belum punya awalan nomor dada'
                : 'awalan nomor dada ' . $namaKu . ' (' . implode(', ', array_column($awalan, 'awalan')) . ') sudah penuh';
        }

        $semuaNama = array_map(static fn (int $kuId): string => $nama[$kuId] ?? 'kelompok umur ini', array_keys($penuh));

        return 'Pendaftaran belum ditandai Lunas dan belum ada nomor dada yang dibuat karena ' . self::gabung($bagian) . '. '
            . 'Tambahkan awalan baru untuk ' . self::gabung($semuaNama) . ' di Pengaturan, Kelompok Umur, lalu tandai Lunas lagi.';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function kunciPendaftaran(int $pendaftaranId): ?array
    {
        return $this->baca('SELECT id, status FROM pendaftaran WHERE id = ? FOR UPDATE', [$pendaftaranId])[0] ?? null;
    }

    /**
     * Kunci awalan kelompok umur ini (berurutan menurut `id` supaya dua
     * transaksi tidak saling menunggu), lalu kelompokkan menurut `urutan`.
     *
     * @param list<int> $kelompokUmurId
     *
     * @return array<int, list<array<string, mixed>>> id kelompok umur => awalan berurutan
     */
    private function kunciAwalan(array $kelompokUmurId): array
    {
        $tanda = implode(', ', array_fill(0, count($kelompokUmurId), '?'));
        $baris = $this->baca(
            "SELECT id, kelompok_umur_id, awalan, urutan, nomor_terakhir FROM awalan_nomor_dada WHERE kelompok_umur_id IN ({$tanda}) ORDER BY id FOR UPDATE",
            $kelompokUmurId,
        );

        usort($baris, static fn (array $a, array $b): int => [(int) $a['urutan'], (int) $a['id']] <=> [(int) $b['urutan'], (int) $b['id']]);

        $hasil = [];

        foreach ($baris as $satu) {
            $hasil[(int) $satu['kelompok_umur_id']][] = $satu;
        }

        return $hasil;
    }

    /**
     * Urut tertinggi yang sudah tersimpan di `atlet.nomor_dada` per awalan.
     * Pengaman bila `nomor_terakhir` tertinggal dari data yang ada, supaya
     * nomor tidak pernah dipakai dua kali.
     *
     * @param list<int|string> $awalan
     *
     * @return array<string, int>
     */
    private function urutTertinggi(array $awalan): array
    {
        $hasil = [];

        foreach (array_unique(array_map('strval', $awalan)) as $kode) {
            $baris = $this->baca('SELECT MAX(nomor_dada) AS tertinggi FROM atlet WHERE nomor_dada LIKE ?', [$kode . '___']);

            $tertinggi = (string) ($baris[0]['tertinggi'] ?? '');

            if (preg_match('/^\d{4}$/D', $tertinggi) === 1) {
                $hasil[$kode] = (int) substr($tertinggi, 1);
            }
        }

        return $hasil;
    }

    /**
     * @param list<int> $kelompokUmurId
     *
     * @return array<int, string>
     */
    private function namaKelompokUmur(array $kelompokUmurId): array
    {
        $nama = [];

        $tanda = implode(', ', array_fill(0, count($kelompokUmurId), '?'));

        foreach ($this->baca("SELECT id, nama FROM kelompok_umur WHERE id IN ({$tanda})", $kelompokUmurId) as $baris) {
            $nama[(int) $baris['id']] = (string) $baris['nama'];
        }

        $hasil = [];

        foreach ($kelompokUmurId as $id) {
            $hasil[$id] = $nama[$id] ?? 'kelompok umur ini';
        }

        return $hasil;
    }

    /**
     * Gabung daftar menjadi kalimat: "A", "A dan B", "A, B, dan C".
     *
     * @param list<string> $daftar
     */
    private static function gabung(array $daftar): string
    {
        $daftar = array_values(array_unique($daftar));

        if (count($daftar) <= 2) {
            return implode(' dan ', $daftar);
        }

        $akhir = array_pop($daftar);

        return implode(', ', $daftar) . ', dan ' . $akhir;
    }

    private function mulai(): void
    {
        if ($this->db->transDepth === 0) {
            // Penanda gagal transaksi CodeIgniter melekat dari kegagalan sebelumnya.
            $this->db->resetTransStatus();
        }

        if (! $this->db->transBegin()) {
            throw $this->galat('Transaksi status pembayaran tidak bisa dimulai');
        }
    }

    private function selesai(): void
    {
        if ($this->db->transStatus() === false) {
            throw $this->galat('Transaksi status pembayaran gagal');
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
     * Jalankan kueri baca. Di dalam transaksi, CodeIgniter mengembalikan
     * `false` alih-alih melempar galat (misalnya menunggu kunci terlalu lama);
     * di sini selalu menjadi `DatabaseException`.
     *
     * @param list<int|string> $ikat
     *
     * @return list<array<string, mixed>>
     */
    private function baca(string $sql, array $ikat): array
    {
        $hasil = $this->db->query($sql, $ikat);

        if (! is_object($hasil)) {
            throw $this->galat('Data status pembayaran gagal dibaca');
        }

        return $hasil->getResultArray();
    }

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            throw $this->galat('Data status pembayaran gagal ditulis');
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
