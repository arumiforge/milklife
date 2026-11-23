<?php
/**
 * Daftar akun juri (`GET /admin/pengaturan/akun-juri`, F11-16, K181), tata
 * letak `admin_tw`. Data dari `AkunJuriController::index()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var list<array<string, mixed>> $daftar akun berperan juri, urut nama
 */

// ikon-sprite: clipboard-pen plus pencil trash-2
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Akun juri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-akun-juri">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="judul-akun-juri" class="flex items-center gap-2 text-lg font-bold text-gray-900">
                    <?= ikon('clipboard-pen', 'text-primary-700') ?> Akun juri
                </h2>
                <p class="mt-1 max-w-3xl text-sm text-gray-600">
                    Buat satu akun untuk setiap juri. Akun juri hanya bisa membuka Lembar juri dan halaman Akun miliknya,
                    jadi juri tidak bisa melihat data pendaftaran atau mengubah pengaturan.
                </p>
            </div>
            <?= komponen('tombol', ['label' => 'Tambah juri', 'href' => site_url('admin/pengaturan/akun-juri/tambah'), 'ikon' => 'plus']) ?>
        </div>

        <?php if ($daftar === []) : ?>
            <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-gray-600" data-akun-juri-kosong>
                <span class="mb-2 inline-flex size-12 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon('clipboard-pen', 'size-6') ?></span>
                <p class="max-w-xl">Belum ada akun juri. Tambahkan juri yang bertugas, lalu berikan nama pengguna dan kata sandinya kepada masing-masing juri.</p>
            </div>
        <?php else : ?>
            <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm text-gray-700" data-akun-juri>
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-end">No</th>
                            <th scope="col" class="px-4 py-3">Nama</th>
                            <th scope="col" class="px-4 py-3">Nama pengguna</th>
                            <th scope="col" class="px-4 py-3">Dibuat</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $no => $juri) : ?>
                            <tr data-id="<?= (int) $juri['id'] ?>" class="border-t border-gray-100">
                                <td class="px-4 py-3 text-end align-top text-gray-500"><?= $no + 1 ?></td>
                                <td class="min-w-40 px-4 py-3 align-top font-semibold break-words text-gray-900"><?= esc($juri['nama']) ?></td>
                                <td class="px-4 py-3 align-top whitespace-nowrap font-mono"><?= esc($juri['nama_pengguna']) ?></td>
                                <td class="px-4 py-3 align-top whitespace-nowrap"><?= esc(tanggal_id($juri['dibuat_pada'] ?? null)) ?></td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <?= komponen('tombol', ['label' => 'Ubah', 'href' => site_url('admin/pengaturan/akun-juri/' . (int) $juri['id'] . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah akun ' . $juri['nama']]]) ?>
                                        <form action="<?= site_url('admin/pengaturan/akun-juri/' . (int) $juri['id'] . '/hapus') ?>" method="post"
                                            data-konfirmasi="<?= esc('Akun juri "' . $juri['nama_pengguna'] . '" akan dihapus. Juri ini tidak bisa masuk lagi, dan bila sedang masuk akan dikeluarkan. Prestasi yang sudah diisinya tetap tersimpan.', 'attr') ?>"
                                            data-konfirmasi-judul="Hapus akun juri?"
                                            data-konfirmasi-tombol="Hapus akun">
                                            <?= csrf_field() ?>
                                            <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus akun ' . $juri['nama']]]) ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-lembut sm:p-5" aria-labelledby="judul-cara-juri">
        <h2 id="judul-cara-juri" class="text-base font-bold text-gray-900">Cara juri memakai akunnya</h2>
        <ol class="mt-2 list-decimal space-y-1 ps-5">
            <li>Juri membuka <span class="font-mono"><?= esc(site_url('admin/masuk')) ?></span> di ponsel, lalu masuk dengan nama pengguna dan kata sandinya.</li>
            <li>Setelah masuk, juri langsung sampai di Lembar juri. Menu lain tidak tampil.</li>
            <li>Bila juri lupa kata sandi, tekan Ubah di barisnya, lalu buat kata sandi baru.</li>
        </ol>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
