<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman Data Acara: `GET` dan `POST /admin/pengaturan/acara`.
 *
 * Menyimpan `Acara.nama`, `seri`, `tahun`, `tempat`, `tanggal_mulai`, dan
 * `tanggal_selesai` (rencana Tahap 2, "Kunci pengaturan"). Semua kotak wajib
 * diisi, karena semuanya dibutuhkan sebelum pendaftaran dibuka (rancangan 6.1).
 * Nama, seri, tahun, tempat, dan tanggal acara langsung dipakai tata letak dan
 * halaman depan.
 */
class AcaraController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/acara';

    /**
     * Teks pengganti nama acara di tata letak dan halaman depan selama admin
     * belum mengisinya.
     */
    public const NAMA_PENGGANTI = 'Pendaftaran Lomba';

    /**
     * Batas tahun dan seri yang masuk akal, supaya salah ketik tertangkap.
     */
    private const TAHUN_PALING_AWAL  = 2000;
    private const TAHUN_PALING_AKHIR = 2100;
    private const SERI_PALING_BESAR  = 99;

    public function index(): string
    {
        return $this->tampilkan([
            'nama'            => (string) setting('Acara.nama'),
            'seri'            => self::teks(setting('Acara.seri')),
            'tahun'           => self::teks(setting('Acara.tahun')),
            'tempat'          => (string) setting('Acara.tempat'),
            'tanggal_mulai'   => self::teks(setting('Acara.tanggal_mulai')),
            'tanggal_selesai' => self::teks(setting('Acara.tanggal_selesai')),
        ]);
    }

    public function simpan(): ResponseInterface|string
    {
        $isian = [];

        foreach (['nama', 'seri', 'tahun', 'tempat', 'tanggal_mulai', 'tanggal_selesai'] as $kotak) {
            // Spasi berlebih dirapikan, supaya nama tidak tampil berjarak aneh.
            $isian[$kotak] = trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost($kotak)));
        }

        $galat = $this->periksa($isian);

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat);
        }

        setting('Acara.nama', $isian['nama']);
        setting('Acara.seri', (int) $isian['seri']);
        setting('Acara.tahun', (int) $isian['tahun']);
        setting('Acara.tempat', $isian['tempat']);
        setting('Acara.tanggal_mulai', $isian['tanggal_mulai']);
        setting('Acara.tanggal_selesai', $isian['tanggal_selesai']);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Data acara tersimpan.');
    }

    /**
     * @param array<string, string> $isian
     *
     * @return array<string, string> kotak => pesan
     */
    private function periksa(array $isian): array
    {
        $aturan = [
            'nama' => [
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama acara belum diisi. Tulis nama acara, misalnya MilkLife Athletics Challenge.',
                    'max_length' => 'Nama acara terlalu panjang. Persingkat menjadi paling banyak 100 huruf.',
                ],
            ],
            'seri' => [
                'rules'  => 'required|is_natural_no_zero|less_than_equal_to[' . self::SERI_PALING_BESAR . ']',
                'errors' => [
                    'required'           => 'Seri belum diisi. Tulis nomor serinya saja, misalnya 2.',
                    'is_natural_no_zero' => 'Seri harus berupa angka, misalnya 2. Tulis angkanya saja tanpa kata "Seri".',
                    'less_than_equal_to' => 'Seri terlalu besar. Tulis nomor seri acara ini, misalnya 2.',
                ],
            ],
            'tahun' => [
                'rules'  => 'required|is_natural|greater_than_equal_to[' . self::TAHUN_PALING_AWAL . ']|less_than_equal_to[' . self::TAHUN_PALING_AKHIR . ']',
                'errors' => [
                    'required'              => 'Tahun belum diisi. Tulis tahun acara dengan empat angka, misalnya 2026.',
                    'is_natural'            => 'Tahun harus berupa empat angka, misalnya 2026.',
                    'greater_than_equal_to' => 'Tahun belum benar. Tulis tahun acara dengan empat angka, misalnya 2026.',
                    'less_than_equal_to'    => 'Tahun belum benar. Tulis tahun acara dengan empat angka, misalnya 2026.',
                ],
            ],
            'tempat' => [
                'rules'  => 'required|max_length[150]',
                'errors' => [
                    'required'   => 'Tempat belum diisi. Tulis tempat dan kotanya, misalnya Supersoccer Arena, Kudus.',
                    'max_length' => 'Tempat terlalu panjang. Persingkat menjadi paling banyak 150 huruf.',
                ],
            ],
            'tanggal_mulai' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Tanggal mulai belum diisi. Pilih tanggal hari pertama acara.',
                    'valid_date' => 'Tanggal mulai belum benar. Pilih tanggal dari kalender.',
                ],
            ],
            'tanggal_selesai' => [
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Tanggal selesai belum diisi. Pilih tanggal hari terakhir acara. Bila acara hanya sehari, pilih tanggal yang sama dengan tanggal mulai.',
                    'valid_date' => 'Tanggal selesai belum benar. Pilih tanggal dari kalender.',
                ],
            ],
        ];

        $galat = $this->validateData($isian, $aturan) ? [] : $this->validator->getErrors();

        // Teks `Y-m-d` yang sah bisa dibandingkan langsung sebagai teks.
        if (! isset($galat['tanggal_mulai'])
            && ! isset($galat['tanggal_selesai'])
            && $isian['tanggal_selesai'] < $isian['tanggal_mulai']
        ) {
            $galat['tanggal_selesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai (' . tanggal_id($isian['tanggal_mulai']) . '). Periksa lagi kedua tanggal.';
        }

        return $galat;
    }

    /**
     * Nama acara untuk ditampilkan: `Acara.nama`, atau `NAMA_PENGGANTI` bila
     * belum diisi. Dipakai tata letak (`tata_letak/_merek`, kaki publik) dan
     * halaman depan.
     */
    public static function namaTampil(): string
    {
        $nama = trim((string) setting('Acara.nama'));

        return $nama !== '' ? $nama : self::NAMA_PENGGANTI;
    }

    /**
     * Nilai pengaturan (angka atau teks, boleh `null`) sebagai isi kotak.
     */
    private static function teks(mixed $nilai): string
    {
        return $nilai === null ? '' : trim((string) $nilai);
    }

    /**
     * @param array<string, string> $isian
     * @param array<string, string> $galat
     */
    private function tampilkan(array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/acara', ['isian' => $isian, 'galat' => $galat]);
    }
}
