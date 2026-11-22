<?php

namespace App\Controllers;

use App\Libraries\BerkasUnduhan;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Unggahan;
use App\Models\UnduhanModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman publik semua berkas unduhan (`/unduhan`, K40).
 *
 * Datanya sama dengan bagian unduhan di halaman depan
 * (`IsiHalamanDepan::unduhan()`): hanya berkas yang ditandai tampil dan
 * berkasnya ada di disk. Berbeda dengan
 * `App\Controllers\Admin\Pengaturan\UnduhanController` (pengaturan admin).
 *
 * `lihat()` (F10-09): pratinjau PDF dan gambar dengan
 * `Content-Disposition: inline`, untuk modal "Lihat" dan tab baru di ponsel.
 * Alamat unduh lama (`base_url($berkas)`) tidak berubah.
 */
class UnduhanController extends BaseController
{
    public function index(): string
    {
        $isi = new IsiHalamanDepan();

        return view('unduhan', [
            'namaAcara' => $isi->acara()['nama'],
            'unduhan'   => $isi->unduhan(),
        ]);
    }

    /**
     * Pratinjau satu berkas unduhan yang ditandai tampil. Hanya PDF dan
     * gambar (`BerkasUnduhan::JENIS_LIHAT`); berkas lain, berkas tersembunyi,
     * atau berkas yang hilang dari disk menghasilkan 404.
     */
    public function lihat(int $id): ResponseInterface
    {
        $baris = model(UnduhanModel::class)->where('tampil', 1)->find($id);

        if (! is_array($baris)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $berkas   = (string) $baris['berkas'];
        $unggahan = new Unggahan();
        $penuh    = $unggahan->jalurPenuh($berkas);
        $jenis    = $unggahan->jenis($berkas);
        $mime     = BerkasUnduhan::JENIS_LIHAT[$jenis] ?? null;

        if ($penuh === null || $mime === null || ! is_file($penuh)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $nama = BerkasUnduhan::namaSimpan((string) $baris['judul'], $jenis);
        $aman = (string) preg_replace('/[^A-Za-z0-9 ._-]+/', '-', $nama);

        return $this->response
            ->setContentType($mime, '')
            ->setHeader('Content-Disposition', 'inline; filename="' . $aman . '"; filename*=UTF-8\'\'' . rawurlencode($nama))
            ->setHeader('Cache-Control', 'public, max-age=300')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($penuh));
    }
}
