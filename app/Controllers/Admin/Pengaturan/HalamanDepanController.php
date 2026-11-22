<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\CarouselHalamanDepan;
use App\Libraries\MenuPublik;
use App\Libraries\NomorWa;
use App\Libraries\PetaLokasi;
use App\Libraries\Unggahan;
use App\Libraries\VarianFoto;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Pengaturan isi halaman depan: `GET` dan `POST /admin/pengaturan/halaman-depan`.
 *
 * Menyimpan gambar kepala, tiga logo, judul, teks, footer, nomor WA tanya
 * admin, pesan tanya admin, pesan bagikan (kunci `HalamanDepan.*` di
 * rencana Tahap 2), dan sakelar tampil-tidaknya biaya di bagian lomba
 * (`HalamanDepan.tampilkan_biaya`), serta alamat embed dan tautan peta lokasi
 * (`HalamanDepan.peta_embed`, `HalamanDepan.peta_tautan`, F10-02), serta butir
 * bilah menu atas publik (`HalamanDepan.bilah_atas_<kunci>`, F10-07). Tampilan halaman depan publik yang memakai isi ini
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
            'saran' => 'Tampil di bagian pembuka halaman depan hanya bila carousel belum berisi foto. Foto dipotong otomatis supaya memenuhi bingkainya, jadi letakkan atlet di tengah foto. Pilih foto mendatar tanpa tulisan, paling kecil 1600 × 900 piksel.',
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
        $isian['peta_embed']     = (string) setting('HalamanDepan.peta_embed');
        $isian['peta_tautan']    = (string) setting('HalamanDepan.peta_tautan');

        // Bilah menu atas (F10-07): nilai yang belum pernah disimpan berarti menyala.
        $bilahAtas = [];
        foreach (array_keys(MenuPublik::BILAH_ATAS) as $kunci) {
            $nilai             = setting('HalamanDepan.bilah_atas_' . $kunci);
            $bilahAtas[$kunci] = $nilai === null || (bool) $nilai;
        }

        return $this->tampilkan($isian, [], [], (bool) setting('HalamanDepan.tampilkan_biaya'), (bool) setting('HalamanDepan.hasil_di_atas'), $bilahAtas);
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
            'peta_embed'     => PetaLokasi::rapikanEmbed((string) $this->request->getPost('peta_embed')),
            'peta_tautan'    => trim((string) $this->request->getPost('peta_tautan')),
        ];

        // Kotak centang: tidak terkirim sama sekali bila tidak dicentang.
        $tampilkanBiaya = $this->request->getPost('tampilkan_biaya') === '1';
        $hasilDiAtas    = $this->request->getPost('hasil_di_atas') === '1';
        $bilahAtas      = [];
        foreach (array_keys(MenuPublik::BILAH_ATAS) as $kunci) {
            $bilahAtas[$kunci] = $this->request->getPost('bilah_atas_' . $kunci) === '1';
        }

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

        // Peta lokasi (F10-02): kosong boleh (bagian Lokasi atau tautannya tidak tampil).
        if ($isian['peta_embed'] !== '' && ! PetaLokasi::embedSah($isian['peta_embed'])) {
            $galat['peta_embed'] = PetaLokasi::GALAT_EMBED;
        }
        if ($isian['peta_tautan'] !== '' && ! PetaLokasi::tautanSah($isian['peta_tautan'])) {
            $galat['peta_tautan'] = PetaLokasi::GALAT_TAUTAN;
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
            return $this->tampilkan($isian, $galat, $dipilihSah, $tampilkanBiaya, $hasilDiAtas, $bilahAtas);
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

                return $this->tampilkan($isian, [$kunci => self::GAMBAR[$kunci]['label'] . ': ' . $e->getMessage()], [], $tampilkanBiaya, $hasilDiAtas, $bilahAtas);
            }
        }

        foreach (array_keys(self::BATAS_TEKS) as $kunci) {
            setting('HalamanDepan.' . $kunci, $isian[$kunci]);
        }

        setting('HalamanDepan.nomor_wa_tanya', $nomorWa);
        setting('HalamanDepan.tampilkan_biaya', $tampilkanBiaya);
        setting('HalamanDepan.hasil_di_atas', $hasilDiAtas);
        setting('HalamanDepan.peta_embed', $isian['peta_embed']);
        setting('HalamanDepan.peta_tautan', $isian['peta_tautan']);

        foreach ($bilahAtas as $kunci => $nyala) {
            setting('HalamanDepan.bilah_atas_' . $kunci, $nyala);
        }

        // Berkas lama dibuang setelah yang baru tersimpan. Gambar baru
        // mengalahkan centang "Hapus gambar" pada kotak yang sama.
        foreach (array_keys(self::GAMBAR) as $kunci) {
            $lama = (string) setting('HalamanDepan.' . $kunci);

            if (isset($jalurBaru[$kunci])) {
                setting('HalamanDepan.' . $kunci, $jalurBaru[$kunci]);
                VarianFoto::hapus($lama);
                $unggahan->hapus($lama);
            } elseif ($this->request->getPost('hapus_' . $kunci) === '1' && $lama !== '') {
                setting('HalamanDepan.' . $kunci, '');
                VarianFoto::hapus($lama);
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
     * @param bool                  $tampilkanBiaya         keadaan sakelar biaya di bagian lomba
     * @param bool                  $hasilDiAtas            keadaan sakelar hasil di atas (hari lomba, K143)
     * @param array<string, bool>   $bilahAtas              butir bilah menu atas yang dicentang (F10-07)
     */
    private function tampilkan(array $isian, array $galat = [], array $gambarPerluDipilihLagi = [], bool $tampilkanBiaya = true, bool $hasilDiAtas = false, array $bilahAtas = []): string
    {
        $unggahan = new Unggahan();
        $gambar   = [];

        foreach (self::GAMBAR as $kunci => $keterangan) {
            $jalur          = (string) setting('HalamanDepan.' . $kunci);
            $gambar[$kunci] = $keterangan + ['jalur' => $unggahan->ada($jalur) ? $jalur : ''];
        }

        // Foto carousel (F9-03, `CarouselController`); foto yang berkasnya hilang tetap tampil supaya bisa dihapus.
        $carousel = array_map(
            static fn (array $butir): array => $butir + ['ada' => $unggahan->ada($butir['berkas'])],
            CarouselHalamanDepan::tersimpan() ?? [],
        );

        return view('admin/pengaturan/halaman_depan', [
            'carousel'               => $carousel,
            'isian'                  => $isian,
            'galat'                  => $galat,
            'gambar'                 => $gambar,
            'gambarPerluDipilihLagi' => $gambarPerluDipilihLagi,
            'tampilkanBiaya'         => $tampilkanBiaya,
            'hasilDiAtas'            => $hasilDiAtas,
            'bilahAtas'              => $bilahAtas,
        ]);
    }
}
