<?php

namespace App\Libraries\Hasil;

use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;
use Throwable;

/**
 * Jadwal pertandingan per babak (F9-07, K140 butir 9).
 *
 * Satu baris jadwal = satu babak di panel hasil (`babak`), jadi jadwal selalu
 * sama dengan babak yang diisi juri. Babak diberi `tanggal`, `jam_mulai`
 * (WIB), dan `lokasi`; babak tanpa tanggal belum dijadwalkan dan tidak tampil
 * di halaman publik.
 *
 * Status otomatis (`status()`, murni):
 * - **Selesai**: hasil nomornya sudah terbit dan babak itu punya prestasi
 *   atau keterangan (DNS/DNF/DQ).
 * - **Berlangsung**: belum selesai dan waktu mulainya sudah lewat.
 * - **Belum mulai**: selain itu.
 *
 * Dipakai halaman publik `jadwal`, unduhan PDF per hari, halaman admin
 * Jadwal pertandingan, dan isian jadwal di halaman babak. F9-08 (seri dan
 * lintasan) memakai `babak()` dan `semua()` yang sama.
 *
 *     $jadwal = new JadwalPertandingan();
 *     $hari   = $jadwal->perHari();                     // ['2026-12-05' => [babak, …], …]
 *     JadwalPertandingan::status($babak, new DateTimeImmutable());   // 'berlangsung'
 *     $jadwal->simpan(12, ['tanggal' => '05/12/2026', 'jam_mulai' => '07.30', 'lokasi' => 'Lintasan utama']);
 */
final class JadwalPertandingan
{
    public const LOKASI_MAKSIMAL = 100;

    /**
     * Kode status => label.
     *
     * @var array<string, string>
     */
    public const STATUS = [
        'belum'       => 'Belum mulai',
        'berlangsung' => 'Berlangsung',
        'selesai'     => 'Selesai',
    ];

    private const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Semua babak beserta jadwalnya. Urut: yang terjadwal menurut tanggal dan
     * jam (jam kosong di akhir harinya), lalu yang belum dijadwalkan; dalam
     * waktu yang sama menurut kelompok umur, nomor lomba, lalu babak (Final
     * paling akhir).
     *
     * @return list<array{id: int, nomor_id: int, nomor: string, nomor_lengkap: string, kategori: string, jenis_kelamin: string, kelompok_umur_id: int, kelompok_umur: string, babak: string, final: bool, tanggal: string|null, jam: string|null, lokasi: string, terbit: bool, ada_hasil: bool, seri_dikunci: bool}>
     */
    public function semua(): array
    {
        $adaHasil = array_flip(array_map('intval', array_column($this->db->table('hasil')
            ->select('babak_id')
            ->groupStart()->where('prestasi IS NOT NULL', null, false)->orWhere('keterangan IS NOT NULL', null, false)->groupEnd()
            ->groupBy('babak_id')
            ->get()->getResultArray(), 'babak_id')));

        $baris = $this->db->table('babak b')
            ->select('b.id, b.nama AS babak, b.final, b.tanggal, b.jam_mulai, b.lokasi, b.seri_dikunci_pada, nl.id AS nomor_id, nl.nama, nl.kategori, nl.hasil_terbit_pada, ku.id AS kelompok_umur_id, ku.nama AS kelompok_umur')
            ->join('nomor_lomba nl', 'nl.id = b.nomor_lomba_id')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            // Nomor yang tidak dilombakan keluar dari jadwal, lembar juri, dan videotron (F11-03).
            ->where('nl.tidak_dilombakan', 0)
            ->orderBy('b.tanggal IS NULL', '', false)->orderBy('b.tanggal')
            ->orderBy('b.jam_mulai IS NULL', '', false)->orderBy('b.jam_mulai')
            ->orderBy('ku.urutan')->orderBy('ku.id')
            ->orderBy('nl.urutan')->orderBy('nl.id')
            ->orderBy('b.final')->orderBy('b.urutan')->orderBy('b.id')
            ->get()->getResultArray();

        return array_map(static fn (array $b): array => [
            'id'               => (int) $b['id'],
            'nomor_id'         => (int) $b['nomor_id'],
            'nomor'            => (string) $b['nama'],
            'nomor_lengkap'    => NomorLombaModel::namaLengkap($b),
            'kategori'         => (string) $b['kategori'],
            'jenis_kelamin'    => NomorLombaModel::KATEGORI[$b['kategori']] ?? '',
            'kelompok_umur_id' => (int) $b['kelompok_umur_id'],
            'kelompok_umur'    => (string) $b['kelompok_umur'],
            'babak'            => (string) $b['babak'],
            'final'            => (int) $b['final'] === 1,
            'tanggal'          => $b['tanggal'] === null ? null : (string) $b['tanggal'],
            'jam'              => $b['jam_mulai'] === null ? null : substr((string) $b['jam_mulai'], 0, 5),
            'lokasi'           => (string) ($b['lokasi'] ?? ''),
            'terbit'           => $b['hasil_terbit_pada'] !== null,
            'ada_hasil'        => isset($adaHasil[(int) $b['id']]),
            // Daftar seri sudah dikunci (F9-08): tautan Daftar seri di halaman publik.
            'seri_dikunci'     => $b['seri_dikunci_pada'] !== null,
        ], $baris);
    }

    /**
     * Babak terjadwal dikelompokkan per tanggal, urut tanggal.
     *
     * @return array<string, list<array<string, mixed>>> 'Y-m-d' => babak (`semua()`)
     */
    public function perHari(): array
    {
        $hari = [];

        foreach ($this->semua() as $b) {
            if ($b['tanggal'] !== null) {
                $hari[$b['tanggal']][] = $b;
            }
        }

        return $hari;
    }

    /**
     * Jadwal satu babak, atau `null` bila babak tidak ada.
     *
     * @return array{tanggal: string|null, jam: string|null, lokasi: string}|null
     */
    public function babak(int $id): ?array
    {
        $b = $this->db->table('babak')->select('tanggal, jam_mulai, lokasi')->where('id', $id)->get()->getRowArray();

        return is_array($b) ? [
            'tanggal' => $b['tanggal'] === null ? null : (string) $b['tanggal'],
            'jam'     => $b['jam_mulai'] === null ? null : substr((string) $b['jam_mulai'], 0, 5),
            'lokasi'  => (string) ($b['lokasi'] ?? ''),
        ] : null;
    }

    /**
     * Status otomatis satu babak pada waktu `$kini` (WIB).
     *
     * @param array{tanggal: string|null, jam: string|null, terbit: bool, ada_hasil: bool} $babak
     *
     * @return 'belum'|'berlangsung'|'selesai'
     */
    public static function status(array $babak, DateTimeInterface $kini): string
    {
        if ($babak['terbit'] && $babak['ada_hasil']) {
            return 'selesai';
        }

        if ($babak['tanggal'] === null) {
            return 'belum';
        }

        $mulai = $babak['tanggal'] . ' ' . ($babak['jam'] ?? '00:00');

        return $kini->format('Y-m-d H:i') >= $mulai ? 'berlangsung' : 'belum';
    }

    /**
     * Hari yang dibuka: yang diminta bila ada di jadwal, selain itu hari ini,
     * lalu hari terdekat berikutnya, lalu hari terakhir. `null` bila jadwal kosong.
     *
     * @param list<string> $hari 'Y-m-d' urut
     */
    public static function pilihHari(array $hari, ?string $diminta, DateTimeInterface $kini): ?string
    {
        if ($hari === []) {
            return null;
        }

        if ($diminta !== null && in_array($diminta, $hari, true)) {
            return $diminta;
        }

        $hariIni = $kini->format('Y-m-d');

        foreach ($hari as $h) {
            if ($h >= $hariIni) {
                return $h;
            }
        }

        return $hari[count($hari) - 1];
    }

    /**
     * "Rabu, 15 Juli 2026" dari 'Y-m-d'.
     */
    public static function namaHari(string $tanggal): string
    {
        $waktu = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);

        return $waktu === false ? $tanggal : self::NAMA_HARI[(int) $waktu->format('w')] . ', ' . tanggal_id($waktu);
    }

    /**
     * Atribut `min` dan `max` isian tanggal jadwal (`<input type="date">`,
     * ditampilkan `pilih-tanggal.js` sebagai pilihan Tanggal, Bulan, Tahun,
     * K89): setahun ke belakang sampai dua tahun ke depan dari `$kini`.
     */
    public static function rentangIsian(?DateTimeInterface $kini = null): string
    {
        $tahun = (int) ($kini ?? new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y');

        return ' min="' . ($tahun - 1) . '-01-01" max="' . ($tahun + 2) . '-12-31"';
    }

    /**
     * Periksa isian jadwal satu babak (murni). Tanggal boleh `05/12/2026`,
     * `5-12-2026`, atau `2026-12-05`; jam `07:30` atau `7.30`. Semua kosong =
     * belum dijadwalkan.
     *
     * @param array<string, mixed> $isian ['tanggal' => …, 'jam_mulai' => …, 'lokasi' => …]
     *
     * @return array{data: array{tanggal: string|null, jam_mulai: string|null, lokasi: string|null}, galat: array<string, string>}
     */
    public static function periksa(array $isian): array
    {
        $teks    = static fn (string $kunci): string => is_string($isian[$kunci] ?? null) ? trim($isian[$kunci]) : '';
        $tanggal = $teks('tanggal');
        $jam     = $teks('jam_mulai');
        $lokasi  = trim((string) preg_replace('/\s+/u', ' ', $teks('lokasi')));
        $galat   = [];
        $data    = ['tanggal' => null, 'jam_mulai' => null, 'lokasi' => $lokasi === '' ? null : $lokasi];

        if ($tanggal !== '') {
            $data['tanggal'] = self::uraiTanggal($tanggal);

            if ($data['tanggal'] === null) {
                $galat['tanggal'] = 'Tanggal "' . $tanggal . '" belum benar. Pilih tanggal, bulan, dan tahunnya lagi.';
            }
        }

        if ($jam !== '') {
            if (preg_match('/^([01]?\d|2[0-3])[:.]([0-5]\d)(?:[:.]00)?$/D', $jam, $c) === 1) {
                $data['jam_mulai'] = sprintf('%02d:%s:00', (int) $c[1], $c[2]);
            } else {
                $galat['jam_mulai'] = 'Jam "' . $jam . '" belum benar. Tulis seperti 07:30.';
            }

            if ($tanggal === '') {
                $galat['tanggal'] = 'Isi tanggalnya juga, atau kosongkan jam mulai.';
            }
        }

        if (mb_strlen($lokasi) > self::LOKASI_MAKSIMAL) {
            $galat['lokasi'] = 'Lokasi terlalu panjang. Persingkat menjadi paling banyak ' . self::LOKASI_MAKSIMAL . ' huruf.';
        }

        return ['data' => $data, 'galat' => $galat];
    }

    /**
     * Simpan jadwal satu babak.
     *
     * @param array<string, mixed> $isian
     *
     * @return array<string, string> galat; kosong bila tersimpan
     */
    public function simpan(int $babakId, array $isian): array
    {
        $galat = $this->simpanBanyak([$babakId => $isian]);

        return $galat[$babakId] ?? [];
    }

    /**
     * Simpan jadwal banyak babak sekaligus (halaman Jadwal pertandingan).
     * Bila ada isian yang salah tidak ada yang tersimpan. Babak yang tidak ada
     * diabaikan.
     *
     * @param array<int|string, mixed> $isian id babak => ['tanggal' => …, 'jam_mulai' => …, 'lokasi' => …]
     *
     * @return array<int, array<string, string>> id babak => galat per kotak; kosong bila tersimpan
     *
     * @throws RuntimeException bila basis data gagal menyimpan; tidak ada yang berubah
     */
    public function simpanBanyak(array $isian): array
    {
        $id = array_values(array_filter(array_map('intval', array_keys($isian)), static fn (int $i): bool => $i > 0));

        if ($id === []) {
            return [];
        }

        $ada   = [];
        $galat = [];
        $ubah  = [];

        foreach ($this->db->table('babak')->select('id, tanggal, jam_mulai, lokasi')->whereIn('id', $id)->get()->getResultArray() as $b) {
            $ada[(int) $b['id']] = $b;
        }

        foreach ($isian as $babakId => $baris) {
            $babakId = (int) $babakId;

            if (! isset($ada[$babakId]) || ! is_array($baris)) {
                continue;
            }

            $periksa = self::periksa($baris);

            if ($periksa['galat'] !== []) {
                $galat[$babakId] = $periksa['galat'];

                continue;
            }

            $lama = $ada[$babakId];

            if ($periksa['data'] !== ['tanggal' => $lama['tanggal'], 'jam_mulai' => $lama['jam_mulai'], 'lokasi' => $lama['lokasi']]) {
                $ubah[$babakId] = $periksa['data'];
            }
        }

        if ($galat !== [] || $ubah === []) {
            return $galat;
        }

        $sekarang = date('Y-m-d H:i:s');

        // transBegin/transCommit, bukan transStart/transComplete: dalam mode
        // ketat status gagal transaksi lain di koneksi yang sama tidak ikut
        // membatalkan penyimpanan ini (lihat FaqModel::pindah(), #129).
        $this->db->transBegin();

        try {
            foreach ($ubah as $babakId => $data) {
                if (! $this->db->table('babak')->where('id', $babakId)->update($data + ['diubah_pada' => $sekarang])) {
                    throw new RuntimeException('Jadwal babak gagal disimpan.');
                }
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return [];
    }

    /**
     * Ada babak yang sudah punya tanggal?
     */
    public function adaTerjadwal(): bool
    {
        return $this->db->table('babak')->where('tanggal IS NOT NULL', null, false)->countAllResults() > 0;
    }

    private static function uraiTanggal(string $teks): ?string
    {
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/D', $teks, $c) === 1) {
            [$t, $b, $h] = [(int) $c[1], (int) $c[2], (int) $c[3]];
        } elseif (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#D', $teks, $c) === 1) {
            [$h, $b, $t] = [(int) $c[1], (int) $c[2], (int) $c[3]];
        } else {
            return null;
        }

        return $t >= 2000 && $t <= 2100 && checkdate($b, $h, $t) ? sprintf('%04d-%02d-%02d', $t, $b, $h) : null;
    }
}
