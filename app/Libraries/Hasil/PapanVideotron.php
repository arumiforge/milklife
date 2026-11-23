<?php

namespace App\Libraries\Hasil;

use App\Libraries\IsiHalamanDepan;
use App\Libraries\Unggahan;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Data papan putar videotron `/layar/putar` (F9-13, K141; sejak F11-09 `/layar`
 * berisi daftar tampilan tetap, `TampilanVideotron`). Hanya data yang sudah terbit:
 * hasil nomor yang `hasil_terbit_pada`-nya terisi dan klasemen dari nomor
 * terbit. Jadwal dan status babak dari `JadwalPertandingan` (F9-07), sama
 * dengan halaman `/jadwal`.
 *
 * `panelHasil()` dan `juaraUmum()` juga dipakai tampilan tetap.
 *
 * Isi yang dikirim (JSON `/layar/data`, dibaca `public/aset/js/layar.js`):
 *
 *     $data = (new PapanVideotron())->data();
 *     // ['versi' => 'a1b2…', 'acara' => ['nama' => …, 'seri_tahun' => …],
 *     //  'pengaturan' => ['isi' => ['hasil', 'juara_umum', …], 'lama' => 12],
 *     //  'hasil' => [['id' => 10, 'nomor' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'babak' => 'Final',
 *     //               'satuan' => '', 'baris' => [['peringkat' => 1, 'nama' => …, 'sekolah' => …, 'nomor_dada' => …, 'prestasi' => '9,87', 'keterangan' => null], …]], …],
 *     //  'juara_umum' => [['kunci' => 'ku-1-2', 'label' => 'KU 8 dan KU 10', 'metode' => 'medali', 'baris' => [['peringkat' => 1, 'sekolah' => …, 'emas' => 3, …], …]], …],
 *     //  'jadwal' => [['nomor' => …, 'kelompok_umur' => …, 'babak' => …, 'hari' => 'Rabu, 9 Desember', 'jam' => '08.30', 'lokasi' => …], …],
 *     //  'berlangsung' => [… sama dengan jadwal …]]
 *
 * Klasemen memakai `HasilLomba::klasemen(true)`, jadi mengikuti metode di
 * Pengaturan > Hasil (F9-11); kolom `poin` ikut terkirim, dan `metode`
 * (`medali` atau `poin`) supaya layar sempit tahu kolom mana yang dipertahankan.
 *
 * "Sedang berlangsung": babak hari ini yang berstatus Berlangsung menurut
 * `JadwalPertandingan::status()` (jam mulai sudah lewat, belum Selesai).
 */
final class PapanVideotron
{
    /**
     * Isi yang bisa diputar, kode => label di Pengaturan > Videotron.
     *
     * @var array<string, string>
     */
    public const ISI = [
        'hasil'       => 'Hasil nomor yang baru terbit',
        'juara_umum'  => 'Juara umum per kelompok umur',
        'berlangsung' => 'Sedang berlangsung',
        'jadwal'      => 'Jadwal berikutnya',
    ];

    public const LAMA_MIN = 5;

    public const LAMA_MAKS = 120;

    public const LAMA_BAWAAN = 12;

    /**
     * Nomor terbit terbaru yang hasilnya diputar.
     */
    public const JUMLAH_HASIL = 6;

    /**
     * Peringkat klasemen paling bawah yang tampil per klasemen.
     */
    public const BATAS_KLASEMEN = 10;

    /**
     * Babak berikutnya paling banyak di tampilan Jadwal.
     */
    public const JUMLAH_JADWAL = 24;

    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private BaseConnection $db;

    private Time $sekarang;

    public function __construct(?BaseConnection $db = null, ?Time $sekarang = null)
    {
        $this->db       = $db ?? db_connect();
        $this->sekarang = $sekarang ?? Time::now(config('App')->appTimezone);
    }

    /**
     * Sakelar tampil dari Pengaturan > Videotron (bawaan mati).
     */
    public static function tampil(): bool
    {
        return (bool) setting('Videotron.tampil');
    }

    /**
     * Jalur gambar latar unggahan admin (K173), atau `null` bila kosong atau
     * berkasnya tidak ada lagi.
     */
    public static function latar(): ?string
    {
        $jalur = (string) setting('Videotron.latar');

        return $jalur !== '' && (new Unggahan())->ada($jalur) ? $jalur : null;
    }

    /**
     * Isi dan lama tampilan yang sudah dirapikan.
     *
     * @return array{isi: list<string>, lama: int}
     */
    public static function pengaturan(): array
    {
        return [
            'isi'  => self::rapikanIsi(setting('Videotron.isi')),
            'lama' => self::rapikanLama(setting('Videotron.lama')),
        ];
    }

    /**
     * Kode isi yang sah, urut seperti masukan, tanpa ganda.
     *
     * @param mixed $isi teks dipisah koma atau daftar kode
     *
     * @return list<string>
     */
    public static function rapikanIsi(mixed $isi): array
    {
        $daftar = is_array($isi) ? $isi : explode(',', is_string($isi) ? $isi : '');
        $hasil  = [];

        foreach ($daftar as $kode) {
            $kode = is_string($kode) ? trim($kode) : '';

            if (isset(self::ISI[$kode]) && ! in_array($kode, $hasil, true)) {
                $hasil[] = $kode;
            }
        }

        return $hasil;
    }

    public static function rapikanLama(mixed $lama): int
    {
        if (! is_numeric($lama)) {
            return self::LAMA_BAWAAN;
        }

        return max(self::LAMA_MIN, min(self::LAMA_MAKS, (int) $lama));
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $acara  = (new IsiHalamanDepan())->acara();
        $jadwal = $this->jadwal();

        $data = [
            'acara'       => ['nama' => (string) $acara['nama'], 'seri_tahun' => (string) ($acara['seri_tahun'] ?? '')],
            'pengaturan'  => self::pengaturan(),
            'hasil'       => $this->hasilTerbaru(),
            'juara_umum'  => $this->juaraUmum(),
            'jadwal'      => $jadwal['berikutnya'],
            'berlangsung' => $jadwal['berlangsung'],
        ];

        return ['versi' => substr(md5((string) json_encode($data)), 0, 16)] + $data;
    }

    /**
     * Babak Final (atau babak terakhir) nomor yang paling baru terbit.
     *
     * @return list<array<string, mixed>>
     */
    private function hasilTerbaru(): array
    {
        $hasil  = new HasilLomba($this->db);
        $daftar = [];

        $terbit = $this->db->table('nomor_lomba')->select('id')
            ->where('hasil_terbit_pada IS NOT NULL', null, false)
            ->where('tidak_dilombakan', 0) // F11-03
            ->orderBy('hasil_terbit_pada', 'DESC')->orderBy('id', 'DESC')
            ->limit(self::JUMLAH_HASIL)->get()->getResultArray();

        foreach ($terbit as $t) {
            $nomor = $hasil->nomor((int) $t['id']);

            if ($nomor === null) {
                continue;
            }

            $babak = $this->db->table('babak')->where('nomor_lomba_id', $nomor['id'])
                ->orderBy('final', 'DESC')->orderBy('urutan', 'DESC')->orderBy('id', 'DESC')
                ->limit(1)->get()->getRowArray();

            if (! is_array($babak)) {
                continue;
            }

            $daftar[] = $this->panelHasil($nomor, ['id' => (int) $babak['id'], 'nama' => (string) $babak['nama']], $hasil);
        }

        return $daftar;
    }

    /**
     * Satu panel hasil babak untuk videotron: peringkat, nama, sekolah, nomor
     * atlet, dan prestasi seperti di halaman hasil (`HasilLomba::hasilBabak()`),
     * tanpa NISN dan NPSN. Dipakai papan putar dan tampilan tetap
     * (`TampilanVideotron`, F11-09).
     *
     * - `$seri`: hanya peserta seri itu, dengan peringkat di dalam seri.
     * - `$tanda`: tanda lolos per kunci peserta (`SeriLintasan::lolosDari()`).
     *
     * @param array<string, mixed>  $nomor `HasilLomba::nomor()`
     * @param array{id: int, nama: string} $babak
     * @param array<string, string> $tanda
     *
     * @return array{id: int, kunci: string, nomor: string, kelompok_umur: string, babak: string, beregu: bool, satuan: string, baris: list<array<string, mixed>>}
     */
    public function panelHasil(array $nomor, array $babak, ?HasilLomba $hasil = null, ?int $seri = null, array $tanda = []): array
    {
        $baris = [];

        foreach (($hasil ?? new HasilLomba($this->db))->hasilBabak($babak['id'], $nomor) as $h) {
            if ($h['peringkat'] === null && $h['prestasi'] === null && $h['keterangan'] === null) {
                continue;
            }

            if ($seri !== null && $h['seri'] !== $seri) {
                continue;
            }

            $baris[] = [
                'peringkat'  => $h['peringkat'],
                'nama'       => $h['nama'],
                'sekolah'    => $h['sekolah'],
                'nomor_dada' => $h['nomor_dada'] === null ? null : (string) $h['nomor_dada'],
                'prestasi'   => $h['prestasi_teks'],
                'keterangan' => $h['keterangan'],
                'tanda'      => $tanda[$h['kunci']] ?? null,
                // Rekor kejuaraan (F11-06): tanda CR.
                'rekor'      => $h['rekor'] ?? false,
            ];
        }

        if ($seri !== null) {
            // Peringkat di dalam seri: urutan peringkat babak, yang sama tetap sama.
            $peringkat = array_values(array_filter(array_column($baris, 'peringkat'), static fn (mixed $p): bool => $p !== null));

            foreach ($baris as $i => $b) {
                if ($b['peringkat'] !== null) {
                    $baris[$i]['peringkat'] = 1 + count(array_filter($peringkat, static fn (int $p): bool => $p < $b['peringkat']));
                }
            }
        }

        return [
            'id'            => (int) $nomor['id'],
            'kunci'         => 'babak:' . $babak['id'] . ($seri === null ? '' : ':' . $seri),
            'nomor'         => (string) $nomor['nama'],
            'kelompok_umur' => (string) $nomor['kelompok_umur'],
            'babak'         => $babak['nama'] . ($seri === null ? '' : ' · Seri ' . $seri),
            'beregu'        => $nomor['jenis'] === 'beregu',
            'satuan'        => self::satuan((string) $nomor['jenis_hasil']),
            'baris'         => $baris,
        ];
    }

    /**
     * Satuan prestasi di judul kolom: `m` (jarak), `poin` (poin, multi), atau kosong (waktu).
     */
    public static function satuan(string $jenisHasil): string
    {
        return match ($jenisHasil) {
            JenisHasil::JARAK => 'm',
            JenisHasil::POIN, JenisHasil::MULTI => 'poin',
            default => '',
        };
    }

    /**
     * Klasemen nomor terbit per kelompok umur (F11-02), sampai peringkat `BATAS_KLASEMEN`,
     * tanpa NPSN dan rincian piala. `kunci` juga dipakai alamat tampilan tetap
     * `layar/klasemen/<kunci>` (F11-09).
     *
     * @return list<array{kunci: string, label: string, metode: string, baris: list<array<string, mixed>>}>
     */
    public function juaraUmum(): array
    {
        $daftar = [];
        $metode = MetodeKlasemen::metode();

        // Metode dan poin dari Pengaturan > Hasil (F9-11), sama dengan `hasil/klasemen`.
        foreach ((new HasilLomba($this->db))->klasemen(true) as $klasemen) {
            $baris = [];

            foreach ($klasemen['baris'] as $b) {
                if ((int) $b['peringkat'] > self::BATAS_KLASEMEN) {
                    continue;
                }

                unset($b['npsn'], $b['piala']);
                $baris[] = array_filter($b, static fn (mixed $nilai): bool => is_scalar($nilai) || $nilai === null);
            }

            $daftar[] = ['kunci' => (string) $klasemen['kunci'], 'label' => (string) $klasemen['label'], 'metode' => $metode, 'baris' => $baris];
        }

        return $daftar;
    }

    /**
     * Jadwal dan status babak dari `JadwalPertandingan` (F9-07), sama dengan
     * halaman `/jadwal`: "Sedang berlangsung" = babak hari ini berstatus
     * berlangsung; "Jadwal berikutnya" = babak hari ini atau sesudahnya yang
     * belum mulai.
     *
     * @return array{berikutnya: list<array<string, mixed>>, berlangsung: list<array<string, mixed>>}
     */
    private function jadwal(): array
    {
        $hariIni     = $this->sekarang->format('Y-m-d');
        $berikutnya  = [];
        $berlangsung = [];

        foreach ((new JadwalPertandingan($this->db))->semua() as $b) {
            if ($b['tanggal'] === null || $b['tanggal'] < $hariIni) {
                continue;
            }

            $status = JadwalPertandingan::status($b, $this->sekarang);
            $butir  = [
                'id'            => $b['id'],
                'nomor'         => $b['nomor_lengkap'],
                'kelompok_umur' => $b['kelompok_umur'],
                'babak'         => $b['babak'],
                'hari'          => $this->labelHari($b['tanggal']),
                'jam'           => $b['jam'] === null ? '' : str_replace(':', '.', $b['jam']),
                'lokasi'        => $b['lokasi'],
            ];

            if ($status === 'berlangsung' && $b['tanggal'] === $hariIni) {
                if (count($berlangsung) < self::JUMLAH_JADWAL) {
                    $berlangsung[] = $butir;
                }
            } elseif ($status === 'belum' && count($berikutnya) < self::JUMLAH_JADWAL) {
                $berikutnya[] = $butir;
            }
        }

        return ['berikutnya' => $berikutnya, 'berlangsung' => $berlangsung];
    }

    private function labelHari(string $tanggal): string
    {
        if ($tanggal === $this->sekarang->format('Y-m-d')) {
            return 'Hari ini';
        }

        if ($tanggal === $this->sekarang->addDays(1)->format('Y-m-d')) {
            return 'Besok';
        }

        return self::HARI[(int) date('w', (int) strtotime($tanggal))] . ', ' . preg_replace('/\s\d{4}$/', '', tanggal_id($tanggal));
    }
}
