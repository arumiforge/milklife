<?php

namespace App\Libraries\Hasil;

use App\Libraries\IsiHalamanDepan;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Data papan videotron `/layar` (F9-13, K141). Hanya data yang sudah terbit:
 * hasil nomor yang `hasil_terbit_pada`-nya terisi dan klasemen dari nomor
 * terbit. Jadwal berasal dari kolom babak `tanggal`, `jam_mulai`, `lokasi`
 * (F9-07); selama kolom itu belum ada, tampilan Jadwal dan Sedang
 * berlangsung disembunyikan (`null` di data).
 *
 * Isi yang dikirim (JSON `/layar/data`, dibaca `public/aset/js/layar.js`):
 *
 *     $data = (new PapanVideotron())->data();
 *     // ['versi' => 'a1b2…', 'acara' => ['nama' => …, 'seri_tahun' => …],
 *     //  'pengaturan' => ['isi' => ['hasil', 'juara_umum', …], 'lama' => 12],
 *     //  'hasil' => [['id' => 10, 'nomor' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'babak' => 'Final',
 *     //               'satuan' => '', 'baris' => [['peringkat' => 1, 'nama' => …, 'sekolah' => …, 'nomor_dada' => …, 'prestasi' => '9,87', 'keterangan' => null], …]], …],
 *     //  'juara_umum' => [['jenjang' => 'A', 'label' => 'SD/MI', 'baris' => [['peringkat' => 1, 'sekolah' => …, 'emas' => 3, …], …]], …],
 *     //  'jadwal' => [['nomor' => …, 'kelompok_umur' => …, 'babak' => …, 'hari' => 'Rabu, 9 Desember', 'jam' => '08.30', 'lokasi' => …], …] | null,
 *     //  'berlangsung' => [… sama dengan jadwal …] | null]
 *
 * Klasemen memakai `Klasemen::hitung()` apa adanya; kolom tambahan yang
 * dihasilkannya kelak (misalnya poin, F9-11) ikut terkirim tanpa diubah di sini.
 *
 * "Sedang berlangsung": babak hari ini yang jam mulainya sudah lewat (paling
 * lama `BATAS_BERLANGSUNG_MENIT`), nomornya belum terbit, dan merupakan babak
 * yang paling akhir dimulai di lokasinya (babak yang sudah disusul babak lain
 * di lokasi yang sama dianggap selesai).
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

    public const BATAS_BERLANGSUNG_MENIT = 180;

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
     * Kolom jadwal babak (F9-07) sudah ada?
     */
    public function adaJadwal(): bool
    {
        $kolom = $this->kolomBabak();

        return in_array('tanggal', $kolom, true) && in_array('jam_mulai', $kolom, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $acara  = (new IsiHalamanDepan())->acara();
        $jadwal = $this->adaJadwal() ? $this->jadwal() : null;

        $data = [
            'acara'       => ['nama' => (string) $acara['nama'], 'seri_tahun' => (string) ($acara['seri_tahun'] ?? '')],
            'pengaturan'  => self::pengaturan(),
            'hasil'       => $this->hasilTerbaru(),
            'juara_umum'  => $this->juaraUmum(),
            'jadwal'      => $jadwal['berikutnya'] ?? null,
            'berlangsung' => $jadwal['berlangsung'] ?? null,
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

        foreach (Klasemen::hitung((new HasilLomba($this->db))->juara(true)) as $jenjang) {
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
     * @return array{berikutnya: list<array<string, mixed>>, berlangsung: list<array<string, mixed>>}
     */
    private function jadwal(): array
    {
        $kolom     = $this->kolomBabak();
        $adaLokasi = in_array('lokasi', $kolom, true);
        $hariIni   = $this->sekarang->format('Y-m-d');
        $detikIni  = $this->sekarang->getTimestamp();

        $baris = $this->db->table('babak b')
            ->select('b.id, b.nama AS babak, b.tanggal, b.jam_mulai, ' . ($adaLokasi ? 'b.lokasi' : 'NULL AS lokasi') . ', nl.nama, nl.kategori, nl.hasil_terbit_pada, ku.nama AS kelompok_umur', false)
            ->join('nomor_lomba nl', 'nl.id = b.nomor_lomba_id')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->where('b.tanggal IS NOT NULL', null, false)
            ->where('b.jam_mulai IS NOT NULL', null, false)
            ->where('b.tanggal >=', $hariIni)
            ->orderBy('b.tanggal')->orderBy('b.jam_mulai')->orderBy('b.id')
            ->get()->getResultArray();

        $berikutnya = [];
        $mulai      = [];

        foreach ($baris as $b) {
            $tanggal = substr((string) $b['tanggal'], 0, 10);
            $jam     = substr((string) $b['jam_mulai'], 0, 5);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1 || preg_match('/^\d{1,2}:\d{2}$/', $jam) !== 1) {
                continue;
            }

            $waktu = Time::createFromFormat('Y-m-d H:i', $tanggal . ' ' . $jam, $this->sekarang->getTimezone());
            $butir = [
                'id'            => (int) $b['id'],
                'nomor'         => NomorLombaModel::namaLengkap($b),
                'kelompok_umur' => (string) $b['kelompok_umur'],
                'babak'         => (string) $b['babak'],
                'hari'          => $this->labelHari($waktu),
                'jam'           => $waktu->format('H.i'),
                'lokasi'        => trim((string) ($b['lokasi'] ?? '')),
            ];

            if ($waktu->getTimestamp() > $detikIni) {
                if (count($berikutnya) < self::JUMLAH_JADWAL) {
                    $berikutnya[] = $butir;
                }

                continue;
            }

            // Sudah dimulai hari ini: calon "sedang berlangsung".
            if ($tanggal === $hariIni && $b['hasil_terbit_pada'] === null && $detikIni - $waktu->getTimestamp() <= self::BATAS_BERLANGSUNG_MENIT * 60) {
                $mulai[mb_strtolower($butir['lokasi'])][] = $butir + ['detik' => $waktu->getTimestamp()];
            }
        }

        $berlangsung = [];

        foreach ($mulai as $diLokasi) {
            $terakhir = max(array_column($diLokasi, 'detik'));

            foreach ($diLokasi as $butir) {
                if ($butir['detik'] === $terakhir) {
                    unset($butir['detik']);
                    $berlangsung[] = $butir;
                }
            }
        }

        usort($berlangsung, static fn (array $a, array $b): int => [$a['jam'], $a['id']] <=> [$b['jam'], $b['id']]);

        return ['berikutnya' => $berikutnya, 'berlangsung' => $berlangsung];
    }

    /**
     * Nama kolom tabel `babak`, dibaca langsung (tanpa tembolok nama kolom
     * sambungan, supaya kolom yang baru ditambah migrasi langsung terlihat).
     *
     * @return list<string>
     */
    private function kolomBabak(): array
    {
        return array_values(array_map(static fn (object $k): string => (string) $k->name, $this->db->getFieldData('babak')));
    }

    private function labelHari(Time $waktu): string
    {
        $tanggal = $waktu->format('Y-m-d');

        if ($tanggal === $this->sekarang->format('Y-m-d')) {
            return 'Hari ini';
        }

        if ($tanggal === $this->sekarang->addDays(1)->format('Y-m-d')) {
            return 'Besok';
        }

        $teks = tanggal_id($tanggal);

        return self::HARI[(int) $waktu->format('w')] . ', ' . preg_replace('/\s\d{4}$/', '', $teks);
    }
}
