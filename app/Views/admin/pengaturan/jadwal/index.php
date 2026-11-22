<?php
/**
 * Daftar agenda (`GET /admin/pengaturan/jadwal`; nama tampil "Agenda" sejak
 * F9-07, alamat dan tabel tetap `jadwal`), tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View            $this
 * @var list<array<string, mixed>>       $daftar jadwal menurut urutan tampil (`JadwalModel::daftar()`)
 */

use App\Models\TautanModel;

?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Agenda<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Agenda<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php if ($daftar === []) : ?>
        <section class="kartu-kosong flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-6 text-center" aria-labelledby="judul-jadwal-kosong" data-jadwal-kosong>
            <span class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon('clock', 'size-7') ?></span>
            <h2 id="judul-jadwal-kosong" class="text-lg font-bold text-gray-900">Belum ada agenda</h2>
            <p class="mt-1 text-gray-600">
                Tambahkan sosialisasi daring, pertemuan teknik (technical meeting), atau kegiatan lain supaya sekolah tahu
                kapan dan di mana harus hadir. Agenda tampil di halaman depan.
            </p>
            <div class="mt-5">
                <?= komponen('tombol', ['label' => 'Tambah agenda', 'href' => site_url('admin/pengaturan/jadwal/tambah'), 'ikon' => 'plus']) ?>
            </div>
        </section>
    <?php else : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-daftar-jadwal">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="judul-daftar-jadwal" class="text-lg font-bold text-gray-900">Daftar agenda</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Tampil di halaman depan menurut urutan. Angka kecil lebih dulu.
                    </p>
                </div>
                <?= komponen('tombol', ['label' => 'Tambah agenda', 'href' => site_url('admin/pengaturan/jadwal/tambah'), 'ikon' => 'plus']) ?>
            </div>

            <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm text-gray-700" data-tabel-klien>
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-end">No</th>
                            <th scope="col" class="px-4 py-3 text-center">Urutan</th>
                            <th scope="col" class="px-4 py-3">Agenda</th>
                            <th scope="col" class="px-4 py-3">Tampil</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 0; ?>
                        <?php foreach ($daftar as $jadwal) : ?>
                            <tr data-id="<?= (int) $jadwal['id'] ?>" class="border-t border-gray-100">
                                <td class="px-4 py-3 text-end align-top text-gray-500"><?= ++$no ?></td>
                                <td class="px-4 py-3 text-center align-top"><?= (int) $jadwal['urutan'] ?></td>
                                <td class="min-w-52 px-4 py-3 align-top">
                                    <span class="block font-semibold break-words text-gray-900"><?= esc($jadwal['judul']) ?></span>
                                    <ul class="mt-1 space-y-0.5 text-sm text-gray-600">
                                        <li class="flex items-start gap-1.5">
                                            <?= ikon('calendar', 'mt-0.5 size-4 shrink-0') ?>
                                            <span>
                                                <?php if ($jadwal['waktu'] !== null) : ?>
                                                    <?= esc(tanggal_id($jadwal['waktu'], true)) ?> WIB
                                                <?php else : ?>
                                                    Waktu belum pasti
                                                <?php endif ?>
                                            </span>
                                        </li>
                                        <?php if (($jadwal['tempat'] ?? '') !== '') : ?>
                                            <li class="flex items-start gap-1.5"><?= ikon('map-pin', 'mt-0.5 size-4 shrink-0') ?> <span class="min-w-0 break-words"><?= esc($jadwal['tempat']) ?></span></li>
                                        <?php endif ?>
                                        <?php if (($jadwal['tautan'] ?? '') !== '') : ?>
                                            <li class="flex items-start gap-1.5">
                                                <?= ikon('link', 'mt-0.5 size-4 shrink-0') ?>
                                                <a class="min-w-0 break-all text-primary-700 hover:underline" href="<?= esc($jadwal['tautan'], 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc(TautanModel::alamatRingkas($jadwal['tautan'])) ?></a>
                                            </li>
                                        <?php endif ?>
                                        <?php if (($jadwal['keterangan'] ?? '') !== '') : ?>
                                            <li class="mt-1 break-words"><?= esc(mb_strimwidth($jadwal['keterangan'], 0, 140, '…')) ?></li>
                                        <?php endif ?>
                                    </ul>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <?php if ((int) $jadwal['tampil'] === 1) : ?>
                                        <?= komponen('lencana', ['label' => 'Tampil', 'warna' => 'hijau', 'ikon' => 'eye']) ?>
                                    <?php else : ?>
                                        <?= komponen('lencana', ['label' => 'Disembunyikan', 'warna' => 'abu', 'ikon' => 'eye-off']) ?>
                                    <?php endif ?>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <?= komponen('tombol', ['label' => 'Ubah', 'href' => site_url('admin/pengaturan/jadwal/' . (int) $jadwal['id'] . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah ' . $jadwal['judul']]]) ?>
                                        <form action="<?= site_url('admin/pengaturan/jadwal/' . (int) $jadwal['id'] . '/hapus') ?>" method="post"
                                            data-konfirmasi="<?= esc('Agenda "' . $jadwal['judul'] . '" akan dihapus dari daftar dan dari halaman depan. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                            data-konfirmasi-judul="Hapus agenda?"
                                            data-konfirmasi-tombol="Hapus agenda">
                                            <?= csrf_field() ?>
                                            <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus ' . $jadwal['judul']]]) ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
