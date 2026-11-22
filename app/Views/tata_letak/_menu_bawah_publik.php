<?php
/**
 * Menu bawah ponsel tata letak publik (F10-07, K154), di bawah layar `lg`.
 *
 * Empat butir dari `MenuPublik::bawah()` lalu Menu yang membuka laci kiri
 * (`tata_letak/_laci_publik`). Butir aktif ikon oranye dan `aria-current`.
 * Tidak ditulis di wizard pendaftaran (bagian `tanpa_menu_bawah`); videotron
 * memakai tata letak sendiri. `<body class="ada-menu-bawah">` memberi ruang
 * bawah supaya isi halaman tidak tertutup (blok F10-07 `masukan.css`).
 *
 * @var list<array{label: string, alamat: string, aktif: bool, ikon: string}> $butir
 */

$kelas = 'flex min-h-14 w-full flex-col items-center justify-center gap-0.5 px-1 text-[0.6875rem] font-semibold leading-tight';
?>
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Menu bawah" data-menu-bawah-publik>
    <ul class="mx-auto grid max-w-lg grid-cols-5">
        <?php foreach ($butir as $b) : ?>
            <li>
                <a href="<?= esc($b['alamat']) ?>" class="<?= $kelas ?> <?= $b['aktif'] ? 'text-primary-900' : 'text-gray-600 hover:text-primary-800' ?>"<?= $b['aktif'] ? ' aria-current="page"' : '' ?>>
                    <?= ikon($b['ikon'], 'size-6 ' . ($b['aktif'] ? 'text-oranye-600' : '')) ?>
                    <span><?= esc($b['label']) ?></span>
                </a>
            </li>
        <?php endforeach ?>
        <li>
            <button type="button" class="<?= $kelas ?> text-gray-600 hover:text-primary-800" aria-controls="laci-publik" aria-expanded="false" data-buka-laci>
                <?= ikon('menu', 'size-6') ?>
                <span>Menu</span>
            </button>
        </li>
    </ul>
</nav>
