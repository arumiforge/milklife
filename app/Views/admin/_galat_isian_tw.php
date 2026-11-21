<?php
/**
 * Ringkasan isian yang salah untuk halaman admin bertampilan baru (`admin_tw`,
 * Tahap 9), padanan `admin/_galat_isian.php`. Diletakkan tepat di atas
 * formulir yang ditampilkan ulang (status 200, tanpa pengalihan). Pesan per
 * kotak ditulis komponen `isian`/`pilihan` (`galat`) atau halaman sendiri
 * dengan `aria-invalid="true"` dan `aria-describedby="<id>-galat"`.
 *
 *     <?= view('admin/_galat_isian_tw', ['galat' => $galat ?? []], ['saveData' => false]) ?>
 *
 * Data yang diterima:
 * - `galat` (array<string, string>): nama kotak => pesan. Kosong = tidak tampil.
 *   Bila kunci sama dengan `id` kotak isian, butir menjadi tautan ke kotak itu.
 * - `judul` (string, pilihan): kalimat pembuka.
 */

$galat = isset($galat) && is_array($galat) ? array_filter($galat, static fn ($pesan): bool => is_string($pesan) && trim($pesan) !== '') : [];

if ($galat === []) {
    return;
}

$judul = isset($judul) && is_string($judul) && trim($judul) !== ''
    ? trim($judul)
    : (count($galat) === 1 ? 'Ada 1 isian yang perlu diperbaiki:' : 'Ada ' . count($galat) . ' isian yang perlu diperbaiki:');
?>
<div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" tabindex="-1" id="galat-isian" data-galat-isian>
    <?= ikon('circle-alert', 'mt-0.5 shrink-0') ?>
    <div class="min-w-0 flex-1">
        <p class="mb-1 font-semibold"><?= esc($judul) ?></p>
        <ul class="list-disc space-y-0.5 ps-5">
            <?php foreach ($galat as $kotak => $pesan) : ?>
                <?php $idKotak = preg_match('/^[A-Za-z][\w\-]*$/', (string) $kotak) === 1 ? (string) $kotak : ''; ?>
                <li>
                    <?php if ($idKotak !== '') : ?>
                        <a class="underline hover:no-underline" href="#<?= esc($idKotak, 'attr') ?>"><?= esc(trim($pesan)) ?></a>
                    <?php else : ?>
                        <?= esc(trim($pesan)) ?>
                    <?php endif ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
