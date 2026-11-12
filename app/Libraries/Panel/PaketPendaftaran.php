<?php

namespace App\Libraries\Panel;

use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Paket data pendaftaran untuk pindah server (K76): ekspor ke satu zip dan
 * impor zip itu di server baru, tanpa memindahkan seluruh basis data.
 *
 * Isi zip:
 * - `data.json`: semua pendaftaran beserta penanggung jawab, atlet, nomor lomba
 *   yang diikuti, tim beregu, dan bukti bayar; juga `nomor_terakhir` tiap
 *   awalan nomor dada (supaya nomor dada tidak dipakai ulang, K05) dan nomor
 *   urut ID pendaftaran berikutnya.
 * - `berkas/penanggung-jawab/<acak>.jpg` dan `berkas/bukti-bayar/<acak>.jpg`.
 *
 * Kelompok umur dicocokkan menurut namanya, nomor lomba menurut nama kelompok
 * umur + nama nomor + kategori. Jadi server baru harus sudah punya pengaturan
 * yang sama (misalnya lewat `php spark data:awal`, lalu disamakan di panel).
 *
 * Impor hanya ke server yang **belum punya pendaftaran**. Bila ada kelompok
 * umur atau nomor lomba yang tidak ditemukan, tidak ada yang diimpor.
 * Berkas disalin lebih dulu; bila penyimpanan basis data gagal, berkas yang
 * sudah disalin dihapus lagi.
 *
 *     $jumlah = (new PaketPendaftaran())->ekspor('/tmp/paket.zip');
 *     $jumlah = (new PaketPendaftaran())->impor('/tmp/paket.zip');
 *     // ['pendaftaran' => 12, 'atlet' => 85, 'berkas' => 20]
 */
final class PaketPendaftaran
{
    public const VERSI = 1;

    /**
     * Jalur berkas yang sah di dalam zip dan di kolom basis data.
     */
    private const POLA_BERKAS = '#^(' . FotoPenanggungJawab::FOLDER . '|' . BerkasBuktiBayar::FOLDER . ')/[0-9a-f]{32}\.jpg$#D';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Tulis paket ke `$tujuan` (ditimpa bila ada).
     *
     * @return array{pendaftaran: int, atlet: int, berkas: int}
     *
     * @throws RuntimeException Bila zip gagal ditulis.
     */
    public function ekspor(string $tujuan): array
    {
        $ku    = array_column($this->db->table('kelompok_umur')->select('id, nama')->get()->getResultArray(), 'nama', 'id');
        $nomor = [];

        foreach ($this->db->table('nomor_lomba')->select('id, kelompok_umur_id, nama, kategori')->get()->getResultArray() as $n) {
            $nomor[(int) $n['id']] = ['kelompok_umur' => $ku[$n['kelompok_umur_id']] ?? '', 'nama' => $n['nama'], 'kategori' => $n['kategori']];
        }

        $kelompok = static function (array $baris): array {
            $hasil = [];

            foreach ($baris as $b) {
                $hasil[(int) $b['pendaftaran_id']][] = $b;
            }

            return $hasil;
        };

        $pj    = $kelompok($this->db->table('penanggung_jawab')->orderBy('id')->get()->getResultArray());
        $atlet = $kelompok($this->db->table('atlet')->orderBy('id')->get()->getResultArray());
        $tim   = $kelompok($this->db->table('pendaftaran_beregu')->orderBy('id')->get()->getResultArray());
        $bukti = $kelompok($this->db->table('bukti_bayar')->orderBy('id')->get()->getResultArray());

        $ikutNomor = [];

        foreach ($this->db->table('atlet_nomor')->orderBy('id')->get()->getResultArray() as $an) {
            $ikutNomor[(int) $an['atlet_id']][] = $nomor[(int) $an['nomor_lomba_id']] + ['biaya' => $an['biaya'] === null ? null : (int) $an['biaya']];
        }

        $berkas = [];
        $data   = [];

        foreach ($this->db->table('pendaftaran')->orderBy('id')->get()->getResultArray() as $p) {
            $id = (int) $p['id'];

            $data[] = [
                'id'                => $id,
                'id_pendaftaran'    => $p['id_pendaftaran'],
                'jenjang'           => $p['jenjang'],
                'npsn'              => $p['npsn'],
                'nama_sekolah'      => $p['nama_sekolah'],
                'kabupaten'         => $p['kabupaten'],
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
                'status'            => $p['status'],
                'waktu_lunas'       => $p['waktu_lunas'],
                'dibuat_pada'       => $p['dibuat_pada'],
                'diubah_pada'       => $p['diubah_pada'],
                'penanggung_jawab'  => array_map(function (array $o) use (&$berkas): array {
                    $foto = $this->berkasSah($o['foto']) ? $o['foto'] : null;

                    if ($foto !== null) {
                        $berkas[$foto] = FotoPenanggungJawab::folderDasar() . $foto;
                    }

                    return ['nama' => $o['nama'], 'nomor_wa' => $o['nomor_wa'], 'foto' => $foto, 'dibuat_pada' => $o['dibuat_pada'], 'diubah_pada' => $o['diubah_pada']];
                }, $pj[$id] ?? []),
                'atlet' => array_map(static fn (array $a): array => [
                    'nama'          => $a['nama'],
                    'nisn'          => $a['nisn'],
                    'tanggal_lahir' => $a['tanggal_lahir'],
                    'jenis_kelamin' => $a['jenis_kelamin'],
                    'kelompok_umur' => $ku[$a['kelompok_umur_id']] ?? '',
                    'nomor_dada'    => $a['nomor_dada'],
                    'dibuat_pada'   => $a['dibuat_pada'],
                    'diubah_pada'   => $a['diubah_pada'],
                    'nomor'         => $ikutNomor[(int) $a['id']] ?? [],
                ], $atlet[$id] ?? []),
                'beregu' => array_map(static fn (array $t): array => $nomor[(int) $t['nomor_lomba_id']] + [
                    'biaya_tim' => (int) $t['biaya_tim'], 'dibuat_pada' => $t['dibuat_pada'], 'diubah_pada' => $t['diubah_pada'],
                ], $tim[$id] ?? []),
                'bukti_bayar' => array_values(array_filter(array_map(function (array $b) use (&$berkas): ?array {
                    if (! $this->berkasSah($b['berkas'])) {
                        return null;
                    }

                    $berkas[$b['berkas']] = BerkasBuktiBayar::folderDasar() . $b['berkas'];

                    return ['berkas' => $b['berkas'], 'ukuran' => (int) $b['ukuran'], 'waktu_unggah' => $b['waktu_unggah']];
                }, $bukti[$id] ?? []))),
            ];
        }

        $isi = [
            'versi'           => self::VERSI,
            'dibuat'          => date('Y-m-d H:i:s'),
            'acara'           => trim(setting('Acara.nama') . ' Seri ' . setting('Acara.seri') . ' ' . setting('Acara.tahun')),
            'urut_berikutnya' => $this->urutBerikutnya(),
            'awalan'          => array_map(
                static fn (array $a): array => ['awalan' => (string) $a['awalan'], 'nomor_terakhir' => (int) $a['nomor_terakhir']],
                $this->db->table('awalan_nomor_dada')->select('awalan, nomor_terakhir')->get()->getResultArray(),
            ),
            'pendaftaran' => $data,
        ];

        $zip = new ZipArchive();

        if ($zip->open($tujuan, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Berkas ekspor gagal dibuat di ' . $tujuan . '. Periksa sisa ruang diska.');
        }

        $zip->addFromString('data.json', (string) json_encode($isi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $tersalin = 0;

        foreach ($berkas as $jalur => $penuh) {
            if (is_file($penuh)) {
                $zip->addFile($penuh, 'berkas/' . $jalur);
                $tersalin++;
            }
        }

        if (! $zip->close()) {
            throw new RuntimeException('Berkas ekspor gagal ditulis. Periksa sisa ruang diska, lalu coba lagi.');
        }

        return ['pendaftaran' => count($data), 'atlet' => array_sum(array_map(static fn (array $p): int => count($p['atlet']), $data)), 'berkas' => $tersalin];
    }

    /**
     * Masukkan paket ke basis data dan folder unggahan server ini.
     *
     * @return array{pendaftaran: int, atlet: int, berkas: int}
     *
     * @throws RuntimeException Dengan pesan ramah bila paket ditolak atau gagal disimpan; tidak ada yang tersimpan.
     */
    public function impor(string $sumber): array
    {
        $zip = new ZipArchive();

        if (! is_file($sumber) || $zip->open($sumber) !== true) {
            throw new RuntimeException('Berkas ' . $sumber . ' tidak ditemukan atau bukan zip ekspor pendaftaran.');
        }

        try {
            $isi = json_decode((string) $zip->getFromName('data.json'), true);

            if (! is_array($isi) || ($isi['versi'] ?? null) !== self::VERSI || ! is_array($isi['pendaftaran'] ?? null)) {
                throw new RuntimeException('Isi zip bukan ekspor pendaftaran yang dikenal. Ekspor ulang dari panel admin server lama.');
            }

            $ada = $this->db->table('pendaftaran')->countAllResults();

            if ($ada > 0) {
                throw new RuntimeException("Server ini sudah punya {$ada} pendaftaran. Impor hanya untuk server yang belum punya pendaftaran, supaya data tidak bentrok.");
            }

            [$petaKu, $petaNomor] = $this->peta($isi['pendaftaran']);
            $berkas               = $this->salinBerkas($zip, $isi['pendaftaran']);
        } finally {
            $zip->close();
        }

        try {
            $jumlah = $this->simpan($isi, $petaKu, $petaNomor);
        } catch (Throwable $e) {
            foreach ($berkas as $penuh) {
                @unlink($penuh);
            }

            throw new RuntimeException('Data gagal disimpan, dan tidak ada yang tersimpan: ' . $e->getMessage(), 0, $e);
        }

        return $jumlah + ['berkas' => count($berkas)];
    }

    /**
     * Peta nama kelompok umur => id dan "KU|nama|kategori" => id nomor lomba di
     * server ini, untuk semua yang dipakai paket.
     *
     * @param list<array<string, mixed>> $pendaftaran
     *
     * @return array{0: array<string, int>, 1: array<string, int>}
     *
     * @throws RuntimeException Bila ada yang tidak ditemukan.
     */
    private function peta(array $pendaftaran): array
    {
        $ku = [];

        foreach ($this->db->table('kelompok_umur')->select('id, nama')->orderBy('id')->get()->getResultArray() as $k) {
            $ku[$k['nama']] ??= (int) $k['id'];
        }

        $nomor = [];

        foreach ($this->db->table('nomor_lomba')->select('id, kelompok_umur_id, nama, kategori')->orderBy('id')->get()->getResultArray() as $n) {
            $nama = array_search((int) $n['kelompok_umur_id'], $ku, true);

            if ($nama !== false) {
                $nomor[self::kunciNomor($nama, $n['nama'], $n['kategori'])] ??= (int) $n['id'];
            }
        }

        $hilang = [];

        foreach ($pendaftaran as $p) {
            foreach ($p['atlet'] ?? [] as $a) {
                if (! isset($ku[$a['kelompok_umur'] ?? ''])) {
                    $hilang['Kelompok umur ' . ($a['kelompok_umur'] ?? '')] = true;
                }

                foreach ($a['nomor'] ?? [] as $n) {
                    if (! isset($nomor[self::kunciNomor($n['kelompok_umur'] ?? '', $n['nama'] ?? '', $n['kategori'] ?? '')])) {
                        $hilang['Nomor ' . ($n['nama'] ?? '') . ' ' . ($n['kategori'] ?? '') . ' (' . ($n['kelompok_umur'] ?? '') . ')'] = true;
                    }
                }
            }

            foreach ($p['beregu'] ?? [] as $n) {
                if (! isset($nomor[self::kunciNomor($n['kelompok_umur'] ?? '', $n['nama'] ?? '', $n['kategori'] ?? '')])) {
                    $hilang['Nomor ' . ($n['nama'] ?? '') . ' ' . ($n['kategori'] ?? '') . ' (' . ($n['kelompok_umur'] ?? '') . ')'] = true;
                }
            }
        }

        if ($hilang !== []) {
            throw new RuntimeException(
                'Pengaturan server ini belum sama dengan server lama. Tidak ditemukan: ' . implode(', ', array_keys($hilang))
                . '. Samakan nama kelompok umur dan nomor lomba di Pengaturan, lalu impor lagi. Tidak ada yang diimpor.',
            );
        }

        return [$ku, $nomor];
    }

    /**
     * Salin foto dan bukti bayar dari zip ke folder unggahan.
     *
     * @param list<array<string, mixed>> $pendaftaran
     *
     * @return list<string> Jalur lengkap berkas yang disalin.
     *
     * @throws RuntimeException Bila sebuah berkas gagal disalin; yang sudah disalin dihapus lagi.
     */
    private function salinBerkas(ZipArchive $zip, array $pendaftaran): array
    {
        $daftar = [];

        foreach ($pendaftaran as $p) {
            foreach ($p['penanggung_jawab'] ?? [] as $o) {
                if ($this->berkasSah($o['foto'] ?? null)) {
                    $daftar[$o['foto']] = FotoPenanggungJawab::folderDasar();
                }
            }

            foreach ($p['bukti_bayar'] ?? [] as $b) {
                if ($this->berkasSah($b['berkas'] ?? null)) {
                    $daftar[$b['berkas']] = BerkasBuktiBayar::folderDasar();
                }
            }
        }

        $tersalin = [];

        foreach ($daftar as $jalur => $dasar) {
            $isi    = $zip->getFromName('berkas/' . $jalur);
            $tujuan = $dasar . $jalur;
            $folder = dirname($tujuan);

            if ($isi === false) {
                continue; // Berkas yang memang tidak ada di server lama dilewati; rujukannya tetap.
            }

            if ((! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) || @file_put_contents($tujuan, $isi) === false) {
                foreach ($tersalin as $penuh) {
                    @unlink($penuh);
                }

                throw new RuntimeException('Berkas ' . $jalur . ' gagal disalin ke ' . $folder . '. Pastikan folder writable/unggahan boleh ditulisi, lalu impor lagi.');
            }

            $tersalin[] = $tujuan;
        }

        return $tersalin;
    }

    /**
     * @param array<string, mixed> $isi
     * @param array<string, int>   $ku
     * @param array<string, int>   $nomor
     *
     * @return array{pendaftaran: int, atlet: int}
     */
    private function simpan(array $isi, array $ku, array $nomor): array
    {
        $jumlahAtlet = 0;
        $tabel       = fn (string $nama, array $baris): int => $this->db->table($nama)->insert($baris) ? (int) $this->db->insertID() : throw new RuntimeException('Baris ' . $nama . ' gagal disimpan.');

        $this->db->transBegin();

        try {
            $this->simpanBaris($isi, $ku, $nomor, $tabel, $jumlahAtlet);
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        // Di luar transaksi: ALTER TABLE di MySQL menutup transaksi yang sedang berjalan.
        $urut = max((int) ($isi['urut_berikutnya'] ?? 0), $this->urutBerikutnya());
        $this->db->query('ALTER TABLE pendaftaran AUTO_INCREMENT = ' . $urut);

        return ['pendaftaran' => count($isi['pendaftaran']), 'atlet' => $jumlahAtlet];
    }

    /**
     * @param array<string, mixed>     $isi
     * @param array<string, int>       $ku
     * @param array<string, int>       $nomor
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanBaris(array $isi, array $ku, array $nomor, callable $tabel, int &$jumlahAtlet): void
    {
        foreach ($isi['pendaftaran'] as $p) {
            $id = $tabel('pendaftaran', [
                'id'                => (int) $p['id'],
                'id_pendaftaran'    => (string) $p['id_pendaftaran'],
                'jenjang'           => (string) $p['jenjang'],
                'npsn'              => $p['npsn'],
                'nama_sekolah'      => (string) $p['nama_sekolah'],
                'kabupaten'         => (string) $p['kabupaten'],
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
                'status'            => (string) $p['status'],
                'waktu_lunas'       => $p['waktu_lunas'],
                'dibuat_pada'       => $p['dibuat_pada'],
                'diubah_pada'       => $p['diubah_pada'],
            ]);

            foreach ($p['penanggung_jawab'] ?? [] as $o) {
                $tabel('penanggung_jawab', [
                    'pendaftaran_id' => $id, 'nama' => (string) $o['nama'], 'nomor_wa' => (string) $o['nomor_wa'],
                    'foto' => $this->berkasSah($o['foto'] ?? null) ? $o['foto'] : null,
                    'dibuat_pada' => $o['dibuat_pada'], 'diubah_pada' => $o['diubah_pada'],
                ]);
            }

            foreach ($p['atlet'] ?? [] as $a) {
                $idAtlet = $tabel('atlet', [
                    'pendaftaran_id'   => $id,
                    'nama'             => (string) $a['nama'],
                    'nisn'             => (string) $a['nisn'],
                    'tanggal_lahir'    => $a['tanggal_lahir'],
                    'jenis_kelamin'    => (string) $a['jenis_kelamin'],
                    'kelompok_umur_id' => $ku[$a['kelompok_umur']],
                    'nomor_dada'       => $a['nomor_dada'],
                    'dibuat_pada'      => $a['dibuat_pada'],
                    'diubah_pada'      => $a['diubah_pada'],
                ]);
                $jumlahAtlet++;

                foreach ($a['nomor'] ?? [] as $n) {
                    $tabel('atlet_nomor', [
                        'atlet_id' => $idAtlet, 'nomor_lomba_id' => $nomor[self::kunciNomor($n['kelompok_umur'], $n['nama'], $n['kategori'])],
                        'biaya' => $n['biaya'] === null ? null : (int) $n['biaya'], 'dibuat_pada' => $a['dibuat_pada'], 'diubah_pada' => $a['diubah_pada'],
                    ]);
                }
            }

            foreach ($p['beregu'] ?? [] as $t) {
                $tabel('pendaftaran_beregu', [
                    'pendaftaran_id' => $id, 'nomor_lomba_id' => $nomor[self::kunciNomor($t['kelompok_umur'], $t['nama'], $t['kategori'])],
                    'biaya_tim' => (int) $t['biaya_tim'], 'dibuat_pada' => $t['dibuat_pada'], 'diubah_pada' => $t['diubah_pada'],
                ]);
            }

            foreach ($p['bukti_bayar'] ?? [] as $b) {
                if ($this->berkasSah($b['berkas'] ?? null)) {
                    $tabel('bukti_bayar', ['pendaftaran_id' => $id, 'berkas' => $b['berkas'], 'ukuran' => (int) $b['ukuran'], 'waktu_unggah' => $b['waktu_unggah']]);
                }
            }
        }

        // Nomor dada dan ID pendaftaran tidak dipakai ulang: lanjutkan dari angka server lama.
        $terakhir = [];

        foreach ($isi['awalan'] ?? [] as $a) {
            $terakhir[(string) $a['awalan']] = max($terakhir[(string) $a['awalan']] ?? 0, (int) $a['nomor_terakhir']);
        }

        foreach ($isi['pendaftaran'] as $p) {
            foreach ($p['atlet'] ?? [] as $a) {
                if (is_string($a['nomor_dada'] ?? null) && strlen($a['nomor_dada']) === 4) {
                    $terakhir[$a['nomor_dada'][0]] = max($terakhir[$a['nomor_dada'][0]] ?? 0, (int) substr($a['nomor_dada'], 1));
                }
            }
        }

        foreach ($terakhir as $awalan => $angka) {
            $this->db->query('UPDATE awalan_nomor_dada SET nomor_terakhir = GREATEST(nomor_terakhir, ?) WHERE awalan = ?', [$angka, (string) $awalan]);
        }
    }

    /**
     * Nomor `pendaftaran.id` berikutnya (nomor urut ID pendaftaran, K32).
     */
    private function urutBerikutnya(): int
    {
        $status = $this->db->query("SHOW TABLE STATUS LIKE 'pendaftaran'")->getRowArray();
        $max    = (int) ($this->db->table('pendaftaran')->selectMax('id')->get()->getRow()->id ?? 0);

        return max((int) ($status['Auto_increment'] ?? 0), $max + 1);
    }

    private function berkasSah(mixed $jalur): bool
    {
        return is_string($jalur) && preg_match(self::POLA_BERKAS, $jalur) === 1;
    }

    private static function kunciNomor(string $ku, string $nama, string $kategori): string
    {
        return $ku . '|' . $nama . '|' . $kategori;
    }
}
