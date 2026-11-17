<?php

namespace App\Libraries\Panel;

use App\Models\SekolahModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Beri nama desa ke sekolah yang namanya sama dengan sekolah lain (K114).
 *
 * Data resmi memakai nama umum untuk banyak sekolah ("MIS MUHAMMADIYAH" ada
 * di beberapa desa). Daripada menyunting satu per satu (K97), admin membuka
 * `admin/sekolah/nama-desa`, memeriksa nama baru yang diusulkan, lalu
 * menerapkannya sekaligus:
 *
 *     $nama    = new NamaDesaSekolah();
 *     $rencana = $nama->pratinjau();
 *     // ['usulan' => [['id' => 7, 'nama' => 'MIS MUHAMMADIYAH', 'nama_baru' => 'MIS MUHAMMADIYAH JATI', 'pembeda' => 'desa', ...]], 'lewati' => [...]]
 *     $hasil   = $nama->terapkan([7, 8]);
 *     // ['sekolah' => 2, 'pendaftaran' => 3]
 *
 * Aturan nama baru (`rencanakan()`, tanpa basis data):
 *
 * - Nama baru = nama lama + spasi + nama desa/kelurahan, ditulis huruf besar
 *   bila nama lamanya huruf besar: "MIS MUHAMMADIYAH" di desa Jati menjadi
 *   "MIS MUHAMMADIYAH JATI".
 * - Desa tidak dipakai bila kosong, sudah tertulis di nama ("SD NEGERI 1
 *   TAHUNAN" di desa Tahunan), atau sama dengan desa sekolah lain yang bernama
 *   sama. Untuk sekolah itu kecamatan lalu dicoba, kemudian kabupaten, dengan
 *   aturan yang sama (dibandingkan dengan sekolah yang juga belum terbedakan).
 * - Sekolah dilewati (diubah admin sendiri lewat Ubah) bila desa, kecamatan,
 *   dan kabupatennya tidak bisa membedakan, bila nama barunya sama dengan
 *   sekolah lain atau dengan nama baru sekolah lain, atau bila nama barunya
 *   terlalu panjang.
 *
 * Saat diterapkan, sekolah ditandai disunting (tidak ditimpa
 * `sekolah:impor`), dan pendaftaran ber-NPSN itu yang masih memakai nama lama
 * ikut memakai nama baru. Pendaftaran yang memakai nama lain, misalnya nama
 * usulan pendaftar yang belum diputuskan (K99), tidak diubah.
 */
final class NamaDesaSekolah
{
    /**
     * Sumber pembeda berurutan, kolom => label.
     *
     * @var array<string, string>
     */
    public const PEMBEDA = ['desa_kelurahan' => 'Desa/kelurahan', 'kecamatan' => 'Kecamatan', 'kabupaten' => 'Kabupaten'];

    public const ALASAN_LETAK = 'Desa, kecamatan, dan kabupatennya sama dengan sekolah lain bernama sama, kosong, atau sudah tertulis di nama.';
    public const ALASAN_BENTROK = 'Nama baru akan sama dengan sekolah lain.';
    public const ALASAN_PANJANG = 'Nama baru terlalu panjang.';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Susun nama baru untuk sekolah bernama kembar.
     *
     * @param list<array<string, mixed>> $kembar       Sekolah yang namanya sama dengan sekolah lain
     *                                                 (kunci id, npsn, nama, desa_kelurahan, kecamatan, kabupaten)
     * @param list<string>               $namaTerpakai Nama semua sekolah di data
     *
     * @return array{
     *     usulan: list<array<string, mixed>>,
     *     lewati: list<array<string, mixed>>
     * } Sekolah asli ditambah `nama_baru` dan `pembeda` (kolom sumber), atau `alasan` untuk yang dilewati
     */
    public static function rencanakan(array $kembar, array $namaTerpakai): array
    {
        $kelompok = [];

        foreach ($kembar as $sekolah) {
            $kelompok[self::kunci((string) $sekolah['nama'])][] = $sekolah;
        }

        $usulan = [];
        $lewati = [];

        foreach ($kelompok as $anggota) {
            // Per kolom, hanya sekolah yang belum terbedakan di kolom
            // sebelumnya yang dibandingkan: dua sekolah di desa yang sama
            // lalu dibedakan kecamatannya, walau kecamatan salah satunya sama
            // dengan sekolah lain yang sudah dibedakan desanya.
            $sisa = $anggota;

            foreach (array_keys(self::PEMBEDA) as $kolom) {
                $hitung = [];

                foreach ($sisa as $sekolah) {
                    $kunci = self::kunci((string) ($sekolah[$kolom] ?? ''));

                    if ($kunci !== '') {
                        $hitung[$kunci] = ($hitung[$kunci] ?? 0) + 1;
                    }
                }

                $belum = [];

                foreach ($sisa as $sekolah) {
                    $nama  = self::rapikan((string) $sekolah['nama']);
                    $nilai = self::rapikan((string) ($sekolah[$kolom] ?? ''));
                    $kunci = self::kunci($nilai);

                    if ($kunci === '' || $hitung[$kunci] > 1 || self::memuatKata(self::kunci($nama), $kunci)) {
                        $belum[] = $sekolah;

                        continue;
                    }

                    $sekolah += [
                        'nama_baru' => $nama . ' ' . ($nama === mb_strtoupper($nama) ? mb_strtoupper($nilai) : $nilai),
                        'pembeda'   => $kolom,
                    ];

                    if (mb_strlen($sekolah['nama_baru']) > SekolahModel::PANJANG['nama']) {
                        unset($sekolah['nama_baru'], $sekolah['pembeda']);
                        $lewati[] = $sekolah + ['alasan' => self::ALASAN_PANJANG];
                    } else {
                        $usulan[] = $sekolah;
                    }
                }

                $sisa = $belum;
            }

            foreach ($sisa as $sekolah) {
                $lewati[] = $sekolah + ['alasan' => self::ALASAN_LETAK];
            }
        }

        // Nama baru tidak boleh sama dengan sekolah lain mana pun, termasuk
        // nama baru sekolah lain di rencana yang sama.
        $terpakai = [];

        foreach ($namaTerpakai as $nama) {
            $terpakai[self::kunci($nama)] = true;
        }

        $namaBaru = array_count_values(array_map(static fn (array $s): string => self::kunci($s['nama_baru']), $usulan));
        $lolos    = [];

        foreach ($usulan as $sekolah) {
            $kunci = self::kunci($sekolah['nama_baru']);

            if (isset($terpakai[$kunci]) || $namaBaru[$kunci] > 1) {
                unset($sekolah['nama_baru'], $sekolah['pembeda']);
                $lewati[] = $sekolah + ['alasan' => self::ALASAN_BENTROK];
            } else {
                $lolos[] = $sekolah;
            }
        }

        return ['usulan' => $lolos, 'lewati' => $lewati];
    }

    /**
     * Rencana untuk semua sekolah bernama kembar di basis data, ditambah
     * `lokasi` dan `pendaftaran` (banyak pendaftaran yang memakai nama lama).
     *
     * @return array{usulan: list<array<string, mixed>>, lewati: list<array<string, mixed>>}
     */
    public function pratinjau(): array
    {
        $tabel  = $this->db->prefixTable('sekolah');
        $kembar = $this->db->table('sekolah s')
            ->select('s.id, s.npsn, s.nama, s.desa_kelurahan, s.kecamatan, s.kabupaten')
            ->where('EXISTS (SELECT 1 FROM ' . $tabel . ' s2 WHERE s2.nama = s.nama AND s2.id <> s.id)', null, false)
            ->orderBy('s.nama')->orderBy('s.kabupaten')->orderBy('s.npsn')
            ->get()->getResultArray();

        if ($kembar === []) {
            return ['usulan' => [], 'lewati' => []];
        }

        $semuaNama = array_column($this->db->table('sekolah')->select('nama')->get()->getResultArray(), 'nama');
        $rencana   = self::rencanakan($kembar, $semuaNama);

        // Pendaftaran yang masih memakai nama lama, per NPSN.
        $pendaftaran = [];
        $baris       = $this->db->table('pendaftaran p')
            ->select('p.npsn, COUNT(*) AS jumlah')
            ->join('sekolah s', 's.npsn = p.npsn')
            ->where('p.nama_sekolah = s.nama', null, false)
            ->whereIn('p.npsn', array_column($kembar, 'npsn'))
            ->groupBy('p.npsn')
            ->get()->getResultArray();

        foreach ($baris as $b) {
            $pendaftaran[$b['npsn']] = (int) $b['jumlah'];
        }

        foreach (['usulan', 'lewati'] as $bagian) {
            foreach ($rencana[$bagian] as $i => $sekolah) {
                $rencana[$bagian][$i]['id']          = (int) $sekolah['id'];
                $rencana[$bagian][$i]['lokasi']      = SekolahModel::lokasi($sekolah);
                $rencana[$bagian][$i]['pendaftaran'] = $pendaftaran[$sekolah['npsn']] ?? 0;
            }
        }

        return $rencana;
    }

    /**
     * Terapkan nama baru ke sekolah yang dipilih admin. Nama baru dihitung
     * ulang di sini; id yang tidak ada di rencana diabaikan.
     *
     * @param list<int> $id
     *
     * @return array{sekolah: int, pendaftaran: int, galat: string|null}
     */
    public function terapkan(array $id): array
    {
        $dipilih = array_flip(array_map('intval', $id));
        $usulan  = array_values(array_filter(
            $this->pratinjau()['usulan'],
            static fn (array $s): bool => isset($dipilih[$s['id']]),
        ));
        $hasil = ['sekolah' => 0, 'pendaftaran' => 0, 'galat' => null];

        if ($usulan === []) {
            return $hasil;
        }

        $waktu = date('Y-m-d H:i:s');

        $this->db->transStart();

        foreach ($usulan as $sekolah) {
            $this->db->table('sekolah')
                ->where('id', $sekolah['id'])->where('nama', $sekolah['nama'])
                ->update(['nama' => $sekolah['nama_baru'], 'disunting_pada' => $waktu, 'diubah_pada' => $waktu]);

            if ($this->db->affectedRows() === 0) {
                continue;
            }

            $hasil['sekolah']++;

            $this->db->table('pendaftaran')
                ->where('npsn', $sekolah['npsn'])->where('nama_sekolah', $sekolah['nama'])
                ->update(['nama_sekolah' => $sekolah['nama_baru'], 'diubah_pada' => $waktu]);
            $hasil['pendaftaran'] += $this->db->affectedRows();
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return [
                'sekolah'     => 0,
                'pendaftaran' => 0,
                'galat'       => 'Nama baru belum tersimpan karena basis data sedang bermasalah. Tunggu sebentar, lalu tekan Terapkan lagi.',
            ];
        }

        return $hasil;
    }

    /**
     * Spasi berlebih dirapikan: "  Jati  Kulon " → "Jati Kulon".
     */
    private static function rapikan(string $teks): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }

    /**
     * Kunci pembanding: rapi dan huruf besar.
     */
    private static function kunci(string $teks): string
    {
        return mb_strtoupper(self::rapikan($teks));
    }

    /**
     * Apakah `$kata` (boleh lebih dari satu kata) sudah tertulis utuh di `$nama`.
     */
    private static function memuatKata(string $nama, string $kata): bool
    {
        return preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($kata, '/') . '($|[^\p{L}\p{N}])/u', $nama) === 1;
    }
}
