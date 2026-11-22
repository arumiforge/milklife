<?php
/**
 * Urutkan ulang nomor dada per sekolah (K111). Data dari
 * `NomorDadaController::index()` (`UrutkanNomorDada::ringkasan()`).
 * Tata letak `admin_tw` (F9-15).
 *
 * @var CodeIgniter\View\View $this
 * @var array{atlet: int, sekolah: int, menunggu_bernomor: int, per_kelompok_umur: list<array{nama: string, atlet: int, awalan: string, cukup: bool}>, diurutkan_pada: string|null, diurutkan_oleh: string|null} $ringkasan
 */

use App\Controllers\Admin\NomorDadaController;

$kurang = array_filter($ringkasan['per_kelompok_umur'], static fn (array $ku): bool => ! $ku['cukup']);
$kosong = $ringkasan['atlet'] === 0;
$th     = 'border-b border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold uppercase tracking-wide whitespace-nowrap text-gray-600';
$td     = 'px-3 py-2 align-top';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Urutkan Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Urutkan Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Atlet', 'alamat' => 'admin/atlet'], ['label' => 'Urutkan nomor atlet (BIB)']]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="text-gray-700">
        Nomor atlet (BIB) dibagikan saat pertemuan teknik (technical meeting). Sebelum itu, nomor bisa dibuat ulang supaya nomor atlet satu sekolah berurutan,
        walau pendaftarannya masuk tidak berurutan. Sekolah yang mendaftar lebih dulu mendapat nomor lebih kecil, dan di dalam satu sekolah
        atlet diurutkan menurut urutan mendaftar. Nomor dimulai lagi dari 001 di awalan setiap kelompok umur.
    </p>

    <div class="grid gap-6 lg:grid-cols-12 lg:items-stretch">
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 min-w-0 lg:col-span-7" aria-labelledby="judul-ringkasan">
            <h2 class="text-lg font-bold text-gray-900" id="judul-ringkasan">Yang akan diberi nomor</h2>

            <?php if ($kosong) : ?>
                <p class="text-gray-600" id="ringkasan-kosong">Belum ada atlet dari pendaftaran Lunas. Nomor atlet (BIB) hanya dibuat untuk pendaftaran yang sudah Lunas.</p>
            <?php else : ?>
                <p class="text-gray-700" id="ringkasan-jumlah"><strong><?= $ringkasan['atlet'] ?></strong> atlet dari <strong><?= $ringkasan['sekolah'] ?></strong> sekolah dengan pendaftaran Lunas.</p>
                <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm text-gray-700">
                        <thead><tr><th scope="col" class="<?= $th ?>">Kelompok umur</th><th scope="col" class="<?= $th ?> text-end">Atlet</th><th scope="col" class="<?= $th ?>">Awalan</th><th scope="col" class="<?= $th ?>">Keadaan</th></tr></thead>
                        <tbody>
                            <?php foreach ($ringkasan['per_kelompok_umur'] as $ku) : ?>
                                <tr class="border-b border-gray-100 last:border-b-0">
                                    <td class="<?= $td ?>"><?= esc($ku['nama']) ?></td>
                                    <td class="<?= $td ?> text-end"><?= $ku['atlet'] ?></td>
                                    <td class="<?= $td ?>"><?= $ku['awalan'] === '' ? '<span class="font-semibold text-red-700">Belum ada</span>' : esc($ku['awalan']) ?></td>
                                    <td class="<?= $td ?>"><?= $ku['cukup'] ? '<span class="font-semibold text-green-700">Cukup</span>' : '<span class="font-semibold text-red-700">Awalan kurang</span>' ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>

            <?php if ($ringkasan['menunggu_bernomor'] > 0) : ?>
                <p class="text-sm text-gray-600">
                    <?= $ringkasan['menunggu_bernomor'] ?> atlet dari pendaftaran yang kembali ke Menunggu masih memegang nomor lama.
                    Nomornya akan dikosongkan, lalu dibuat lagi saat pendaftarannya ditandai Lunas.
                </p>
            <?php endif ?>
        </section>

        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 min-w-0 lg:col-span-5" aria-labelledby="judul-urutkan">
            <h2 class="text-lg font-bold text-gray-900" id="judul-urutkan">Urutkan ulang</h2>

            <p class="text-sm text-gray-700" id="terakhir-diurutkan">
                <?php if ($ringkasan['diurutkan_pada'] === null) : ?>
                    Nomor atlet (BIB) belum pernah diurutkan ulang.
                <?php else : ?>
                    Terakhir diurutkan ulang <?= esc(tanggal_id($ringkasan['diurutkan_pada'])) ?><?= $ringkasan['diurutkan_oleh'] !== null ? ' oleh ' . esc($ringkasan['diurutkan_oleh']) : '' ?>.
                    Halaman Cek Pendaftaran, bukti PDF, dan rincian pendaftaran menuliskan keterangan ini.
                <?php endif ?>
            </p>

            <div class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="note">
                <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                <p>Semua nomor atlet (BIB) lama diganti. Jangan urutkan ulang setelah nomor atlet dibagikan ke peserta.
                Pendaftaran yang ditandai Lunas sesudahnya mendapat nomor lanjutan, jadi urutkan lagi sebelum pertemuan teknik (technical meeting) bila perlu.</p>
            </div>

            <?php if ($kurang !== []) : ?>
                <p class="text-sm font-medium text-red-700" id="awalan-kurang">
                    Tambahkan awalan untuk <?= esc(implode(', ', array_column($kurang, 'nama'))) ?> di
                    <a class="font-semibold underline hover:no-underline" href="<?= site_url('admin/pengaturan/kelompok-umur') ?>">Pengaturan, Kelompok Umur</a> sebelum mengurutkan.
                </p>
            <?php endif ?>

            <form action="<?= site_url('admin/atlet/nomor-dada') ?>" method="post" id="formulir-urutkan" class="space-y-3"
                data-konfirmasi="<?= esc('Nomor atlet (BIB) ' . $ringkasan['atlet'] . ' atlet akan dibuat ulang, berurutan per sekolah. Nomor lama tidak berlaku lagi.', 'attr') ?>"
                data-konfirmasi-judul="Urutkan ulang nomor atlet (BIB)?"
                data-konfirmasi-tombol="Urutkan ulang">
                <?= csrf_field() ?>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-900" for="konfirmasi">Ketik <strong><?= NomorDadaController::KATA_KONFIRMASI ?></strong> untuk melanjutkan</label>
                    <input class="block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 disabled:cursor-not-allowed disabled:bg-gray-100 sm:text-sm" type="text" id="konfirmasi" name="konfirmasi" autocomplete="off" required
                        pattern="[Uu][Rr][Uu][Tt][Kk][Aa][Nn]" title="Ketik <?= NomorDadaController::KATA_KONFIRMASI ?>"<?= $kosong ? ' disabled' : '' ?>>
                </div>
                <?= komponen('tombol', ['label' => 'Urutkan ulang nomor atlet (BIB)', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'arrow-down-0-1', 'kelas' => 'disabled:pointer-events-none disabled:opacity-50', 'atribut' => ['disabled' => $kosong]]) ?>
            </form>
        </section>
    </div>

    <p><a class="inline-flex min-h-11 items-center gap-1 font-semibold text-primary-700 hover:underline" href="<?= site_url('admin/atlet') ?>"><?= ikon('arrow-left', 'size-4') ?> Kembali ke daftar atlet</a></p>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
