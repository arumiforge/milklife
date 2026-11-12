<?php

namespace App\Controllers;

use App\Libraries\IsiHalamanDepan;

/**
 * Halaman publik semua berkas unduhan (`/unduhan`, K40).
 *
 * Datanya sama dengan bagian unduhan di halaman depan
 * (`IsiHalamanDepan::unduhan()`): hanya berkas yang ditandai tampil dan
 * berkasnya ada di disk. Berbeda dengan
 * `App\Controllers\Admin\Pengaturan\UnduhanController` (pengaturan admin).
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
}
