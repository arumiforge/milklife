<?php
/**
 * Menu panel admin bertampilan baru (`tata_letak/admin_tw.php`, Tahap 9, K140),
 * dikelompokkan sesuai rencana Tahap 9: Pendaftaran, Hari lomba, Hasil, Konten,
 * Pengaturan. Butir baru Tahap 9 ditambahkan di kelompoknya bersama halamannya.
 * Selama ada halaman yang belum pindah, `tata_letak/_menu_admin.php` (tata
 * letak lama) tetap dipakai dan isinya harus tetap sama (F9-14 menyatukannya).
 *
 * Dipakai dua kali:
 * - `$tempatMenu = 'samping'`: sidebar berkelompok (layar lebar tetap di kiri,
 *   ponsel jadi laci yang dibuka tombol menu di kepala, K131, K133).
 * - `$tempatMenu = 'bawah'`: menu bawah ponsel, 5 butir bertanda `bawah`;
 *   butir aktif cukup ikon oranye (K133).
 *
 * Ikon Lucide. `aktif_juga`: alamat lain yang ikut menandai butir.
 *
 * @var string|null $tempatMenu `samping` atau `bawah`
 */

$kelompok = [
    'Pendaftaran' => [
        ['label' => 'Dasbor', 'ikon' => 'layout-dashboard', 'alamat' => 'admin/dasbor', 'bawah' => true],
        ['label' => 'Pendaftaran', 'ikon' => 'clipboard-check', 'alamat' => 'admin/pendaftaran', 'bawah' => true],
        ['label' => 'Atlet', 'ikon' => 'users', 'alamat' => 'admin/atlet', 'bawah' => true],
        ['label' => 'Atlet ditolak', 'label_pendek' => 'Ditolak', 'ikon' => 'user-x', 'alamat' => 'admin/atlet-ditolak', 'bawah' => true],
        ['label' => 'Sekolah', 'ikon' => 'school', 'alamat' => 'admin/sekolah'],
        ['label' => 'Rekap', 'ikon' => 'chart-column', 'alamat' => 'admin/rekap', 'bawah' => true],
    ],
    'Hari lomba' => [
        ['label' => 'Daftar ulang', 'ikon' => 'scan-qr-code', 'alamat' => 'admin/daftar-ulang'],
        ['label' => 'Cetak BIB', 'ikon' => 'printer', 'alamat' => 'admin/nomor-atlet/cetak'],
        ['label' => 'Daftar peserta', 'ikon' => 'list-checks', 'alamat' => 'admin/daftar-peserta'],
        // Jadwal pertandingan (F9-07).
        ['label' => 'Jadwal pertandingan', 'ikon' => 'calendar-clock', 'alamat' => 'admin/jadwal-pertandingan'],
    ],
    'Hasil' => [
        ['label' => 'Hasil lomba', 'ikon' => 'trophy', 'alamat' => 'admin/hasil'],
        ['label' => 'Klasemen', 'ikon' => 'medal', 'alamat' => 'admin/klasemen'],
    ],
    'Pengaturan' => [
        ['label' => 'Pengaturan', 'ikon' => 'settings', 'alamat' => 'admin/pengaturan', 'aktif_juga' => ['admin/akun']],
    ],
];

$menuAkhir = [
    ['label' => 'Pengecekan', 'ikon' => 'clipboard-list', 'alamat' => 'admin/pengecekan'],
    ['label' => 'Katalog tampilan', 'ikon' => 'palette', 'alamat' => 'admin/gaya'],
];

$menuAsal = App\Libraries\Panel\AsalRincian::menuAktif();

$aktif = static function (array $butir) use ($menuAsal): bool {
    if ($menuAsal !== null) {
        return $butir['alamat'] === $menuAsal;
    }

    foreach ([$butir['alamat'], ...($butir['aktif_juga'] ?? [])] as $alamat) {
        if (url_is($alamat) || url_is($alamat . '/*')) {
            return true;
        }
    }

    return false;
};

$tautanSamping = static function (array $butir) use ($aktif): string {
    $kini = $aktif($butir);

    return '<a href="' . site_url($butir['alamat']) . '" class="' . ($kini ? 'bg-primary-100 text-primary-800' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900')
        . ' group flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold"' . ($kini ? ' aria-current="page"' : '') . '>'
        . ikon($butir['ikon'], $kini ? 'text-primary-700' : 'text-gray-400 group-hover:text-gray-700')
        . '<span>' . esc($butir['label']) . '</span></a>';
};
?>
<?php if (($tempatMenu ?? 'samping') === 'bawah') : ?>
    <ul class="grid grid-cols-5">
        <?php foreach (array_merge(...array_values($kelompok)) as $butir) : ?>
            <?php if (empty($butir['bawah'])) { continue; } $kini = $aktif($butir); ?>
            <li>
                <a href="<?= site_url($butir['alamat']) ?>" class="flex min-h-14 flex-col items-center justify-center gap-0.5 px-1 text-[11px] font-semibold text-gray-700"<?= $kini ? ' aria-current="page"' : '' ?>>
                    <?= ikon($butir['ikon'], $kini ? 'size-6 text-oranye-500' : 'size-6 text-gray-500') ?>
                    <span class="truncate"><?= esc($butir['label_pendek'] ?? $butir['label']) ?></span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php else : ?>
    <div class="space-y-6">
        <?php foreach ($kelompok as $nama => $daftar) : ?>
            <div>
                <p class="mb-1 px-3 text-xs font-bold uppercase tracking-wider text-gray-400"><?= esc($nama) ?></p>
                <ul class="space-y-0.5">
                    <?php foreach ($daftar as $butir) : ?>
                        <li><?= $tautanSamping($butir) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endforeach ?>
        <ul class="space-y-0.5 border-t border-gray-200 pt-4">
            <?php foreach ($menuAkhir as $butir) : ?>
                <li><?= $tautanSamping($butir) ?></li>
            <?php endforeach ?>
            <li>
                <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" class="group flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                    <?= ikon('house', 'text-gray-400 group-hover:text-gray-700') ?><span>Halaman depan</span><?= ikon('external-link', 'ms-auto size-4 text-gray-400') ?><span class="sr-only">(tab baru)</span>
                </a>
            </li>
        </ul>
    </div>
<?php endif ?>
