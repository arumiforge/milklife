<?php
/**
 * Tombol bantuan di judul tiap langkah wizard (F9-04): membuka laci
 * `pendaftaran/_laci_bantuan.php` berisi tanya-jawab kategori langkah itu.
 * Bootstrap offcanvas sampai wizard pindah ke Tailwind (F9-05).
 *
 * @var int $langkah 1–5
 */
?>
<button type="button" class="btn btn-outline-primary tombol-bantuan-langkah" data-bs-toggle="offcanvas" data-bs-target="#laci-bantuan" aria-controls="laci-bantuan" data-buka-bantuan="<?= (int) $langkah ?>">
    <i class="bi bi-question-circle" aria-hidden="true"></i> Bantuan<span class="visually-hidden"> untuk langkah <?= (int) $langkah ?></span>
</button>
