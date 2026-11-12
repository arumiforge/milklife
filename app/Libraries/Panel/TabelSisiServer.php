<?php

namespace App\Libraries\Panel;

use Closure;
use CodeIgniter\Database\BaseBuilder;
use InvalidArgumentException;

/**
 * Tabel data admin yang halamannya, pencariannya, saringannya, dan urutannya
 * dikerjakan basis data (rencana Tahap 6, "Tabel dan ekspor").
 *
 * Peramban (DataTables lewat `aset/js/tabel-admin.js`) mengirim parameter
 * `draw`, `start`, `length`, `search[value]`, `order[i][column]`,
 * `order[i][dir]`, `columns[i][data]`, dan saringan bernama (`status=lunas`).
 * Alamat Excel memakai bentuk pendek yang sama artinya: `cari`, `urut`, `arah`,
 * dan saringan bernama yang sama.
 *
 * Nama kolom dari permintaan **tidak pernah** dipakai sebagai SQL. Permintaan
 * hanya memilih kunci dari daftar kolom dan saringan yang didaftarkan di sini;
 * kunci yang tidak dikenal diabaikan. Nilai pencarian dan saringan selalu
 * di-escape oleh query builder.
 *
 * Pemakaian di pengendali:
 *
 *     $tabel = new TabelSisiServer(
 *         kolom: [
 *             // kunci data JSON => ekspresi SQL dan sifatnya
 *             'id_pendaftaran' => ['sql' => 'p.id_pendaftaran', 'cari' => true],
 *             'nama_sekolah'   => ['sql' => 'p.nama_sekolah', 'cari' => true],
 *             'total'          => ['sql' => 'p.total'],                 // bisa diurutkan, tidak dicari
 *             'aksi'           => ['urut' => false],                    // kolom tampilan saja
 *         ],
 *         saringan: [
 *             'status'    => ['sql' => 'p.status', 'nilai' => ['lunas', 'menunggu']],
 *             'kabupaten' => 'p.kabupaten',                             // nilai apa pun, dicocokkan persis
 *             'jenis_kelamin' => static fn (BaseBuilder $b, string $nilai) => $b->where('a.jenis_kelamin', $nilai),
 *         ],
 *         urutBawaan: ['p.id' => 'desc'],
 *     );
 *
 *     $pembuat = fn (): BaseBuilder => db_connect()->table('pendaftaran p')->select('p.id, p.id_pendaftaran, …');
 *
 *     // JSON untuk DataTables:
 *     return $this->response->setJSON($tabel->jawab($pembuat, $this->request->getGet()));
 *
 *     // Excel dengan saringan, pencarian, dan urutan yang sama, tanpa batas halaman:
 *     $baris = $tabel->semuaBaris($pembuat, $this->request->getGet());
 *
 * `$pembuat` dipanggil beberapa kali (jumlah semua, jumlah tersaring, data);
 * setiap panggilan harus menghasilkan builder baru dengan `select`, `join`,
 * dan `groupBy` yang sama. Bentuk baris JSON bisa diubah dengan
 * `bentukBaris()`.
 */
final class TabelSisiServer
{
    /**
     * Baris per halaman bila peramban tidak memintanya (asumsi 5 rencana Tahap 6).
     */
    public const PANJANG_BAWAAN = 25;

    /**
     * Baris per halaman paling banyak untuk JSON. Excel tidak dibatasi.
     */
    public const PANJANG_MAKSIMAL = 100;

    /**
     * Panjang teks pencarian paling banyak; sisanya dibuang.
     */
    public const CARI_MAKSIMAL = 100;

    /**
     * @var array<string, array{sql: string|null, cari: bool, urut: bool}>
     */
    private array $kolom = [];

    /**
     * @var array<string, array{fungsi: Closure(BaseBuilder, string): mixed, nilai: list<string>|null}>
     */
    private array $saringan = [];

    /**
     * @var list<array{0: string, 1: string}>
     */
    private array $urutBawaan = [];

    /**
     * @var (Closure(array<string, mixed>): array<string, mixed>)|null
     */
    private ?Closure $pembentuk = null;

    /**
     * @param array<string, array{sql?: string|null, cari?: bool, urut?: bool}|string> $kolom
     *        Kunci data => definisi. Bentuk pendek `'kunci' => 'p.kolom'` sama dengan
     *        `['sql' => 'p.kolom']`. `cari` bawaan `false`; `urut` bawaan `true` bila ada `sql`.
     * @param array<string, array{sql: string, nilai?: list<string>|null}|Closure|string> $saringan
     *        Nama parameter => kolom SQL yang dicocokkan persis, daftar nilai yang diizinkan
     *        (nilai lain diabaikan), atau fungsi `fn (BaseBuilder $b, string $nilai)`.
     * @param array<string, string> $urutBawaan Ekspresi SQL => `asc`/`desc`; selalu dipakai
     *        sesudah urutan pilihan pengguna supaya urutan baris tetap.
     */
    public function __construct(array $kolom, array $saringan = [], array $urutBawaan = [])
    {
        foreach ($kolom as $kunci => $definisi) {
            if (! is_string($kunci) || $kunci === '') {
                throw new InvalidArgumentException('Kunci kolom tabel harus berupa teks.');
            }

            $definisi = is_string($definisi) ? ['sql' => $definisi] : $definisi;
            $sql      = $definisi['sql'] ?? null;

            $this->kolom[$kunci] = [
                'sql'  => $sql,
                'cari' => $sql !== null && ($definisi['cari'] ?? false),
                'urut' => $sql !== null && ($definisi['urut'] ?? true),
            ];
        }

        foreach ($saringan as $nama => $definisi) {
            if (! is_string($nama) || $nama === '' || in_array($nama, ['draw', 'start', 'length', 'search', 'order', 'columns', 'cari', 'urut', 'arah', '_'], true)) {
                throw new InvalidArgumentException("Nama saringan tidak boleh dipakai: {$nama}.");
            }

            $this->saringan[$nama] = self::definisiSaringan($definisi);
        }

        foreach ($urutBawaan as $sql => $arah) {
            $this->urutBawaan[] = [$sql, strtolower($arah) === 'desc' ? 'DESC' : 'ASC'];
        }
    }

    /**
     * Ubah bentuk setiap baris sebelum dikirim sebagai JSON atau ke Excel.
     *
     * @param Closure(array<string, mixed>): array<string, mixed> $pembentuk
     */
    public function bentukBaris(Closure $pembentuk): self
    {
        $this->pembentuk = $pembentuk;

        return $this;
    }

    /**
     * Parameter permintaan yang sudah dibersihkan. Tidak menyentuh basis data.
     *
     * @param array<string, mixed> $masukan Biasanya `$this->request->getGet()`.
     *
     * @return array{draw: int, mulai: int, panjang: int, cari: string, urut: list<array{0: string, 1: string}>, saringan: array<string, string>}
     */
    public function parameter(array $masukan): array
    {
        $panjang = self::bilangan($masukan['length'] ?? null, self::PANJANG_BAWAAN);

        return [
            'draw'     => max(0, self::bilangan($masukan['draw'] ?? null, 0)),
            'mulai'    => max(0, self::bilangan($masukan['start'] ?? null, 0)),
            'panjang'  => $panjang < 1 ? self::PANJANG_BAWAAN : min($panjang, self::PANJANG_MAKSIMAL),
            'cari'     => $this->teksCari($masukan),
            'urut'     => $this->urutan($masukan),
            'saringan' => $this->nilaiSaringan($masukan),
        ];
    }

    /**
     * Jawaban JSON untuk DataTables: `{draw, recordsTotal, recordsFiltered, data}`.
     *
     * @param Closure(): BaseBuilder $pembuat
     * @param array<string, mixed>   $masukan
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function jawab(Closure $pembuat, array $masukan): array
    {
        $parameter = $this->parameter($masukan);
        $total     = $this->hitung($pembuat());

        $tersaring = $this->terapkanSaringan($pembuat(), $parameter);
        $jumlah    = ($parameter['cari'] === '' && $parameter['saringan'] === []) ? $total : $this->hitung($tersaring);

        $data = $this->terapkanUrut($this->terapkanSaringan($pembuat(), $parameter), $parameter)
            ->limit($parameter['panjang'], $parameter['mulai'])
            ->get()
            ->getResultArray();

        return [
            'draw'            => $parameter['draw'],
            'recordsTotal'    => $total,
            'recordsFiltered' => $jumlah,
            'data'            => $this->bentuk($data),
        ];
    }

    /**
     * Semua baris yang lolos pencarian dan saringan, berurutan, tanpa batas
     * halaman (untuk Excel).
     *
     * @param Closure(): BaseBuilder $pembuat
     * @param array<string, mixed>   $masukan
     *
     * @return list<array<string, mixed>>
     */
    public function semuaBaris(Closure $pembuat, array $masukan): array
    {
        $parameter = $this->parameter($masukan);

        return $this->bentuk(
            $this->terapkanUrut($this->terapkanSaringan($pembuat(), $parameter), $parameter)->get()->getResultArray(),
        );
    }

    /**
     * Saringan, pencarian, dan urutan aktif dalam bentuk pendek untuk alamat
     * Excel (`cari`, `urut`, `arah`, dan saringan bernama).
     *
     * @param array<string, mixed> $masukan
     *
     * @return array<string, string>
     */
    public function parameterExcel(array $masukan): array
    {
        $parameter = $this->parameter($masukan);
        $hasil     = $parameter['saringan'];

        if ($parameter['cari'] !== '') {
            $hasil['cari'] = $parameter['cari'];
        }

        $urut = $this->urutanPengguna($masukan);

        if ($urut !== null) {
            $hasil['urut'] = $urut[0];
            $hasil['arah'] = strtolower($urut[1]);
        }

        return $hasil;
    }

    private function hitung(BaseBuilder $builder): int
    {
        return $builder->countAllResults();
    }

    /**
     * @param array{cari: string, saringan: array<string, string>} $parameter
     */
    private function terapkanSaringan(BaseBuilder $builder, array $parameter): BaseBuilder
    {
        foreach ($parameter['saringan'] as $nama => $nilai) {
            ($this->saringan[$nama]['fungsi'])($builder, $nilai);
        }

        $dicari = array_filter($this->kolom, static fn (array $k): bool => $k['cari']);

        if ($parameter['cari'] !== '' && $dicari !== []) {
            // `like()` CodeIgniter tidak meloloskan `%` dan `_` di teks yang
            // dicari, jadi polanya disusun sendiri: teks di-escape untuk SQL
            // dan untuk LIKE. Kolasi tabel tidak membedakan huruf besar-kecil.
            $db    = $builder->db();
            $pola  = "'%" . $db->escapeLikeString($parameter['cari']) . "%' ESCAPE '" . $db->likeEscapeChar . "'";

            $builder->groupStart();

            foreach ($dicari as $kolom) {
                $builder->orWhere($kolom['sql'] . ' LIKE ' . $pola, null, false);
            }

            $builder->groupEnd();
        }

        return $builder;
    }

    /**
     * @param array{urut: list<array{0: string, 1: string}>} $parameter
     */
    private function terapkanUrut(BaseBuilder $builder, array $parameter): BaseBuilder
    {
        foreach ($parameter['urut'] as [$sql, $arah]) {
            $builder->orderBy($sql, $arah, false);
        }

        return $builder;
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return list<array<string, mixed>>
     */
    private function bentuk(array $baris): array
    {
        if ($this->pembentuk === null) {
            return $baris;
        }

        return array_map($this->pembentuk, $baris);
    }

    /**
     * @param array<string, mixed> $masukan
     */
    private function teksCari(array $masukan): string
    {
        $cari = $masukan['search']['value'] ?? $masukan['cari'] ?? '';

        if (! is_string($cari)) {
            return '';
        }

        $cari = trim(preg_replace('/\s+/u', ' ', $cari) ?? '');

        return mb_substr($cari, 0, self::CARI_MAKSIMAL);
    }

    /**
     * Urutan pilihan pengguna (satu kolom saja) lalu urutan bawaan.
     *
     * @param array<string, mixed> $masukan
     *
     * @return list<array{0: string, 1: string}>
     */
    private function urutan(array $masukan): array
    {
        $hasil = [];
        $urut  = $this->urutanPengguna($masukan);

        if ($urut !== null) {
            $hasil[] = [(string) $this->kolom[$urut[0]]['sql'], $urut[1]];
        }

        foreach ($this->urutBawaan as $bawaan) {
            if (! in_array($bawaan[0], array_column($hasil, 0), true)) {
                $hasil[] = $bawaan;
            }
        }

        return $hasil;
    }

    /**
     * Kunci kolom dan arah yang diminta pengguna, atau `null`.
     *
     * DataTables mengirim nomor kolom (`order[0][column]`) dan kunci data kolom
     * itu (`columns[n][data]`); bentuk pendek mengirim kunci langsung (`urut`).
     * Keduanya hanya diterima bila kuncinya terdaftar dan boleh diurutkan.
     *
     * @param array<string, mixed> $masukan
     *
     * @return array{0: string, 1: string}|null
     */
    private function urutanPengguna(array $masukan): ?array
    {
        $kunci = null;
        $arah  = null;

        if (isset($masukan['order'][0]) && is_array($masukan['order'][0])) {
            $nomor = $masukan['order'][0]['column'] ?? null;
            $arah  = $masukan['order'][0]['dir'] ?? null;
            $data  = is_numeric($nomor) ? ($masukan['columns'][(int) $nomor]['data'] ?? null) : null;
            $kunci = is_string($data) ? $data : null;
        } elseif (isset($masukan['urut'])) {
            $kunci = is_string($masukan['urut']) ? $masukan['urut'] : null;
            $arah  = $masukan['arah'] ?? null;
        }

        if ($kunci === null || ! isset($this->kolom[$kunci]) || ! $this->kolom[$kunci]['urut']) {
            return null;
        }

        return [$kunci, is_string($arah) && strtolower($arah) === 'desc' ? 'DESC' : 'ASC'];
    }

    /**
     * @param array<string, mixed> $masukan
     *
     * @return array<string, string>
     */
    private function nilaiSaringan(array $masukan): array
    {
        $hasil = [];

        foreach ($this->saringan as $nama => $definisi) {
            $nilai = $masukan[$nama] ?? null;

            if (is_int($nilai)) {
                $nilai = (string) $nilai;
            }

            if (! is_string($nilai) || ($nilai = trim($nilai)) === '' || mb_strlen($nilai) > self::CARI_MAKSIMAL) {
                continue;
            }

            // Nilai di luar daftar yang diizinkan diabaikan, bukan menghasilkan tabel kosong.
            if ($definisi['nilai'] !== null && ! in_array($nilai, $definisi['nilai'], true)) {
                continue;
            }

            $hasil[$nama] = $nilai;
        }

        return $hasil;
    }

    /**
     * @param array{sql: string, nilai?: list<string>|null}|Closure|string $definisi
     *
     * @return array{fungsi: Closure(BaseBuilder, string): mixed, nilai: list<string>|null}
     */
    private static function definisiSaringan(array|Closure|string $definisi): array
    {
        if ($definisi instanceof Closure) {
            return ['fungsi' => $definisi, 'nilai' => null];
        }

        $definisi = is_string($definisi) ? ['sql' => $definisi] : $definisi;
        $sql      = $definisi['sql'];

        return [
            'fungsi' => static fn (BaseBuilder $builder, string $nilai): BaseBuilder => $builder->where($sql, $nilai),
            'nilai'  => isset($definisi['nilai']) ? array_map('strval', $definisi['nilai']) : null,
        ];
    }

    private static function bilangan(mixed $nilai, int $bawaan): int
    {
        if (is_int($nilai)) {
            return $nilai;
        }

        return is_string($nilai) && preg_match('/^-?\d{1,9}$/D', $nilai) === 1 ? (int) $nilai : $bawaan;
    }
}
