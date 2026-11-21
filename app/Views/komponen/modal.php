<?php
/**
 * Jendela modal (Flowbite Pro). Dibuka tombol ber-`data-modal-target="<id>" data-modal-toggle="<id>"`.
 *
 *     komponen('modal', ['id' => 'modal-hapus', 'judul' => 'Hapus album?', 'isi' => '<p>…</p>', 'kaki' => komponen('tombol', […])]);
 *
 * @var string      $id
 * @var string      $judul
 * @var string      $isi   HTML
 * @var string|null $kaki  HTML (tombol tindakan)
 * @var string|null $lebar sedang (bawaan) | lebar
 */

$lebar = ($lebar ?? 'sedang') === 'lebar' ? 'max-w-2xl' : 'max-w-md';
?>
<div id="<?= esc($id) ?>" tabindex="-1" aria-hidden="true" aria-labelledby="<?= esc($id) ?>-judul" role="dialog" aria-modal="true" class="fixed inset-x-0 top-0 z-50 hidden h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4">
    <div class="<?= $lebar ?> relative max-h-full w-full">
        <div class="relative rounded-2xl bg-white shadow-angkat">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 p-4 sm:px-6">
                <h3 id="<?= esc($id) ?>-judul" class="text-lg font-bold text-gray-900"><?= esc($judul) ?></h3>
                <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900" data-modal-hide="<?= esc($id) ?>">
                    <?= ikon('x', '', 'Tutup') ?>
                </button>
            </div>
            <div class="space-y-4 p-4 text-gray-700 sm:p-6"><?= $isi ?></div>
            <?php if (! empty($kaki)) : ?>
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 p-4 sm:px-6"><?= $kaki ?></div>
            <?php endif ?>
        </div>
    </div>
</div>
