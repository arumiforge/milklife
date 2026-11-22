<?php
/**
 * Pemisah bagian halaman depan: potongan lintasan G9-12 `pola/garis-lintasan.webp`
 * (hiasan, `aria-hidden`), hanya bila berkasnya ada (F9-19); tanpa berkas tidak
 * menulis apa pun. Dimuat `lazy` dan pendek di ponsel.
 */

$garis = gambar_opsional('pola/garis-lintasan.webp', ['class' => 'pointer-events-none block h-8 w-full select-none object-cover sm:h-12']);

if ($garis === '') {
    return;
}
?>
<div class="mx-auto max-w-screen-xl px-4" aria-hidden="true" data-hiasan="garis-lintasan"><?= $garis ?></div>
