<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Rute aplikasi. Perutean otomatis dimatikan (`app/Config/Routing.php`),
 * jadi hanya rute yang ditulis di sini yang bisa dibuka.
 *
 * @var RouteCollection $routes
 */

// Publik
$routes->get('/', 'BerandaController::index');

// Admin. Semua rute dijaga filter `admin` (`App\Filters\AdminFilter`):
// yang belum masuk hanya bisa membuka halaman masuk.
$routes->get('admin', static fn () => redirect()->to('admin/dasbor'), ['filter' => 'admin']);
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'admin'], static function (RouteCollection $routes): void {
    $routes->get('masuk', 'MasukController::index');
    $routes->post('masuk', 'MasukController::masuk');
    $routes->post('keluar', 'MasukController::keluar');
    $routes->get('dasbor', 'DasborController::index');
});
