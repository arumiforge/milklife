<?php

namespace App\Controllers;

/**
 * Halaman depan.
 */
class BerandaController extends BaseController
{
    public function index(): string
    {
        return view('beranda');
    }
}
