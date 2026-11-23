<?php
/**
 * Menu bawah ponsel tata letak publik (F10-07, K175), di bawah layar `lg`.
 *
 * Lima butir dari `MenuPublik::bawah()`: Beranda, Bantuan, tombol tengah
 * (Daftar, atau Hasil setelah pendaftaran ditutup), Cek, Jadwal (atau
 * Unduhan). Tombol tengah (`utama`) berupa lingkaran oranye lebih besar yang
 * sedikit menonjol ke atas bilah. Butir aktif ikon oranye dan `aria-current`.
 * Tidak ditulis di wizard pendaftaran (bagian `tanpa_menu_bawah`); videotron
 * memakai tata letak sendiri. `<body class="ada-menu-bawah">` memberi ruang
 * bawah supaya isi halaman tidak tertutup (blok F10-07 `masukan.css`).
 *
 * @var list<array{label: string, alamat: string, aktif: bool, ikon: string, utama?: bool}> $butir
 */

$kelas = 'flex min-h-14 w-full flex-col items-center justify-center gap-0.5 px-1 text-[0.6875rem] font-semibold leading-tight';
?>
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Menu bawah" data-menu-bawah-publik>
    <ul class="mx-auto grid max-w-lg grid-cols-5">
        <?php foreach ($butir as $b) : ?>
            <li>
                <?php if ($b['utama'] ?? false) : ?>
                    <a href="<?= esc($b['alamat']) ?>" class="<?= $kelas ?> group text-primary-900"<?= $b['aktif'] ? ' aria-current="page"' : '' ?> data-menu-bawah-utama>
                        <span class="-mt-6 flex size-14 items-center justify-center rounded-full bg-oranye-600 text-white shadow-angkat ring-4 ring-white transition group-hover:bg-oranye-700 group-focus-visible:ring-oranye-300 group-aria-[current=page]:bg-primary-900">
                            <?= ikon($b['ikon'], 'size-7') ?>
                        </span>
                        <span class="text-xs font-extrabold"><?= esc($b['label']) ?></span>
                    </a>
                <?php else : ?>
                    <a href="<?= esc($b['alamat']) ?>" class="<?= $kelas ?> <?= $b['aktif'] ? 'text-primary-900' : 'text-gray-600 hover:text-primary-800' ?>"<?= $b['aktif'] ? ' aria-current="page"' : '' ?>>
                        <?= ikon($b['ikon'], 'size-6 ' . ($b['aktif'] ? 'text-oranye-600' : '')) ?>
                        <span><?= esc($b['label']) ?></span>
                    </a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
