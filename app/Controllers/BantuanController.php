<?php

namespace App\Controllers;

use App\Libraries\Bantuan\IsiBantuan;
use App\Libraries\IsiHalamanDepan;

/**
 * Halaman publik Bantuan (`/bantuan`, F9-04): pencarian langsung, kategori
 * tanya-jawab, panduan bergambar langkah pendaftaran, dan tombol WA panitia.
 * Isinya dikelola admin di Pengaturan > Bantuan
 * (`App\Controllers\Admin\Pengaturan\FaqController`).
 */
class BantuanController extends BaseController
{
    public function index(): string
    {
        $isi = new IsiBantuan();

        return view('bantuan/index', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'kategori'  => $isi->kategori(),
            'panduan'   => IsiBantuan::panduan(),
            'kontak'    => IsiBantuan::kontak(),
            'kepala'    => IsiBantuan::gambar(IsiBantuan::FOLDER_KATEGORI . 'kepala-bantuan.webp'),
            'gambarWa'  => IsiBantuan::gambar(IsiBantuan::FOLDER_KATEGORI . 'tanya-panitia.webp'),
        ]);
    }
}
