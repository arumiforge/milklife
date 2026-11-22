<?php
/**
 * Bagian Agenda halaman depan (`#jadwal`, judul "Agenda" sejak F9-07 supaya beda dari Jadwal pertandingan): sosialisasi, technical meeting,
 * dan jadwal lain yang ditandai tampil oleh admin, berurutan seperti di panel
 * admin.
 *
 * Tiap jadwal menjadi satu kartu: kotak tanggal, judul, waktu (WIB), tempat,
 * tautan (tab baru), dan keterangan (baris baru menjadi `<br>`). Teks admin
 * di-escape.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::jadwal()`):
 *
 * @var list<array{id: int, judul: string, keterangan: string, waktu: string|null, tempat: string, tautan: string}> $jadwal
 *      Jadwal yang ditandai tampil, berurutan; [] bila belum ada.
 *      `waktu` 'Y-m-d H:i:s' (WIB) atau null (belum pasti); `keterangan`, `tempat`,
 *      `tautan` '' bila kosong; `tautan` selalu alamat http/https.
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - Bagian tidak ditampilkan bila `$jadwal === []`.
 * - `<section id="jadwal" aria-labelledby="judul-jadwal">` dengan `<h2 id="judul-jadwal">`.
 * - Waktu dengan `tanggal_id($waktu, true)` + " WIB"; null → "Waktu segera diumumkan".
 * - Tautan dibuka di tab baru dengan `rel="noopener noreferrer"`.
 * - Tata letak baru Tailwind (F9-03); penanda uji berupa atribut `data-*`.
 */

if ($jadwal === []) {
    return;
}
?>
<section id="jadwal" class="scroll-mt-20" aria-labelledby="judul-jadwal">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <p class="judul-tampilan ornamen-judul" lang="en" aria-hidden="true" data-judul-inggris>Key Dates</p>
        <h2 id="judul-jadwal" class="judul-keterangan mt-2">Agenda</h2>

        <ol class="mt-8 grid gap-4 md:grid-cols-2">
            <?php foreach ($jadwal as $butir) : ?>
                <?php
                // Kotak tanggal: angka hari dan tiga huruf awal bulan ("9", "Des").
                $tanggal = $butir['waktu'] === null ? null : explode(' ', tanggal_id($butir['waktu']));
                ?>
                <li class="flex gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut" data-jadwal-butir data-id="<?= (int) $butir['id'] ?>">
                    <?php if ($tanggal === null || count($tanggal) < 3) : ?>
                        <span class="flex w-14 shrink-0 justify-center pt-1 text-gray-400" aria-hidden="true" data-jadwal-tanggal><?= ikon('hourglass', 'size-8') ?></span>
                    <?php else : ?>
                        <span class="flex w-14 shrink-0 flex-col items-center border-e border-gray-200 pe-3 text-primary-900" aria-hidden="true" data-jadwal-tanggal>
                            <span class="font-tampilan text-4xl font-extrabold leading-none"><?= esc($tanggal[0]) ?></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-oranye-700"><?= esc(mb_substr($tanggal[1], 0, 3)) ?></span>
                        </span>
                    <?php endif ?>

                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-bold text-gray-900"><?= esc($butir['judul']) ?></h3>

                        <ul class="mt-1 space-y-1 text-sm text-gray-600">
                            <li class="flex items-start gap-1.5" data-jadwal-waktu>
                                <?= ikon('clock', 'mt-0.5 size-4 text-primary-700') ?>
                                <?php if ($butir['waktu'] === null) : ?>
                                    <span>Waktu segera diumumkan</span>
                                <?php else : ?>
                                    <span><?= esc(tanggal_id($butir['waktu'], true)) ?> WIB</span>
                                <?php endif ?>
                            </li>
                            <?php if ($butir['tempat'] !== '') : ?>
                                <li class="flex items-start gap-1.5" data-jadwal-tempat>
                                    <?= ikon('map-pin', 'mt-0.5 size-4 text-oranye-600') ?>
                                    <span class="break-words"><?= esc($butir['tempat']) ?></span>
                                </li>
                            <?php endif ?>
                        </ul>

                        <?php if ($butir['keterangan'] !== '') : ?>
                            <p class="mt-2 break-words text-gray-700" data-jadwal-keterangan><?= nl2br(esc($butir['keterangan']), false) ?></p>
                        <?php endif ?>

                        <?php if ($butir['tautan'] !== '') : ?>
                            <a class="mt-3 inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-primary-800 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100" href="<?= esc($butir['tautan'], 'attr') ?>" target="_blank" rel="noopener noreferrer" data-jadwal-tautan>
                                <?= ikon('external-link', 'size-4') ?> Buka tautan<span class="sr-only"> <?= esc($butir['judul']) ?> (di tab baru)</span>
                            </a>
                        <?php endif ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ol>
    </div>
</section>
