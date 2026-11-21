<?php
/**
 * Kotak pesan (alert Flowbite Pro). Pembaca layar membacakan jenis bahaya/peringatan segera.
 *
 *     komponen('peringatan', ['jenis' => 'sukses', 'isi' => esc('Pendaftaran tersimpan.')]);
 *
 * @var string|null $jenis info (bawaan) | sukses | peringatan | bahaya
 * @var string|null $judul
 * @var string      $isi   HTML yang sudah di-escape
 * @var string|null $kelas
 */

[$warna, $ikonJenis] = [
    'info'       => ['border-primary-200 bg-primary-100 text-primary-900', 'info'],
    'sukses'     => ['border-green-200 bg-green-50 text-green-900', 'circle-check'],
    'peringatan' => ['border-yellow-200 bg-yellow-50 text-yellow-900', 'triangle-alert'],
    'bahaya'     => ['border-red-200 bg-red-50 text-red-900', 'circle-alert'],
][$jenis ?? 'info'] ?? ['', 'info'];

$peran = in_array($jenis ?? 'info', ['bahaya', 'peringatan'], true) ? 'alert' : 'status';
?>
<div class="<?= esc(trim('flex gap-3 rounded-xl border p-4 text-sm ' . $warna . ' ' . ($kelas ?? ''))) ?>" role="<?= $peran ?>">
    <?= ikon($ikonJenis, 'mt-0.5') ?>
    <div>
        <?php if (! empty($judul)) : ?>
            <p class="font-semibold"><?= esc($judul) ?></p>
        <?php endif ?>
        <div class="<?= empty($judul) ? '' : 'mt-1' ?>"><?= $isi ?></div>
    </div>
</div>
