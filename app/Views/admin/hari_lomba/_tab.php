<?php
/**
 * Tab halaman hari lomba (F8-03), tata letak `admin_tw`: Daftar ulang, Cetak
 * BIB, Daftar peserta, Jadwal pertandingan (F9-07), Seri dan lintasan (F9-08),
 * dan Lembar juri (F9-09). Di ponsel deretannya bisa digeser mendatar dan
 * label panjang dipendekkan supaya muat.
 *
 * @var string $aktif alamat tab yang sedang dibuka
 */
$tab = [
    'admin/daftar-ulang'        => ['Daftar ulang', 'scan-qr-code', null],
    'admin/nomor-atlet/cetak'   => ['Cetak BIB', 'printer', null],
    'admin/daftar-peserta'      => ['Daftar peserta', 'list-ordered', 'Peserta'],
    'admin/jadwal-pertandingan' => ['Jadwal pertandingan', 'calendar-days', 'Jadwal'],
    'admin/seri'                => ['Seri dan lintasan', 'list-ordered', 'Seri'],
    'admin/juri'                => ['Lembar juri', 'smartphone', 'Juri'],
];
// ikon-sprite: scan-qr-code printer list-ordered calendar-days smartphone
?>
<nav class="min-w-0" aria-label="Halaman hari lomba" data-tab-hari-lomba>
    <ul class="relative -mx-4 flex gap-1 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
        <?php foreach ($tab as $alamat => [$label, $ikon, $pendek]) : ?>
            <?php $aktifTab = $alamat === $aktif; ?>
            <li class="shrink-0">
                <a class="inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-lg px-3 text-sm font-semibold <?= $aktifTab ? 'bg-primary-700 text-white' : 'text-gray-700 hover:bg-gray-100 hover:text-primary-700' ?>" href="<?= site_url($alamat) ?>"<?= $aktifTab ? ' aria-current="page"' : '' ?>>
                    <?= ikon($ikon, 'size-4') ?>
                    <?php if ($pendek === null) : ?>
                        <?= esc($label) ?>
                    <?php else : ?>
                        <span class="max-sm:sr-only"><?= esc($label) ?></span><span class="sm:hidden" aria-hidden="true"><?= esc($pendek) ?></span>
                    <?php endif ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
