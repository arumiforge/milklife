<?php
/**
 * Tombol Kosongkan di judul tiap langkah wizard, di kanan tombol Bantuan
 * (permintaan pemilik 22 November 2026, K160). Layar sm ke atas: ikon dan
 * kata "Kosongkan"; ponsel: ikon saja (nama lengkap tetap untuk pembaca
 * layar). Saat diarahkan tetikus, popover Flowbite menjelaskan akibatnya.
 * Klik membuka jendela konfirmasi dari `aset/js/pendaftaran.js`
 * (`[data-mulai-ulang]`); semua tombol disembunyikan setelah pendaftaran
 * terkirim (`pendaftaran/pembayaran.js`).
 *
 * @var int $langkah 1–5
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$idPopover = 'popover-kosongkan-' . (int) $langkah;
?>
<button type="button" class="tombol-mulai-ulang <?= K::GARIS ?> shrink-0 whitespace-nowrap" data-mulai-ulang data-popover-target="<?= $idPopover ?>" data-popover-trigger="hover" data-popover-placement="bottom" aria-describedby="<?= $idPopover ?>">
    <?= ikon('rotate-ccw', 'size-5') ?><span class="hidden sm:inline">Kosongkan</span><span class="sr-only sm:hidden">Kosongkan isian</span>
</button>
<div id="<?= $idPopover ?>" role="tooltip" class="invisible absolute z-30 inline-block w-64 rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-700 opacity-0 shadow-angkat transition-opacity duration-200" data-popover>
    Hapus semua isian sekolah, ofisial, dan atlet, lalu mulai lagi dari langkah 1.
</div>
