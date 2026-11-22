<?php

namespace App\Libraries\Hasil;

use CodeIgniter\Database\BaseConnection;

/**
 * Status nomor dan batas peserta (F11-03, K172 butir 4).
 *
 * - Nomor bertanda tidak dilombakan (`nomor_lomba.tidak_dilombakan`) keluar
 *   dari jadwal, seri, klasemen, dan videotron; halaman hasil menulis
 *   `keterangan()` dengan alasannya.
 * - Batas peserta minimal (`nomor_lomba.peserta_minimal`, kosong = tanpa
 *   batas) hanya memunculkan `peringatan()` di halaman Hasil dan Seri admin
 *   bila peserta Lunas, atau peserta hadir setelah daftar ulang dipakai,
 *   kurang dari batas. Web tidak mengubah status nomor dan tidak mengurangi
 *   medali; panitia yang memutuskan.
 *
 * Peserta = atlet (nomor individu) atau regu (nomor beregu) pendaftaran
 * Lunas; regu dihitung hadir bila ada anggotanya yang hadir (sama dengan
 * `SeriLintasan`).
 *
 *     StatusNomor::keterangan('Peserta kurang dari 3');        // 'Tidak dilombakan. Peserta kurang dari 3.'
 *     StatusNomor::peringatan(3, 2, null, false);              // 'Peserta Lunas baru 2 atlet, …'
 *     (new StatusNomor())->jumlahPeserta();                    // [10 => ['lunas' => 5, 'hadir' => 4], …]
 */
final class StatusNomor
{
    public const ALASAN_MAKSIMAL  = 150;
    public const BATAS_MAKSIMAL   = 99;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Periksa isian status dan batas peserta formulir nomor lomba.
     *
     * @param array<string, string> $isian `tidak_dilombakan` ('1'/'0'), `alasan_tidak_dilombakan`, `peserta_minimal`
     *
     * @return array<string, string> nama kotak => pesan; kosong bila benar
     */
    public static function periksa(array $isian): array
    {
        $galat  = [];
        $alasan = $isian['alasan_tidak_dilombakan'] ?? '';

        if (($isian['tidak_dilombakan'] ?? '0') === '1' && $alasan === '') {
            $galat['alasan_tidak_dilombakan'] = 'Alasan belum diisi. Tulis alasan singkat yang tampil di halaman hasil, misalnya Peserta kurang dari 3.';
        } elseif (mb_strlen($alasan) > self::ALASAN_MAKSIMAL) {
            $galat['alasan_tidak_dilombakan'] = 'Alasan terlalu panjang. Persingkat menjadi paling banyak ' . self::ALASAN_MAKSIMAL . ' huruf.';
        }

        $batas = $isian['peserta_minimal'] ?? '';

        if ($batas !== '' && (preg_match('/^\d{1,2}$/', $batas) !== 1 || (int) $batas < 1)) {
            $galat['peserta_minimal'] = 'Batas peserta harus angka 1 sampai ' . self::BATAS_MAKSIMAL . ', misalnya 3. Kosongkan bila tanpa batas.';
        }

        return $galat;
    }

    /**
     * Keterangan nomor yang tidak dilombakan untuk halaman hasil.
     */
    public static function keterangan(?string $alasan): string
    {
        $alasan = trim((string) $alasan);

        if ($alasan === '') {
            return 'Tidak dilombakan.';
        }

        return 'Tidak dilombakan. ' . rtrim($alasan, '.') . '.';
    }

    /**
     * Peringatan bila peserta kurang dari batas, atau `null`.
     *
     * @param int|null $batas `peserta_minimal`; `null` = tanpa batas
     * @param int      $lunas peserta pendaftaran Lunas
     * @param int|null $hadir peserta hadir; `null` bila daftar ulang belum dipakai
     */
    public static function peringatan(?int $batas, int $lunas, ?int $hadir, bool $beregu): ?string
    {
        if ($batas === null || $batas < 1) {
            return null;
        }

        $satuan = $beregu ? 'regu' : 'atlet';
        $akhir  = ' Medali tidak dikurangi otomatis. Bila nomor ini batal, tandai Tidak dilombakan di Pengaturan > Nomor lomba.';

        if ($lunas < $batas) {
            return "Peserta Lunas baru {$lunas} {$satuan}, kurang dari batas {$batas} {$satuan}." . $akhir;
        }

        if ($hadir !== null && $hadir < $batas) {
            return "Peserta hadir baru {$hadir} {$satuan}, kurang dari batas {$batas} {$satuan}." . $akhir;
        }

        return null;
    }

    /**
     * Banyak peserta Lunas dan hadir per nomor. Nomor tanpa peserta Lunas tidak ada di hasil.
     *
     * @return array<int, array{lunas: int, hadir: int}> id nomor => jumlah
     */
    public function jumlahPeserta(): array
    {
        $peserta = "CASE WHEN nl.jenis = 'beregu' THEN CONCAT(a.pendaftaran_id, ':', an.regu) ELSE CAST(a.id AS CHAR) END";

        $baris = $this->db->table('atlet_nomor an')
            ->select("an.nomor_lomba_id, COUNT(DISTINCT {$peserta}) AS lunas, COUNT(DISTINCT CASE WHEN a.hadir_pada IS NOT NULL THEN {$peserta} END) AS hadir", false)
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('p.status', 'lunas')
            ->groupStart()->where('nl.jenis !=', 'beregu')->orWhere('an.regu IS NOT NULL', null, false)->groupEnd()
            ->groupBy('an.nomor_lomba_id')
            ->get()->getResultArray();

        $jumlah = [];

        foreach ($baris as $b) {
            $jumlah[(int) $b['nomor_lomba_id']] = ['lunas' => (int) $b['lunas'], 'hadir' => (int) $b['hadir']];
        }

        return $jumlah;
    }

    /**
     * Peringatan batas peserta per nomor yang dilombakan dan punya batas.
     *
     * @param list<array{id: int, jenis: string, dilombakan: bool, peserta_minimal: int|null}> $nomor
     *
     * @return array<int, string> id nomor => peringatan
     */
    public function peringatanNomor(array $nomor): array
    {
        $berbatas = array_filter($nomor, static fn (array $n): bool => $n['dilombakan'] && $n['peserta_minimal'] !== null);

        if ($berbatas === []) {
            return [];
        }

        $jumlah      = $this->jumlahPeserta();
        $daftarUlang = (new SeriLintasan($this->db))->daftarUlangDipakai();
        $hasil       = [];

        foreach ($berbatas as $n) {
            $j     = $jumlah[$n['id']] ?? ['lunas' => 0, 'hadir' => 0];
            $teks  = self::peringatan($n['peserta_minimal'], $j['lunas'], $daftarUlang ? $j['hadir'] : null, $n['jenis'] === 'beregu');

            if ($teks !== null) {
                $hasil[$n['id']] = $teks;
            }
        }

        return $hasil;
    }
}
