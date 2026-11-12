<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\NomorWa;
use App\Libraries\Unggahan;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Pengaturan isi halaman depan: `GET` dan `POST /admin/pengaturan/halaman-depan`.
 *
 * Menyimpan gambar kepala, tiga logo, judul, teks, footer, nomor WA tanya
 * admin, pesan tanya admin, dan pesan bagikan (kunci `HalamanDepan.*` di
 * rencana Tahap 2). Tampilan halaman depan publik yang memakai isi ini
 * dikerjakan di Tahap 3.
 *
 * - Semua isian boleh kosong; yang diisi harus benar.
 * - Nomor WA disimpan dalam bentuk baku `628…` (`NomorWa::normalkan()`).
 * - Gambar diunggah lewat `App\Libraries\Unggahan` (folder `halaman-depan`).
 *   Berkas lama dihapus setelah yang baru tersimpan, atau saat admin
 *   mencentang "Hapus gambar". Bila satu isian salah, tidak ada yang disimpan,
 *   termasuk gambar.
 */
class HalamanDepanController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/halaman-depan';

    private const FOLDER_UNGGAHAN = 'halaman-depan';

    /**
     * Kotak gambar: kunci pengaturan (sama dengan nama kotak unggah) => label,
     * rasio kotak pratinjau (`tata_letak/_tanpa_gambar`), dan saran ukuran.
     *
     * @var array<string, array{label: string, rasio: string, saran: string}>
     */
    public const GAMBAR = [
        'gambar_kepala' => [
            'label' => 'Gambar kepala',
            'rasio' => '21x9',
            'saran' => 'Tampil selebar layar sebagai latar di belakang judul halaman depan, diberi lapisan biru supaya tulisan tetap terbaca. Pilih foto tanpa tulisan, misalnya 1920 × 1080 piksel.',
        ],
        'logo_persegi' => [
            'label' => 'Logo persegi',
            'rasio' => '1x1',
            'saran' => 'Logo MilkLife berbentuk persegi, juga dipakai di bukti pendaftaran. Misalnya 500 × 500 piksel.',
        ],
        'logo_persegi_panjang' => [
            'label' => 'Logo persegi panjang',
            'rasio' => '16x9',
            'saran' => 'Logo MilkLife mendatar, misalnya 800 × 300 piksel.',
        ],
        'logo_djarum' => [
            'label' => 'Logo Bakti Olahraga Djarum Foundation',
            'rasio' => '16x9',
            'saran' => 'Logo penyelenggara, misalnya 800 × 300 piksel.',
        ],
    ];

    /**
     * Kotak teks: kunci pengaturan => panjang paling banyak.
     */
    private const BATAS_TEKS = [
        'judul'         => 150,
        'teks'          => 2000,
        'footer'        => 500,
        'pesan_tanya'   => 1000,
        'pesan_bagikan' => 1000,
    ];

    public function index(): string
    {
        $isian = [];

        foreach (array_keys(self::BATAS_TEKS) as $kunci) {
            $isian[$kunci] = (string) setting('HalamanDepan.' . $kunci);
        }

        $nomorWa                 = (string) setting('HalamanDepan.nomor_wa_tanya');
        $isian['nomor_wa_tanya'] = $nomorWa === '' ? '' : NomorWa::tampilan($nomorWa);

        return $this->tampilkan($isian);
    }

    public function simpan(): ResponseInterface|string
    {
        $isian = [
            'judul'          => trim((string) $this->request->getPost('judul')),
            'teks'           => self::rapikanTeks((string) $this->request->getPost('teks')),
            'footer'         => self::rapikanTeks((string) $this->request->getPost('footer')),
            'nomor_wa_tanya' => trim((string) $this->request->getPost('nomor_wa_tanya')),
            'pesan_tanya'    => self::rapikanTeks((string) $this->request->getPost('pesan_tanya')),
            'pesan_bagikan'  => self::rapikanTeks((string) $this->request->getPost('pesan_bagikan')),
        ];

        $sebutan = [
            'judul'         => 'Judul',
            'teks'          => 'Teks pengantar',
            'footer'        => 'Teks kaki halaman',
            'pesan_tanya'   => 'Pesan tanya admin',
            'pesan_bagikan' => 'Pesan bagikan',
        ];

        $aturan = [];
        foreach (self::BATAS_TEKS as $kunci => $batas) {
            $aturan[$kunci] = [
                'rules'  => 'permit_empty|max_length[' . $batas . ']',
                'errors' => ['max_length' => $sebutan[$kunci] . ' terlalu panjang. Persingkat menjadi paling banyak ' . number_format($batas, 0, ',', '.') . ' huruf.'],
            ];
        }

        $galat = $this->validateData($isian, $aturan) ? [] : $this->validator->getErrors();

        $nomorWa = '';
        if ($isian['nomor_wa_tanya'] !== '') {
            $nomorWa = (string) NomorWa::normalkan($isian['nomor_wa_tanya']);

            if ($nomorWa === '') {
                $galat['nomor_wa_tanya'] = NomorWa::PESAN_GALAT;
            }
        }

        $unggahan    = new Unggahan();
        $gambarBaru  = [];
        $dipilihSah  = [];

        foreach (array_keys(self::GAMBAR) as $kunci) {
            $berkas = $this->request->getFile($kunci);

            if (! Unggahan::dipilih($berkas)) {
                continue;
            }

            $pesan = $unggahan->periksaGambar($berkas);

            if ($pesan !== null) {
                $galat[$kunci] = self::GAMBAR[$kunci]['label'] . ': ' . $pesan;
            } else {
                $gambarBaru[$kunci] = $berkas;
                $dipilihSah[]       = self::GAMBAR[$kunci]['label'];
            }
        }

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat, $dipilihSah);
        }

        // Simpan semua gambar baru dulu. Bila satu gagal, yang sudah tersimpan
        // dibuang lagi supaya tidak ada berkas yatim dan pengaturan tidak berubah.
        $jalurBaru = [];

        foreach ($gambarBaru as $kunci => $berkas) {
            try {
                $jalurBaru[$kunci] = $unggahan->simpanGambar($berkas, self::FOLDER_UNGGAHAN);
            } catch (RuntimeException $e) {
                foreach ($jalurBaru as $jalur) {
                    $unggahan->hapus($jalur);
                }

                return $this->tampilkan($isian, [$kunci => self::GAMBAR[$kunci]['label'] . ': ' . $e->getMessage()]);
            }
        }

        foreach (array_keys(self::BATAS_TEKS) as $kunci) {
            setting('HalamanDepan.' . $kunci, $isian[$kunci]);
        }

        setting('HalamanDepan.nomor_wa_tanya', $nomorWa);

        // Berkas lama dibuang setelah yang baru tersimpan. Gambar baru
        // mengalahkan centang "Hapus gambar" pada kotak yang sama.
        foreach (array_keys(self::GAMBAR) as $kunci) {
            $lama = (string) setting('HalamanDepan.' . $kunci);

            if (isset($jalurBaru[$kunci])) {
                setting('HalamanDepan.' . $kunci, $jalurBaru[$kunci]);
                $unggahan->hapus($lama);
            } elseif ($this->request->getPost('hapus_' . $kunci) === '1' && $lama !== '') {
                setting('HalamanDepan.' . $kunci, '');
                $unggahan->hapus($lama);
            }
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Isi halaman depan tersimpan.');
    }

    /**
     * Akhir baris dibakukan menjadi `\n`, spasi di awal dan akhir dibuang.
     */
    private static function rapikanTeks(string $teks): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $teks));
    }

    /**
     * @param array<string, string> $isian
     * @param array<string, string> $galat
     * @param list<string>          $gambarPerluDipilihLagi label gambar sah yang belum tersimpan karena isian lain salah
     */
    private function tampilkan(array $isian, array $galat = [], array $gambarPerluDipilihLagi = []): string
    {
        $unggahan = new Unggahan();
        $gambar   = [];

        foreach (self::GAMBAR as $kunci => $keterangan) {
            $jalur          = (string) setting('HalamanDepan.' . $kunci);
            $gambar[$kunci] = $keterangan + ['jalur' => $unggahan->ada($jalur) ? $jalur : ''];
        }

        return view('admin/pengaturan/halaman_depan', [
            'isian'                  => $isian,
            'galat'                  => $galat,
            'gambar'                 => $gambar,
            'gambarPerluDipilihLagi' => $gambarPerluDipilihLagi,
        ]);
    }
}
