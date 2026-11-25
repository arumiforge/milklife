<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Panel\GantiAtlet;
use App\Libraries\SesiAdmin;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Ganti atlet di pendaftaran Lunas: `admin/pendaftaran/<id>/ganti-atlet/<id atlet>`
 * (F11-01, K172, `GantiAtlet`).
 *
 * - `index`: data atlet lama, nomor lomba yang diwariskan, dan isian atlet
 *   pengganti. Bila atlet tidak bisa diganti (bukan Lunas, sudah punya
 *   hasil, seri dikunci), alasannya tampil tanpa formulir.
 * - `ganti`: simpan, lalu kembali ke rincian pendaftaran dengan nomor atlet
 *   (BIB) pengganti.
 */
class GantiAtletController extends BaseController
{
    private const ALAMAT = 'admin/pendaftaran';

    public function index(int $id, int $atletId): string
    {
        return $this->tampilkan($this->formulirAtau404($id, $atletId), [], '', []);
    }

    public function ganti(int $id, int $atletId): RedirectResponse|string
    {
        $form = $this->formulirAtau404($id, $atletId);
        $baru = [
            'nama'          => $this->teks('nama'),
            'nisn'          => $this->teks('nisn'),
            'tanggal_lahir' => $this->teks('tanggal_lahir'),
        ];
        $alasan = $this->teks('alasan');

        try {
            $hasil = (new GantiAtlet())->ganti($id, $atletId, $baru, $alasan, (string) (session(SesiAdmin::KUNCI_NAMA) ?? 'Admin'));
        } catch (DatabaseException $e) {
            log_message('error', 'Ganti atlet {atlet} pendaftaran {id} gagal: {pesan}', ['atlet' => $atletId, 'id' => $id, 'pesan' => $e->getMessage()]);

            return $this->tampilkan($form, $baru, $alasan, [
                'umum' => 'Atlet belum diganti. Data sedang dipakai admin lain pada saat yang sama. Tunggu beberapa detik, lalu coba lagi.',
            ]);
        }

        if ($hasil === null) {
            return redirect()->to(self::ALAMAT . '/' . $id)->with('pesan_galat', 'Atlet ini tidak ditemukan di pendaftaran. Mungkin sudah diganti atau dihapus.');
        }

        if (! $hasil['tersimpan']) {
            return $this->tampilkan($form, $baru, $alasan, $hasil['galat']);
        }

        $pesan = $form['atlet']['nama'] . ' sudah diganti ' . $hasil['nama']
            . ($hasil['nomor_dada'] === null ? '.' : ' dengan nomor atlet (BIB) baru ' . $hasil['nomor_dada'] . '.')
            . ' Nomor lomba, regu, dan tempatnya di seri ikut pindah. Cetak BIB baru di menu Cetak BIB, dan cetak ulang daftar seri bila sudah dibagikan.';

        return redirect()->to(self::ALAMAT . '/' . $id)->with('pesan_sukses', $pesan);
    }

    private function teks(string $kunci): string
    {
        $nilai = $this->request->getPost($kunci);

        return is_string($nilai) ? $nilai : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function formulirAtau404(int $id, int $atletId): array
    {
        $form = (new GantiAtlet())->formulir($id, $atletId);

        if ($form === null) {
            throw PageNotFoundException::forPageNotFound('Atlet tidak ditemukan di pendaftaran ini. Mungkin sudah diganti atau dihapus.');
        }

        return $form;
    }

    /**
     * @param array<string, mixed>  $form
     * @param array<string, string> $baru
     * @param array<string, string> $galat
     */
    private function tampilkan(array $form, array $baru, string $alasan, array $galat): string
    {
        return view('admin/pendaftaran/ganti_atlet', [
            'form'      => $form,
            'baru'      => $baru,
            'alasan'    => $alasan,
            'galat'     => $galat,
            'alasanMin' => GantiAtlet::PANJANG_ALASAN_MIN,
            'alasanMax' => GantiAtlet::PANJANG_ALASAN_MAKS,
        ]);
    }
}
