<?php
/**
 * Laci bantuan wizard (F9-04, tampilan Tailwind F9-05): drawer Flowbite
 * `#laci-bantuan` di kanan, dibuka tombol Bantuan di judul tiap langkah
 * (`_tombol_bantuan.php`, `data-drawer-show`). Drawer dipasang Flowbite dari
 * `data-drawer-target` di tombol tutupnya, supaya hanya ada satu instans.
 * Berisi satu kelompok per langkah (`data-bantuan-langkah`); `aset/js/bantuan.js`
 * menampilkan kelompok langkah yang tombolnya ditekan dan menyaring isinya
 * lewat kotak cari. Tanya-jawab dari Pengaturan > Bantuan
 * (`FaqModel::KATEGORI_LANGKAH`) dalam akordeon Flowbite, gambar panduan
 * G9-03 bila berkasnya ada.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Libraries\Bantuan\IsiBantuan;
use App\Libraries\Pendaftaran\KelasTampilan as K;

$isiBantuan = new IsiBantuan();
$kontak     = IsiBantuan::kontak();
$judul      = [1 => 'Data sekolah', 2 => 'Ofisial', 3 => 'Atlet', 4 => 'Biaya', 5 => 'Pembayaran'];
?>
<div id="laci-bantuan" class="laci-bantuan fixed top-0 right-0 z-50 flex h-screen w-full max-w-md translate-x-full flex-col bg-white shadow-angkat transition-transform aria-hidden:invisible" tabindex="-1" aria-labelledby="laci-bantuan-judul" aria-hidden="true">
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
        <h2 id="laci-bantuan-judul" class="flex items-center gap-2 text-lg font-bold text-primary-900"><?= ikon('circle-help', 'text-primary-700') ?> Bantuan</h2>
        <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-200" data-drawer-target="laci-bantuan" data-drawer-placement="right" data-drawer-hide="laci-bantuan" aria-controls="laci-bantuan">
            <?= ikon('x', '', 'Tutup bantuan') ?>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto p-4">
        <p class="text-sm text-gray-500">Isian Anda tetap aman selama laci ini dibuka.</p>
        <label class="<?= K::LABEL ?> mt-3" for="cari-laci-bantuan">Cari pertanyaan</label>
        <input type="search" class="<?= K::ISIAN ?> mb-4" id="cari-laci-bantuan" autocomplete="off" placeholder="Contoh: foto, NISN, transfer">

        <?php foreach ($judul as $langkah => $nama) : ?>
            <?php $butir = $isiBantuan->untukLangkah($langkah); ?>
            <?php $gambar = IsiBantuan::gambarLangkah($langkah); ?>
            <div data-bantuan-langkah="<?= $langkah ?>" hidden>
                <h3 class="mb-2 text-sm font-semibold text-gray-500">Langkah <?= $langkah ?>: <?= esc($nama) ?></h3>
                <?php if ($gambar !== null) : ?>
                    <img src="<?= esc($gambar) ?>" alt="" class="gambar-panduan mb-3 h-auto w-full rounded-xl bg-primary-50" width="1200" height="900" loading="lazy">
                <?php endif ?>
                <?php if ($butir === []) : ?>
                    <p class="text-gray-500">Belum ada tanya-jawab untuk langkah ini. Buka semua bantuan atau tanya panitia.</p>
                <?php else : ?>
                    <div id="laci-akordeon-<?= $langkah ?>" data-accordion="collapse" data-active-classes="bg-primary-50 text-primary-900" data-inactive-classes="text-gray-900" class="overflow-hidden rounded-xl border border-gray-200">
                        <?php foreach ($butir as $b) : ?>
                            <?php $id = 'laci-' . $langkah . '-' . (int) $b['id']; ?>
                            <div class="border-b border-gray-200 last:border-b-0" data-butir-faq data-cari="<?= esc($b['cari'], 'attr') ?>">
                                <h4 id="<?= $id ?>-kepala">
                                    <button type="button" class="flex min-h-12 w-full items-center justify-between gap-3 px-4 py-3 text-start text-sm font-semibold hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-inset focus:ring-primary-200" data-accordion-target="#<?= $id ?>" aria-expanded="false" aria-controls="<?= $id ?>">
                                        <span><?= esc($b['pertanyaan']) ?></span><?= ikon('chevron-down', 'size-5 shrink-0 transition-transform') ?>
                                    </button>
                                </h4>
                                <div id="<?= $id ?>" class="hidden" aria-labelledby="<?= $id ?>-kepala">
                                    <div class="format format-sm max-w-none px-4 pt-1 pb-4 text-gray-700 format-primary"><?= $b['jawaban'] ?></div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        <?php endforeach ?>

        <p class="mt-3 text-gray-500" data-cari-kosong-laci hidden>Belum ada jawaban yang cocok. Coba kata lain, atau buka semua bantuan.</p>

        <div class="mt-6 grid gap-2">
            <a class="<?= K::GARIS ?>" href="<?= site_url('bantuan') ?>" target="_blank" rel="noopener">
                <?= ikon('external-link', 'size-4') ?> Buka semua bantuan
            </a>
            <?php if ($kontak['wa_tanya'] !== null) : ?>
                <a class="<?= K::WA ?>" href="<?= esc($kontak['wa_tanya'], 'attr') ?>" target="_blank" rel="noopener noreferrer">
                    <?= ikon('message-circle', 'size-4') ?> Tanya panitia lewat WA
                </a>
            <?php endif ?>
        </div>
    </div>
</div>
