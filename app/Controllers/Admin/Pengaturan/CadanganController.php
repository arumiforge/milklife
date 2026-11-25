<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\Admin\AkunController;
use App\Controllers\BaseController;
use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Cadangan\SalinanR2;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\RiwayatAdmin;
use App\Libraries\SesiAdmin;
use App\Libraries\Unggahan;
use App\Models\AdminModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
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
 * - `POST admin/pengaturan/cadangan/r2`: simpan pengaturan salinan Cloudflare
 *   R2 (F11-21, K187) dengan kata sandi admin. Disimpan hanya bila
 *   sambungannya lulus diperiksa (berkas uji ditulis, isi folder terbaca).
 * - `POST admin/pengaturan/cadangan/r2/periksa`: periksa lagi sambungan tersimpan.
 * - `POST admin/pengaturan/cadangan/r2/hapus`: matikan salinan, dengan kata sandi.
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

        $galatSandi = $this->periksaSandi($idAdmin, $sandi, 'Tulis kata sandi Anda untuk memastikan impor ini memang Anda yang meminta.');

        if ($galatSandi !== null) {
            $galat['sandi'] = $galatSandi;
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
     * Simpan pengaturan salinan R2 setelah sambungannya lulus diperiksa.
     *
     * @return RedirectResponse|string
     */
    public function simpanR2()
    {
        $lama  = SalinanR2::pengaturan();
        $isian = [
            'id_akun'  => SalinanR2::rapikanIdAkun((string) $this->request->getPost('id_akun')),
            'bucket'   => strtolower(trim((string) $this->request->getPost('bucket'))),
            'kunci_id' => trim((string) $this->request->getPost('kunci_id')),
            'folder'   => strtolower(trim((string) $this->request->getPost('folder'), " \t\n\r\0\x0B/")),
        ];
        $rahasia = trim((string) $this->request->getPost('rahasia'));
        $galat   = [];

        if (preg_match(SalinanR2::POLA_ID_AKUN, $isian['id_akun']) !== 1) {
            $galat['id_akun'] = 'ID akun berupa 32 huruf dan angka, misalnya 0123456789abcdef0123456789abcdef. Salin dari dasbor Cloudflare > R2, atau tempel alamat S3 API bucket.';
        }

        if (preg_match(SalinanR2::POLA_BUCKET, $isian['bucket']) !== 1) {
            $galat['bucket'] = 'Nama bucket 3–63 huruf kecil, angka, atau tanda hubung, persis seperti di dasbor Cloudflare > R2.';
        }

        if (preg_match(SalinanR2::POLA_KUNCI_ID, $isian['kunci_id']) !== 1) {
            $galat['kunci_id'] = 'Access Key ID berupa huruf dan angka tanpa spasi. Salin dari halaman token R2.';
        }

        if ($rahasia === '' && $lama === null) {
            $galat['rahasia'] = 'Tempel Secret Access Key dari halaman token R2.';
        } elseif ($rahasia !== '' && preg_match(SalinanR2::POLA_RAHASIA, $rahasia) !== 1) {
            $galat['rahasia'] = 'Secret Access Key berupa huruf dan angka tanpa spasi. Salin ulang dari halaman token R2.';
        }

        if ($isian['folder'] === '') {
            $isian['folder'] = SalinanR2::folderBawaan();
        } elseif (preg_match(SalinanR2::POLA_FOLDER, $isian['folder']) !== 1) {
            $galat['folder'] = 'Folder 1–63 huruf kecil, angka, titik, garis bawah, atau tanda hubung, tanpa garis miring, misalnya ' . SalinanR2::folderBawaan() . '.';
        }

        $galatSandi = $this->periksaSandi((int) session(SesiAdmin::KUNCI_ID), (string) $this->request->getPost('sandi'), 'Tulis kata sandi Anda untuk memastikan perubahan ini memang Anda yang meminta.');

        if ($galatSandi !== null) {
            $galat['sandi_r2'] = $galatSandi;
        }

        if ($galat !== []) {
            return $this->tampilkan([], $galat, $isian);
        }

        try {
            // Secret kosong = pakai yang sudah tersimpan.
            $hasil = SalinanR2::periksa(SalinanR2::klien($isian, $rahasia === '' ? null : $rahasia), $isian['folder']);
            SalinanR2::simpan($isian, $rahasia === '' ? null : $rahasia);
        } catch (RuntimeException $e) {
            return $this->tampilkan([], ['r2' => 'Pengaturan belum disimpan. ' . $e->getMessage()], $isian);
        }

        (new RiwayatAdmin())->catat(null, RiwayatAdmin::TANPA_ID, RiwayatAdmin::ATUR_R2, sprintf(
            'Salinan Cloudflare R2 %s: bucket %s, folder %s, Access Key ID %s%s.',
            $lama === null ? 'diatur' : 'diubah',
            $isian['bucket'],
            $isian['folder'],
            self::samarkan($isian['kunci_id']),
            $rahasia === '' ? '' : ', Secret Access Key baru',
        ));

        return redirect()->to(self::ALAMAT . '#salinan-r2')->with('pesan_sukses', sprintf(
            'Tersambung ke bucket %s dan pengaturan disimpan. Folder %s sekarang berisi %d berkas. Cadangan harian berikutnya ikut dikirim ke sana.',
            $isian['bucket'],
            $isian['folder'],
            count($hasil['berkas']),
        ));
    }

    public function periksaR2(): RedirectResponse
    {
        $pengaturan = SalinanR2::pengaturan();

        if ($pengaturan === null) {
            return redirect()->to(self::ALAMAT . '#salinan-r2')->with('pesan_galat', 'Salinan ke Cloudflare R2 belum diatur.');
        }

        try {
            $hasil = SalinanR2::periksa(SalinanR2::klien(), $pengaturan['folder']);
        } catch (RuntimeException $e) {
            return redirect()->to(self::ALAMAT . '#salinan-r2')->with('pesan_galat', 'Sambungan ke Cloudflare R2 gagal. ' . $e->getMessage());
        }

        return redirect()->to(self::ALAMAT . '#salinan-r2')->with('pesan_sukses', sprintf(
            'Sambungan ke bucket %s baik: berkas uji tertulis dan folder %s berisi %d berkas.',
            $pengaturan['bucket'],
            $pengaturan['folder'],
            count($hasil['berkas']),
        ));
    }

    /**
     * @return RedirectResponse|string
     */
    public function hapusR2()
    {
        $pengaturan = SalinanR2::pengaturan();
        $galatSandi = $this->periksaSandi((int) session(SesiAdmin::KUNCI_ID), (string) $this->request->getPost('sandi'), 'Tulis kata sandi Anda untuk memastikan salinan memang Anda yang mematikan.');

        if ($pengaturan === null) {
            return redirect()->to(self::ALAMAT . '#salinan-r2');
        }

        if ($galatSandi !== null) {
            return $this->tampilkan([], ['sandi_hapus_r2' => $galatSandi]);
        }

        SalinanR2::hapus();
        (new RiwayatAdmin())->catat(null, RiwayatAdmin::TANPA_ID, RiwayatAdmin::ATUR_R2, sprintf(
            'Salinan Cloudflare R2 dimatikan (bucket %s, folder %s). Berkas di bucket tidak dihapus.',
            $pengaturan['bucket'],
            $pengaturan['folder'],
        ));

        return redirect()->to(self::ALAMAT . '#salinan-r2')->with('pesan_sukses', 'Salinan ke Cloudflare R2 dimatikan. Berkas yang sudah ada di bucket tidak dihapus; cadangan harian tetap dibuat di server.');
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

    /**
     * Pesan galat kata sandi admin yang sedang masuk, atau `null` bila benar.
     * Percobaan dibatasi bersama untuk semua formulir di halaman ini.
     */
    private function periksaSandi(int $idAdmin, string $sandi, string $pesanKosong): ?string
    {
        if ($sandi === '') {
            return $pesanKosong;
        }

        $tunggu = $this->tungguPembatas($idAdmin);

        if ($tunggu !== null) {
            return 'Terlalu banyak percobaan kata sandi. Tunggu ' . $tunggu . ' detik, lalu coba lagi.';
        }

        $hash = model(AdminModel::class)->sandiTersimpan($idAdmin);

        return $hash === null || strlen($sandi) > 255 || ! password_verify($sandi, $hash) ? self::PESAN_SANDI_SALAH : null;
    }

    /**
     * Access Key ID untuk riwayat: hanya 4 huruf awal dan akhir.
     */
    private static function samarkan(string $kunci): string
    {
        return strlen($kunci) <= 8 ? str_repeat('•', strlen($kunci)) : substr($kunci, 0, 4) . '…' . substr($kunci, -4);
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
     * @param array<string, string>      $galat   Pesan per kotak formulir impor.
     * @param array<string, string>      $galatR2 Pesan per kotak formulir R2 (`r2` = pesan umum).
     * @param array<string, string>|null $isianR2 Isian formulir R2 yang dikirim ulang.
     */
    private function tampilkan(array $galat = [], array $galatR2 = [], ?array $isianR2 = null): string
    {
        $r2         = SalinanR2::pengaturan();
        $isiR2      = null;
        $galatIsiR2 = null;

        if ($r2 !== null) {
            try {
                $isiR2 = SalinanR2::isiBucket();
            } catch (RuntimeException $e) {
                $galatIsiR2 = $e->getMessage();
            }
        }

        return view('admin/pengaturan/cadangan', [
            'galat'    => $galat,
            'r2'       => $r2,
            'galatR2'  => $galatR2,
            'isianR2'  => $isianR2 ?? [
                'id_akun'  => $r2['id_akun'] ?? '',
                'bucket'   => $r2['bucket'] ?? '',
                'kunci_id' => $r2['kunci_id'] ?? '',
                'folder'   => $r2['folder'] ?? SalinanR2::folderBawaan(),
            ],
            'isiR2'      => $isiR2,
            'galatIsiR2' => $galatIsiR2,
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
