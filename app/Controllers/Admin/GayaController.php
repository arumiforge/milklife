<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

/**
 * Katalog komponen tampilan baru (Tahap 9, F9-01, K140, K141).
 *
 * Satu halaman berisi semua komponen `app/Views/komponen/`, palet, ikon, dan
 * contoh adegan Three.js, supaya pemilik bisa menilai gaya Flowbite Pro di
 * staging sebelum halaman lain dipindah. Tidak mengubah data apa pun.
 */
class GayaController extends BaseController
{
    public function index(): string
    {
        return view('admin/gaya');
    }
}
