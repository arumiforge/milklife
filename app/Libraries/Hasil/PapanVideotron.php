<?php

namespace App\Libraries\Hasil;

use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Data papan videotron `/layar` (F9-13, K141). Hanya data yang sudah terbit:
 * hasil nomor yang `hasil_terbit_pada`-nya terisi dan klasemen dari nomor
 * terbit. Jadwal dan status babak dari `JadwalPertandingan` (F9-07), sama
 * dengan halaman `/jadwal`.
 *
 * Isi yang dikirim (JSON `/layar/data`, dibaca `public/aset/js/layar.js`):
 *
 *     $data = (new PapanVideotron())->data();
 *     // ['versi' => 'a1b2…', 'acara' => ['nama' => …, 'seri_tahun' => …],
 *     //  'pengaturan' => ['isi' => ['hasil', 'juara_umum', …], 'lama' => 12],
 *     //  'hasil' => [['id' => 10, 'nomor' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'babak' => 'Final',
 *     //               'satuan' => '', 'baris' => [['peringkat' => 1, 'nama' => …, 'sekolah' => …, 'nomor_dada' => …, 'prestasi' => '9,87', 'keterangan' => null], …]], …],
 *     //  'juara_umum' => [['jenjang' => 'A', 'label' => 'SD/MI', 'baris' => [['peringkat' => 1, 'sekolah' => …, 'emas' => 3, …], …]], …],
 *     //  'jadwal' => [['nomor' => …, 'kelompok_umur' => …, 'babak' => …, 'hari' => 'Rabu, 9 Desember', 'jam' => '08.30', 'lokasi' => …], …],
 *     //  'berlangsung' => [… sama dengan jadwal …]]
 *
 * Klasemen memakai `HasilLomba::klasemen(true)`, jadi mengikuti metode di
 * Pengaturan > Hasil (F9-11); kolom `poin` ikut terkirim.
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
        'juara_umum'  => 'Juara umum per jenjang',
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
     * Peringkat klasemen paling bawah yang tampil per jenjang.
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

            $baris = [];

            foreach ($hasil->hasilBabak((int) $babak['id'], $nomor) as $h) {
                if ($h['peringkat'] === null && $h['prestasi'] === null && $h['keterangan'] === null) {
                    continue;
                }

                $baris[] = [
                    'peringkat'  => $h['peringkat'],
                    'nama'       => $h['nama'],
                    'sekolah'    => $h['sekolah'],
                    'nomor_dada' => $h['nomor_dada'] === null ? null : (string) $h['nomor_dada'],
                    'prestasi'   => $h['prestasi_teks'],
                    'keterangan' => $h['keterangan'],
                ];
            }

            $daftar[] = [
                'id'            => $nomor['id'],
                'nomor'         => $nomor['nama'],
                'kelompok_umur' => $nomor['kelompok_umur'],
                'babak'         => (string) $babak['nama'],
                'beregu'        => $nomor['jenis'] === 'beregu',
                'satuan'        => match ($nomor['jenis_hasil']) {
                    JenisHasil::JARAK => 'm',
                    JenisHasil::POIN, JenisHasil::MULTI => 'poin',
                    default => '',
                },
                'baris' => $baris,
            ];
        }

        return $daftar;
    }

    /**
     * Klasemen nomor terbit per jenjang, sampai peringkat `BATAS_KLASEMEN`,
     * tanpa NPSN dan rincian piala.
     *
     * @return list<array{jenjang: string, label: string, baris: list<array<string, mixed>>}>
     */
    private function juaraUmum(): array
    {
        $daftar = [];

        // Metode dan poin dari Pengaturan > Hasil (F9-11), sama dengan `hasil/klasemen`.
        foreach ((new HasilLomba($this->db))->klasemen(true) as $jenjang) {
            $baris = [];

            foreach ($jenjang['baris'] as $b) {
                if ((int) $b['peringkat'] > self::BATAS_KLASEMEN) {
                    continue;
                }

                unset($b['npsn'], $b['piala']);
                $baris[] = array_filter($b, static fn (mixed $nilai): bool => is_scalar($nilai) || $nilai === null);
            }

            $daftar[] = ['jenjang' => (string) $jenjang['jenjang'], 'label' => (string) $jenjang['label'], 'baris' => $baris];
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
