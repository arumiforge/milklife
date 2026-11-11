<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\NomorWa;
use App\Libraries\Unggahan;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Pembayaran;
use RuntimeException;

/**
 * Pengaturan pembayaran: `GET` dan `POST /admin/pengaturan/pembayaran`.
 *
 * Rekening tujuan transfer, nomor WA pemilik rekening untuk konfirmasi, logo
 * bank, dan templat pesan konfirmasi (rancangan 7, langkah 5). Kunci
 * pengaturan `Pembayaran.*` sesuai rencana Tahap 2.
 *
 * - Bank, nomor rekening, pemilik, dan nomor WA boleh disimpan kosong; yang
 *   kosong ditandai belum lengkap di dasbor (F2-09) dan pendaftaran belum bisa
 *   dibuka. Yang diisi harus benar.
 * - Nomor rekening disimpan hanya angka; spasi, titik, dan tanda hubung yang
 *   ikut diketik dibuang.
 * - Nomor WA disimpan dalam bentuk baku `628…` (`NomorWa::normalkan()`).
 * - Templat pesan wajib diisi dan hanya boleh memakai penanda di
 *   `Config\Pembayaran::PENANDA`. Halaman menampilkan contoh hasilnya dengan
 *   data karangan (`contohPesan()`).
 * - Logo bank diunggah lewat `App\Libraries\Unggahan` (folder `pembayaran`);
 *   berkas lama dihapus setelah yang baru tersimpan, atau saat admin
 *   mencentang "Hapus logo".
 */
class PembayaranController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/pembayaran';

    private const FOLDER_UNGGAHAN = 'pembayaran';

    /**
     * Data karangan untuk contoh hasil pesan. Bukan data sekolah sungguhan.
     *
     * @var array<string, int|string>
     */
    public const DATA_CONTOH = [
        '{nama_sekolah}'      => 'SD Negeri Contoh 1',
        '{npsn}'              => '12345678',
        '{id_pendaftaran}'    => 'MAC2A0000126',
        '{subtotal_individu}' => 300000,
        '{subtotal_beregu}'   => 200000,
        '{total}'             => 500000,
    ];

    /**
     * Penanda yang berisi uang; di contoh ditulis dengan `rupiah()`.
     */
    private const PENANDA_UANG = ['{subtotal_individu}', '{subtotal_beregu}', '{total}'];

    public function index(): string
    {
        $nomorWa = (string) setting('Pembayaran.nomor_wa');

        return $this->tampilkan([
            'bank'             => (string) setting('Pembayaran.bank'),
            'nomor_rekening'   => (string) setting('Pembayaran.nomor_rekening'),
            'pemilik_rekening' => (string) setting('Pembayaran.pemilik_rekening'),
            'nomor_wa'         => $nomorWa === '' ? '' : NomorWa::tampilan($nomorWa),
            'templat_pesan'    => (string) setting('Pembayaran.templat_pesan'),
        ]);
    }

    public function simpan(): ResponseInterface|string
    {
        $isian = [
            'bank'             => trim((string) $this->request->getPost('bank')),
            'nomor_rekening'   => trim((string) $this->request->getPost('nomor_rekening')),
            'pemilik_rekening' => trim((string) $this->request->getPost('pemilik_rekening')),
            'nomor_wa'         => trim((string) $this->request->getPost('nomor_wa')),
            'templat_pesan'    => self::rapikanTeks((string) $this->request->getPost('templat_pesan')),
        ];

        $aturan = [
            'bank' => [
                'rules'  => 'permit_empty|max_length[50]',
                'errors' => ['max_length' => 'Nama bank terlalu panjang. Persingkat menjadi paling banyak 50 huruf, misalnya BRI atau Bank Jateng.'],
            ],
            'pemilik_rekening' => [
                'rules'  => 'permit_empty|max_length[100]',
                'errors' => ['max_length' => 'Nama pemilik rekening terlalu panjang. Persingkat menjadi paling banyak 100 huruf.'],
            ],
            'templat_pesan' => [
                'rules'  => 'required|max_length[2000]',
                'errors' => [
                    'required'   => 'Isi pesan konfirmasi belum diisi. Tulis pesannya, atau tekan "Pakai pesan awal" untuk memakai contoh yang sudah disiapkan.',
                    'max_length' => 'Isi pesan konfirmasi terlalu panjang. Persingkat menjadi paling banyak 2.000 huruf.',
                ],
            ],
        ];

        $galat = $this->validateData($isian, $aturan) ? [] : $this->validator->getErrors();

        $nomorRekening = '';
        if ($isian['nomor_rekening'] !== '') {
            $nomorRekening = (string) preg_replace('/[\s.\-]+/', '', $isian['nomor_rekening']);

            if (preg_match('/^\d{5,30}$/', $nomorRekening) !== 1) {
                $galat['nomor_rekening'] = 'Nomor rekening hanya boleh berisi angka, 5 sampai 30 angka. Spasi, titik, dan tanda hubung boleh ikut diketik; huruf tidak.';
            }
        }

        $nomorWa = '';
        if ($isian['nomor_wa'] !== '') {
            $nomorWa = (string) NomorWa::normalkan($isian['nomor_wa']);

            if ($nomorWa === '') {
                $galat['nomor_wa'] = NomorWa::PESAN_GALAT;
            }
        }

        if (! isset($galat['templat_pesan'])) {
            $tidakDikenal = self::penandaTidakDikenal($isian['templat_pesan']);

            if ($tidakDikenal !== []) {
                $galat['templat_pesan'] = 'Penanda ' . implode(', ', $tidakDikenal) . ' tidak dikenal. '
                    . 'Pakai penanda dari daftar di samping kotak pesan, ditulis persis sama termasuk kurung kurawalnya, '
                    . 'atau hapus penanda itu.';
            }
        }

        $unggahan  = new Unggahan();
        $logo      = $this->request->getFile('logo_bank');
        $logoBaru  = Unggahan::dipilih($logo);
        $hapusLogo = $this->request->getPost('hapus_logo_bank') === '1';

        if ($logoBaru) {
            $pesan = $unggahan->periksaGambar($logo);

            if ($pesan !== null) {
                $galat['logo_bank'] = $pesan;
            }
        }

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat, $logoBaru && ! isset($galat['logo_bank']));
        }

        $logoLama = (string) setting('Pembayaran.logo_bank');

        if ($logoBaru) {
            try {
                $jalur = $unggahan->simpanGambar($logo, self::FOLDER_UNGGAHAN);
            } catch (RuntimeException $e) {
                return $this->tampilkan($isian, ['logo_bank' => $e->getMessage()]);
            }
        }

        setting('Pembayaran.bank', $isian['bank']);
        setting('Pembayaran.nomor_rekening', $nomorRekening);
        setting('Pembayaran.pemilik_rekening', $isian['pemilik_rekening']);
        setting('Pembayaran.nomor_wa', $nomorWa);
        setting('Pembayaran.templat_pesan', $isian['templat_pesan']);

        // Berkas lama dibuang setelah yang baru tersimpan. Bila logo baru
        // dipilih, centang "Hapus logo" diabaikan karena logo diganti.
        if ($logoBaru) {
            setting('Pembayaran.logo_bank', $jalur);
            $unggahan->hapus($logoLama);
        } elseif ($hapusLogo && $logoLama !== '') {
            setting('Pembayaran.logo_bank', '');
            $unggahan->hapus($logoLama);
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Pengaturan pembayaran tersimpan.');
    }

    /**
     * Hasil templat pesan bila diisi data karangan `DATA_CONTOH`.
     */
    public static function contohPesan(string $templat): string
    {
        return strtr($templat, self::dataContohTeks());
    }

    /**
     * `DATA_CONTOH` dalam bentuk yang tampil di pesan (uang dengan `rupiah()`).
     *
     * @return array<string, string>
     */
    public static function dataContohTeks(): array
    {
        $hasil = [];

        foreach (self::DATA_CONTOH as $penanda => $nilai) {
            $hasil[$penanda] = in_array($penanda, self::PENANDA_UANG, true) ? rupiah((int) $nilai) : (string) $nilai;
        }

        return $hasil;
    }

    /**
     * Penanda berbentuk `{kata}` di templat yang tidak ada di `PENANDA`, tanpa kembar.
     *
     * @return list<string>
     */
    public static function penandaTidakDikenal(string $templat): array
    {
        preg_match_all('/\{[^{}\n]{1,40}\}/u', $templat, $cocok);

        return array_values(array_unique(array_filter(
            $cocok[0],
            static fn (string $penanda): bool => ! array_key_exists($penanda, Pembayaran::PENANDA),
        )));
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
     */
    private function tampilkan(array $isian, array $galat = [], bool $logoPerluDipilihLagi = false): string
    {
        $logo     = (string) setting('Pembayaran.logo_bank');
        $nomorWa  = (string) setting('Pembayaran.nomor_wa');

        return view('admin/pengaturan/pembayaran', [
            'isian'                => $isian,
            'galat'                => $galat,
            'logo'                 => (new Unggahan())->ada($logo) ? $logo : '',
            'logoPerluDipilihLagi' => $logoPerluDipilihLagi,
            'penanda'              => Pembayaran::PENANDA,
            'dataContoh'           => self::dataContohTeks(),
            'contohPesan'          => self::contohPesan($isian['templat_pesan']),
            'templatAwal'          => (new Pembayaran())->templat_pesan,
            'nomorWaTersimpan'     => $nomorWa,
        ]);
    }
}
