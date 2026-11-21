<?php
/**
 * Menu panel admin bertampilan baru (`tata_letak/admin_tw.php`, Tahap 9).
 * Butirnya dari `App\Libraries\Panel\MenuAdmin`, sumber yang sama dengan menu
 * tata letak lama (`tata_letak/_menu_admin.php`), jadi isinya selalu sama.
 *
 * Dipakai dua kali:
 * - `$tempatMenu = 'samping'`: sidebar berkelompok (layar lebar tetap di kiri,
 *   ponsel jadi laci yang dibuka tombol menu di kepala, K131, K133), lalu
 *   daftar akhir Pengecekan dan Lihat halaman depan (K102).
 * - `$tempatMenu = 'bawah'`: menu bawah ponsel, 5 butir urusan pendaftaran;
 *   butir aktif cukup ikon oranye (K133).
 *
 * Penanda untuk uji dan skrip (tidak bergantung pada kelas CSS):
 * `data-menu-kelompok="<nama>"`, `data-menu-tautan="<alamat>"`, dan
 * `data-menu-akhir` pada daftar akhir.
 *
 * @var string|null $tempatMenu `samping` atau `bawah`
 */

use App\Libraries\Panel\MenuAdmin;

$alamatAktif = MenuAdmin::alamatAktif();

$tautanSamping = static function (array $butir) use ($alamatAktif): string {
    $kini = $butir['alamat'] === $alamatAktif;

    return '<a data-menu-tautan="' . esc($butir['alamat'], 'attr') . '" href="' . site_url($butir['alamat']) . '"' . ($kini ? ' aria-current="page"' : '') . ' class="'
        . ($kini ? 'bg-primary-100 text-primary-800' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900')
        . ' group flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold">'
        . ikon($butir['ikon'], $kini ? 'text-primary-700' : 'text-gray-400 group-hover:text-gray-700')
        . '<span>' . esc($butir['label']) . '</span></a>';
};
?>
<?php if (($tempatMenu ?? 'samping') === 'bawah') : ?>
    <ul class="mx-auto grid max-w-xl grid-cols-5">
        <?php foreach (MenuAdmin::bawah() as $butir) : ?>
            <?php $kini = $butir['alamat'] === $alamatAktif; ?>
            <li class="min-w-0">
                <a data-menu-tautan="<?= esc($butir['alamat'], 'attr') ?>" href="<?= site_url($butir['alamat']) ?>"<?= $kini ? ' aria-current="page"' : '' ?> class="flex min-h-14 flex-col items-center justify-center gap-0.5 px-0.5 text-[11px] font-semibold text-gray-700 focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary-700"<?= isset($butir['label_pendek']) ? ' title="' . esc($butir['label'], 'attr') . '"' : '' ?>>
                    <?= ikon($butir['ikon'], $kini ? 'size-6 text-oranye-500' : 'size-6 text-gray-500') ?>
                    <span class="max-w-full truncate"><?= esc($butir['label_pendek'] ?? $butir['label']) ?></span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php else : ?>
    <div class="flex min-h-full flex-col gap-5">
        <?php foreach (MenuAdmin::kelompok() as $nama => $daftar) : ?>
            <div data-menu-kelompok="<?= esc($nama, 'attr') ?>">
                <?php if (count($daftar) > 1) : ?>
                    <p class="mb-1 px-3 text-xs font-bold uppercase tracking-wider text-gray-500"><?= esc($nama) ?></p>
                <?php endif ?>
                <ul class="space-y-0.5">
                    <?php foreach ($daftar as $butir) : ?>
                        <li><?= $tautanSamping($butir) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endforeach ?>
        <ul class="mt-auto space-y-0.5 border-t border-gray-200 pt-4" data-menu-akhir>
            <?php foreach (MenuAdmin::akhir() as $butir) : ?>
                <li><?= $tautanSamping($butir) ?></li>
            <?php endforeach ?>
            <li>
                <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" data-menu-tautan="/" class="group flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                    <?= ikon('external-link', 'text-gray-400 group-hover:text-gray-700') ?><span>Lihat halaman depan</span> <span class="sr-only">(tab baru)</span>
                </a>
            </li>
        </ul>
    </div>
<?php endif ?>
