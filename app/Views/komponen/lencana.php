<?php
/**
 * Lencana keadaan (badge Flowbite Pro).
 *
 *     komponen('lencana', ['label' => 'Lunas', 'warna' => 'hijau', 'ikon' => 'check']);
 *
 * @var string      $label
 * @var string|null $warna biru (bawaan) | hijau | kuning | merah | abu | oranye
 * @var string|null $ikon
 */

$warna = [
    'biru'   => 'bg-primary-100 text-primary-800',
    'hijau'  => 'bg-green-100 text-green-800',
    'kuning' => 'bg-yellow-100 text-yellow-800',
    'merah'  => 'bg-red-100 text-red-800',
    'abu'    => 'bg-gray-100 text-gray-800',
    'oranye' => 'bg-oranye-100 text-oranye-800',
][$warna ?? 'biru'] ?? '';
?>
<span class="<?= esc('inline-flex items-center gap-1 rounded-md px-2.5 py-0.5 text-xs font-semibold ' . $warna) ?>"><?= empty($ikon) ? '' : ikon($ikon, 'size-3.5') ?><?= esc($label) ?></span>
