<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use DateInterval;
use DateTimeImmutable;

/**
 * Catatan pengunjung halaman publik (K102), tabel `kunjungan`.
 *
 * Dicatat oleh filter `CatatPengunjung` setelah halaman publik terkirim, dan
 * dibaca dasbor admin serta halaman Monitoring `public/arumi.php`.
 *
 *     $p = new Pengunjung();
 *     $p->catat('203.0.113.5', 'Mozilla/5.0 …', '/cek-pendaftaran');
 *     $p->perJam('2026-11-15');                   // 24 baris: jam, kunjungan, unik
 *     $p->perHari('2026-11-01', '2026-11-15');    // satu baris per tanggal, hari kosong bernilai 0
 *     $p->ringkasan('2026-11-15');                // hari ini, kemarin, 7 hari, 30 hari, semua
 *     $p->halamanTeratas('2026-11-15');           // halaman paling sering dibuka
 *
 * - **Kunjungan**: jumlah halaman yang dibuka.
 * - **Unik**: jumlah pengunjung berbeda dalam satu hari. IP tidak disimpan;
 *   `sidik` = 16 huruf pertama HMAC-SHA256 dari IP, peramban, dan tanggal,
 *   jadi pengunjung yang sama di hari lain mendapat sidik lain. Unik untuk
 *   rentang beberapa hari adalah jumlah unik harian.
 * - Bot, mesin pencari, permintaan bukan `GET`, jawaban selain HTML 200, dan
 *   halaman admin tidak dicatat (`layakDicatat()`).
 * - Catatan yang lebih tua dari `SIMPAN_HARI` dihapus sesekali saat mencatat.
 * - Waktu memakai zona waktu aplikasi (WIB).
 */
final class Pengunjung
{
    /**
     * Catatan lebih tua dari ini (hari) dihapus otomatis.
     */
    public const SIMPAN_HARI = 400;

    /**
     * Tanda peramban otomatis: bot, alat baris perintah, pratinjau tautan.
     */
    private const POLA_BOT = '~bot\b|bot/|crawl|spider|slurp|scrap|facebookexternalhit|whatsapp|telegram|preview|monitor|uptime|curl/|wget/|python|go-http|java/|okhttp|axios|node-fetch|headless|lighthouse|pingdom|httpclient|libwww|feed~i';

    private const POLA_PONSEL = '~mobile|android|iphone|ipad|ipod|opera mini|iemobile~i';

    /**
     * Satu dari sekian catatan ikut membersihkan catatan lama (`hapusLama()`);
     * 0 = tidak pernah. Uji yang menghitung baris mengisinya 0 supaya hasilnya pasti.
     */
    public int $peluangBersih = 200;

    private BaseConnection $db;

    private string $kunci;

    public function __construct(?BaseConnection $db = null, ?string $kunci = null)
    {
        $this->db    = $db ?? db_connect();
        $this->kunci = $kunci ?? self::kunciBawaan();
    }

    /**
     * Apakah permintaan ini dicatat: `GET` halaman publik yang berhasil
     * (HTML 200) dari peramban sungguhan, bukan prefetch.
     */
    public static function layakDicatat(string $metode, string $jalur, int $status, string $jenisIsi, string $peramban, bool $prefetch = false): bool
    {
        if (strtoupper($metode) !== 'GET' || $status !== 200 || $prefetch) {
            return false;
        }

        if (! str_starts_with(strtolower(trim($jenisIsi)), 'text/html')) {
            return false;
        }

        $jalur = '/' . trim($jalur, '/');

        if ($jalur === '/admin' || str_starts_with($jalur, '/admin/')) {
            return false;
        }

        return ! self::bot($peramban);
    }

    /**
     * Peramban kosong atau bertanda bot dianggap bot.
     */
    public static function bot(string $peramban): bool
    {
        return trim($peramban) === '' || preg_match(self::POLA_BOT, $peramban) === 1;
    }

    public static function ponsel(string $peramban): bool
    {
        return preg_match(self::POLA_PONSEL, $peramban) === 1;
    }

    /**
     * Jalur yang disimpan: tanpa kueri, paling panjang 191 huruf. Alamat
     * bukti PDF memuat kode rahasia, jadi diringkas menjadi `/bukti`.
     */
    public static function rapikanJalur(string $jalur): string
    {
        $jalur = (string) parse_url($jalur, PHP_URL_PATH);
        $jalur = '/' . trim($jalur, '/');

        if (str_starts_with($jalur, '/bukti/')) {
            return '/bukti';
        }

        return mb_substr($jalur, 0, 191);
    }

    /**
     * Pengenal pengunjung untuk satu hari, tanpa menyimpan IP.
     */
    public function sidik(string $ip, string $peramban, string $tanggal): string
    {
        return substr(hash_hmac('sha256', $ip . '|' . $peramban . '|' . $tanggal, $this->kunci), 0, 16);
    }

    /**
     * Simpan satu kunjungan.
     */
    public function catat(string $ip, string $peramban, string $jalur, ?Time $waktu = null): void
    {
        $waktu ??= Time::now(config('App')->appTimezone);

        $this->db->table('kunjungan')->insert([
            'waktu'   => $waktu->format('Y-m-d H:i:s'),
            'sidik'   => $this->sidik($ip, $peramban, $waktu->format('Y-m-d')),
            'halaman' => self::rapikanJalur($jalur),
            'ponsel'  => self::ponsel($peramban) ? 1 : 0,
        ]);

        // Sekitar satu dari `$peluangBersih` catatan ikut membersihkan catatan lama.
        if ($this->peluangBersih > 0 && random_int(1, $this->peluangBersih) === 1) {
            $this->hapusLama($waktu);
        }
    }

    /**
     * Hapus catatan yang lebih tua dari `SIMPAN_HARI`.
     */
    public function hapusLama(?Time $sekarang = null): int
    {
        $sekarang ??= Time::now(config('App')->appTimezone);
        $batas = $sekarang->subDays(self::SIMPAN_HARI)->format('Y-m-d 00:00:00');

        $this->db->table('kunjungan')->where('waktu <', $batas)->delete();

        return $this->db->affectedRows();
    }

    /**
     * Kunjungan per jam pada satu tanggal, jam 0 sampai 23.
     *
     * @return list<array{jam: int, kunjungan: int, unik: int}>
     */
    public function perJam(string $tanggal): array
    {
        $tanggal = self::tanggalSah($tanggal, date('Y-m-d'));
        $hasil   = [];

        for ($jam = 0; $jam < 24; $jam++) {
            $hasil[$jam] = ['jam' => $jam, 'kunjungan' => 0, 'unik' => 0];
        }

        $baris = $this->db->query(
            'SELECT HOUR(waktu) AS jam, COUNT(*) AS kunjungan, COUNT(DISTINCT sidik) AS unik FROM ' . $this->db->prefixTable('kunjungan')
            . ' WHERE waktu >= ? AND waktu < ? GROUP BY HOUR(waktu)',
            [$tanggal . ' 00:00:00', self::geser($tanggal, 1) . ' 00:00:00'],
        )->getResultArray();

        foreach ($baris as $b) {
            $hasil[(int) $b['jam']] = ['jam' => (int) $b['jam'], 'kunjungan' => (int) $b['kunjungan'], 'unik' => (int) $b['unik']];
        }

        return array_values($hasil);
    }

    /**
     * Kunjungan per hari dari `$dari` sampai `$sampai` (keduanya ikut).
     * Hari tanpa kunjungan tetap ada dengan nilai 0. Paling banyak 366 hari.
     *
     * @return list<array{tanggal: string, kunjungan: int, unik: int}>
     */
    public function perHari(string $dari, string $sampai): array
    {
        $dari   = self::tanggalSah($dari, date('Y-m-d'));
        $sampai = self::tanggalSah($sampai, $dari);

        if ($sampai < $dari) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        if (self::selisihHari($dari, $sampai) > 365) {
            $dari = self::geser($sampai, -365);
        }

        $hasil = [];

        for ($t = $dari; $t <= $sampai; $t = self::geser($t, 1)) {
            $hasil[$t] = ['tanggal' => $t, 'kunjungan' => 0, 'unik' => 0];
        }

        $baris = $this->db->query(
            'SELECT DATE(waktu) AS tanggal, COUNT(*) AS kunjungan, COUNT(DISTINCT sidik) AS unik FROM ' . $this->db->prefixTable('kunjungan')
            . ' WHERE waktu >= ? AND waktu < ? GROUP BY DATE(waktu)',
            [$dari . ' 00:00:00', self::geser($sampai, 1) . ' 00:00:00'],
        )->getResultArray();

        foreach ($baris as $b) {
            if (isset($hasil[$b['tanggal']])) {
                $hasil[$b['tanggal']] = ['tanggal' => $b['tanggal'], 'kunjungan' => (int) $b['kunjungan'], 'unik' => (int) $b['unik']];
            }
        }

        return array_values($hasil);
    }

    /**
     * Angka ringkas dihitung dari `$tanggal` sebagai "hari ini".
     *
     * @return array{hari_ini: array{kunjungan: int, unik: int}, kemarin: array{kunjungan: int, unik: int}, tujuh_hari: array{kunjungan: int, unik: int}, tiga_puluh_hari: array{kunjungan: int, unik: int}, semua: array{kunjungan: int, unik: int}, ponsel_persen: int}
     */
    public function ringkasan(string $tanggal): array
    {
        $tanggal = self::tanggalSah($tanggal, date('Y-m-d'));
        $harian  = $this->perHari(self::geser($tanggal, -29), $tanggal);
        $jumlah  = static function (array $baris): array {
            return [
                'kunjungan' => array_sum(array_column($baris, 'kunjungan')),
                'unik'      => array_sum(array_column($baris, 'unik')),
            ];
        };

        $semua = $this->db->query(
            'SELECT COUNT(*) AS kunjungan, COALESCE(SUM(ponsel), 0) AS ponsel FROM ' . $this->db->prefixTable('kunjungan'),
        )->getRowArray();
        $unikSemua = $this->db->query(
            'SELECT COALESCE(SUM(n), 0) AS n FROM (SELECT COUNT(DISTINCT sidik) AS n FROM ' . $this->db->prefixTable('kunjungan') . ' GROUP BY DATE(waktu)) h',
        )->getRowArray();

        $total = (int) ($semua['kunjungan'] ?? 0);

        return [
            'hari_ini'        => $jumlah(array_slice($harian, -1)),
            'kemarin'         => $jumlah(array_slice($harian, -2, 1)),
            'tujuh_hari'      => $jumlah(array_slice($harian, -7)),
            'tiga_puluh_hari' => $jumlah($harian),
            'semua'           => ['kunjungan' => $total, 'unik' => (int) ($unikSemua['n'] ?? 0)],
            'ponsel_persen'   => $total > 0 ? (int) round((int) $semua['ponsel'] / $total * 100) : 0,
        ];
    }

    /**
     * Halaman paling sering dibuka pada satu tanggal.
     *
     * @return list<array{halaman: string, kunjungan: int, unik: int}>
     */
    public function halamanTeratas(string $tanggal, int $batas = 8): array
    {
        $tanggal = self::tanggalSah($tanggal, date('Y-m-d'));

        $baris = $this->db->query(
            'SELECT halaman, COUNT(*) AS kunjungan, COUNT(DISTINCT sidik) AS unik FROM ' . $this->db->prefixTable('kunjungan')
            . ' WHERE waktu >= ? AND waktu < ? GROUP BY halaman ORDER BY kunjungan DESC, halaman LIMIT ' . max(1, $batas),
            [$tanggal . ' 00:00:00', self::geser($tanggal, 1) . ' 00:00:00'],
        )->getResultArray();

        return array_map(static fn (array $b): array => [
            'halaman'   => (string) $b['halaman'],
            'kunjungan' => (int) $b['kunjungan'],
            'unik'      => (int) $b['unik'],
        ], $baris);
    }

    /**
     * Tanggal pertama yang punya catatan, atau `null` bila belum ada.
     */
    public function tanggalPertama(): ?string
    {
        $baris = $this->db->query('SELECT MIN(waktu) AS w FROM ' . $this->db->prefixTable('kunjungan'))->getRowArray();

        return empty($baris['w']) ? null : substr((string) $baris['w'], 0, 10);
    }

    /**
     * `$isian` bila berbentuk tanggal `YYYY-MM-DD` yang sah, selain itu `$bawaan`.
     */
    public static function tanggalSah(?string $isian, string $bawaan): string
    {
        if (is_string($isian) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $isian, $m) === 1 && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $isian;
        }

        return $bawaan;
    }

    /**
     * Tanggal `YYYY-MM-DD` digeser sejumlah hari (boleh negatif).
     */
    public static function geser(string $tanggal, int $hari): string
    {
        $t = new DateTimeImmutable($tanggal);

        return ($hari >= 0 ? $t->add(new DateInterval('P' . $hari . 'D')) : $t->sub(new DateInterval('P' . -$hari . 'D')))->format('Y-m-d');
    }

    private static function selisihHari(string $dari, string $sampai): int
    {
        return (int) (new DateTimeImmutable($dari))->diff(new DateTimeImmutable($sampai))->days;
    }

    /**
     * Kunci HMAC: kunci enkripsi aplikasi, atau teks tetap bila belum diisi.
     */
    private static function kunciBawaan(): string
    {
        $kunci = (string) (config('Encryption')->key ?? '');

        return $kunci !== '' ? $kunci : 'milklife-pengunjung';
    }
}
