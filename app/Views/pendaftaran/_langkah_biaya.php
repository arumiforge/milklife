<?php
/**
 * Langkah 4: Biaya (rancangan 7; K45 rincian selalu tampil). Rincian dihitung
 * server (`POST pendaftaran/periksa`) dan ditulis `aset/js/pendaftaran/biaya.js` ke
 * `#rincian-biaya`. Belum ada yang disimpan di langkah ini (K90): pendaftaran
 * baru masuk sistem bersama bukti transfer di langkah 5.
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;
?>
<section class="langkah" id="langkah-4" data-langkah="4" aria-labelledby="judul-langkah-4" hidden>
    <?= view('pendaftaran/_kepala_langkah', ['langkah' => 4, 'judul' => 'Biaya'], ['saveData' => false]) ?>
    <p class="langkah-keterangan mb-5 text-gray-600">Periksa lagi daftar atlet dan biayanya. Pendaftaran baru terkirim ke panitia setelah bukti transfer diunggah di langkah berikutnya.</p>

    <div class="<?= K::RINGKASAN ?>" role="alert" hidden></div>

    <div id="rincian-biaya" aria-live="polite"></div>

    <div class="langkah-tombol mt-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" class="<?= K::KEDUA ?>" data-kembali><?= ikon('arrow-left', 'size-5') ?> Kembali</button>
        <button type="button" class="<?= K::UTAMA_BESAR ?>" data-lanjut>Lanjut ke Pembayaran <?= ikon('arrow-right', 'size-5') ?></button>
    </div>

</section>
