<?php
/**
 * Sakelar nyala/mati (toggle Flowbite Pro). Mengirim `1` bila menyala;
 * isian tersembunyi `0` di depannya memastikan nilai mati ikut terkirim.
 *
 *     komponen('sakelar', ['label' => 'Tampilkan halaman Peserta', 'nama' => 'tampil_peserta', 'centang' => true]);
 *
 * @var string      $label
 * @var string      $nama
 * @var string|null $id
 * @var bool|null   $centang
 * @var string|null $bantuan
 */

$id ??= $nama;
?>
<div>
    <input type="hidden" name="<?= esc($nama) ?>" value="0">
    <label for="<?= esc($id) ?>" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
        <input type="checkbox" id="<?= esc($id) ?>" name="<?= esc($nama) ?>" value="1" class="peer sr-only"<?= ($centang ?? false) ? ' checked' : '' ?><?= empty($bantuan) ? '' : ' aria-describedby="' . esc($id) . '-bantuan"' ?>>
        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
        <span class="text-sm font-semibold text-gray-900"><?= esc($label) ?></span>
    </label>
    <?php if (! empty($bantuan)) : ?>
        <p id="<?= esc($id) ?>-bantuan" class="ms-14 text-sm text-gray-500"><?= esc($bantuan) ?></p>
    <?php endif ?>
</div>
