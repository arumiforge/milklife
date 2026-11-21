<?php
/**
 * Satu atlet sebagai kartu (ponsel) dan baris tabel (layar lebar), F9-12.
 * Dipakai server untuk tiap baris dan sebagai `<template>` yang diisi
 * `aset/js/peserta.js` (`$p` kosong). Isi diganti JS lewat `data-isi`.
 *
 * @var array{nama: string, sekolah: string, nomor_atlet: string|null, kelompok_umur: string, jenis_kelamin: string, nomor: list<string>} $p
 * @var string $bentuk kartu | baris
 */

$nomorAtlet = $p['nomor_atlet'] ?? null;
?>
<?php if ($bentuk === 'kartu') : ?>
<li class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut" data-peserta>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-base font-bold break-words text-gray-900" data-isi="nama"><?= esc($p['nama']) ?></p>
            <p class="mt-0.5 text-sm break-words text-gray-700" data-isi="sekolah"><?= esc($p['sekolah']) ?></p>
            <p class="mt-1 text-sm text-gray-600"><span data-isi="kelompok_umur"><?= esc($p['kelompok_umur']) ?></span> · <span data-isi="jenis_kelamin"><?= esc($p['jenis_kelamin']) ?></span></p>
        </div>
        <div class="shrink-0 rounded-xl bg-primary-800 px-3 py-2 text-center text-white">
            <span class="block text-[0.6875rem] font-semibold tracking-wide text-primary-100 uppercase">No. atlet</span>
            <span class="block text-xl leading-tight font-extrabold tabular-nums" data-isi="nomor_atlet"><?= esc($nomorAtlet ?? '–') ?></span>
        </div>
    </div>
    <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Nomor lomba" data-isi="nomor">
        <?php foreach ($p['nomor'] as $n) : ?>
            <li class="rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-800"><?= esc($n) ?></li>
        <?php endforeach ?>
    </ul>
</li>
<?php else : ?>
<tr class="border-t border-gray-100 align-top hover:bg-gray-50" data-peserta>
    <td class="px-4 py-3 text-base font-extrabold text-primary-800 tabular-nums" data-isi="nomor_atlet"><?= esc($nomorAtlet ?? '–') ?></td>
    <th scope="row" class="px-4 py-3 font-semibold text-gray-900" data-isi="nama"><?= esc($p['nama']) ?></th>
    <td class="px-4 py-3" data-isi="sekolah"><?= esc($p['sekolah']) ?></td>
    <td class="px-4 py-3 whitespace-nowrap"><span data-isi="kelompok_umur"><?= esc($p['kelompok_umur']) ?></span> · <span data-isi="jenis_kelamin"><?= esc($p['jenis_kelamin']) ?></span></td>
    <td class="px-4 py-3">
        <ul class="flex flex-wrap gap-1.5" aria-label="Nomor lomba" data-isi="nomor">
            <?php foreach ($p['nomor'] as $n) : ?>
                <li class="rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-800"><?= esc($n) ?></li>
            <?php endforeach ?>
        </ul>
    </td>
</tr>
<?php endif ?>
