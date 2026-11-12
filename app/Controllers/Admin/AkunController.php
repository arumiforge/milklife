<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman Akun: admin yang sedang masuk mengganti nama, nama pengguna, dan
 * kata sandinya sendiri (K26). Akun yang diubah selalu `admin_id` di sesi;
 * tidak ada ID di alamat atau formulir.
 *
 * - `GET admin/akun` → `index`: dua formulir di satu halaman.
 * - `POST admin/akun` → `simpan`: nama dan nama pengguna. Kata sandi saat ini
 *   wajib bila nama pengguna diganti; mengganti nama tampilan saja tidak.
 * - `POST admin/akun/sandi` → `simpanSandi`: kata sandi saat ini, kata sandi
 *   baru, dan ulangannya. Setelah berhasil, ID sesi dibuat ulang dan admin
 *   tetap masuk.
 *
 * Percobaan yang memeriksa kata sandi saat ini dibatasi per akun, seperti
 * halaman masuk: 5 percobaan, lalu satu percobaan baru tiap 12 detik.
 */
class AkunController extends BaseController
{
    /**
     * Batas percobaan yang memeriksa kata sandi saat ini, per akun.
     */
    public const BATAS_PERCOBAAN = 5;

    /**
     * Lama jendela pembatasan, dalam detik.
     */
    public const JENDELA_DETIK = 60;

    /**
     * Isian kata sandi lebih panjang dari ini langsung dianggap salah, tanpa
     * diperiksa (sama dengan batas di halaman masuk).
     */
    private const PANJANG_SANDI_DIPERIKSA = 255;

    private const ALAMAT = 'admin/akun';

    public const PESAN_SANDI_SALAH = 'Kata sandi saat ini salah. Tulis kata sandi yang Anda pakai untuk masuk ke panel ini.';

    /**
     * GET /admin/akun
     */
    public function index(): ResponseInterface|string
    {
        $admin = $this->adminMasuk();
        if ($admin === null) {
            return $this->akunTidakDitemukan();
        }

        return $this->tampilkan($admin);
    }

    /**
     * POST /admin/akun — nama dan nama pengguna.
     */
    public function simpan(): ResponseInterface|string
    {
        $admin = $this->adminMasuk();
        if ($admin === null) {
            return $this->akunTidakDitemukan();
        }

        $model = model(AdminModel::class);
        $isian = [
            'nama'          => trim((string) $this->request->getPost('nama')),
            'nama_pengguna' => AdminModel::normalkanNamaPengguna((string) $this->request->getPost('nama_pengguna')),
        ];
        $sandi = (string) $this->request->getPost('sandi_akun');

        $galat = $model->periksaIsian($isian, false, (int) $admin['id']);

        // Nama pengguna dipakai untuk masuk, jadi menggantinya perlu kata
        // sandi saat ini. Nama tampilan saja boleh diganti tanpa kata sandi.
        if ($isian['nama_pengguna'] !== $admin['nama_pengguna']) {
            $tunggu = $this->tungguPembatas((int) $admin['id']);
            if ($tunggu !== null) {
                $this->response->setStatusCode(429);
                $this->response->setHeader('Retry-After', (string) $tunggu);

                return $this->tampilkan($admin, isianAkun: $isian, galatAkun: $galat + [
                    'sandi_akun' => self::pesanTerlaluBanyak($tunggu),
                ]);
            }

            $pesanSandi = $this->periksaSandiSaatIni(
                $sandi,
                (string) $admin['sandi'],
                'Kata sandi saat ini belum diisi. Untuk mengganti nama pengguna, tulis kata sandi yang Anda pakai untuk masuk.',
            );
            if ($pesanSandi !== null) {
                $galat['sandi_akun'] = $pesanSandi;
            }
        }

        if ($galat !== []) {
            return $this->tampilkan($admin, isianAkun: $isian, galatAkun: $galat);
        }

        if (! $model->update((int) $admin['id'], $isian)) {
            // Misalnya nama pengguna baru saja dipakai admin lain.
            return $this->tampilkan($admin, isianAkun: $isian, galatAkun: $model->errors());
        }

        session()->set('admin_nama', $isian['nama']);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Data akun tersimpan.');
    }

    /**
     * POST /admin/akun/sandi — ganti kata sandi.
     */
    public function simpanSandi(): ResponseInterface|string
    {
        $admin = $this->adminMasuk();
        if ($admin === null) {
            return $this->akunTidakDitemukan();
        }

        $saatIni = (string) $this->request->getPost('sandi_saat_ini');
        $baru    = (string) $this->request->getPost('sandi_baru');
        $ulangi  = (string) $this->request->getPost('ulangi_sandi');

        // Setiap percobaan dihitung sebelum kata sandi diperiksa.
        $tunggu = $this->tungguPembatas((int) $admin['id']);
        if ($tunggu !== null) {
            $this->response->setStatusCode(429);
            $this->response->setHeader('Retry-After', (string) $tunggu);

            return $this->tampilkan($admin, galatSandi: ['sandi_saat_ini' => self::pesanTerlaluBanyak($tunggu)]);
        }

        $galat = [];

        $pesanSandi = $this->periksaSandiSaatIni(
            $saatIni,
            (string) $admin['sandi'],
            'Kata sandi saat ini belum diisi. Tulis kata sandi yang Anda pakai untuk masuk.',
        );
        if ($pesanSandi !== null) {
            $galat['sandi_saat_ini'] = $pesanSandi;
        }

        if ($baru === '') {
            $galat['sandi_baru'] = 'Kata sandi baru belum diisi. Buat kata sandi baru paling sedikit ' . AdminModel::SANDI_MINIMAL . ' karakter.';
        } else {
            $galatModel = model(AdminModel::class)->periksaIsian(['sandi' => $baru]);
            if (isset($galatModel['sandi'])) {
                $galat['sandi_baru'] = $galatModel['sandi'];
            } elseif ($pesanSandi === null && $baru === $saatIni) {
                $galat['sandi_baru'] = 'Kata sandi baru sama dengan kata sandi saat ini. Buat kata sandi yang berbeda.';
            }
        }

        if ($ulangi === '') {
            $galat['ulangi_sandi'] = 'Ulangi kata sandi baru belum diisi. Ketik sekali lagi kata sandi baru yang sama.';
        } elseif ($baru !== '' && $ulangi !== $baru) {
            $galat['ulangi_sandi'] = 'Ulangan kata sandi tidak sama dengan kata sandi baru. Ketik ulang keduanya dengan teliti.';
        }

        if ($galat !== []) {
            return $this->tampilkan($admin, galatSandi: $galat);
        }

        $model = model(AdminModel::class);
        if (! $model->simpanSandi((int) $admin['id'], $baru)) {
            return $this->tampilkan($admin, galatSandi: ['sandi_baru' => (string) ($model->errors()['sandi'] ?? 'Kata sandi belum bisa disimpan. Coba sekali lagi.')]);
        }

        // ID sesi baru, isi sesi (admin tetap masuk) dipertahankan.
        session()->regenerate(true);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Kata sandi sudah diganti. Pakai kata sandi baru saat masuk berikutnya.');
    }

    /**
     * Admin yang sedang masuk, atau `null` bila akunnya sudah tidak ada.
     *
     * @return array<string, mixed>|null
     */
    private function adminMasuk(): ?array
    {
        $id = session('admin_id');

        if (! is_int($id) || $id < 1) {
            return null;
        }

        return model(AdminModel::class)->find($id);
    }

    /**
     * Akun di sesi sudah dihapus (lewat basis data): keluarkan, lalu ke
     * halaman masuk dengan penjelasan.
     */
    private function akunTidakDitemukan(): ResponseInterface
    {
        $sesi = session();
        $sesi->remove(array_keys($_SESSION ?? []));
        $sesi->regenerate(true);

        return redirect()->to('admin/masuk')->with(
            'pesan_masuk',
            'Akun Anda tidak ditemukan lagi, jadi Anda dikeluarkan. Masuk dengan akun lain atau hubungi pengelola web.',
        );
    }

    /**
     * Pesan galat untuk kata sandi saat ini, atau `null` bila benar.
     */
    private function periksaSandiSaatIni(string $sandi, string $hash, string $pesanKosong): ?string
    {
        if ($sandi === '') {
            return $pesanKosong;
        }

        if (strlen($sandi) > self::PANJANG_SANDI_DIPERIKSA || ! password_verify($sandi, $hash)) {
            return self::PESAN_SANDI_SALAH;
        }

        return null;
    }

    /**
     * Catat satu percobaan. Mengembalikan lama tunggu (detik) bila batas
     * sudah terlampaui, atau `null` bila percobaan boleh dilanjutkan.
     */
    private function tungguPembatas(int $idAdmin): ?int
    {
        $pembatas = service('throttler');

        if ($pembatas->check('sandi_akun_' . $idAdmin, self::BATAS_PERCOBAAN, self::JENDELA_DETIK)) {
            return null;
        }

        return max(1, $pembatas->getTokenTime());
    }

    private static function pesanTerlaluBanyak(int $detik): string
    {
        return 'Terlalu banyak percobaan kata sandi. Tunggu ' . $detik . ' detik lagi, lalu coba sekali lagi.';
    }

    /**
     * @param array<string, mixed>       $admin
     * @param array<string, string>|null $isianAkun  isian nama dan nama pengguna yang tadi diketik
     * @param array<string, string>      $galatAkun
     * @param array<string, string>      $galatSandi
     */
    private function tampilkan(array $admin, ?array $isianAkun = null, array $galatAkun = [], array $galatSandi = []): string
    {
        return view('admin/akun', [
            'admin'      => $admin,
            'isianAkun'  => $isianAkun ?? [
                'nama'          => (string) $admin['nama'],
                'nama_pengguna' => (string) $admin['nama_pengguna'],
            ],
            'galatAkun'  => $galatAkun,
            'galatSandi' => $galatSandi,
        ]);
    }
}
