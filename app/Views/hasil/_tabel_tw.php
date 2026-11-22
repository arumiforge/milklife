<?php
/**
 * Tabel hasil satu babak (hanya baca) untuk halaman publik (F9-03) dan
 * halaman nomor admin (F9-16, `admin` = tanda "diubah admin"). Juara I–III babak Final diberi latar kuning dan
 * `data-juara`. Di layar sempit kolom sekolah pindah ke bawah nama.
 *
 * @var array<string, mixed> $nomor `HasilLomba::nomor()`
 * @var array<string, mixed> $babak satu butir `HasilLomba::semuaBabak()`
 * @var bool|null            $admin tampilkan tanda "diubah admin" pada peringkat yang dikoreksi
 */

use App\Libraries\Hasil\Peringkat;

$beregu = $nomor['jenis'] === 'beregu';
$admin ??= false;
?>
<div class="relative overflow-x-auto rounded-xl border border-gray-200">
    <table class="w-full text-left text-sm text-gray-700">
        <caption class="sr-only">Hasil <?= esc($babak['nama']) ?></caption>
        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
            <tr>
                <th scope="col" class="px-2 py-3 sm:px-3 text-center">Per.</th>
                <?php if (! $beregu) : ?><th scope="col" class="hidden whitespace-nowrap px-2 py-3 sm:px-3 sm:table-cell">No. Atlet</th><?php endif ?>
                <th scope="col" class="px-2 py-3 sm:px-3"><?= $beregu ? 'Regu' : 'Nama' ?></th>
                <?php if (! $beregu) : ?><th scope="col" class="hidden px-2 py-3 sm:px-3 md:table-cell">Sekolah</th><?php endif ?>
                <th scope="col" class="px-2 py-3 sm:px-3">Prestasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($babak['hasil'] as $h) : ?>
                <?php $juara = $babak['final'] && $h['peringkat'] !== null && $h['peringkat'] <= 3; ?>
                <tr class="border-t border-gray-100 <?= $juara ? 'bg-yellow-50' : 'bg-white' ?>"<?= $juara ? ' data-juara="' . (int) $h['peringkat'] . '"' : '' ?>>
                    <td class="px-2 py-3 sm:px-3 text-center font-bold text-gray-900"><?= $h['peringkat'] ?? '–' ?></td>
                    <?php if (! $beregu) : ?><td class="hidden whitespace-nowrap px-2 py-3 sm:px-3 font-semibold sm:table-cell"><?= esc((string) $h['nomor_dada']) ?></td><?php endif ?>
                    <td class="px-2 py-3 sm:px-3">
                        <span class="font-semibold text-gray-900"><?= esc($h['nama']) ?></span>
                        <?php if ($admin && ! empty($h['manual'])) : ?><?= komponen('lencana', ['label' => 'diubah admin', 'warna' => 'oranye']) ?><?php endif ?>
                        <?php if (! $beregu) : ?><span class="block text-gray-500 md:hidden"><span class="sm:hidden">No. <?= esc((string) $h['nomor_dada']) ?> · </span><?= esc($h['sekolah']) ?></span><?php endif ?>
                        <?php if ($beregu && $h['anggota'] !== []) : ?><span class="block text-gray-500"><?= esc(implode(', ', $h['anggota'])) ?></span><?php endif ?>
                    </td>
                    <?php if (! $beregu) : ?><td class="hidden px-2 py-3 sm:px-3 md:table-cell"><?= esc($h['sekolah']) ?></td><?php endif ?>
                    <td class="whitespace-nowrap px-2 py-3 sm:px-3 font-semibold text-gray-900">
                        <?= esc($h['prestasi_teks']) ?><?= $h['prestasi_teks'] !== '' && ($h['rincian'] ?? []) !== [] ? ' poin' : '' ?><?= $h['keterangan'] !== null ? ' ' . komponen('lencana', ['label' => Peringkat::teks($h['keterangan'], $h['pasal'] ?? null), 'warna' => 'abu']) : '' ?>
                        <?php if (($h['rincian'] ?? []) !== []) : ?>
                            <?php // Poin multi-lomba (F9-10): prestasi dan poin tiap komponen. ?>
                            <ul class="mt-1 whitespace-normal text-xs font-normal text-gray-500" data-rincian-poin>
                                <?php foreach ($h['rincian'] as $r) : ?>
                                    <li><?= esc($r['nama']) ?> <?= $r['prestasi'] === '' ? '– (0)' : esc($r['prestasi']) . ' (' . $r['poin'] . ')' ?></li>
                                <?php endforeach ?>
                            </ul>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if ($babak['hasil'] === []) : ?>
                <tr><td colspan="<?= $beregu ? 3 : 5 ?>" class="px-3 py-6 text-center text-gray-500">Belum ada peserta.</td></tr>
            <?php endif ?>
        </tbody>
    </table>
</div>
