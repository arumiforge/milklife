<?php
/**
 * Menu panel admin bertampilan baru (`tata_letak/admin_tw.php`, Tahap 9,
 * F10-03). Butirnya dari `App\Libraries\Panel\MenuAdmin`.
 *
 * Dipakai dua kali:
 * - `$tempatMenu = 'samping'`: sidebar biru tua berkelompok (K154; layar
 *   lebar tetap di kiri, ponsel jadi laci yang dibuka tombol menu di kepala,
 *   K131, K133), lalu daftar akhir Pengecekan dan Lihat halaman depan (K102).
 *   Kelompok dan submenu (`anak`) bisa dilipat dengan tombol
 *   `data-lipat="<kunci>"` (`aset/js/menu-admin.js` mengingat keadaannya);
 *   yang memuat halaman aktif selalu terbuka (`data-lipat-aktif`). Tanpa
 *   JavaScript kelompok terbuka dan submenu yang tidak aktif tertutup.
 * - `$tempatMenu = 'bawah'`: menu bawah ponsel, 5 butir Dasbor, Pendaftaran,
 *   Atlet, Rekap, Pengaturan (K174 butir 3); butir aktif ikon dan label
 *   oranye (K133).
 *
 * Penanda untuk uji dan skrip (tidak bergantung pada kelas CSS):
 * `data-menu-kelompok="<nama>"`, `data-menu-tautan="<alamat>"`,
 * `data-menu-anak` pada tautan submenu, dan `data-menu-akhir` pada daftar
 * akhir.
 *
 * @var string|null $tempatMenu `samping` atau `bawah`
 */

use App\Libraries\Panel\MenuAdmin;

$alamatAktif = MenuAdmin::alamatAktif();

$kelasTautan = static fn (bool $kini): string => ($kini
    ? 'bg-white/10 text-white'
    : 'text-primary-100 hover:bg-white/10 hover:text-white')
    . ' group flex min-h-11 min-w-0 flex-1 items-center gap-3 rounded-lg px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-white';

$tombolLipat = static fn (string $kunci, string $idDaftar, bool $buka, bool $aktif, string $label, string $kelas): string => '<button type="button" data-lipat="' . esc($kunci, 'attr') . '"' . ($aktif ? ' data-lipat-aktif' : '')
    . ' aria-controls="' . esc($idDaftar, 'attr') . '" aria-expanded="' . ($buka ? 'true' : 'false') . '" class="' . $kelas . ' focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-white">'
    . $label . ikon('chevron-down', 'size-4 shrink-0 transition-transform [[aria-expanded=false]>&]:-rotate-90') . '</button>';

$tautanSamping = static function (array $butir) use ($alamatAktif, $kelasTautan, $tombolLipat): string {
    $kini    = $butir['alamat'] === $alamatAktif;
    $anak    = $butir['anak'] ?? [];
    $tautan  = '<a data-menu-tautan="' . esc($butir['alamat'], 'attr') . '" href="' . site_url($butir['alamat']) . '"' . ($kini ? ' aria-current="page"' : '') . ' class="' . $kelasTautan($kini) . '">'
        . ikon($butir['ikon'], $kini ? 'text-oranye-400' : 'text-langit-300 group-hover:text-white')
        . '<span class="truncate">' . esc($butir['label']) . '</span></a>';

    if ($anak === []) {
        return $tautan;
    }

    $memuat = MenuAdmin::memuat($butir, $alamatAktif);
    $kunci  = 'butir-' . trim((string) preg_replace('/[^a-z0-9]+/', '-', $butir['alamat']), '-');
    $id     = 'menu-' . $kunci;
    $html   = '<div class="flex items-center">' . $tautan
        . $tombolLipat($kunci, $id, $memuat, $memuat, '<span class="sr-only">Submenu ' . esc($butir['label']) . '</span>', 'inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-langit-300 hover:bg-white/10 hover:text-white')
        . '</div><ul id="' . esc($id, 'attr') . '" class="mb-1 ms-6 mt-0.5 space-y-0.5 border-s border-white/15 ps-2"' . ($memuat ? '' : ' hidden') . '>';

    foreach ($anak as $satu) {
        $anakKini = $satu['alamat'] === $alamatAktif;
        $html .= '<li><a data-menu-tautan="' . esc($satu['alamat'], 'attr') . '" data-menu-anak href="' . site_url($satu['alamat']) . '"' . ($anakKini ? ' aria-current="page"' : '')
            . ' class="flex min-h-11 items-center rounded-lg px-3 text-sm ' . ($anakKini ? 'bg-white/10 font-semibold text-white' : 'font-medium text-primary-100 hover:bg-white/10 hover:text-white')
            . ' focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-white">' . esc($satu['label']) . '</a></li>';
    }

    return $html . '</ul>';
};
?>
<?php if (($tempatMenu ?? 'samping') === 'bawah') : ?>
    <ul class="mx-auto grid max-w-xl grid-cols-5">
        <?php foreach (MenuAdmin::bawah() as $butir) : ?>
            <?php $kini = MenuAdmin::aktifDiBawah($butir, $alamatAktif); ?>
            <li class="min-w-0">
                <a data-menu-tautan="<?= esc($butir['alamat'], 'attr') ?>" href="<?= site_url($butir['alamat']) ?>"<?= $kini ? ' aria-current="page"' : '' ?> class="flex min-h-14 flex-col items-center justify-center gap-0.5 px-0.5 text-[11px] min-[380px]:text-xs focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary-700 <?= $kini ? 'font-bold text-oranye-600' : 'font-semibold text-gray-700' ?>"<?= isset($butir['label_pendek']) ? ' title="' . esc($butir['label'], 'attr') . '"' : '' ?>>
                    <?= ikon($butir['ikon'], $kini ? 'size-6 text-oranye-500' : 'size-6 text-gray-500') ?>
                    <span class="max-w-full truncate"><?= esc($butir['label_pendek'] ?? $butir['label']) ?></span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php else : ?>
    <div class="flex min-h-full flex-col gap-3">
        <?php foreach (MenuAdmin::kelompok() as $nama => $daftar) : ?>
            <?php
            $memuat = array_filter($daftar, static fn (array $butir): bool => MenuAdmin::memuat($butir, $alamatAktif)) !== [];
            $kunci  = 'kelompok-' . trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');
            ?>
            <div data-menu-kelompok="<?= esc($nama, 'attr') ?>">
                <?php if (count($daftar) > 1) : ?>
                    <?= $tombolLipat($kunci, 'menu-' . $kunci, true, $memuat, '<span>' . esc($nama) . '</span>', 'flex min-h-9 w-full items-center justify-between rounded-lg px-3 text-xs font-bold uppercase tracking-wider text-langit-300 hover:text-white') ?>
                <?php endif ?>
                <ul id="menu-<?= esc($kunci, 'attr') ?>" class="space-y-0.5">
                    <?php foreach ($daftar as $butir) : ?>
                        <li><?= $tautanSamping($butir) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endforeach ?>
        <ul class="mt-auto space-y-0.5 border-t border-white/15 pt-3" data-menu-akhir>
            <?php foreach (MenuAdmin::akhir() as $butir) : ?>
                <li><?= $tautanSamping($butir) ?></li>
            <?php endforeach ?>
            <li>
                <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" data-menu-tautan="/" class="<?= $kelasTautan(false) ?>">
                    <?= ikon('external-link', 'text-langit-300 group-hover:text-white') ?><span>Lihat halaman depan</span> <span class="sr-only">(tab baru)</span>
                </a>
            </li>
        </ul>
    </div>
<?php endif ?>
