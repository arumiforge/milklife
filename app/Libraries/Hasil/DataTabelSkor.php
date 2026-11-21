<?php

namespace App\Libraries\Hasil;

use CodeIgniter\Database\BaseConnection;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Tabel skor multi-lomba di basis data (F9-10, K140 butir 7b): pasang tabel
 * bawaan dari `data/tabel-skor/*.json`, ubah baris, ganti baris dari Excel,
 * dan hitung ulang hasil nomor yang memakai tabel itu.
 *
 * Perhitungan poinnya sendiri di `TabelSkor` (tanpa basis data).
 *
 *     $data = new DataTabelSkor();
 *     $data->pasangBawaan();                        // ['dipasang' => ['Trilomba O2SN SMP', …], 'sudah_ada' => []]
 *     $data->baris(1, 'lari-60', 'putra');           // [[546, 1500], [547, 1499], …]
 *     $data->simpanBaris(1, 'lari-60', 'putra', '7,00', '882');
 *     $data->gantiBaris(1, 'lari-60', 'putra', [[546, 1500], …]);  // dari Excel, lalu hasil dihitung ulang
 */
final class DataTabelSkor
{
    /**
     * Folder tabel skor bawaan.
     */
    public const FOLDER_BAWAAN = ROOTPATH . 'data/tabel-skor';

    /**
     * Kode tabel bawaan yang dipasang ke nomor Trilomba oleh `data:awal`.
     */
    public const KODE_TRILOMBA = 'trilomba-o2sn-smp';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Semua tabel beserta jumlah baris per komponen dan kategori, dan nomor
     * lomba yang memakainya.
     *
     * @return list<array{id: int, kode: string, nama: string, keterangan: string, komponen: list<array{kode: string, nama: string, jenis: string}>, jumlah: array<string, array<string, int>>, nomor: list<string>}>
     */
    public function daftar(): array
    {
        $jumlah = [];

        foreach ($this->db->table('tabel_skor_baris')->select('tabel_skor_id, komponen, kategori, COUNT(*) AS jumlah')->groupBy('tabel_skor_id, komponen, kategori')->get()->getResultArray() as $b) {
            $jumlah[(int) $b['tabel_skor_id']][(string) $b['komponen']][(string) $b['kategori']] = (int) $b['jumlah'];
        }

        $nomor = [];

        foreach ($this->db->table('nomor_lomba nl')
            ->select('nl.tabel_skor_id, nl.nama, nl.kategori, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->where('nl.tabel_skor_id IS NOT NULL', null, false)
            ->orderBy('ku.urutan')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray() as $n) {
            $nomor[(int) $n['tabel_skor_id']][] = $n['kelompok_umur'] . ' ' . $n['nama'] . ' ' . $n['kategori'];
        }

        $daftar = [];

        foreach ($this->db->table('tabel_skor')->orderBy('nama')->orderBy('id')->get()->getResultArray() as $t) {
            $id       = (int) $t['id'];
            $daftar[] = [
                'id'         => $id,
                'kode'       => (string) $t['kode'],
                'nama'       => (string) $t['nama'],
                'keterangan' => (string) ($t['keterangan'] ?? ''),
                'komponen'   => TabelSkor::bacaKomponen($t['komponen']),
                'jumlah'     => $jumlah[$id] ?? [],
                'nomor'      => $nomor[$id] ?? [],
            ];
        }

        return $daftar;
    }

    /**
     * Satu tabel, atau `null`.
     *
     * @return array{id: int, kode: string, nama: string, keterangan: string, komponen: list<array{kode: string, nama: string, jenis: string}>}|null
     */
    public function tabel(int $id): ?array
    {
        $t = $this->db->table('tabel_skor')->where('id', $id)->get()->getRowArray();

        if (! is_array($t)) {
            return null;
        }

        return [
            'id'         => (int) $t['id'],
            'kode'       => (string) $t['kode'],
            'nama'       => (string) $t['nama'],
            'keterangan' => (string) ($t['keterangan'] ?? ''),
            'komponen'   => TabelSkor::bacaKomponen($t['komponen']),
        ];
    }

    /**
     * Pilihan tabel untuk formulir nomor lomba.
     *
     * @return array<int, array{nama: string, komponen: list<array{kode: string, nama: string, jenis: string}>}> id => tabel
     */
    public function pilihan(): array
    {
        $pilihan = [];

        foreach ($this->db->table('tabel_skor')->select('id, nama, komponen')->orderBy('nama')->orderBy('id')->get()->getResultArray() as $t) {
            $pilihan[(int) $t['id']] = ['nama' => (string) $t['nama'], 'komponen' => TabelSkor::bacaKomponen($t['komponen'])];
        }

        return $pilihan;
    }

    /**
     * Komponen yang dipakai satu nomor (urut seperti `komponen_skor`). Kode yang
     * tidak ada di tabel dilewati; `komponen_skor` kosong = semua komponen tabel.
     *
     * @return list<array{kode: string, nama: string, jenis: string}>
     */
    public function komponenNomor(?int $tabelId, ?string $komponenSkor): array
    {
        $tabel = $tabelId === null ? null : $this->tabel($tabelId);

        if ($tabel === null) {
            return [];
        }

        return self::pilihKomponen($tabel['komponen'], $komponenSkor);
    }

    /**
     * @param list<array{kode: string, nama: string, jenis: string}> $komponen
     *
     * @return list<array{kode: string, nama: string, jenis: string}>
     */
    public static function pilihKomponen(array $komponen, ?string $komponenSkor): array
    {
        $kode = array_values(array_filter(explode(',', (string) $komponenSkor), static fn (string $k): bool => $k !== ''));

        if ($kode === []) {
            return $komponen;
        }

        $menurutKode = array_column($komponen, null, 'kode');

        return array_values(array_filter(array_map(static fn (string $k): ?array => $menurutKode[$k] ?? null, $kode)));
    }

    /**
     * Baris satu komponen dan kategori, urut dari prestasi terbaik.
     *
     * @return list<array{0: int, 1: int}>
     */
    public function baris(int $tabelId, string $komponen, string $kategori, string $jenis = JenisHasil::WAKTU): array
    {
        return array_map(static fn (array $b): array => [(int) $b['prestasi'], (int) $b['poin']], $this->db->table('tabel_skor_baris')
            ->select('prestasi, poin')
            ->where('tabel_skor_id', $tabelId)
            ->where('komponen', $komponen)
            ->where('kategori', $kategori)
            ->orderBy('prestasi', $jenis === JenisHasil::WAKTU ? 'ASC' : 'DESC')
            ->get()->getResultArray());
    }

    /**
     * Baris semua komponen satu nomor untuk kategorinya.
     *
     * @param list<array{kode: string, nama: string, jenis: string}> $komponen
     *
     * @return array<string, list<array{0: int, 1: int}>>
     */
    public function barisKomponen(int $tabelId, array $komponen, string $kategori): array
    {
        $baris = [];

        foreach ($komponen as $k) {
            $baris[$k['kode']] = $this->baris($tabelId, $k['kode'], $kategori, $k['jenis']);
        }

        return $baris;
    }

    /**
     * Pasang tabel bawaan dari `data/tabel-skor/`. Tabel yang kodenya sudah ada
     * dilewati, kecuali `$timpa` (barisnya diganti isi berkas, lalu hasil
     * nomor yang memakainya dihitung ulang).
     *
     * @return array{dipasang: list<string>, sudah_ada: list<string>}
     *
     * @throws RuntimeException berkas rusak
     */
    public function pasangBawaan(bool $timpa = false, string $folder = self::FOLDER_BAWAAN): array
    {
        $hasil = ['dipasang' => [], 'sudah_ada' => []];

        foreach (self::berkasBawaan($folder) as $data) {
            $ada = $this->db->table('tabel_skor')->where('kode', $data['kode'])->countAllResults() > 0;

            if ($ada && ! $timpa) {
                $hasil['sudah_ada'][] = $data['nama'];

                continue;
            }

            $this->simpanData($data);
            $hasil['dipasang'][] = $data['nama'];
        }

        return $hasil;
    }

    /**
     * Pakai tabel Trilomba bawaan untuk nomor individu bernama Trilomba yang
     * masih berjenis hasil poin dan belum punya prestasi (data awal, atau
     * `tabel-skor:impor --trilomba`). Nomor yang sudah punya prestasi tidak diubah.
     *
     * @return int banyak nomor yang diubah
     */
    public function pakaiUntukTrilomba(): int
    {
        $tabel = $this->db->table('tabel_skor')->select('id')->where('kode', self::KODE_TRILOMBA)->get()->getRow();

        if ($tabel === null) {
            return 0;
        }

        $id = $this->trilombaMasihPoin();

        if ($id !== []) {
            $this->db->table('nomor_lomba')->whereIn('id', $id)->update(['jenis_hasil' => JenisHasil::MULTI, 'tabel_skor_id' => (int) $tabel->id, 'komponen_skor' => null]);
        }

        return count($id);
    }

    /**
     * ID nomor individu bernama Trilomba yang masih berjenis hasil poin dan
     * belum punya prestasi (yang akan dipindah `pakaiUntukTrilomba()`).
     *
     * @return list<int>
     */
    public function trilombaMasihPoin(): array
    {
        $id = [];

        foreach ($this->db->table('nomor_lomba')->select('id, nama')->where('jenis', 'individu')->where('jenis_hasil', JenisHasil::POIN)->get()->getResultArray() as $n) {
            if (JenisHasil::tebak((string) $n['nama']) !== JenisHasil::POIN) {
                continue;
            }

            $adaPrestasi = $this->db->table('hasil h')->join('babak b', 'b.id = h.babak_id')->where('b.nomor_lomba_id', $n['id'])->where('h.prestasi IS NOT NULL', null, false)->countAllResults() > 0;

            if (! $adaPrestasi) {
                $id[] = (int) $n['id'];
            }
        }

        return $id;
    }

    /**
     * Isi berkas tabel bawaan yang sah, urut nama berkas.
     *
     * @return list<array<string, mixed>>
     *
     * @throws RuntimeException berkas rusak
     */
    public static function berkasBawaan(string $folder = self::FOLDER_BAWAAN): array
    {
        $daftar = [];

        foreach (glob(rtrim($folder, '/') . '/*.json') ?: [] as $berkas) {
            try {
                $data = json_decode((string) file_get_contents($berkas), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('Tabel skor ' . basename($berkas) . ' rusak: ' . $e->getMessage(), 0, $e);
            }

            $galat = is_array($data) ? TabelSkor::periksaData($data) : ['bukan objek JSON'];

            if ($galat !== []) {
                throw new RuntimeException('Tabel skor ' . basename($berkas) . ' rusak: ' . implode('; ', $galat) . '.');
            }

            $daftar[] = $data;
        }

        return $daftar;
    }

    /**
     * Simpan satu tabel lengkap (bentuk berkas JSON bawaan atau paket pindah
     * server): dibuat bila kodenya belum ada, atau nama, komponen, dan semua
     * barisnya diganti. Hasil nomor yang memakai tabel itu dihitung ulang.
     *
     * @param array<string, mixed> $data
     */
    public function simpanData(array $data): int
    {
        $sekarang = date('Y-m-d H:i:s');
        $komponen = TabelSkor::bacaKomponen($data['komponen'] ?? null);
        $isi      = [
            'nama'        => mb_substr((string) $data['nama'], 0, TabelSkor::NAMA_MAKSIMAL),
            'keterangan'  => is_string($data['keterangan'] ?? null) ? mb_substr($data['keterangan'], 0, 255) : null,
            'komponen'    => json_encode($komponen, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'diubah_pada' => $sekarang,
        ];

        return $this->transaksi(function () use ($data, $isi, $sekarang): int {
            $lama = $this->db->table('tabel_skor')->select('id')->where('kode', $data['kode'])->get()->getRow();

            if ($lama !== null) {
                $id = (int) $lama->id;
                $this->pastikan($this->db->table('tabel_skor')->where('id', $id)->update($isi));
                $this->pastikan($this->db->table('tabel_skor_baris')->where('tabel_skor_id', $id)->delete());
            } else {
                $this->pastikan($this->db->table('tabel_skor')->insert($isi + ['kode' => $data['kode'], 'dibuat_pada' => $sekarang]));
                $id = (int) $this->db->insertID();
            }

            foreach ((array) $data['komponen'] as $k) {
                if (! is_array($k) || ! TabelSkor::kodeSah($k['kode'] ?? null)) {
                    continue;
                }

                foreach (array_keys(TabelSkor::KATEGORI) as $kategori) {
                    $this->tulisBaris($id, $k['kode'], $kategori, is_array($k['baris'][$kategori] ?? null) ? $k['baris'][$kategori] : []);
                }
            }

            $this->hitungUlangTabel($id);

            return $id;
        });
    }

    /**
     * Seluruh isi tabel dalam bentuk berkas JSON bawaan (untuk paket pindah server).
     *
     * @return list<array<string, mixed>>
     */
    public function eksporSemua(): array
    {
        $semua = [];

        foreach ($this->daftar() as $t) {
            $komponen = [];

            foreach ($t['komponen'] as $k) {
                $baris = [];

                foreach (array_keys(TabelSkor::KATEGORI) as $kategori) {
                    $baris[$kategori] = $this->baris($t['id'], $k['kode'], $kategori, $k['jenis']);
                }

                $komponen[] = $k + ['baris' => $baris];
            }

            $semua[] = ['kode' => $t['kode'], 'nama' => $t['nama'], 'keterangan' => $t['keterangan'], 'komponen' => $komponen];
        }

        return $semua;
    }

    /**
     * Buat tabel kosong baru dengan komponennya (baris diisi lewat Excel atau satu per satu).
     *
     * @param list<array{nama: string, jenis: string}> $komponen
     *
     * @return array{id?: int, galat?: array<string, string>}
     */
    public function buatTabel(string $nama, array $komponen): array
    {
        $nama  = trim((string) preg_replace('/\s+/u', ' ', $nama));
        $galat = [];

        if ($nama === '') {
            $galat['nama'] = 'Nama tabel belum diisi. Contoh: Trilomba KU 12 versi panitia.';
        } elseif (mb_strlen($nama) > TabelSkor::NAMA_MAKSIMAL) {
            $galat['nama'] = 'Nama tabel terlalu panjang. Persingkat menjadi paling banyak ' . TabelSkor::NAMA_MAKSIMAL . ' huruf.';
        } elseif ($this->db->table('tabel_skor')->where('LOWER(nama)', mb_strtolower($nama))->countAllResults() > 0) {
            $galat['nama'] = 'Tabel bernama ' . $nama . ' sudah ada. Pakai nama lain.';
        }

        $daftar = [];
        $kode   = [];

        foreach ($komponen as $k) {
            $namaK = trim((string) preg_replace('/\s+/u', ' ', $k['nama']));

            if ($namaK === '') {
                continue;
            }

            if (mb_strlen($namaK) > TabelSkor::NAMA_KOMPONEN_MAKSIMAL || ! isset(TabelSkor::JENIS_KOMPONEN[$k['jenis']])) {
                $galat['komponen'] = 'Nama komponen paling banyak ' . TabelSkor::NAMA_KOMPONEN_MAKSIMAL . ' huruf, dan jenisnya waktu atau jarak.';

                continue;
            }

            $kodeK = TabelSkor::kodeDariNama($namaK);

            if (isset($kode[$kodeK])) {
                $galat['komponen'] = 'Komponen ' . $namaK . ' tertulis dua kali. Hapus salah satunya.';

                continue;
            }

            $kode[$kodeK] = true;
            $daftar[]     = ['kode' => $kodeK, 'nama' => $namaK, 'jenis' => $k['jenis']];
        }

        if ($daftar === [] && ! isset($galat['komponen'])) {
            $galat['komponen'] = 'Tulis paling sedikit satu komponen, misalnya Lari 60 m (waktu).';
        } elseif (count($daftar) > TabelSkor::KOMPONEN_MAKSIMAL) {
            $galat['komponen'] = 'Paling banyak ' . TabelSkor::KOMPONEN_MAKSIMAL . ' komponen.';
        }

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        $kodeTabel = TabelSkor::kodeDariNama($nama);

        while ($this->db->table('tabel_skor')->where('kode', $kodeTabel)->countAllResults() > 0) {
            $kodeTabel = substr(TabelSkor::kodeDariNama($nama), 0, 50) . '-' . bin2hex(random_bytes(2));
        }

        $sekarang = date('Y-m-d H:i:s');
        $this->db->table('tabel_skor')->insert([
            'kode' => $kodeTabel, 'nama' => $nama, 'keterangan' => null,
            'komponen' => json_encode($daftar, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'dibuat_pada' => $sekarang, 'diubah_pada' => $sekarang,
        ]);

        return ['id' => (int) $this->db->insertID()];
    }

    /**
     * Ubah atau tambah satu baris: bila prestasinya sudah ada, poinnya diganti.
     *
     * @return array<string, string> galat per kotak; kosong bila tersimpan
     */
    public function simpanBaris(int $tabelId, string $komponen, string $kategori, string $teksPrestasi, string $teksPoin): array
    {
        $k = $this->komponen($tabelId, $komponen);

        if ($k === null || ! isset(TabelSkor::KATEGORI[$kategori])) {
            return ['prestasi' => 'Komponen tidak ditemukan. Muat ulang halaman.'];
        }

        $galat    = [];
        $prestasi = TabelSkor::uraiPrestasiTabel($teksPrestasi, $k['jenis']);
        $teksPoin = trim($teksPoin);

        if ($prestasi['nilai'] === null) {
            $galat['prestasi'] = $prestasi['galat'] ?? 'Prestasi belum diisi. ' . ($k['jenis'] === JenisHasil::WAKTU ? 'Contoh: 7,00.' : 'Contoh: 4,25.');
        }

        if (preg_match('/^\d{1,4}$/D', $teksPoin) !== 1 || (int) $teksPoin > TabelSkor::POIN_MAKSIMAL) {
            $galat['poin'] = 'Poin harus angka bulat 0 sampai ' . TabelSkor::POIN_MAKSIMAL . ', misalnya 882.';
        }

        if ($galat !== []) {
            return $galat;
        }

        $kunci = ['tabel_skor_id' => $tabelId, 'komponen' => $komponen, 'kategori' => $kategori];

        $this->transaksi(function () use ($kunci, $prestasi, $teksPoin, $tabelId): void {
            $this->pastikan($this->db->table('tabel_skor_baris')->where($kunci + ['prestasi' => $prestasi['nilai']])->delete());
            $this->pastikan($this->db->table('tabel_skor_baris')->insert($kunci + ['prestasi' => $prestasi['nilai'], 'poin' => (int) $teksPoin]));
            $this->sentuh($tabelId);
            $this->hitungUlangTabel($tabelId);
        });

        return [];
    }

    public function hapusBaris(int $tabelId, string $komponen, string $kategori, int $prestasi): bool
    {
        return $this->transaksi(function () use ($tabelId, $komponen, $kategori, $prestasi): bool {
            $this->pastikan($this->db->table('tabel_skor_baris')->where(['tabel_skor_id' => $tabelId, 'komponen' => $komponen, 'kategori' => $kategori, 'prestasi' => $prestasi])->delete());
            $hapus = $this->db->affectedRows() > 0;
            $this->sentuh($tabelId);
            $this->hitungUlangTabel($tabelId);

            return $hapus;
        });
    }

    /**
     * Ganti semua baris satu komponen dan kategori (impor Excel), lalu hitung ulang.
     *
     * @param list<array{0: int, 1: int}> $baris
     */
    public function gantiBaris(int $tabelId, string $komponen, string $kategori, array $baris): void
    {
        $this->transaksi(function () use ($tabelId, $komponen, $kategori, $baris): void {
            $this->pastikan($this->db->table('tabel_skor_baris')->where(['tabel_skor_id' => $tabelId, 'komponen' => $komponen, 'kategori' => $kategori])->delete());
            $this->tulisBaris($tabelId, $komponen, $kategori, $baris);
            $this->sentuh($tabelId);
            $this->hitungUlangTabel($tabelId);
        });
    }

    /**
     * Hapus tabel yang tidak dipakai nomor lomba mana pun.
     */
    public function hapusTabel(int $id): bool
    {
        if ($this->dipakai($id)) {
            return false;
        }

        return $this->db->table('tabel_skor')->where('id', $id)->delete() !== false && $this->db->affectedRows() > 0;
    }

    public function dipakai(int $id): bool
    {
        return $this->db->table('nomor_lomba')->where('tabel_skor_id', $id)->countAllResults() > 0;
    }

    /**
     * Satu komponen tabel, atau `null`.
     *
     * @return array{kode: string, nama: string, jenis: string}|null
     */
    public function komponen(int $tabelId, string $kode): ?array
    {
        foreach ($this->tabel($tabelId)['komponen'] ?? [] as $k) {
            if ($k['kode'] === $kode) {
                return $k;
            }
        }

        return null;
    }

    /**
     * Hitung ulang poin dan peringkat semua hasil nomor `multi` yang memakai
     * tabel ini (setelah barisnya berubah). Koreksi peringkat admin tetap.
     */
    public function hitungUlangTabel(int $tabelId): int
    {
        $tabel = $this->tabel($tabelId);

        if ($tabel === null) {
            return 0;
        }

        $ubah   = 0;
        $hasil  = new HasilLomba($this->db);
        $cache  = [];

        foreach ($this->db->table('nomor_lomba')->select('id, kategori, komponen_skor')->where('tabel_skor_id', $tabelId)->where('jenis_hasil', JenisHasil::MULTI)->get()->getResultArray() as $n) {
            $komponen = self::pilihKomponen($tabel['komponen'], $n['komponen_skor']);
            $kunci    = $n['kategori'] . '|' . implode(',', array_column($komponen, 'kode'));
            $baris    = $cache[$kunci] ??= $this->barisKomponen($tabelId, $komponen, (string) $n['kategori']);

            foreach ($this->db->table('babak')->select('id')->where('nomor_lomba_id', $n['id'])->get()->getResultArray() as $b) {
                $adaUbah = false;

                foreach ($this->db->table('hasil')->select('id, prestasi, rincian')->where('babak_id', $b['id'])->where('rincian IS NOT NULL', null, false)->get()->getResultArray() as $h) {
                    $baru  = TabelSkor::hitungUlang($komponen, $baris, TabelSkor::bacaRincian($h['rincian']));
                    $teks  = TabelSkor::tulisRincian($baru['rincian']);
                    $lama  = $h['prestasi'] === null ? null : (int) $h['prestasi'];

                    if ($teks !== $h['rincian'] || $baru['total'] !== $lama) {
                        $this->pastikan($this->db->table('hasil')->where('id', $h['id'])->update(['prestasi' => $baru['total'], 'rincian' => $teks]));
                        $adaUbah = true;
                        $ubah++;
                    }
                }

                if ($adaUbah) {
                    $hasil->hitungUlang((int) $b['id'], JenisHasil::MULTI);
                }
            }
        }

        return $ubah;
    }

    /**
     * @param list<mixed> $baris
     */
    private function tulisBaris(int $tabelId, string $komponen, string $kategori, array $baris): void
    {
        $isi = [];
        $ada = [];

        foreach ($baris as $b) {
            if (is_array($b) && is_int($b[0] ?? null) && is_int($b[1] ?? null) && $b[0] > 0 && $b[1] >= 0 && $b[1] <= TabelSkor::POIN_MAKSIMAL && ! isset($ada[$b[0]])) {
                $ada[$b[0]] = true;
                $isi[]      = ['tabel_skor_id' => $tabelId, 'komponen' => $komponen, 'kategori' => $kategori, 'prestasi' => $b[0], 'poin' => $b[1]];
            }
        }

        foreach (array_chunk($isi, 500) as $potong) {
            $this->pastikan($this->db->table('tabel_skor_baris')->insertBatch($potong));
        }
    }

    private function sentuh(int $tabelId): void
    {
        $this->pastikan($this->db->table('tabel_skor')->where('id', $tabelId)->update(['diubah_pada' => date('Y-m-d H:i:s')]));
    }

    /**
     * Jalankan `$kerja` dalam satu transaksi: semua tersimpan atau tidak ada.
     *
     * transBegin/transCommit dalam try/catch, bukan transStart/transComplete:
     * dalam mode ketat status gagal transaksi lain di koneksi yang sama tidak
     * ikut membatalkan penyimpanan ini (lihat `FaqModel::pindah()`, #129).
     * Di dalam transaksi CodeIgniter mengembalikan `false` alih-alih melempar
     * galat, jadi setiap tulis diperiksa dengan `pastikan()`.
     *
     * @template T
     *
     * @param callable(): T $kerja
     *
     * @return T
     */
    private function transaksi(callable $kerja): mixed
    {
        $this->db->transBegin();

        try {
            $hasil = $kerja();
            $this->db->transCommit();

            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * @param mixed $hasil Hasil `insert`, `insertBatch`, `update`, atau `delete`.
     */
    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            throw new RuntimeException('Tabel skor gagal disimpan ke basis data.');
        }
    }
}
