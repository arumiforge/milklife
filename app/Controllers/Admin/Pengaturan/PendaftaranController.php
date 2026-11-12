<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use DateTimeImmutable;

/**
 * Halaman pengaturan Pendaftaran: `GET` dan `POST /admin/pengaturan/pendaftaran`.
 *
 * Menyimpan kunci `Pendaftaran.*` (rencana Tahap 2, "Kunci pengaturan"):
 * waktu buka dan tutup, buka manual beserta batasnya, tampilan usia, batas
 * penanggung jawab, dan batas nomor individu per atlet.
 *
 * - Waktu diketik di kotak `datetime-local` (`Y-m-d\TH:i`, WIB) dan disimpan
 *   `Y-m-d H:i:00`, teliti sampai menit.
 * - Tidak ada tanggal patokan usia (K37). Usia hanya keterangan, dihitung pada
 *   tanggal mulai acara; kelompok umur ditentukan tahun lahir (K36).
 * - Keadaan buka-tutup pendaftaran dihitung `BukaTutupPendaftaran` (F2-09),
 *   bukan di sini.
 */
class PendaftaranController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/pendaftaran';

    /**
     * Pilihan tampilan usia: nilai tersimpan => label.
     */
    public const TAMPILAN_USIA = [
        'tahun'       => 'Tahun saja',
        'tahun_bulan' => 'Tahun dan bulan',
    ];

    /**
     * Batas atas yang masuk akal, supaya salah ketik tertangkap.
     */
    private const PENANGGUNG_JAWAB_PALING_BANYAK = 20;
    private const NOMOR_INDIVIDU_PALING_BANYAK   = 10;

    public function index(): string
    {
        return $this->tampilkan([
            'mulai'                     => self::waktuKotak(setting('Pendaftaran.mulai')),
            'akhir'                     => self::waktuKotak(setting('Pendaftaran.akhir')),
            'buka_manual'               => (bool) setting('Pendaftaran.buka_manual') ? '1' : '',
            'buka_manual_sampai'        => self::waktuKotak(setting('Pendaftaran.buka_manual_sampai')),
            'tampilan_usia'             => (string) setting('Pendaftaran.tampilan_usia'),
            'penanggung_jawab_minimal'  => (string) setting('Pendaftaran.penanggung_jawab_minimal'),
            'penanggung_jawab_maksimal' => (string) setting('Pendaftaran.penanggung_jawab_maksimal'),
            'nomor_individu_maksimal'   => (string) setting('Pendaftaran.nomor_individu_maksimal'),
        ]);
    }

    public function simpan(): ResponseInterface|string
    {
        $isian = [];

        foreach ([
            'mulai', 'akhir', 'buka_manual_sampai', 'tampilan_usia',
            'penanggung_jawab_minimal', 'penanggung_jawab_maksimal', 'nomor_individu_maksimal',
        ] as $kotak) {
            $isian[$kotak] = trim((string) $this->request->getPost($kotak));
        }

        $isian['buka_manual'] = $this->request->getPost('buka_manual') === '1' ? '1' : '';

        [$galat, $waktu] = $this->periksa($isian);

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat);
        }

        setting('Pendaftaran.mulai', $waktu['mulai']);
        setting('Pendaftaran.akhir', $waktu['akhir']);
        setting('Pendaftaran.buka_manual', $isian['buka_manual'] === '1');
        setting('Pendaftaran.buka_manual_sampai', $waktu['buka_manual_sampai']);
        setting('Pendaftaran.tampilan_usia', $isian['tampilan_usia']);
        setting('Pendaftaran.penanggung_jawab_minimal', (int) $isian['penanggung_jawab_minimal']);
        setting('Pendaftaran.penanggung_jawab_maksimal', (int) $isian['penanggung_jawab_maksimal']);
        setting('Pendaftaran.nomor_individu_maksimal', (int) $isian['nomor_individu_maksimal']);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Pengaturan pendaftaran tersimpan.');
    }

    /**
     * @param array<string, string> $isian
     *
     * @return array{0: array<string, string>, 1: array<string, string|null>} pesan per kotak, dan waktu siap simpan (`Y-m-d H:i:s` atau `null`)
     */
    private function periksa(array $isian): array
    {
        $galat = [];
        $waktu = [];

        $judulWaktu = [
            'mulai'              => ['Waktu buka', 'Pilih tanggal dan jam pendaftaran mulai dibuka.'],
            'akhir'              => ['Waktu tutup', 'Pilih tanggal dan jam pendaftaran ditutup, misalnya 30 November pukul 23.59.'],
            'buka_manual_sampai' => ['Batas buka manual', 'Pilih tanggal dan jam pendaftaran yang dibuka manual ditutup lagi.'],
        ];

        foreach ($judulWaktu as $kotak => [$label]) {
            $waktu[$kotak] = null;

            if ($isian[$kotak] === '') {
                continue;
            }

            $waktu[$kotak] = self::bacaWaktu($isian[$kotak]);

            if ($waktu[$kotak] === null) {
                $galat[$kotak] = "{$label} belum benar. Pilih tanggal dan jam dari kalender di kotaknya.";
            }
        }

        if ($isian['mulai'] === '') {
            $galat['mulai'] = 'Waktu buka belum diisi. ' . $judulWaktu['mulai'][1];
        }

        if ($isian['akhir'] === '') {
            $galat['akhir'] = 'Waktu tutup belum diisi. ' . $judulWaktu['akhir'][1];
        }

        // Teks `Y-m-d H:i:s` yang sah bisa dibandingkan langsung sebagai teks.
        if (! isset($galat['mulai']) && ! isset($galat['akhir']) && $waktu['akhir'] <= $waktu['mulai']) {
            $galat['akhir'] = 'Waktu tutup harus setelah waktu buka (' . tanggal_id($waktu['mulai'], true) . '). Periksa lagi kedua waktu.';
        }

        if ($isian['buka_manual'] === '1' && ! isset($galat['buka_manual_sampai'])) {
            if ($waktu['buka_manual_sampai'] === null) {
                $galat['buka_manual_sampai'] = 'Batas buka manual belum diisi. Pilih sampai kapan pendaftaran dibuka manual, atau hilangkan centang "Buka pendaftaran sekarang".';
            } elseif ($waktu['buka_manual_sampai'] <= Time::now()->format('Y-m-d H:i:s')) {
                $galat['buka_manual_sampai'] = 'Batas buka manual sudah lewat. Pilih waktu yang belum lewat, atau hilangkan centang "Buka pendaftaran sekarang".';
            }
        }

        if (! array_key_exists($isian['tampilan_usia'], self::TAMPILAN_USIA)) {
            $galat['tampilan_usia'] = 'Tampilan usia belum dipilih. Pilih "Tahun saja" atau "Tahun dan bulan".';
        }

        $minimal  = self::bilangan($isian['penanggung_jawab_minimal']);
        $maksimal = self::bilangan($isian['penanggung_jawab_maksimal']);
        $batasPj  = self::PENANGGUNG_JAWAB_PALING_BANYAK;

        if ($minimal === null || $minimal < 1 || $minimal > $batasPj) {
            $galat['penanggung_jawab_minimal'] = "Jumlah penanggung jawab paling sedikit harus angka 1 sampai {$batasPj}, misalnya 1.";
        }

        if ($maksimal === null || $maksimal < 1 || $maksimal > $batasPj) {
            $galat['penanggung_jawab_maksimal'] = "Jumlah penanggung jawab paling banyak harus angka 1 sampai {$batasPj}, misalnya 5.";
        } elseif (! isset($galat['penanggung_jawab_minimal']) && $maksimal < $minimal) {
            $galat['penanggung_jawab_maksimal'] = "Jumlah paling banyak tidak boleh lebih kecil dari jumlah paling sedikit ({$minimal}). Naikkan jumlah paling banyak atau turunkan jumlah paling sedikit.";
        }

        $nomor      = self::bilangan($isian['nomor_individu_maksimal']);
        $batasNomor = self::NOMOR_INDIVIDU_PALING_BANYAK;

        if ($nomor === null || $nomor < 1 || $nomor > $batasNomor) {
            $galat['nomor_individu_maksimal'] = "Batas nomor individu harus angka 1 sampai {$batasNomor}, misalnya 2.";
        }

        // Urutkan pesan seperti urutan kotak di formulir, supaya ringkasan mudah diikuti.
        $urutan = array_flip([
            'mulai', 'akhir', 'buka_manual_sampai',
            'penanggung_jawab_minimal', 'penanggung_jawab_maksimal', 'nomor_individu_maksimal',
            'tampilan_usia',
        ]);
        uksort($galat, static fn (string $a, string $b): int => $urutan[$a] <=> $urutan[$b]);

        return [$galat, $waktu];
    }

    /**
     * Isian kotak `datetime-local` (atau `Y-m-d H:i[:s]`) menjadi `Y-m-d H:i:00`;
     * `null` bila bukan tanggal dan jam yang sah. Detik dibuang, karena kotaknya
     * hanya sampai menit.
     */
    private static function bacaWaktu(string $isian): ?string
    {
        foreach (['!Y-m-d\TH:i', '!Y-m-d\TH:i:s', '!Y-m-d H:i', '!Y-m-d H:i:s'] as $format) {
            $waktu = DateTimeImmutable::createFromFormat($format, $isian);
            $galat = DateTimeImmutable::getLastErrors();

            if ($waktu !== false && ($galat === false || ($galat['warning_count'] === 0 && $galat['error_count'] === 0))) {
                return $waktu->format('Y-m-d H:i:00');
            }
        }

        return null;
    }

    /**
     * Nilai tersimpan `Y-m-d H:i:s` menjadi isi kotak `datetime-local` (`Y-m-d\TH:i`).
     */
    private static function waktuKotak(mixed $nilai): string
    {
        $nilai = $nilai === null ? '' : trim((string) $nilai);
        $baku  = $nilai === '' ? null : self::bacaWaktu($nilai);

        return $baku === null ? '' : str_replace(' ', 'T', substr($baku, 0, 16));
    }

    /**
     * Isian angka bulat positif (tanpa tanda lain), atau `null`.
     */
    private static function bilangan(string $isian): ?int
    {
        return preg_match('/^\d{1,3}$/', $isian) === 1 ? (int) $isian : null;
    }

    /**
     * @param array<string, string> $isian
     * @param array<string, string> $galat
     */
    private function tampilkan(array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/pendaftaran', [
            'isian'             => $isian,
            'galat'             => $galat,
            'pilihanUsia'       => self::TAMPILAN_USIA,
            'tanggalMulaiAcara' => tanggal_id(setting('Acara.tanggal_mulai')),
        ]);
    }
}
