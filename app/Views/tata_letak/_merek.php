<?php
/**
 * Nama acara sebagai merek: kata pertama (`.merek-utama`) dan sisanya
 * (`.merek-sambungan`), dari pengaturan `Acara.nama`. Dipakai kepala publik,
 * menu samping admin, tata letak polos, dan judul halaman depan.
 *
 *     <a class="merek" href="…"><?= view('tata_letak/_merek', [], ['saveData' => false]) ?></a>
 *
 * Bila nama acara belum diisi admin, tampil teks pengganti
 * `AcaraController::NAMA_PENGGANTI` ("Pendaftaran Lomba"), supaya kepala
 * halaman tidak kosong. Nama satu kata tampil tanpa sambungan.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\AcaraController;

$bagian = preg_split('/\s+/u', AcaraController::namaTampil(), 2);
?>
<span class="merek-utama"><?= esc($bagian[0]) ?></span>
<?php if (isset($bagian[1])) : ?>
    <span class="merek-sambungan"><?= esc($bagian[1]) ?></span>
<?php endif ?>
