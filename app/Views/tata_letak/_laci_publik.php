<?php
/**
 * Laci kanan ponsel tata letak publik (F10-07, K154; dari kanan sejak F10-08), di bawah layar `lg`.
 *
 * Dibuka tombol `[data-buka-laci]` (tombol menu di kanan kepala halaman),
 * ditutup tombol `[data-tutup-laci]`, Esc, atau ketuk latar.
 * Fokus terkunci di dalam laci selama terbuka dan kembali ke tombol pembuka
 * saat ditutup (`aset/js/menu-publik.js`). Laci tertutup tidak terlihat dan
 * tidak bisa difokus (`invisible`); keadaan terbuka ditandai `data-terbuka`.
 * Saat membuka hanya `transform` yang beralih, supaya laci langsung terlihat
 * dan tombol tutupnya bisa difokus; saat menutup `visibility` menunggu geseran selesai.
 *
 * Isi: menu utama, tombol Cek Pendaftaran dan Daftar, butir bilah atas, dan
 * media sosial. Ikon polos tanpa latar.
 *
 * @var list<array{label: string, alamat: string, aktif: bool, ikon: string}>                                          $menu
 * @var list<array{label: string, alamat: string, varian: string, ikon: string}>                                       $tombol
 * @var list<array{kunci: string, label: string, alamat: string, aktif: bool, ikon: string, luar: bool}>               $bilahAtas
 * @var list<array{label: string, alamat: string, ikon: string}>                                                       $sosial
 * @var string                                                                                                         $namaAcara
 */

$kelasButir = static fn (bool $aktif): string => ($aktif ? 'bg-primary-100 text-primary-900' : 'text-gray-800 hover:bg-gray-100')
    . ' flex min-h-12 items-center gap-3 rounded-lg px-3 font-semibold';
?>
<div class="fixed inset-0 z-50 hidden bg-primary-950/60 lg:hidden" data-latar-laci aria-hidden="true"></div>
<div id="laci-publik" class="invisible fixed inset-y-0 end-0 z-50 flex w-80 max-w-[85vw] translate-x-full flex-col bg-white shadow-angkat transition-[transform,visibility] duration-200 motion-reduce:transition-none data-terbuka:visible data-terbuka:translate-x-0 data-terbuka:transition-transform lg:hidden" role="dialog" aria-modal="true" aria-labelledby="laci-publik-judul" tabindex="-1" data-laci-publik>
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-2">
        <h2 id="laci-publik-judul" class="min-w-0 truncate text-base font-extrabold text-primary-900"><?= esc($namaAcara) ?></h2>
        <button type="button" class="-me-2 inline-flex size-12 shrink-0 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-300" aria-controls="laci-publik" data-tutup-laci>
            <?= ikon('x', 'size-6', 'Tutup menu') ?>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4">
        <nav aria-label="Menu ponsel">
            <ul class="space-y-1" data-laci-utama>
                <?php foreach ($menu as $butir) : ?>
                    <li>
                        <a href="<?= esc($butir['alamat']) ?>" class="<?= $kelasButir($butir['aktif']) ?>"<?= $butir['aktif'] ? ' aria-current="page"' : '' ?>>
                            <?= ikon($butir['ikon'], 'size-5 shrink-0 ' . ($butir['aktif'] ? 'text-oranye-600' : 'text-primary-700')) ?><?= esc($butir['label']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>

            <div class="mt-4 flex flex-col gap-2 px-1">
                <?php foreach ($tombol as $t) : ?>
                    <?= komponen('tombol', ['label' => $t['label'], 'href' => $t['alamat'], 'varian' => $t['varian'], 'ikon' => $t['ikon'], 'ikonKanan' => $t['varian'] === 'oranye', 'blok' => true, 'ukuran' => 'besar']) ?>
                <?php endforeach ?>
            </div>

            <?php if ($bilahAtas !== []) : ?>
                <ul class="mt-4 space-y-1 border-t border-gray-200 pt-4" data-laci-tambahan>
                    <?php foreach ($bilahAtas as $butir) : ?>
                        <li>
                            <a href="<?= esc($butir['alamat']) ?>" class="<?= $kelasButir($butir['aktif']) ?>"<?= $butir['aktif'] ? ' aria-current="page"' : '' ?><?= $butir['luar'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                <?= ikon($butir['ikon'], 'size-5 shrink-0 ' . ($butir['aktif'] ? 'text-oranye-600' : 'text-primary-700')) ?><?= esc($butir['label']) ?><?= $butir['luar'] ? '<span class="sr-only"> (di tab baru)</span>' : '' ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </nav>

        <?php if ($sosial !== []) : ?>
            <ul class="mt-4 flex flex-wrap gap-1 border-t border-gray-200 pt-4" aria-label="Media sosial" data-laci-sosial>
                <?php foreach ($sosial as $media) : ?>
                    <li>
                        <a href="<?= esc($media['alamat']) ?>" target="_blank" rel="noopener" class="inline-flex size-12 items-center justify-center rounded-lg text-primary-700 hover:bg-gray-100">
                            <?= ikon($media['ikon'], 'size-6', $media['label'] . ' (di tab baru)') ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</div>
