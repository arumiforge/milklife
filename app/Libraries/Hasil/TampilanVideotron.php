<?php

namespace App\Libraries\Hasil;

use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Daftar tampilan videotron dan isi tiap tampilan tetap (F11-09, K173).
 *
 * Videotron di arena dijalankan operator vendor. Mereka membuka halaman daftar
 * `/layar`, memilih satu tautan sesuai keadaan, lalu tampilan itu tetap di
 * layar sampai operator memilih yang lain: tidak berganti sendiri. Isinya
 * diperbarui diam-diam supaya hasil baru masuk tanpa memuat ulang.
 *
 * Jenis tampilan (alamat di bawah `layar/`):
 *
 * - `seri/<id babak>/<seri>`: daftar start (urutan lintasan) setelah daftar
 *   seri dikunci, lalu hasil seri itu setelah hasil nomornya terbit.
 * - `sesi/<Y-m-d>/<pagi|siang|sore|tanpa-jam>`: semua hasil terbit di satu
 *   sesi. Sesi = tanggal dan bagian hari dari jam mulai babak (`bagianHari()`).
 * - `klasemen/<kunci>`: satu klasemen juara umum dari `HasilLomba::klasemen()`.
 * - `kelompok-umur/<id>`: semua hasil terbit satu kelompok umur.
 * - `nomor/<id>`: semua babak terbit satu nomor, Final paling atas.
 *
 * Hanya data terbit, sama dengan papan putar (`PapanVideotron`): hasil dari
 * nomor yang `hasil_terbit_pada`-nya terisi, daftar start dari babak yang
 * daftar serinya dikunci. Baris hasil dan klasemen diambil dari
 * `PapanVideotron::panelHasil()` dan `PapanVideotron::juaraUmum()`, jadi
 * format prestasi dan susunan klasemen selalu sama dengan papan putar dan
 * halaman hasil.
 *
 * Isi tampilan (`tampilan()`) berbentuk sama dengan JSON papan putar, dibaca
 * `public/aset/js/layar.js` dalam mode tetap (`pengaturan.tetap`):
 *
 *     $t = new TampilanVideotron();
 *     $t->daftar();                          // ['hari' => […], 'klasemen' => […], 'kelompok_umur' => […]]
 *     $t->tampilan('seri', ['12', '2']);     // ['versi' => …, 'judul' => 'Lari 60 m putra …', 'start' => […], …] atau null
 */
final class TampilanVideotron
{
    /**
     * Bagian hari => label. Sesi ditentukan dari jam mulai babak.
     *
     * @var array<string, string>
     */
    public const BAGIAN = [
        'pagi'      => 'Pagi',
        'siang'     => 'Siang',
        'sore'      => 'Sore',
        'tanpa-jam' => 'Jam menyusul',
    ];

    /**
     * Jam mulai bagian Siang dan Sore (WIB, `HH:MM`).
     */
    public const MULAI_SIANG = '11:00';

    public const MULAI_SORE = '15:00';

    /**
     * Jenis tampilan tetap => label di halaman daftar.
     *
     * @var array<string, string>
     */
    public const JENIS = [
        'seri'          => 'Per seri',
        'sesi'          => 'Per sesi',
        'klasemen'      => 'Klasemen',
        'kelompok-umur' => 'Per kelompok umur',
        'nomor'         => 'Per nomor lomba',
    ];

    public const KOSONG_HASIL = 'Hasil tampil di sini setelah panitia menerbitkannya.';

    private BaseConnection $db;

    private Time $sekarang;

    private HasilLomba $hasil;

    private SeriLintasan $seri;

    private PapanVideotron $papan;

    public function __construct(?BaseConnection $db = null, ?Time $sekarang = null)
    {
        $this->db       = $db ?? db_connect();
        $this->sekarang = $sekarang ?? Time::now(config('App')->appTimezone);
        $this->hasil    = new HasilLomba($this->db);
        $this->seri     = new SeriLintasan($this->db);
        $this->papan    = new PapanVideotron($this->db, $this->sekarang);
    }

    /**
     * Bagian hari dari jam mulai `HH:MM`: sebelum 11.00 Pagi, sebelum 15.00
     * Siang, selebihnya Sore; tanpa jam = `tanpa-jam`.
     */
    public static function bagianHari(?string $jam): string
    {
        if ($jam === null || $jam === '') {
            return 'tanpa-jam';
        }

        $jam = substr($jam, 0, 5);

        return $jam < self::MULAI_SIANG ? 'pagi' : ($jam < self::MULAI_SORE ? 'siang' : 'sore');
    }

    /**
     * Isi halaman daftar `/layar`.
     *
     * `hari`: babak terjadwal per tanggal lalu sesi, tiap babak dengan nomor
     * seri yang sudah dikunci. `klasemen`: klasemen yang sudah punya isi.
     * `kelompok_umur`: semua kelompok umur dan nomornya, juga yang belum
     * terbit, supaya operator bisa menyiapkan tautan lebih dulu.
     *
     * @return array{hari: list<array{tanggal: string, label: string, sesi: list<array{bagian: string, label: string, alamat: string, terbit: int, babak: list<array<string, mixed>>}>}>, klasemen: list<array{kunci: string, label: string, alamat: string}>, kelompok_umur: list<array{id: int, nama: string, alamat: string, terbit: int, nomor: list<array{id: int, nama: string, terbit: bool, alamat: string}>}>}
     */
    public function daftar(): array
    {
        $seriDikunci = $this->seriDikunci();
        $hari        = [];

        foreach ((new JadwalPertandingan($this->db))->perHari() as $tanggal => $daftarBabak) {
            $sesi = [];

            foreach ($daftarBabak as $b) {
                $bagian = self::bagianHari($b['jam']);
                $sesi[$bagian] ??= [
                    'bagian' => $bagian,
                    'label'  => self::BAGIAN[$bagian],
                    'alamat' => 'layar/sesi/' . $tanggal . '/' . $bagian,
                    'terbit' => 0,
                    'babak'  => [],
                ];

                $terbit = $b['terbit'] && $b['ada_hasil'];
                $sesi[$bagian]['terbit'] += $terbit ? 1 : 0;
                $sesi[$bagian]['babak'][] = [
                    'id'            => $b['id'],
                    'nomor_id'      => $b['nomor_id'],
                    'nomor'         => $b['nomor_lengkap'],
                    'kelompok_umur' => $b['kelompok_umur'],
                    'babak'         => $b['babak'],
                    'jam'           => $b['jam'] === null ? '' : str_replace(':', '.', $b['jam']),
                    'lokasi'        => $b['lokasi'],
                    'terbit'        => $terbit,
                    'seri'          => $b['seri_dikunci'] ? ($seriDikunci[$b['id']] ?? []) : [],
                    'dikunci'       => $b['seri_dikunci'],
                ];
            }

            // Urutan tetap Pagi, Siang, Sore, lalu yang jamnya menyusul.
            $urut = [];

            foreach (array_keys(self::BAGIAN) as $bagian) {
                if (isset($sesi[$bagian])) {
                    $urut[] = $sesi[$bagian];
                }
            }

            $hari[] = ['tanggal' => $tanggal, 'label' => JadwalPertandingan::namaHari($tanggal), 'sesi' => $urut];
        }

        $klasemen = array_map(static fn (array $k): array => [
            'kunci'  => $k['kunci'],
            'label'  => $k['label'],
            'alamat' => 'layar/klasemen/' . rawurlencode($k['kunci']),
        ], array_values(array_filter($this->papan->juaraUmum(), static fn (array $k): bool => $k['baris'] !== [])));

        $kelompokUmur = [];

        foreach ($this->hasil->daftarNomor() as $ku) {
            // Nomor "Tidak dilombakan" keluar dari videotron (F11-03).
            $nomor = array_map(static fn (array $n): array => [
                'id'     => $n['id'],
                'nama'   => $n['nama'],
                'terbit' => $n['terbit_pada'] !== null,
                'alamat' => 'layar/nomor/' . $n['id'],
            ], array_values(array_filter($ku['nomor'], static fn (array $n): bool => $n['dilombakan'])));

            if ($nomor === []) {
                continue;
            }

            $kelompokUmur[] = [
                'id'     => $ku['id'],
                'nama'   => $ku['nama'],
                'alamat' => 'layar/kelompok-umur/' . $ku['id'],
                'terbit' => count(array_filter($nomor, static fn (array $n): bool => $n['terbit'])),
                'nomor'  => $nomor,
            ];
        }

        return ['hari' => $hari, 'klasemen' => $klasemen, 'kelompok_umur' => $kelompokUmur];
    }

    /**
     * Isi satu tampilan tetap, atau `null` bila alamatnya tidak sah (babak,
     * nomor, atau kelompok umur tidak ada; tanggal atau bagian hari salah).
     *
     * @param list<string> $argumen bagian alamat setelah jenis
     *
     * @return array<string, mixed>|null
     */
    public function tampilan(string $jenis, array $argumen): ?array
    {
        $isi = match ($jenis) {
            'seri'          => $this->tampilanSeri($argumen[0] ?? '', $argumen[1] ?? ''),
            'sesi'          => $this->tampilanSesi($argumen[0] ?? '', $argumen[1] ?? ''),
            'klasemen'      => $this->tampilanKlasemen($argumen[0] ?? ''),
            'kelompok-umur' => $this->tampilanKelompokUmur($argumen[0] ?? ''),
            'nomor'         => $this->tampilanNomor($argumen[0] ?? ''),
            default         => null,
        };

        if ($isi === null) {
            return null;
        }

        $acara = (new IsiHalamanDepan())->acara();
        $data  = [
            'acara'      => ['nama' => (string) $acara['nama'], 'seri_tahun' => (string) ($acara['seri_tahun'] ?? '')],
            'judul'      => $isi['judul'],
            'pengaturan' => ['isi' => $isi['isi'], 'tetap' => true],
            'kosong'     => $isi['kosong'],
            'start'      => $isi['start'] ?? [],
            'hasil'      => $isi['hasil'] ?? [],
            'juara_umum' => $isi['juara_umum'] ?? [],
        ];

        return ['versi' => substr(md5((string) json_encode($data)), 0, 16)] + $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tampilanSeri(string $babakId, string $nomorSeri): ?array
    {
        if (! ctype_digit($babakId) || ! ctype_digit($nomorSeri) || (int) $nomorSeri < 1) {
            return null;
        }

        $babak = $this->seri->babak((int) $babakId);

        if ($babak === null) {
            return null;
        }

        $seri  = (int) $nomorSeri;
        $nomor = $babak['nomor'];
        $sebutan = PapanVideotron::sebutanSeri();
        $judul   = $nomor['nama'] . ' · ' . $nomor['kelompok_umur'] . ' · ' . $babak['nama'] . ' · ' . $sebutan . ' ' . $seri;
        $dasar = ['judul' => $judul, 'isi' => ['hasil', 'start']];

        if ($nomor['terbit_pada'] !== null && $babak['ada_hasil']) {
            $panel = $this->papan->panelHasil($nomor, ['id' => $babak['id'], 'nama' => $babak['nama']], $this->hasil, $seri, $this->tandaLolos($babak));

            if ($panel['baris'] !== []) {
                return $dasar + ['kosong' => self::KOSONG_HASIL, 'hasil' => [$panel]];
            }
        }

        if (! $babak['dikunci']) {
            return $dasar + ['kosong' => 'Daftar start tampil di sini setelah panitia mengunci daftar seri.'];
        }

        $lintasan = $this->seri->aturanNomor($nomor)['lintasan'];
        $baris    = [];

        foreach ($this->seri->peserta($babak) as $p) {
            if ($p['seri'] === $seri) {
                $baris[] = [
                    'lintasan'   => $p['lintasan'],
                    'nama'       => $p['nama'],
                    'sekolah'    => $p['sekolah'],
                    'nomor_dada' => $p['nomor_dada'] === null ? null : (string) $p['nomor_dada'],
                ];
            }
        }

        $start = $baris === [] ? [] : [[
            'id'            => $nomor['id'],
            'kunci'         => 'start:' . $babak['id'] . ':' . $seri,
            'nomor'         => $nomor['nama'],
            'kelompok_umur' => $nomor['kelompok_umur'],
            'babak'         => $babak['nama'] . ' · ' . $sebutan . ' ' . $seri,
            'beregu'        => $nomor['jenis'] === 'beregu',
            'lintasan'      => $lintasan,
            'baris'         => $baris,
        ]];

        return $dasar + ['kosong' => $sebutan . ' ini tidak punya peserta.', 'start' => $start];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tampilanSesi(string $tanggal, string $bagian): ?array
    {
        $waktu = \DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);

        if (! isset(self::BAGIAN[$bagian]) || $waktu === false || $waktu->format('Y-m-d') !== $tanggal) {
            return null;
        }

        $panel = [];

        foreach ((new JadwalPertandingan($this->db))->perHari()[$tanggal] ?? [] as $b) {
            if (self::bagianHari($b['jam']) !== $bagian || ! $b['terbit'] || ! $b['ada_hasil']) {
                continue;
            }

            $nomor = $this->hasil->nomor($b['nomor_id']);

            if ($nomor !== null) {
                $panel[] = $this->panelBabak($nomor, ['id' => $b['id'], 'nama' => $b['babak']]);
            }
        }

        $label = $bagian === 'tanpa-jam' ? 'Sesi jam menyusul' : 'Sesi ' . mb_strtolower(self::BAGIAN[$bagian]);

        return [
            'judul'  => $label . ' · ' . JadwalPertandingan::namaHari($tanggal),
            'isi'    => ['hasil'],
            'kosong' => 'Hasil sesi ini tampil di sini setelah panitia menerbitkannya.',
            'hasil'  => $this->tanpaKosong($panel),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tampilanKlasemen(string $kunci): ?array
    {
        if (preg_match('/^[A-Za-z0-9_-]{1,40}$/', $kunci) !== 1) {
            return null;
        }

        $klasemen = array_values(array_filter($this->papan->juaraUmum(), static fn (array $k): bool => $k['kunci'] === $kunci));
        $label    = $klasemen === [] ? '' : ' · ' . $klasemen[0]['label'];

        return [
            'judul'      => 'Klasemen juara umum' . $label,
            'isi'        => ['juara_umum'],
            'kosong'     => 'Klasemen tampil di sini setelah ada hasil Final yang terbit.',
            'juara_umum' => array_values(array_filter($klasemen, static fn (array $k): bool => $k['baris'] !== [])),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tampilanKelompokUmur(string $id): ?array
    {
        if (! ctype_digit($id)) {
            return null;
        }

        $ku = $this->db->table('kelompok_umur')->select('id, nama')->where('id', (int) $id)->get()->getRowArray();

        if (! is_array($ku)) {
            return null;
        }

        $panel = [];

        foreach ($this->hasil->daftarNomor(true) as $daftar) {
            if ($daftar['id'] !== (int) $ku['id']) {
                continue;
            }

            foreach ($daftar['nomor'] as $n) {
                $nomor = $n['dilombakan'] && $n['terbit_pada'] !== null ? $this->hasil->nomor($n['id']) : null;

                if ($nomor !== null) {
                    array_push($panel, ...$this->panelNomor($nomor));
                }
            }
        }

        return [
            'judul'  => 'Hasil ' . $ku['nama'],
            'isi'    => ['hasil'],
            'kosong' => self::KOSONG_HASIL,
            'hasil'  => $this->tanpaKosong($panel),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tampilanNomor(string $id): ?array
    {
        $nomor = ctype_digit($id) ? $this->hasil->nomor((int) $id) : null;

        if ($nomor === null) {
            return null;
        }

        return [
            'judul'  => $nomor['nama'] . ' · ' . $nomor['kelompok_umur'],
            'isi'    => ['hasil'],
            'kosong' => $nomor['dilombakan'] ? self::KOSONG_HASIL : 'Nomor ini tidak dilombakan.',
            'hasil'  => $nomor['terbit_pada'] === null || ! $nomor['dilombakan'] ? [] : $this->tanpaKosong($this->panelNomor($nomor)),
        ];
    }

    /**
     * Panel semua babak satu nomor terbit, Final paling atas lalu babak yang lebih akhir.
     *
     * @param array<string, mixed> $nomor `HasilLomba::nomor()`
     *
     * @return list<array<string, mixed>>
     */
    private function panelNomor(array $nomor): array
    {
        $babak = $this->db->table('babak')->select('id, nama')->where('nomor_lomba_id', $nomor['id'])
            ->orderBy('final', 'DESC')->orderBy('urutan', 'DESC')->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return array_map(fn (array $b): array => $this->panelBabak($nomor, ['id' => (int) $b['id'], 'nama' => (string) $b['nama']]), $babak);
    }

    /**
     * Panel hasil satu babak dengan tanda lolos Q/q bila babaknya lengkap.
     *
     * @param array<string, mixed>        $nomor
     * @param array{id: int, nama: string} $babak
     *
     * @return array<string, mixed>
     */
    private function panelBabak(array $nomor, array $babak): array
    {
        $lengkap = $this->seri->babak($babak['id']);

        return $this->papan->panelHasil($nomor, $babak, $this->hasil, null, $lengkap === null ? [] : $this->tandaLolos($lengkap));
    }

    /**
     * Tanda Q/q dari babak ini ke babak berikutnya, hanya bila semua peserta
     * sudah berprestasi (sama dengan bagan publik, `SeriLintasan::bagan()`).
     *
     * @param array<string, mixed> $babak `SeriLintasan::babak()`
     *
     * @return array<string, string>
     */
    private function tandaLolos(array $babak): array
    {
        return $babak['berikut'] !== null && $this->seri->lengkap((int) $babak['id']) ? $this->seri->lolosDari($babak) : [];
    }

    /**
     * @param list<array<string, mixed>> $panel
     *
     * @return list<array<string, mixed>>
     */
    private function tanpaKosong(array $panel): array
    {
        return array_values(array_filter($panel, static fn (array $p): bool => $p['baris'] !== []));
    }

    /**
     * Nomor seri tiap babak yang pesertanya sudah dibagi.
     *
     * @return array<int, list<int>> id babak => nomor seri urut
     */
    private function seriDikunci(): array
    {
        $seri = [];

        foreach ($this->db->table('hasil')->select('babak_id, seri')->where('seri IS NOT NULL', null, false)
            ->groupBy('babak_id, seri')->orderBy('babak_id')->orderBy('seri')->get()->getResultArray() as $h) {
            $seri[(int) $h['babak_id']][] = (int) $h['seri'];
        }

        return $seri;
    }
}
