<?php
/**
 * Penunjuk langkah (stepper Flowbite Pro), misalnya langkah wizard pendaftaran.
 *
 *     komponen('langkah', ['daftar' => ['Sekolah', 'Ofisial', 'Atlet', 'Biaya', 'Pembayaran'], 'aktif' => 2]);
 *
 * @var list<string> $daftar
 * @var int          $aktif nomor langkah sekarang, mulai 1
 */
?>
<ol class="flex w-full items-center gap-2 text-sm font-medium text-gray-500 sm:gap-4" aria-label="Langkah">
    <?php foreach ($daftar as $i => $nama) : ?>
        <?php $no = $i + 1; $selesai = $no < $aktif; $kini = $no === $aktif; ?>
        <li class="flex flex-1 items-center gap-2<?= $kini ? ' text-primary-700' : ($selesai ? ' text-gray-900' : '') ?>"<?= $kini ? ' aria-current="step"' : '' ?>>
            <span class="<?= $kini ? 'bg-primary-700 text-white' : ($selesai ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600') ?> inline-flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                <?= $selesai ? ikon('check', 'size-4', 'Selesai') : $no ?>
            </span>
            <span class="<?= $kini ? '' : 'hidden sm:inline' ?>"><?= esc($nama) ?></span>
            <?php if ($no < count($daftar)) : ?>
                <span class="hidden h-px flex-1 bg-gray-200 sm:block" aria-hidden="true"></span>
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ol>
