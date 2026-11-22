<?php
/**
 * Tombol bantuan di judul tiap langkah wizard (F9-04, F9-05): membuka laci
 * `pendaftaran/_laci_bantuan.php` (drawer Flowbite) berisi tanya-jawab
 * kategori langkah itu. Di ponsel ikon saja, seperti tombol Kosongkan (K161). `data-buka-bantuan` dibaca `aset/js/bantuan.js`.
 *
 * @var int $langkah 1–5
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;
?>
<button type="button" class="tombol-bantuan-langkah <?= K::GARIS ?> shrink-0 whitespace-nowrap" data-drawer-show="laci-bantuan" aria-controls="laci-bantuan" data-buka-bantuan="<?= (int) $langkah ?>">
    <?= ikon('circle-help', 'size-5') ?><span class="hidden sm:inline">Bantuan</span><span class="sr-only sm:hidden">Bantuan</span><span class="sr-only"> untuk langkah <?= (int) $langkah ?></span>
</button>
