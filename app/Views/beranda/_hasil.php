<?php
/**
 * Bagian hasil halaman depan (`#hasil`, F9-03): nomor yang paling akhir
 * diterbitkan beserta juara I–III Final-nya, dan juara umum I–III per
 * jenjang (seperti poster "Juara umum"). Hanya nomor yang hasilnya sudah
 * diterbitkan panitia; bagian tidak tampil sebelum ada yang terbit
 * (`IsiHalamanDepan::hasilTerbaru()` = `null`).
 *
 * @var array{nomor: list<array{id: int, nama: string, kelompok_umur: string, beregu: bool, juara: list<array{peringkat: int, nama: string, sekolah: string}>}>, juara_umum: list<array{jenjang: string, label: string, baris: list<array<string, mixed>>}>, metode: string}|null $hasilTerbaru
 */

if ($hasilTerbaru === null) {
    return;
}

$romawi = [1 => 'I', 2 => 'II', 3 => 'III'];
$medali = [1 => 'bg-yellow-100 text-yellow-800', 2 => 'bg-gray-200 text-gray-800', 3 => 'bg-oranye-100 text-oranye-800'];
?>
<section id="hasil" class="scroll-mt-20 bg-white" aria-labelledby="judul-hasil">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <div class="flex flex-col items-center gap-4 text-center">
            <div>
                <p class="judul-tampilan" lang="en" aria-hidden="true" data-judul-inggris>Results</p>
                <h2 id="judul-hasil" class="judul-keterangan mt-2">Hasil dan juara umum terbaru</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Lihat semua hasil', 'href' => site_url('hasil'), 'varian' => 'kedua', 'ikon' => 'trophy']) ?>
                <?= komponen('tombol', ['label' => 'Lihat klasemen sekolah', 'href' => site_url('hasil/klasemen'), 'varian' => 'kedua', 'ikon' => 'award']) ?>
            </div>
        </div>

        <?php if ($hasilTerbaru['juara_umum'] !== []) : ?>
            <div class="mt-8 grid gap-5 <?= count($hasilTerbaru['juara_umum']) >= 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' ?>" data-hasil-juara-umum>
                <?php
                // Gambar G9-06 bila berkasnya ada (F9-19): piala di judul, medali menggantikan kotak peringkat 1–3.
                $piala  = gambar_opsional('layar/piala.webp', ['class' => 'size-7 shrink-0 object-contain', 'data-piala-juara-umum' => true]);
                $medali = [1 => 'emas', 2 => 'perak', 3 => 'perunggu'];
                ?>
                <?php foreach ($hasilTerbaru['juara_umum'] as $jenjang) : ?>
                    <article class="rounded-2xl bg-primary-900 p-5 text-white shadow-angkat" aria-labelledby="hasil-umum-<?= esc($jenjang['jenjang'], 'attr') ?>">
                        <h3 id="hasil-umum-<?= esc($jenjang['jenjang'], 'attr') ?>" class="flex items-center gap-2 text-lg font-extrabold"><?= $piala !== '' ? $piala : ikon('medal', 'text-oranye-400') ?>Juara umum <?= esc($jenjang['label']) ?></h3>
                        <ol class="mt-4 space-y-2">
                            <?php foreach ($jenjang['baris'] as $b) : ?>
                                <li class="flex items-center gap-3 rounded-xl bg-white/10 p-3">
                                    <?php $gambarMedali = isset($medali[(int) $b['peringkat']]) ? gambar_opsional('layar/medali-' . $medali[(int) $b['peringkat']] . '.webp', ['class' => 'size-9 shrink-0 object-contain', 'data-medali-juara-umum' => (int) $b['peringkat']]) : ''; ?>
                                    <?php if ($gambarMedali !== '') : ?>
                                        <?= $gambarMedali ?><span class="sr-only"><?= (int) $b['peringkat'] ?>.</span>
                                    <?php else : ?>
                                        <span class="w-9 shrink-0 text-center font-tampilan text-3xl font-extrabold text-langit-300"><?= (int) $b['peringkat'] ?><span class="sr-only">.</span></span>
                                    <?php endif ?>
                                    <span class="min-w-0 flex-1 font-bold break-words"><?= esc($b['sekolah']) ?></span>
                                    <?php if (($hasilTerbaru['metode'] ?? '') === 'poin') : ?>
                                        <span class="shrink-0 text-sm text-primary-100" data-poin><span class="font-extrabold text-white"><?= (int) $b['poin'] ?></span> poin</span>
                                    <?php else : ?>
                                        <span class="shrink-0 text-sm text-primary-100"><span class="font-extrabold text-white"><?= (int) $b['emas'] ?></span><span class="sr-only"> juara I,</span> · <?= (int) $b['perak'] ?><span class="sr-only"> juara II,</span> · <?= (int) $b['perunggu'] ?><span class="sr-only"> juara III</span></span>
                                    <?php endif ?>
                                </li>
                            <?php endforeach ?>
                        </ol>
                        <p class="mt-3 text-xs text-primary-200"><?= ($hasilTerbaru['metode'] ?? '') === 'poin' ? 'Diurutkan dari poin terbanyak, lalu jumlah juara I, II, dan III.' : 'Diurutkan dari jumlah juara I terbanyak, lalu juara II, lalu juara III.' ?></p>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($hasilTerbaru['nomor'] !== []) : ?>
            <h3 class="mt-10 text-xl font-bold text-gray-900">Baru diterbitkan</h3>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-hasil-terbaru>
                <?php foreach ($hasilTerbaru['nomor'] as $n) : ?>
                    <li class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut" data-hasil-nomor="<?= (int) $n['id'] ?>">
                        <p class="text-sm font-semibold text-oranye-700"><?= esc($n['kelompok_umur']) ?></p>
                        <h4 class="text-lg font-bold text-primary-900"><?= esc($n['nama']) ?></h4>
                        <?php if ($n['juara'] === []) : ?>
                            <p class="mt-2 text-sm text-gray-600">Juara belum ditetapkan. Lihat hasil semua babak.</p>
                        <?php else : ?>
                            <ol class="mt-3 space-y-2 text-sm">
                                <?php foreach ($n['juara'] as $j) : ?>
                                    <li class="flex items-start gap-2">
                                        <span class="<?= $medali[$j['peringkat']] ?? 'bg-gray-100 text-gray-800' ?> shrink-0 rounded-md px-2 py-0.5 text-xs font-bold">Juara <?= $romawi[$j['peringkat']] ?? (int) $j['peringkat'] ?></span>
                                        <span class="min-w-0 break-words"><span class="font-semibold text-gray-900"><?= esc($j['nama']) ?></span><?php if (! $n['beregu']) : ?> <span class="block text-gray-500"><?= esc($j['sekolah']) ?></span><?php endif ?></span>
                                    </li>
                                <?php endforeach ?>
                            </ol>
                        <?php endif ?>
                        <a href="<?= site_url('hasil/' . $n['id']) ?>" class="mt-auto inline-flex min-h-11 items-center gap-1 pt-3 text-sm font-semibold text-primary-700 hover:underline">Lihat hasil<span class="sr-only"> <?= esc($n['nama'] . ' ' . $n['kelompok_umur']) ?></span><?= ikon('arrow-right', 'size-4') ?></a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
