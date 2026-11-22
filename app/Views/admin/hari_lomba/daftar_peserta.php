<?php
/**
 * Daftar peserta per nomor lomba (F8-03), tata letak `admin_tw`. Data dari
 * `DaftarPesertaController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, nomor: list<array{id: int, nama: string, jenis: string, peserta: int}>}> $kelompokUmur
 */
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Daftar peserta<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/daftar-peserta'], ['saveData' => false]) ?>

    <p class="text-gray-700">
        Daftar peserta untuk pemanggilan atlet dan juri: No, nomor atlet (BIB), nama, sekolah, tanda hadir dari daftar ulang, dan kolom kosong untuk catatan juri.
        Nomor beregu ditulis per regu, anggota inti dulu lalu cadangan. Hanya atlet dari pendaftaran Lunas.
        Unduh lagi menjelang pemanggilan supaya tanda hadirnya terbaru.
    </p>

    <?php if ($kelompokUmur === []) : ?>
        <p class="text-gray-600" id="peserta-kosong">Belum ada nomor lomba. Tambahkan di Pengaturan > Nomor lomba.</p>
    <?php endif ?>

    <?php foreach ($kelompokUmur as $ku) : ?>
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-ku-<?= $ku['id'] ?>">
            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-4 py-3 sm:px-5">
                <h2 class="me-auto text-lg font-bold text-gray-900" id="judul-ku-<?= $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
                <span class="text-sm text-gray-600">Semua nomor:</span>
                <div class="flex gap-2" role="group" aria-label="<?= esc('Unduh semua nomor ' . $ku['nama'], 'attr') ?>">
                    <?= komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url('admin/daftar-peserta/kelompok-umur/' . $ku['id'] . '/pdf'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-text', 'kelas' => 'min-h-11']) ?>
                    <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/daftar-peserta/kelompok-umur/' . $ku['id'] . '/excel'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'sheet', 'kelas' => 'min-h-11']) ?>
                </div>
            </div>
            <ul class="-mt-px grid lg:grid-cols-2">
                <?php foreach ($ku['nomor'] as $n) : ?>
                    <li class="flex items-center gap-2 border-t border-gray-100 px-4 py-2.5 sm:px-5">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold break-words text-gray-900"><?= esc($n['nama']) ?></div>
                            <div class="text-sm text-gray-600"><?= $n['jenis'] === 'beregu' ? 'Beregu · ' : '' ?><?= $n['peserta'] ?> atlet</div>
                        </div>
                        <div class="flex shrink-0 gap-2" role="group" aria-label="<?= esc('Unduh ' . $n['nama'], 'attr') ?>">
                            <?= komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url('admin/daftar-peserta/' . $n['id'] . '/pdf'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-text', 'kelas' => 'min-h-11', 'atribut' => ['aria-label' => 'PDF ' . $n['nama']]]) ?>
                            <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/daftar-peserta/' . $n['id'] . '/excel'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'sheet', 'kelas' => 'min-h-11', 'atribut' => ['aria-label' => 'Excel ' . $n['nama']]]) ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
