<?php

namespace App\Libraries\Hasil;

use CodeIgniter\Database\BaseConnection;
use DateTimeInterface;

/**
 * Lembar juri (F9-09, K140 butir 7d): isian prestasi cepat dari ponsel di
 * `admin/juri`, memakai akun admin yang sama (K124 butir 9).
 *
 * - Daftar babak per hari jadwal (F9-07); hari ini di atas.
 * - Satu baris per peserta, dikelompokkan per seri (F9-08), disimpan satu
 *   per satu lewat `HasilLomba::simpanHasil()` (kunci baris babak dan versi
 *   baris, jadi beberapa admin bisa mengisi babak berbeda bersamaan).
 * - Isian mengikuti jenis hasil nomor: waktu (boleh seperseribu detik),
 *   jarak per percobaan (`Percobaan`), poin, atau prestasi tiap komponen
 *   poin multi-lomba (`komponen`, sama dengan halaman isi hasil babak).
 * - Peringkat dan Terbitkan tetap di halaman hasil (F8-04).
 *
 *     $juri = new LembarJuri();
 *     $juri->daftar(new DateTimeImmutable());   // ['hari' => ['2026-12-09' => [...], '' => [...]], 'hari_ini' => '2026-12-09']
 *     $juri->simpan(3, 41, ['prestasi' => '9,87', 'keterangan' => '', 'versi' => '0']);
 *     // ['status' => 'tersimpan', 'pesan' => 'Tersimpan.', 'baris' => [...]]
 */
final class LembarJuri
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Semua babak per tanggal jadwal (kunci `''` = belum dijadwalkan, paling
     * akhir), urut jam. Hari ini (bila ada babaknya) paling atas.
     *
     * @return array{hari: array<string, list<array<string, mixed>>>, hari_ini: string|null}
     */
    public function daftar(DateTimeInterface $kini): array
    {
        $hitung = [];

        foreach ($this->db->table('hasil')
            ->select('babak_id, COUNT(*) AS peserta, SUM(prestasi IS NOT NULL OR keterangan IS NOT NULL) AS terisi', false)
            ->groupBy('babak_id')
            ->get()->getResultArray() as $h) {
            $hitung[(int) $h['babak_id']] = ['peserta' => (int) $h['peserta'], 'terisi' => (int) $h['terisi']];
        }

        $hari = [];

        foreach ((new JadwalPertandingan($this->db))->semua() as $b) {
            $hari[$b['tanggal'] ?? ''][] = $b + ($hitung[$b['id']] ?? ['peserta' => 0, 'terisi' => 0]);
        }

        $tanggal = $kini->format('Y-m-d');

        if (isset($hari[$tanggal])) {
            $hari = [$tanggal => $hari[$tanggal]] + $hari;
        }

        if (isset($hari[''])) {
            $belum = $hari[''];
            unset($hari['']);
            $hari[''] = $belum;
        }

        return ['hari' => $hari, 'hari_ini' => isset($hari[$tanggal]) ? $tanggal : null];
    }

    /**
     * Simpan satu baris dari lembar juri.
     *
     * `$isian`: `prestasi` (waktu, poin), `percobaan` (jarak: daftar teks),
     * `komponen` (poin multi-lomba: kode => teks), `keterangan`, `pasal`
     * (pasal DQ, F11-05), `versi`.
     * Koreksi peringkat Final yang sudah ada dipertahankan.
     *
     * @param array<string, mixed> $isian
     *
     * @return array{status: 'tersimpan'|'galat'|'bentrok'|'hilang', pesan: string, baris?: array<string, mixed>}
     */
    public function simpan(int $babakId, int $hasilId, array $isian): array
    {
        $hasil = new HasilLomba($this->db);
        $babak = $hasil->babak($babakId);

        if ($babak === null || $this->db->table('hasil')->where('id', $hasilId)->where('babak_id', $babakId)->countAllResults() === 0) {
            return ['status' => 'hilang', 'pesan' => 'Peserta ini tidak ada lagi di babak ini. Mungkin admin lain sudah mengeluarkannya. Muat ulang halaman.'];
        }

        $jenis = $babak['nomor']['jenis_hasil'];
        $baris = [
            'keterangan' => is_string($isian['keterangan'] ?? null) ? $isian['keterangan'] : '',
            'pasal'      => is_string($isian['pasal'] ?? null) ? $isian['pasal'] : '',
            'versi'      => is_scalar($isian['versi'] ?? null) ? (string) $isian['versi'] : null,
        ];

        if ($jenis === JenisHasil::MULTI) {
            $baris['komponen'] = array_filter(is_array($isian['komponen'] ?? null) ? $isian['komponen'] : [], 'is_string');
        } elseif ($jenis === JenisHasil::JARAK) {
            $baris['percobaan'] = array_map(static fn ($p): string => is_string($p) ? $p : '', is_array($isian['percobaan'] ?? null) ? array_values($isian['percobaan']) : []);
        } else {
            $baris['prestasi'] = is_string($isian['prestasi'] ?? null) ? $isian['prestasi'] : '';
        }

        $galat = $hasil->simpanHasil($babakId, [$hasilId => $baris]);

        if ($galat !== []) {
            $pesan = (string) ($galat[$hasilId] ?? reset($galat));

            return ['status' => $pesan === HasilLomba::PESAN_BENTROK ? 'bentrok' : 'galat', 'pesan' => $pesan];
        }

        foreach ($hasil->hasilBabak($babakId, $babak['nomor']) as $h) {
            if ($h['id'] === $hasilId) {
                return ['status' => 'tersimpan', 'pesan' => 'Tersimpan.', 'baris' => self::ringkas($h)];
            }
        }

        return ['status' => 'hilang', 'pesan' => 'Peserta ini tidak ada lagi di babak ini. Muat ulang halaman.'];
    }

    /**
     * Isi baris yang dikirim balik ke lembar juri setelah tersimpan.
     *
     * @param array<string, mixed> $h `HasilLomba::hasilBabak()`
     *
     * @return array{id: int, versi: int, prestasi: string, isian: string, percobaan: list<string>, komponen: array<string, string>, rincian: list<array<string, mixed>>, keterangan: string, pasal: string, ringkasan: string}
     */
    public static function ringkas(array $h): array
    {
        return [
            'id'         => (int) $h['id'],
            'versi'      => (int) $h['versi'],
            'prestasi'   => (string) $h['prestasi_teks'],
            'isian'      => (string) $h['prestasi_isian'],
            'percobaan'  => $h['percobaan_teks'],
            'komponen'   => $h['rincian_isian'],
            'rincian'    => $h['rincian'],
            'keterangan' => (string) $h['keterangan'],
            'pasal'      => (string) ($h['pasal'] ?? ''),
            'ringkasan'  => self::ringkasan($h),
        ];
    }

    /**
     * Teks pendek isi baris: prestasi (terbaik/total poin) atau keterangan.
     * Pasal ditulis di belakang DQ; mundur (r) dengan prestasi menulis
     * prestasinya (F11-05).
     *
     * @param array<string, mixed> $h `HasilLomba::hasilBabak()`
     */
    public static function ringkasan(array $h): string
    {
        if ($h['keterangan'] === 'r' && $h['prestasi'] !== null) {
            return self::ringkasanPrestasi($h) . ', mundur';
        }

        if ($h['keterangan'] !== null) {
            $arti = (string) (Peringkat::KETERANGAN[$h['keterangan']] ?? $h['keterangan']);

            return ($h['pasal'] ?? null) !== null ? $arti . ' (' . $h['pasal'] . ')' : $arti;
        }

        return self::ringkasanPrestasi($h);
    }

    /**
     * @param array<string, mixed> $h `HasilLomba::hasilBabak()`
     */
    private static function ringkasanPrestasi(array $h): string
    {
        if ($h['prestasi'] === null) {
            return $h['percobaan'] !== null ? 'Belum ada percobaan sah' : '';
        }

        if ($h['rincian'] !== []) {
            return 'Total ' . $h['prestasi_teks'] . ' poin';
        }

        return ($h['percobaan'] !== null ? 'Terbaik ' : '') . $h['prestasi_teks'];
    }
}
