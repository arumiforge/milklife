<?php

namespace App\Libraries\Panel;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Urutkan ulang nomor dada per sekolah (K111).
 *
 * Nomor dada dibagikan saat technical meeting, jadi sebelum itu panitia bisa
 * membuat ulang semua nomor supaya nomor satu sekolah berurutan, walau
 * pendaftarannya masuk dan ditandai Lunas tidak berurutan.
 *
 * - Semua nomor dada dikosongkan, lalu atlet dari pendaftaran **Lunas** diberi
 *   nomor baru mulai dari `001` di awalan pertama kelompok umurnya (berlanjut
 *   ke awalan berikutnya bila penuh).
 * - Sekolah (NPSN; tanpa NPSN: pendaftaran itu sendiri) diurutkan menurut
 *   atlet yang paling dulu didaftarkan (`atlet.id` terkecil), jadi sekolah
 *   yang mendaftar lebih dulu mendapat nomor lebih kecil. Di dalam satu
 *   sekolah, atlet diurutkan menurut urutan mendaftar.
 * - Atlet pendaftaran yang masih Menunggu tidak punya nomor sampai ditandai
 *   Lunas; nomornya lalu melanjutkan nomor terakhir seperti biasa.
 * - `awalan_nomor_dada.nomor_terakhir` disamakan dengan nomor terakhir yang
 *   dipakai. Waktu dan nama admin disimpan di pengaturan
 *   `NomorDada.diurutkan_pada` dan `NomorDada.diurutkan_oleh`; cek, bukti PDF,
 *   dan panel menuliskan keterangannya.
 *
 * Ini pengecualian kedua aturan "nomor dada tidak dipakai ulang" (selain K94):
 * jangan dijalankan setelah nomor dada dibagikan ke peserta. Bila awalan satu
 * kelompok umur tidak cukup, tidak ada yang diubah.
 *
 *     $hasil = (new UrutkanNomorDada())->urutkan('Admin Panitia');
 *     // ['berhasil' => true, 'pesan' => null, 'atlet' => 120, 'sekolah' => 18, 'dikosongkan' => 3]
 *     (new UrutkanNomorDada())->ringkasan();
 *     // ['atlet' => 120, 'sekolah' => 18, 'menunggu' => 3, 'per_kelompok_umur' => [...]]
 */
final class UrutkanNomorDada
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Keadaan sekarang untuk halaman panel, tanpa mengubah apa pun.
     *
     * @return array{atlet: int, sekolah: int, menunggu_bernomor: int, per_kelompok_umur: list<array{nama: string, atlet: int, awalan: string, cukup: bool}>, diurutkan_pada: string|null, diurutkan_oleh: string|null}
     */
    public function ringkasan(): array
    {
        $atlet  = $this->atletLunas();
        $awalan = $this->awalan(false);
        $perKu  = [];

        foreach ($atlet as $a) {
            $perKu[(int) $a['kelompok_umur_id']] = ($perKu[(int) $a['kelompok_umur_id']] ?? 0) + 1;
        }

        $daftar = [];

        foreach ($this->db->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $ku) {
            $id = (int) $ku['id'];

            if (! isset($perKu[$id])) {
                continue;
            }

            $milik    = array_map(static fn (array $a): array => ['awalan' => $a['awalan'], 'nomor_terakhir' => 0], $awalan[$id] ?? []);
            $daftar[] = [
                'nama'   => (string) $ku['nama'],
                'atlet'  => $perKu[$id],
                'awalan' => implode(', ', array_column($awalan[$id] ?? [], 'awalan')),
                'cukup'  => NomorDada::sisa($milik) >= $perKu[$id],
            ];
        }

        return [
            'atlet'             => count($atlet),
            'sekolah'           => count(array_unique(array_map(self::kunciSekolah(...), $atlet))),
            'menunggu_bernomor' => (int) ($this->db->query(
                "SELECT COUNT(*) AS jumlah FROM atlet a JOIN pendaftaran p ON p.id = a.pendaftaran_id WHERE p.status <> 'lunas' AND a.nomor_dada IS NOT NULL",
            )->getRow()->jumlah ?? 0),
            'per_kelompok_umur' => $daftar,
            'diurutkan_pada'    => self::waktuTerakhir(),
            'diurutkan_oleh'    => self::olehTerakhir(),
        ];
    }

    /**
     * Buat ulang semua nomor dada, berurutan per sekolah.
     *
     * @return array{berhasil: bool, pesan: string|null, atlet: int, sekolah: int, dikosongkan: int}
     *         `dikosongkan`: atlet pendaftaran Menunggu yang nomornya dikosongkan.
     */
    public function urutkan(string $namaAdmin): array
    {
        $this->db->resetTransStatus();
        $this->db->transBegin();

        try {
            $awalan = $this->awalan(true);
            $this->baca('SELECT id FROM atlet WHERE nomor_dada IS NOT NULL ORDER BY id FOR UPDATE', []);
            $atlet = $this->atletLunas();

            // Kelompokkan per kelompok umur, sekolah berurutan menurut atlet pertamanya.
            $urutSekolah = [];

            foreach ($atlet as $a) {
                $urutSekolah[self::kunciSekolah($a)] ??= (int) $a['id'];
            }

            usort($atlet, static fn (array $x, array $y): int => [$urutSekolah[self::kunciSekolah($x)], (int) $x['id']] <=> [$urutSekolah[self::kunciSekolah($y)], (int) $y['id']]);

            $perKu = [];

            foreach ($atlet as $a) {
                $perKu[(int) $a['kelompok_umur_id']][] = (int) $a['id'];
            }

            $nomor    = [];
            $terakhir = [];
            $penuh    = [];

            foreach ($perKu as $kuId => $daftar) {
                $milik = array_map(static fn (array $a): array => ['awalan' => (string) $a['awalan'], 'nomor_terakhir' => 0], $awalan[$kuId] ?? []);
                $hasil = NomorDada::bagikan($milik, count($daftar));

                if ($hasil === null) {
                    $penuh[$kuId] = $awalan[$kuId] ?? [];

                    continue;
                }

                foreach ($daftar as $i => $atletId) {
                    $nomor[$atletId] = $hasil['nomor'][$i];
                }

                $terakhir += $hasil['nomor_terakhir'];
            }

            if ($penuh !== []) {
                $this->db->transRollback();

                return ['berhasil' => false, 'pesan' => $this->pesanPenuh($penuh), 'atlet' => 0, 'sekolah' => 0, 'dikosongkan' => 0];
            }

            $dikosongkan = (int) ($this->baca(
                "SELECT COUNT(*) AS jumlah FROM atlet a JOIN pendaftaran p ON p.id = a.pendaftaran_id WHERE p.status <> 'lunas' AND a.nomor_dada IS NOT NULL",
                [],
            )[0]['jumlah'] ?? 0);

            $sekarang = self::sekarang();

            // Kosongkan dulu supaya kunci unik nomor dada tidak bentrok saat nomor bertukar.
            $this->pastikan($this->db->table('atlet')->where('nomor_dada IS NOT NULL', null, false)->update(['nomor_dada' => null]));

            foreach ($nomor as $atletId => $nomorDada) {
                $this->pastikan($this->db->table('atlet')->where('id', $atletId)->update(['nomor_dada' => $nomorDada, 'diubah_pada' => $sekarang]));
            }

            $this->pastikan($this->db->table('awalan_nomor_dada')->update(['nomor_terakhir' => 0, 'diubah_pada' => $sekarang]));

            foreach ($terakhir as $kode => $urut) {
                $this->pastikan($this->db->table('awalan_nomor_dada')->where('awalan', (string) $kode)->update(['nomor_terakhir' => $urut]));
            }

            if ($this->db->transStatus() === false) {
                throw $this->galat('Nomor dada gagal diurutkan');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            throw $e;
        }

        setting('NomorDada.diurutkan_pada', $sekarang);
        setting('NomorDada.diurutkan_oleh', $namaAdmin);

        return [
            'berhasil'    => true,
            'pesan'       => null,
            'atlet'       => count($nomor),
            'sekolah'     => count($urutSekolah),
            'dikosongkan' => $dikosongkan,
        ];
    }

    /**
     * Waktu nomor dada terakhir diurutkan ulang (`Y-m-d H:i:s`), atau `null`.
     */
    public static function waktuTerakhir(): ?string
    {
        $waktu = setting('NomorDada.diurutkan_pada');

        return is_string($waktu) && $waktu !== '' ? $waktu : null;
    }

    private static function olehTerakhir(): ?string
    {
        $nama = setting('NomorDada.diurutkan_oleh');

        return is_string($nama) && $nama !== '' ? $nama : null;
    }

    /**
     * Keterangan untuk official (cek dan bukti PDF), atau `null` bila nomor
     * dada belum pernah diurutkan ulang atau pendaftaran ini belum Lunas.
     */
    public static function keterangan(string $status): ?string
    {
        $waktu = self::waktuTerakhir();

        if ($waktu === null || $status !== 'lunas') {
            return null;
        }

        return 'Nomor dada sudah diurutkan ulang per sekolah oleh panitia pada ' . tanggal_id($waktu)
            . ', supaya nomor atlet satu sekolah berurutan. Nomor yang berlaku adalah nomor di sini; '
            . 'abaikan nomor dada pada bukti pendaftaran yang dicetak sebelum tanggal itu.';
    }

    /**
     * Atlet dari pendaftaran Lunas, urut `id`.
     *
     * @return list<array<string, mixed>>
     */
    private function atletLunas(): array
    {
        return $this->baca(
            "SELECT a.id, a.kelompok_umur_id, a.pendaftaran_id, p.npsn FROM atlet a JOIN pendaftaran p ON p.id = a.pendaftaran_id WHERE p.status = 'lunas' ORDER BY a.id",
            [],
        );
    }

    /**
     * Awalan per kelompok umur, berurutan seperti dipakai (`urutan`, lalu `id`).
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function awalan(bool $kunci): array
    {
        $hasil = [];

        foreach ($this->baca('SELECT id, kelompok_umur_id, awalan, urutan FROM awalan_nomor_dada ORDER BY urutan, id' . ($kunci ? ' FOR UPDATE' : ''), []) as $baris) {
            $hasil[(int) $baris['kelompok_umur_id']][] = $baris;
        }

        return $hasil;
    }

    /**
     * @param array<string, mixed> $atlet
     */
    private static function kunciSekolah(array $atlet): string
    {
        $npsn = trim((string) ($atlet['npsn'] ?? ''));

        return $npsn !== '' ? 'n' . $npsn : 'p' . (int) $atlet['pendaftaran_id'];
    }

    /**
     * @param array<int, list<array<string, mixed>>> $penuh
     */
    private function pesanPenuh(array $penuh): string
    {
        $nama = array_column($this->db->table('kelompok_umur')->select('id, nama')->whereIn('id', array_keys($penuh))->get()->getResultArray(), 'nama', 'id');

        return 'Nomor dada belum diurutkan ulang dan tidak ada yang berubah karena ' . implode(', ', array_map(
            static fn (int $id, array $awalan): string => ($nama[$id] ?? 'kelompok umur ini') . ($awalan === [] ? ' belum punya awalan nomor dada' : ' kekurangan awalan nomor dada'),
            array_keys($penuh),
            $penuh,
        )) . '. Tambahkan awalan di Pengaturan, Kelompok Umur, lalu urutkan lagi.';
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
            throw $this->galat('Data nomor dada gagal dibaca');
        }

        return $hasil->getResultArray();
    }

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            throw $this->galat('Nomor dada gagal ditulis');
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
