<?php
/**
 * Penunjuk langkah (stepper Flowbite Pro), misalnya langkah wizard pendaftaran.
 *
 *     komponen('langkah', ['daftar' => ['Sekolah', 'Ofisial', 'Atlet', 'Biaya', 'Pembayaran'], 'aktif' => 2]);
 *
 * Mode tombol (`'tombol' => true`, wizard pendaftaran F9-05): tiap langkah
 * berupa `<button class="pil" data-ke-langkah="<n>" disabled>` yang dinyalakan
 * dan ditandai JS (`aset/js/pendaftaran/langkah.js`) dengan kelas `pil-aktif`
 * dan `pil-selesai`; tampilannya mengikuti kelas itu lewat varian `group-[…]`.
 * Garis antarlangkah berwarna biru setelah langkah yang selesai.
 *
 * @var list<string> $daftar
 * @var int|null     $aktif  nomor langkah sekarang, mulai 1 (mode biasa)
 * @var bool|null    $tombol mode tombol untuk wizard
 */
?>
<?php if ($tombol ?? false) : ?>
<ol class="pil-langkah grid grid-cols-5 gap-1">
    <?php foreach ($daftar as $i => $nama) : ?>
        <?php $no = $i + 1; ?>
        <li class="relative">
            <?php if ($no > 1) : ?>
                <span class="absolute top-[1.55rem] right-1/2 left-[-50%] h-[3px] rounded-full bg-gray-200 [li:has(.pil-selesai)+li>&]:bg-primary-700" aria-hidden="true"></span>
            <?php endif ?>
            <button type="button" class="pil group relative z-10 flex w-full flex-col items-center gap-1.5 rounded-lg px-0.5 py-1.5 text-center text-[0.72rem] leading-tight text-gray-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200 enabled:hover:bg-primary-50 sm:text-sm [&:disabled:not(.pil-selesai)]:text-gray-400 [&.pil-aktif]:font-bold [&.pil-aktif]:text-primary-900 [&.pil-selesai]:text-primary-700" data-ke-langkah="<?= $no ?>" disabled>
                <span class="pil-nomor inline-flex size-10 items-center justify-center rounded-full border-2 border-gray-200 bg-white font-bold group-[.pil-aktif]:border-oranye-600 group-[.pil-aktif]:bg-oranye-600 group-[.pil-aktif]:text-white group-[.pil-aktif]:ring-4 group-[.pil-aktif]:ring-oranye-100 group-[.pil-selesai]:border-primary-700 group-[.pil-selesai]:bg-primary-700 group-[.pil-selesai]:text-white" aria-hidden="true">
                    <span class="pil-angka group-[.pil-selesai]:hidden"><?= $no ?></span><?= ikon('check', 'pil-centang hidden size-5 group-[.pil-selesai]:inline-block') ?>
                </span>
                <span class="pil-judul"><?= esc($nama) ?></span>
                <span class="pil-keadaan sr-only"></span>
            </button>
        </li>
    <?php endforeach ?>
</ol>
<?php else : ?>
<ol class="flex w-full items-center gap-2 text-sm font-medium text-gray-500 sm:gap-4" aria-label="Langkah">
    <?php foreach ($daftar as $i => $nama) : ?>
        <?php $no = $i + 1; $selesai = $no < $aktif; $kini = $no === $aktif; ?>
        <li class="flex flex-1 items-center gap-2<?= $kini ? ' text-primary-700' : ($selesai ? ' text-gray-900' : '') ?>"<?= $kini ? ' aria-current="step"' : '' ?>>
            <span class="<?= $kini ? 'bg-primary-700 text-white' : ($selesai ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600') ?> inline-flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                <?= $selesai ? ikon('check', 'size-4', 'Selesai') : $no ?>
            </span>
            <span class="<?= $kini ? '' : 'hidden sm:inline' ?>"><?= esc($nama) ?></span>
            <?php if ($no < count($daftar)) : ?>
                <span class="hidden h-px flex-1 bg-gray-200 sm:block" aria-hidden="true"></span>
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ol>
<?php endif ?>
