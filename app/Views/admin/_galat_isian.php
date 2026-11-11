<?php
/**
 * Ringkasan isian yang salah, diletakkan tepat di atas formulir yang
 * ditampilkan ulang (status 200, tanpa pengalihan). Pesan per kotak tetap
 * ditulis halaman sendiri dengan `is-invalid` + `invalid-feedback`.
 *
 * Cara memakai di tampilan:
 *
 *     <?= view('admin/_galat_isian', ['galat' => $galat ?? []], ['saveData' => false]) ?>
 *
 *     <input class="form-control<?= isset($galat['nama']) ? ' is-invalid' : '' ?>"
 *         id="nama" name="nama" value="<?= esc($isian['nama'] ?? '', 'attr') ?>"
 *         <?= isset($galat['nama']) ? 'aria-describedby="nama-galat"' : '' ?>>
 *     <?php if (isset($galat['nama'])) : ?>
 *         <div id="nama-galat" class="invalid-feedback"><?= esc($galat['nama']) ?></div>
 *     <?php endif ?>
 *
 * Di pengendali, `$galat` biasanya `$this->validator->getErrors()` atau larik
 * buatan sendiri dengan bentuk yang sama.
 *
 * Data yang diterima:
 * - `galat` (array<string, string>): nama kotak => pesan yang menyebut cara
 *   memperbaikinya. Kosong = potongan ini tidak menampilkan apa-apa.
 *   Pesan di-escape di sini. Bila kunci sama dengan `id` kotak isian,
 *   butir ringkasan menjadi tautan yang memindahkan fokus ke kotak itu.
 * - `judul` (string, pilihan): kalimat pembuka. Bawaan: "Ada isian yang
 *   perlu diperbaiki:" (atau "Ada 1 isian yang perlu diperbaiki:").
 *
 * `saveData => false` supaya `galat` tidak terbawa ke tampilan berikutnya.
 */

$galat = isset($galat) && is_array($galat) ? array_filter($galat, static fn ($pesan): bool => is_string($pesan) && trim($pesan) !== '') : [];

if ($galat === []) {
    return;
}

$judul = isset($judul) && is_string($judul) && trim($judul) !== ''
    ? trim($judul)
    : (count($galat) === 1 ? 'Ada 1 isian yang perlu diperbaiki:' : 'Ada ' . count($galat) . ' isian yang perlu diperbaiki:');
?>
<div class="alert alert-danger galat-isian" role="alert" tabindex="-1" id="galat-isian">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
        <div class="flex-grow-1">
            <p class="fw-semibold mb-1"><?= esc($judul) ?></p>
            <ul class="mb-0 ps-3">
                <?php foreach ($galat as $kotak => $pesan) : ?>
                    <?php $idKotak = preg_match('/^[A-Za-z][\w\-]*$/', (string) $kotak) === 1 ? (string) $kotak : ''; ?>
                    <li>
                        <?php if ($idKotak !== '') : ?>
                            <a class="alert-link fw-normal" href="#<?= esc($idKotak, 'attr') ?>"><?= esc(trim($pesan)) ?></a>
                        <?php else : ?>
                            <?= esc(trim($pesan)) ?>
                        <?php endif ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
