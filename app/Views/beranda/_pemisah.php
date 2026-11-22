<?php
/**
 * Pemisah bagian halaman depan (sebelum tanya-jawab dan unduhan), hiasan `aria-hidden`:
 * - potongan lintasan G10-11 lewat kelas `pemisah-lintasan` (latar CSS di bawah penanda
 *   `ada-pemisah-lintasan`, F10-10) bila `elemen/pemisah-lintasan.webp` ada;
 * - selain itu potongan lintasan G9/G10-12 `pola/garis-lintasan.webp` (F9-19), dimuat `lazy`;
 * - tanpa kedua berkas tidak menulis apa pun.
 */

if (ada_gambar('elemen/pemisah-lintasan.webp')) {
    echo '<div class="mx-auto max-w-screen-xl px-4" aria-hidden="true" data-hiasan="pemisah-lintasan"><div class="pemisah-lintasan"></div></div>';

    return;
}

$garis = gambar_opsional('pola/garis-lintasan.webp', ['class' => 'pointer-events-none block h-8 w-full select-none object-cover sm:h-12']);

if ($garis === '') {
    return;
}
?>
<div class="mx-auto max-w-screen-xl px-4" aria-hidden="true" data-hiasan="garis-lintasan"><?= $garis ?></div>
