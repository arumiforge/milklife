<?php
/**
 * Tabel data sederhana (Flowbite Pro), bergeser mendatar di layar sempit.
 * Untuk tabel admin yang besar tetap pakai DataTables (F9-14).
 *
 *     komponen('tabel', ['judul' => 'Daftar sekolah', 'kepala' => ['Sekolah', 'Atlet'], 'baris' => [['SD Contoh', '12']]]);
 *
 * Isi sel berupa teks dan di-escape. Untuk HTML, isi sel dengan `['html' => '…']`.
 *
 * @var string|null                               $judul  keterangan tabel (caption) untuk pembaca layar
 * @var list<string>                              $kepala
 * @var list<list<string|array{html: string}>>    $baris
 * @var string|null                               $kosong teks bila tidak ada baris
 */
?>
<div class="relative overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-lembut">
    <table class="w-full text-left text-sm text-gray-600">
        <?php if (! empty($judul)) : ?>
            <caption class="sr-only"><?= esc($judul) ?></caption>
        <?php endif ?>
        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
            <tr>
                <?php foreach ($kepala as $k) : ?>
                    <th scope="col" class="whitespace-nowrap px-4 py-3"><?= esc($k) ?></th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($baris === []) : ?>
                <tr><td colspan="<?= count($kepala) ?>" class="px-4 py-8 text-center text-gray-500"><?= esc($kosong ?? 'Belum ada data.') ?></td></tr>
            <?php endif ?>
            <?php foreach ($baris as $b) : ?>
                <tr class="border-t border-gray-100 hover:bg-gray-50">
                    <?php foreach (array_values($b) as $i => $sel) : ?>
                        <?php $isiSel = is_array($sel) ? $sel['html'] : esc($sel); ?>
                        <?php if ($i === 0) : ?>
                            <th scope="row" class="px-4 py-3 font-semibold text-gray-900"><?= $isiSel ?></th>
                        <?php else : ?>
                            <td class="px-4 py-3"><?= $isiSel ?></td>
                        <?php endif ?>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
