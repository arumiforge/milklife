<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\Admin\AkunController;
use App\Controllers\BaseController;
use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\RiwayatAdmin;
use App\Libraries\SesiAdmin;
use App\Libraries\Unggahan;
use App\Models\AdminModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Pengaturan > Cadangan (F11-11, K174 butir 10–12): unduh dan impor
 * cadangan lengkap (`CadanganLengkap`). Menggantikan tombol "Backup data" di
 * Pendaftaran (K76, K77); alamat lama `admin/pendaftaran/ekspor` mengarah ke sini.
 *
 * - `GET  admin/pengaturan/cadangan`: halaman.
 * - `POST admin/pengaturan/cadangan/unduh`: zip dikirim langsung dari berkas
 *   sementara, tanpa dimuat ke memori (K118 B4), lalu dicatat di riwayat.
 * - `POST admin/pengaturan/cadangan/impor`: berkas zip dan kata sandi admin
 *   yang sedang masuk. Impor mengganti semua data dan pengaturan; data
 *   sekarang lebih dulu dicadangkan otomatis di server.
 * - `GET  admin/pengaturan/cadangan/<nama>/unduh`: unduh cadangan otomatis.
 *
 * Percobaan kata sandi dibatasi seperti halaman Akun (5 percobaan, lalu satu
 * tiap 12 detik).
 */
class CadanganController extends BaseController
{
    public const ALAMAT = 'admin/pengaturan/cadangan';

    public const PESAN_SANDI_SALAH = 'Kata sandi salah. Tulis kata sandi yang Anda pakai untuk masuk ke panel ini. Tidak ada yang berubah.';

    public function index(): string
    {
        return $this->tampilkan();
    }

    public function unduh(): ResponseInterface
    {
        $sementara = null;

        try {
            $sementara = PaketPendaftaran::jalurSementara();
            $hasil     = (new CadanganLengkap())->ekspor($sementara);
            (new RiwayatAdmin())->catat(null, RiwayatAdmin::TANPA_ID, RiwayatAdmin::UNDUH_CADANGAN, sprintf(
                'Cadangan diunduh: %d pendaftaran, %d atlet, %d baris data dari %d tabel, dan %d berkas unggahan.',
                $hasil['pendaftaran'],
                $hasil['atlet'],
                $hasil['baris'],
                $hasil['tabel'],
                $hasil['berkas'],
            ));
        } catch (RuntimeException $e) {
            if ($sementara !== null) {
                @unlink($sementara);
            }

            return redirect()->to(self::ALAMAT)->with('pesan_galat', $e->getMessage());
        }

        // Berkas sementara dihapus oleh unduhan berikutnya setelah 1 jam (`PaketPendaftaran::jalurSementara()`).
        return $this->response->download($sementara, null)
            ->setFileName('Cadangan_MilkLife_' . date('Ymd_His') . '.zip')
            ->setContentType('application/zip', '');
    }

    public function unduhOtomatis(string $nama): ResponseInterface
    {
        $jalur = CadanganLengkap::jalurOtomatis($nama);

        if ($jalur === null) {
            throw PageNotFoundException::forPageNotFound('Cadangan otomatis itu tidak ditemukan. Mungkin sudah terhapus karena lebih lama dari ' . CadanganLengkap::SIMPAN_OTOMATIS . ' cadangan terakhir.');
        }

        return $this->response->download($jalur, null)->setFileName($nama)->setContentType('application/zip', '');
    }

    /**
     * @return ResponseInterface|string
     */
    public function impor()
    {
        $idAdmin = (int) session(SesiAdmin::KUNCI_ID);
        $sandi   = (string) $this->request->getPost('sandi');
        $berkas  = $this->request->getFile('berkas');
        $galat   = [];

        if (! Unggahan::dipilih($berkas)) {
            $galat['berkas'] = 'Pilih berkas cadangan (.zip) yang akan diimpor.';
        } elseif ($berkas->getError() !== UPLOAD_ERR_OK) {
            $galat['berkas'] = $berkas->getError() === UPLOAD_ERR_INI_SIZE || $berkas->getError() === UPLOAD_ERR_FORM_SIZE
                ? 'Berkas lebih besar dari batas unggah server (' . Unggahan::ukuranTeks(self::batasUnggah()) . '). Pakai perintah server yang dijelaskan di halaman ini.'
                : 'Berkas gagal terunggah. Coba lagi; bila tetap gagal, pakai perintah server yang dijelaskan di halaman ini.';
        } elseif (strtolower($berkas->getClientExtension()) !== 'zip') {
            $galat['berkas'] = 'Berkas cadangan harus berakhiran .zip, seperti yang diunduh dari halaman ini.';
        }

        if ($sandi === '') {
            $galat['sandi'] = 'Tulis kata sandi Anda untuk memastikan impor ini memang Anda yang meminta.';
        } else {
            $tunggu = $this->tungguPembatas($idAdmin);
            $hash   = model(AdminModel::class)->sandiTersimpan($idAdmin);

            if ($tunggu !== null) {
                $galat['sandi'] = 'Terlalu banyak percobaan kata sandi. Tunggu ' . $tunggu . ' detik, lalu coba lagi.';
            } elseif ($hash === null || strlen($sandi) > 255 || ! password_verify($sandi, $hash)) {
                $galat['sandi'] = self::PESAN_SANDI_SALAH;
            }
        }

        if ($galat !== []) {
            return $this->tampilkan($galat);
        }

        $folder = CadanganLengkap::folderOtomatis();
        $sumber = $folder . DIRECTORY_SEPARATOR . 'Impor_' . bin2hex(random_bytes(8)) . '.zip';

        $sementara = $berkas->getTempName();
        // Uji tidak bisa membuat unggahan HTTP sungguhan; selama folder cadangan diganti uji, salinan biasa diterima.
        $tersimpan = (is_dir($folder) || @mkdir($folder, 0700, true) || is_dir($folder))
            && (is_uploaded_file($sementara) ? @move_uploaded_file($sementara, $sumber) : (CadanganLengkap::folderDiganti() && @copy($sementara, $sumber)));

        if (! $tersimpan) {
            return $this->tampilkan(['berkas' => 'Berkas tidak bisa disimpan sementara di ' . $folder . '. Minta pengelola server memeriksa izin tulis folder writable. Tidak ada yang berubah.']);
        }

        // Impor besar bisa lebih lama dari batas waktu biasa; jangan berhenti di tengah bila peramban ditutup.
        ignore_user_abort(true);
        @set_time_limit(0);

        try {
            $hasil = (new CadanganLengkap())->impor($sumber);
        } catch (RuntimeException $e) {
            return $this->tampilkan(['berkas' => $e->getMessage()]);
        } finally {
            @unlink($sumber);
        }

        $otomatis = basename((string) $hasil['cadangan_otomatis']);
        (new RiwayatAdmin())->catat(null, RiwayatAdmin::TANPA_ID, RiwayatAdmin::IMPOR_CADANGAN, sprintf(
            'Cadangan %s diimpor: semua data dan pengaturan diganti (%d baris data dari %d tabel, %d berkas unggahan). Data sebelumnya disimpan sebagai %s.',
            mb_substr($berkas->getClientName(), 0, 150),
            $hasil['baris'],
            $hasil['tabel'],
            $hasil['berkas'],
            $otomatis,
        ));

        $pesan = 'Cadangan berhasil diimpor. Semua data dan pengaturan sekarang sama dengan isi cadangan. Data sebelumnya disimpan sebagai ' . $otomatis . ' di daftar cadangan otomatis.';

        if ($hasil['peringatan'] !== []) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', $pesan . ' ' . implode(' ', $hasil['peringatan']));
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', $pesan);
    }

    /**
     * Batas unggah server (bita): yang terkecil dari `upload_max_filesize` dan `post_max_size`.
     */
    public static function batasUnggah(): int
    {
        $batas = array_filter([
            self::bita((string) ini_get('upload_max_filesize')),
            self::bita((string) ini_get('post_max_size')),
        ], static fn (int $b): bool => $b > 0);

        return $batas === [] ? 0 : min($batas);
    }

    /**
     * Ubah tulisan ukuran php.ini (`8M`, `2G`, `512K`) menjadi bita.
     */
    public static function bita(string $nilai): int
    {
        $nilai = trim($nilai);

        if (preg_match('/^(\d+)\s*([KMG]?)/i', $nilai, $cocok) !== 1) {
            return 0;
        }

        return (int) $cocok[1] * match (strtoupper($cocok[2])) {
            'G'     => 1024 ** 3,
            'M'     => 1024 ** 2,
            'K'     => 1024,
            default => 1,
        };
    }

    private function tungguPembatas(int $idAdmin): ?int
    {
        $pembatas = service('throttler');

        if ($pembatas->check('sandi_cadangan_' . $idAdmin, AkunController::BATAS_PERCOBAAN, AkunController::JENDELA_DETIK)) {
            return null;
        }

        return max(1, $pembatas->getTokenTime());
    }

    /**
     * @param array<string, string> $galat
     */
    private function tampilkan(array $galat = []): string
    {
        return view('admin/pengaturan/cadangan', [
            'galat'    => $galat,
            'batas'    => self::batasUnggah(),
            'otomatis' => CadanganLengkap::daftarOtomatis(),
            'riwayat'  => (new RiwayatAdmin())->untukCadangan(),
            'isi'      => [
                'pendaftaran' => db_connect()->table('pendaftaran')->countAllResults(),
                'atlet'       => db_connect()->table('atlet')->countAllResults(),
            ],
        ]);
    }
}
