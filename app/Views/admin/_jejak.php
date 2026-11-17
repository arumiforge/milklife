<?php
/**
 * Jejak navigasi (breadcrumb) panel admin (K117), di atas isi halaman turunan.
 *
 * Halaman menuliskannya di bagian `jejak` tata letak admin:
 *
 *     <?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [
 *         ['label' => 'Atlet', 'alamat' => 'admin/atlet'],
 *         ['label' => 'MAC2A0000126', 'alamat' => 'admin/pendaftaran/5'],
 *         ['label' => 'Ubah'],                 // butir terakhir: halaman ini, tanpa alamat
 *     ]], ['saveData' => false]) ?><?= $this->endSection() ?>
 *
 * Butir pertama menu induknya (mengikuti asal halaman, `AsalRincian`). Di
 * ponsel hanya tampil satu tautan "kembali" ke butir sebelum terakhir,
 * supaya tidak memenuhi layar.
 *
 * @var list<array{label: string, alamat?: string|null}> $jejak
 */
$jejak   = array_values($jejak ?? []);
$induk   = count($jejak) > 1 ? $jejak[count($jejak) - 2] : null;
?>
<nav class="jejak-admin mb-3" aria-label="Jejak navigasi">
    <?php if ($induk !== null && ($induk['alamat'] ?? null) !== null) : ?>
        <a class="jejak-admin-kembali d-md-none" href="<?= site_url($induk['alamat']) ?>">
            <i class="bi bi-chevron-left" aria-hidden="true"></i> <?= esc($induk['label']) ?>
        </a>
    <?php endif ?>
    <ol class="breadcrumb mb-0<?= $induk !== null ? ' d-none d-md-flex' : '' ?>">
        <li class="breadcrumb-item"><a href="<?= site_url('admin/dasbor') ?>"><i class="bi bi-house-door" aria-hidden="true"></i><span class="visually-hidden">Dasbor</span></a></li>
        <?php foreach ($jejak as $i => $butir) : ?>
            <?php $akhir = $i === count($jejak) - 1; ?>
            <?php if ($akhir || ($butir['alamat'] ?? null) === null) : ?>
                <li class="breadcrumb-item active"<?= $akhir ? ' aria-current="page"' : '' ?>><?= esc($butir['label']) ?></li>
            <?php else : ?>
                <li class="breadcrumb-item"><a href="<?= site_url($butir['alamat']) ?>"><?= esc($butir['label']) ?></a></li>
            <?php endif ?>
        <?php endforeach ?>
    </ol>
</nav>
