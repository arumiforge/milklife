<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Panel\UrutkanNomorDada;
use App\Libraries\SesiAdmin;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Urutkan ulang nomor dada per sekolah (K111): `admin/atlet/nomor-dada`.
 *
 * - `index`: ringkasan atlet Lunas per kelompok umur, waktu terakhir diurutkan,
 *   dan formulir dengan isian "Ketik URUTKAN" serta jendela konfirmasi.
 * - `urutkan`: membuat ulang semua nomor dada (`UrutkanNomorDada`), lalu
 *   kembali ke halaman ini dengan pesan.
 */
class NomorDadaController extends BaseController
{
    /**
     * Kata yang harus diketik admin sebelum nomor dada diurutkan ulang.
     */
    public const KATA_KONFIRMASI = 'URUTKAN';

    private const ALAMAT = 'admin/atlet/nomor-dada';

    public function index(): string
    {
        return view('admin/atlet/nomor_dada', [
            'ringkasan' => (new UrutkanNomorDada())->ringkasan(),
        ]);
    }

    public function urutkan(): RedirectResponse
    {
        if (strtoupper(trim((string) $this->request->getPost('konfirmasi'))) !== self::KATA_KONFIRMASI) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Nomor atlet (BIB) belum diurutkan. Ketik ' . self::KATA_KONFIRMASI . ' di kotak konfirmasi, lalu tekan tombolnya lagi.');
        }

        try {
            $hasil = (new UrutkanNomorDada())->urutkan((string) (session(SesiAdmin::KUNCI_NAMA) ?? 'Admin'));
        } catch (DatabaseException $e) {
            log_message('error', 'Urutkan nomor atlet (BIB) gagal: {pesan}', ['pesan' => $e->getMessage()]);

            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Nomor atlet (BIB) belum diurutkan karena data sedang dipakai, misalnya ada pendaftaran yang sedang ditandai Lunas. Tunggu sebentar, lalu coba lagi.');
        }

        if (! $hasil['berhasil']) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', (string) $hasil['pesan']);
        }

        $pesan = 'Nomor atlet (BIB) ' . $hasil['atlet'] . ' atlet dari ' . $hasil['sekolah'] . ' sekolah sudah diurutkan ulang per sekolah.';

        if ($hasil['dikosongkan'] > 0) {
            $pesan .= ' Nomor atlet (BIB) ' . $hasil['dikosongkan'] . ' atlet dari pendaftaran yang belum Lunas dikosongkan. Atlet itu mendapat nomor baru saat pendaftarannya ditandai Lunas.';
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', $pesan);
    }
}
