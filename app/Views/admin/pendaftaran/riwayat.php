<?php
/**
 * Cari riwayat tindakan admin menurut ID pendaftaran (F8-01, K124), terutama
 * untuk pendaftaran yang sudah dihapus atau digabung, yang rinciannya tidak
 * ada lagi. Data dari `PendaftaranController::riwayat()`. Tata letak
 * `admin_tw` (F9-15).
 *
 * @var CodeIgniter\View\View      $this
 * @var string                     $kata    ID yang dicari (boleh sebagian)
 * @var list<array<string, mixed>> $riwayat `RiwayatAdmin::cari()`
 * @var int                        $batas   paling banyak baris hasil
 */
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Riwayat Pendaftaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Riwayat Pendaftaran<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [
    ['label' => 'Pendaftaran', 'alamat' => 'admin/pendaftaran'],
    ['label' => 'Riwayat'],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <p class="text-gray-700">
        Riwayat tindakan admin tetap disimpan walau pendaftarannya sudah dihapus atau digabung.
        Ketik ID pendaftarannya (contoh MAC2A0000126, boleh sebagian), lalu tekan Cari.
    </p>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-cari-riwayat">
        <h2 id="judul-cari-riwayat" class="sr-only">Cari riwayat</h2>
        <form action="<?= site_url('admin/riwayat') ?>" method="get" role="search" class="flex flex-wrap items-end gap-3" id="form-cari-riwayat">
            <div class="min-w-0 flex-[1_1_16rem] sm:max-w-md">
                <label class="mb-1.5 block text-sm font-semibold text-gray-900" for="cari-id">ID pendaftaran</label>
                <input type="search" class="block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm" id="cari-id" name="id" value="<?= esc($kata, 'attr') ?>" maxlength="20" autocomplete="off" placeholder="MAC2A0000126">
            </div>
            <?= komponen('tombol', ['label' => 'Cari', 'tipe' => 'submit', 'ikon' => 'search', 'kelas' => 'w-full sm:w-auto']) ?>
        </form>
    </section>

    <?php if ($kata !== '') : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-hasil-riwayat">
            <h2 id="judul-hasil-riwayat" class="text-lg font-bold text-gray-900">Hasil untuk "<?= esc($kata) ?>"</h2>
            <p class="mb-2 text-sm text-gray-600">
                <?= count($riwayat) ?> catatan, terbaru di atas.
                <?php if (count($riwayat) >= $batas) : ?>
                    Hanya <?= $batas ?> catatan terbaru yang ditampilkan; ketik ID lebih lengkap untuk mempersempit.
                <?php endif ?>
            </p>
            <?= view('admin/pendaftaran/_riwayat', ['riwayat' => $riwayat, 'denganId' => true], ['saveData' => false]) ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
