<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

/**
 * Dasbor panel admin.
 */
class DasborController extends BaseController
{
    public function index(): string
    {
        return view('admin/dasbor');
    }
}
