<?php
/**
 * Tabel klasemen sekolah per jenjang untuk halaman publik (F9-03) dan panel
 * admin (F9-16, `admin` = tanda "belum terbit" pada piala dari nomor yang
 * belum diterbitkan). Rincian piala tiap sekolah bisa dibuka
 * (`<details>`); juara umum I–III diberi latar kuning dan `data-juara-umum`.
 *
 * Kolom poin selalu tampil (F9-11): pada metode poin ditebalkan dan tampil di
 * ponsel, pada metode medali disembunyikan di ponsel.
 *
 * @var array<string, array<string, mixed>> $klasemen `HasilLomba::klasemen()`
 * @var string                              $metode   `Klasemen::MEDALI` atau `Klasemen::POIN`
 * @var bool|null                           $admin
 */

use App\Libraries\Hasil\Klasemen;

$romawi     = [1 => 'I', 2 => 'II', 3 => 'III'];
$metodePoin = ($metode ?? Klasemen::MEDALI) === Klasemen::POIN;
$admin ??= false;
?>
<?php foreach ($klasemen as $jenjang) : ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-6" aria-labelledby="klasemen-<?= esc($jenjang['jenjang'], 'attr') ?>">
        <h2 class="text-xl font-extrabold text-primary-900" id="klasemen-<?= esc($jenjang['jenjang'], 'attr') ?>"><?= esc($jenjang['label']) ?></h2>
        <div class="relative mt-4 overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-sm text-gray-700">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                    <tr>
                        <th scope="col" class="px-2 py-3 text-center sm:px-3">Per.</th>
                        <th scope="col" class="px-2 py-3 sm:px-3">Sekolah</th>
                        <th scope="col" class="px-2 py-3 text-center sm:px-3"><span class="sr-only">Juara </span>I</th>
                        <th scope="col" class="px-2 py-3 text-center sm:px-3"><span class="sr-only">Juara </span>II</th>
                        <th scope="col" class="px-2 py-3 text-center sm:px-3"><span class="sr-only">Juara </span>III</th>
                        <th scope="col" class="hidden px-2 py-3 text-center sm:px-3 sm:table-cell">Jumlah</th>
                        <th scope="col" class="<?= $metodePoin ? '' : 'hidden sm:table-cell ' ?>px-2 py-3 text-center sm:px-3" data-kolom="poin">Poin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jenjang['baris'] as $b) : ?>
                        <tr class="border-t border-gray-100 align-top <?= $b['juara_umum'] !== null ? 'bg-yellow-50' : 'bg-white' ?>"<?= $b['juara_umum'] !== null ? ' data-juara-umum="' . (int) $b['juara_umum'] . '"' : '' ?>>
                            <td class="px-2 py-3 text-center sm:px-3 font-bold text-gray-900"><?= $b['peringkat'] ?></td>
                            <td class="px-2 py-3 sm:px-3">
                                <span class="font-semibold break-words text-gray-900"><?= esc($b['sekolah']) ?></span>
                                <?php if ($b['juara_umum'] !== null) : ?>
                                    <?= komponen('lencana', ['label' => 'Juara umum ' . $romawi[$b['juara_umum']], 'warna' => 'kuning']) ?>
                                <?php endif ?>
                                <?php if ($b['piala'] === []) : ?>
                                    <span class="mt-1 block text-sm text-gray-600">Tanpa piala; poin dari peringkat 4–<?= Klasemen::PERINGKAT_POIN ?></span>
                                <?php else : ?>
                                    <details class="mt-1 text-gray-600">
                                        <summary class="inline-flex min-h-11 cursor-pointer items-center text-sm font-semibold text-primary-700">Rincian <?= $b['jumlah'] ?> piala</summary>
                                        <ul class="mb-1 list-disc space-y-0.5 ps-5 text-sm">
                                            <?php foreach ($b['piala'] as $p) : ?>
                                                <li>Juara <?= $romawi[$p['peringkat']] ?> <?= esc($p['kelompok_umur'] . ' ' . $p['nomor']) ?> – <?= esc($p['nama']) ?><?php if ($admin && empty($p['terbit'])) : ?> <?= komponen('lencana', ['label' => 'belum terbit', 'warna' => 'abu']) ?><?php endif ?></li>
                                            <?php endforeach ?>
                                        </ul>
                                    </details>
                                <?php endif ?>
                            </td>
                            <td class="px-2 py-3 text-center sm:px-3 font-semibold"><?= $b['emas'] ?></td>
                            <td class="px-2 py-3 text-center sm:px-3 font-semibold"><?= $b['perak'] ?></td>
                            <td class="px-2 py-3 text-center sm:px-3 font-semibold"><?= $b['perunggu'] ?></td>
                            <td class="hidden px-2 py-3 text-center sm:px-3 font-bold sm:table-cell"><?= $b['jumlah'] ?></td>
                            <td class="<?= $metodePoin ? 'font-extrabold text-gray-900' : 'hidden font-semibold sm:table-cell' ?> px-2 py-3 text-center sm:px-3"><?= $b['poin'] ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endforeach ?>
