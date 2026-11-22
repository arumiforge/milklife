<?php
/**
 * Bagian tanya-jawab teratas halaman depan (`#faq`, F9-03): pertanyaan
 * pertama tiap kategori Bantuan (F9-04), dilipat dengan `<details>` (tanpa
 * skrip), dan tautan ke halaman Bantuan. Tidak tampil bila belum ada.
 *
 * @var list<array{id: int, pertanyaan: string, jawaban: string, kategori: string}> $faq `IsiHalamanDepan::faqTeratas()`; `jawaban` HTML aman
 */

if ($faq === []) {
    return;
}
?>
<section id="faq" class="scroll-mt-20" aria-labelledby="judul-faq">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="judul-tampilan ornamen-judul" lang="en" aria-hidden="true" data-judul-inggris>Got Questions?</p>
                <h2 id="judul-faq" class="judul-keterangan mt-2">Pertanyaan yang sering ditanyakan</h2>
            </div>
            <?= komponen('tombol', ['label' => 'Lihat semua bantuan', 'href' => site_url('bantuan'), 'varian' => 'kedua', 'ikon' => 'circle-help']) ?>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut" data-faq-teratas>
            <?php foreach ($faq as $b) : ?>
                <details class="group border-b border-gray-200 last:border-b-0" data-faq-butir="<?= (int) $b['id'] ?>">
                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 font-semibold text-gray-900 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                        <span class="flex flex-col">
                            <span class="text-xs font-bold uppercase tracking-wide text-oranye-700"><?= esc($b['kategori']) ?></span>
                            <span><?= esc($b['pertanyaan']) ?></span>
                        </span>
                        <?= ikon('chevron-down', 'shrink-0 transition-transform group-open:rotate-180') ?>
                    </summary>
                    <div class="format format-primary max-w-none px-5 pb-5 text-gray-700"><?= $b['jawaban'] ?></div>
                </details>
            <?php endforeach ?>
        </div>
    </div>
</section>
