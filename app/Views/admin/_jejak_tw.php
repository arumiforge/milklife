<?php
/**
 * Jejak navigasi (breadcrumb, K117) untuk halaman admin bertampilan baru
 * (`admin_tw`, Tahap 9), dengan data:
 *
 *     <?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [
 *         ['label' => 'Hasil lomba', 'alamat' => 'admin/hasil'],
 *         ['label' => 'Lari 60 m'],            // butir terakhir: halaman ini, tanpa alamat
 *     ]], ['saveData' => false]) ?><?= $this->endSection() ?>
 *
 * Di ponsel hanya tampil satu tautan "kembali" ke butir sebelum terakhir.
 *
 * @var list<array{label: string, alamat?: string|null}> $jejak
 */
$jejak = array_values($jejak ?? []);
$induk = count($jejak) > 1 ? $jejak[count($jejak) - 2] : null;
?>
<nav class="text-sm" aria-label="Jejak navigasi" data-jejak>
    <?php if ($induk !== null && ($induk['alamat'] ?? null) !== null) : ?>
        <a class="inline-flex min-h-11 items-center gap-1 font-semibold text-primary-700 hover:underline md:hidden" href="<?= site_url($induk['alamat']) ?>" data-jejak-kembali>
            <?= ikon('chevron-left', 'size-4') ?><?= esc($induk['label']) ?>
        </a>
    <?php endif ?>
    <ol class="<?= $induk !== null ? 'hidden md:flex' : 'flex' ?> flex-wrap items-center gap-1.5 text-gray-500">
        <li><a class="inline-flex min-h-11 items-center hover:text-primary-700" href="<?= site_url('admin/dasbor') ?>"><?= ikon('house', 'size-4', 'Dasbor') ?></a></li>
        <?php foreach ($jejak as $i => $butir) : ?>
            <?php $akhir = $i === count($jejak) - 1; ?>
            <li class="flex items-center gap-1.5">
                <?= ikon('chevron-right', 'size-4 text-gray-400') ?>
                <?php if ($akhir || ($butir['alamat'] ?? null) === null) : ?>
                    <span class="font-semibold text-gray-900"<?= $akhir ? ' aria-current="page"' : '' ?>><?= esc($butir['label']) ?></span>
                <?php else : ?>
                    <a class="font-medium text-primary-700 hover:underline" href="<?= site_url($butir['alamat']) ?>"><?= esc($butir['label']) ?></a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>
