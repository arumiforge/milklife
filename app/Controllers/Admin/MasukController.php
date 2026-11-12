<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Filters\AdminFilter;
use App\Libraries\SesiAdmin;
use App\Models\AdminModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Masuk dan keluar panel admin.
 */
class MasukController extends BaseController
{
    /**
     * Batas percobaan masuk per alamat IP dalam satu jendela waktu.
     */
    public const BATAS_PERCOBAAN = 5;

    /**
     * Lama jendela pembatasan percobaan masuk, dalam detik.
     */
    public const JENDELA_DETIK = 60;

    public const PESAN_GAGAL = 'Nama pengguna atau kata sandi salah. Periksa lagi lalu coba sekali lagi.';

    /**
     * Hash bcrypt dari nilai acak yang tidak diketahui siapa pun. Dipakai saat
     * nama pengguna tidak ditemukan, supaya pemeriksaan kata sandi tetap
     * dijalankan dan lama waktunya sama dengan nama pengguna yang ada.
     * Bukan kata sandi akun mana pun.
     */
    private const HASH_TIRUAN = '$2y$10$xLAATrC3TMrynDuoOA9/QO44TdACUlM4b0CXkNx6otaOBGHWsAlD6';

    /**
     * GET /admin/masuk
     */
    public function index(): string
    {
        $pesan = session()->getFlashdata('pesan_masuk');

        return $this->tampilkan(info: is_string($pesan) ? $pesan : null);
    }

    /**
     * POST /admin/masuk
     *
     * @return RedirectResponse|string
     */
    public function masuk()
    {
        $namaPengguna = (string) $this->request->getPost('nama_pengguna');
        $sandi        = (string) $this->request->getPost('sandi');

        // Setiap percobaan dihitung, berhasil atau gagal, sebelum kata sandi diperiksa.
        $pembatas = service('throttler');
        if (! $pembatas->check($this->kunciPembatas(), self::BATAS_PERCOBAAN, self::JENDELA_DETIK)) {
            $detik = max(1, $pembatas->getTokenTime());

            $this->response->setStatusCode(429);
            $this->response->setHeader('Retry-After', (string) $detik);

            return $this->tampilkan(
                galat: 'Terlalu banyak percobaan masuk. Tunggu ' . $detik . ' detik lagi, lalu coba masuk kembali.',
                namaPengguna: $namaPengguna,
            );
        }

        $validasi = service('validation');
        $validasi->setRules([
            'nama_pengguna' => [
                'label'  => 'Nama pengguna',
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama pengguna belum diisi. Tulis nama pengguna Anda.',
                    'max_length' => self::PESAN_GAGAL,
                ],
            ],
            'sandi' => [
                'label'  => 'Kata sandi',
                'rules'  => 'required|max_length[255]',
                'errors' => [
                    'required'   => 'Kata sandi belum diisi. Tulis kata sandi Anda.',
                    'max_length' => self::PESAN_GAGAL,
                ],
            ],
        ]);

        if (! $validasi->run(['nama_pengguna' => trim($namaPengguna), 'sandi' => $sandi])) {
            return $this->tampilkan(galat: implode(' ', $validasi->getErrors()), namaPengguna: $namaPengguna);
        }

        $model = model(AdminModel::class);
        $admin = $model->cariNamaPengguna($namaPengguna);
        $hash  = $admin['sandi'] ?? self::HASH_TIRUAN;
        $cocok = password_verify($sandi, $hash);

        if ($admin === null || ! $cocok) {
            return $this->tampilkan(galat: self::PESAN_GAGAL, namaPengguna: $namaPengguna);
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT) && $model->simpanSandi((int) $admin['id'], $sandi)) {
            // Sidik sesi harus dari hash yang baru saja disimpan.
            $admin['sandi'] = $model->sandiTersimpan((int) $admin['id']) ?? $hash;
        }

        $sesi   = session();
        $tujuan = AdminFilter::tujuanAman($sesi->get(AdminFilter::KUNCI_TUJUAN));

        $sesi->regenerate(true);
        $sesi->remove(AdminFilter::KUNCI_TUJUAN);
        SesiAdmin::mulai($admin);

        return redirect()->to($tujuan ?? 'admin/dasbor');
    }

    /**
     * POST /admin/keluar
     */
    public function keluar(): RedirectResponse
    {
        SesiAdmin::akhiri();

        return redirect()->to('admin/masuk')->with('pesan_masuk', 'Anda sudah keluar.');
    }

    private function tampilkan(?string $galat = null, ?string $info = null, string $namaPengguna = ''): string
    {
        return view('admin/masuk', [
            'galat'        => $galat,
            'info'         => $info,
            'namaPengguna' => $namaPengguna,
        ]);
    }

    /**
     * Kunci pembatas per alamat IP. Di-hash supaya tidak memuat karakter yang
     * tidak boleh dipakai sebagai kunci cache (misalnya `:` pada IPv6).
     */
    private function kunciPembatas(): string
    {
        return 'masuk_admin_' . hash('sha256', $this->request->getIPAddress());
    }
}
