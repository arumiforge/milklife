<?php
/**
 * Pengecekan (K102): keadaan pendaftaran, daftar centang kelengkapan
 * pengaturan (F2-09), dan gambar kepala halaman depan. Dulu bagian dasbor.
 * Tata letak `admin_tw` (F9-14). Data dari `PengecekanController::index()`.
 *
 * Penanda uji: `#keadaan-pendaftaran[data-keadaan]`, daftar centang
 * `[data-daftar-kelengkapan] > li[data-kunci][data-lengkap]`, kotak tanpa
 * gambar `[data-tanpa-gambar][role="img"]`.
 *
 * ikon-sprite: hourglass calendar-days lock-open lock circle-check circle-alert image
 *
 * @var CodeIgniter\View\View                                                                         $this
 * @var list<array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}> $butir
 * @var int                                                                                           $jumlahLengkap
 * @var bool                                                                                          $semuaLengkap
 * @var string                                                                                        $keadaan       salah satu konstanta BukaTutupPendaftaran
 * @var string                                                                                        $labelKeadaan
 * @var string                                                                                        $kalimat
 * @var bool                                                                                          $lewatManual
 * @var string                                                                                        $jadwal        kosong bila belum diisi
 * @var string                                                                                        $bukaManual
 * @var string                                                                                        $gambarKepala  jalur unggahan, boleh kosong
 */

use App\Libraries\BukaTutupPendaftaran;

[$warnaKeadaan, $ikonKeadaan] = [
    BukaTutupPendaftaran::DISIAPKAN    => ['kuning', 'hourglass'],
    BukaTutupPendaftaran::BELUM_DIBUKA => ['biru', 'calendar-days'],
    BukaTutupPendaftaran::TERBUKA      => ['hijau', 'lock-open'],
    BukaTutupPendaftaran::DITUTUP      => ['abu', 'lock'],
][$keadaan];
$jumlahButir = count($butir);
$persen      = $jumlahButir > 0 ? (int) round($jumlahLengkap / $jumlahButir * 100) : 0;
$kelasKartu  = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Pengecekan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pengecekan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="p-4 sm:p-6">
    <p class="mb-5 max-w-3xl text-gray-600">Periksa di sini apakah pendaftaran sudah terbuka, pengaturan wajib sudah lengkap, dan halaman depan sudah siap dilihat sekolah.</p>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-12">
        <section class="<?= $kelasKartu ?> xl:col-span-5" aria-labelledby="judul-keadaan" id="keadaan-pendaftaran" data-keadaan="<?= esc($keadaan, 'attr') ?>">
            <h2 id="judul-keadaan" class="mb-3 text-lg font-bold text-gray-900">Keadaan pendaftaran</h2>
            <p class="mb-2"><?= komponen('lencana', ['label' => $labelKeadaan, 'warna' => $warnaKeadaan, 'ikon' => $ikonKeadaan]) ?></p>
            <p class="mb-3 text-lg font-semibold text-gray-900" data-kalimat-keadaan><?= esc($kalimat) ?></p>

            <?php if ($keadaan === BukaTutupPendaftaran::DISIAPKAN) : ?>
                <p class="mb-3 text-gray-600">
                    Pendaftaran baru bisa dibuka setelah semua butir kelengkapan pengaturan bercentang.
                    Sampai saat itu, sekolah melihat keterangan "Pendaftaran sedang disiapkan".
                </p>
            <?php elseif ($lewatManual) : ?>
                <p class="mb-3 text-gray-600">
                    Pendaftaran terbuka karena dibuka manual oleh admin, di luar jadwal.
                </p>
            <?php endif ?>

            <dl class="mb-4 grid grid-cols-1 gap-x-4 gap-y-1 text-sm sm:grid-cols-3">
                <dt class="font-semibold text-gray-900">Jadwal</dt>
                <dd class="mb-2 text-gray-700 sm:col-span-2 sm:mb-0"><?= $jadwal !== '' ? esc($jadwal) : '<span class="text-gray-500">Belum diisi</span>' ?></dd>
                <dt class="font-semibold text-gray-900">Buka manual</dt>
                <dd class="text-gray-700 sm:col-span-2"><?= esc($bukaManual) ?></dd>
            </dl>

            <?= komponen('tombol', ['label' => 'Atur jadwal pendaftaran', 'href' => site_url('admin/pengaturan/pendaftaran'), 'varian' => 'kedua', 'ikon' => 'calendar-days']) ?>
        </section>

        <section class="<?= $kelasKartu ?> xl:col-span-7" aria-labelledby="judul-kelengkapan" id="kelengkapan-pengaturan">
            <h2 id="judul-kelengkapan" class="mb-1 text-lg font-bold text-gray-900">Kelengkapan pengaturan</h2>
            <p class="mb-2 text-gray-600" data-ringkasan-kelengkapan>
                <?php if ($semuaLengkap) : ?>
                    Semua pengaturan wajib sudah lengkap.
                <?php else : ?>
                    <?= $jumlahLengkap ?> dari <?= $jumlahButir ?> sudah lengkap.
                <?php endif ?>
            </p>
            <?php if (! $semuaLengkap) : ?>
                <p class="mb-2 text-sm text-gray-700">
                    <strong>Langkah berikutnya:</strong> buka butir yang belum bercentang dan lengkapi isinya supaya pendaftaran bisa dibuka.
                </p>
            <?php endif ?>
            <div class="mb-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="Kelengkapan pengaturan" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full <?= $semuaLengkap ? 'bg-green-600' : 'bg-primary-700' ?> <?= lebar_tw($persen) ?>"></div>
            </div>

            <ul class="divide-y divide-gray-200 border-t border-gray-200" data-daftar-kelengkapan>
                <?php foreach ($butir as $satu) : ?>
                    <li class="flex gap-3 py-3" data-kunci="<?= esc($satu['kunci'], 'attr') ?>" data-lengkap="<?= $satu['lengkap'] ? 'ya' : 'tidak' ?>">
                        <?= $satu['lengkap'] ? ikon('circle-check', 'mt-0.5 size-6 shrink-0 text-green-600') : ikon('circle-alert', 'mt-0.5 size-6 shrink-0 text-yellow-500') ?>
                        <div class="min-w-0 flex-1">
                            <a class="font-semibold text-primary-700 underline hover:no-underline" href="<?= site_url($satu['alamat']) ?>"><?= esc($satu['label']) ?></a>
                            <span class="sr-only">: <?= $satu['lengkap'] ? 'lengkap' : 'belum lengkap' ?>.</span>
                            <p class="text-sm <?= $satu['lengkap'] ? 'text-gray-500' : 'text-gray-700' ?>" data-keterangan-kelengkapan><?= esc($satu['keterangan']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="<?= $kelasKartu ?> xl:col-span-5" aria-labelledby="judul-gambar">
            <h2 id="judul-gambar" class="mb-3 text-lg font-bold text-gray-900">Gambar kepala halaman depan</h2>
            <?php if ($gambarKepala !== '') : ?>
                <img class="w-full rounded-lg" src="<?= esc(base_url($gambarKepala), 'attr') ?>" alt="Gambar kepala halaman depan">
            <?php else : ?>
                <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '21x9', 'label' => 'Gambar kepala halaman depan', 'kelas' => 'w-full'], ['saveData' => false]) ?>
            <?php endif ?>
            <p class="mt-3 text-sm text-gray-600">
                Gambar kepala dan logo diatur di
                <a class="font-semibold text-primary-700 underline hover:no-underline" href="<?= site_url('admin/pengaturan/halaman-depan') ?>">Pengaturan Halaman Depan</a>.
            </p>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
