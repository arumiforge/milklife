<?php
/**
 * Daftar berkas unduhan (F2-07): judul, keterangan, jenis dan ukuran berkas
 * beserta tautan untuk membukanya, tampil atau tidak, urutan, dan tombol
 * ubah/hapus. Data dari `UnduhanController::index()`. Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View       $this
 * @var list<array<string, mixed>>  $daftar baris `unduhan` + `info` (lihat `UnduhanController::infoBerkas()`)
 */
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Unduhan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Unduhan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php if ($daftar === []) : ?>
        <section class="kartu-kosong flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center" aria-labelledby="judul-kosong" data-unduhan-kosong>
            <span class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon('download', 'size-7') ?></span>
            <h2 id="judul-kosong" class="text-lg font-bold text-gray-900">Belum ada berkas unduhan</h2>
            <p class="mt-1 max-w-xl text-gray-600">
                Unggah flyer, juknis, atau berkas lain yang perlu diunduh sekolah.
                Berkas yang ditandai tampil akan muncul di halaman depan.
            </p>
            <div class="mt-5">
                <?= komponen('tombol', ['label' => 'Tambah berkas', 'href' => site_url('admin/pengaturan/unduhan/tambah'), 'ikon' => 'plus']) ?>
            </div>
        </section>
    <?php else : ?>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="teks-info min-w-0 flex-1 text-gray-700">
                Berkas yang ditandai tampil muncul di halaman depan, urut dari angka urutan terkecil.
            </p>
            <?= komponen('tombol', ['label' => 'Tambah berkas', 'href' => site_url('admin/pengaturan/unduhan/tambah'), 'ikon' => 'plus']) ?>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-lembut">
            <table class="w-full text-left text-sm text-gray-700">
                <caption class="sr-only">Daftar berkas unduhan</caption>
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-end">No</th>
                        <th scope="col" class="px-4 py-3 text-center">Urutan</th>
                        <th scope="col" class="px-4 py-3">Judul</th>
                        <th scope="col" class="px-4 py-3">Berkas</th>
                        <th scope="col" class="px-4 py-3">Tampil</th>
                        <th scope="col" class="px-4 py-3 text-end">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 0; ?>
                    <?php foreach ($daftar as $baris) : ?>
                        <?php
                        $info       = $baris['info'];
                        $keterangan = (string) ($baris['keterangan'] ?? '');
                        ?>
                        <tr data-id="<?= (int) $baris['id'] ?>" class="border-t border-gray-100">
                            <td class="px-4 py-3 text-end align-top text-gray-500"><?= ++$no ?></td>
                            <td class="px-4 py-3 text-center align-top"><?= (int) $baris['urutan'] ?></td>
                            <td class="min-w-48 px-4 py-3 align-top">
                                <span class="font-semibold break-words text-gray-900"><?= esc($baris['judul']) ?></span>
                                <?php if ($keterangan !== '') : ?>
                                    <span class="mt-0.5 block text-sm break-words text-gray-600"><?= esc(mb_strimwidth($keterangan, 0, 120, '…')) ?></span>
                                <?php endif ?>
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <?php if ($info['ada']) : ?>
                                    <span class="jenis-berkas inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800"><?= esc($info['jenis']) ?></span>
                                    <span class="ukuran-berkas ms-1 text-sm text-gray-600"><?= esc($info['ukuran_teks']) ?></span>
                                    <a class="mt-1 flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline" href="<?= esc($info['alamat'], 'attr') ?>" target="_blank" rel="noopener">
                                        <?= ikon('external-link', 'size-4') ?> Buka berkas<span class="sr-only"> <?= esc($baris['judul']) ?> (di tab baru)</span>
                                    </a>
                                <?php else : ?>
                                    <span class="berkas-hilang flex items-start gap-1 text-sm whitespace-normal text-red-700" data-berkas-hilang>
                                        <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                        <span>Berkas tidak ditemukan. Unggah lagi lewat Ubah.</span>
                                    </span>
                                <?php endif ?>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <?php if ((int) $baris['tampil'] === 1) : ?>
                                    <?= komponen('lencana', ['label' => 'Tampil', 'warna' => 'hijau', 'ikon' => 'eye']) ?>
                                <?php else : ?>
                                    <?= komponen('lencana', ['label' => 'Disembunyikan', 'warna' => 'abu', 'ikon' => 'eye-off']) ?>
                                <?php endif ?>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <?= komponen('tombol', ['label' => 'Ubah', 'href' => site_url('admin/pengaturan/unduhan/' . (int) $baris['id'] . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah ' . $baris['judul']]]) ?>
                                    <form action="<?= site_url('admin/pengaturan/unduhan/' . (int) $baris['id'] . '/hapus') ?>" method="post"
                                        data-konfirmasi="<?= esc('Berkas "' . $baris['judul'] . '" akan dihapus dari server dan tidak bisa dikembalikan.', 'attr') ?>"
                                        data-konfirmasi-judul="Hapus berkas?"
                                        data-konfirmasi-tombol="Hapus berkas">
                                        <?= csrf_field() ?>
                                        <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus ' . $baris['judul']]]) ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
