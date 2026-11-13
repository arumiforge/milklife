<?php
/**
 * Langkah 4: Biaya (rancangan 7; K45 rincian selalu tampil). Rincian dihitung
 * server (`POST pendaftaran/periksa`) dan ditulis `pendaftaran.js` ke
 * `#rincian-biaya`. Belum ada yang disimpan di langkah ini (K90): pendaftaran
 * baru masuk sistem bersama bukti transfer di langkah 5.
 */
?>
<section class="langkah" id="langkah-4" data-langkah="4" aria-labelledby="judul-langkah-4" hidden>
    <h2 id="judul-langkah-4" tabindex="-1">4. Biaya</h2>
    <p class="langkah-keterangan">Periksa lagi daftar atlet dan biayanya. Pendaftaran baru terkirim ke panitia setelah bukti transfer diunggah di langkah berikutnya.</p>

    <div class="ringkasan-galat alert alert-danger" role="alert" hidden></div>

    <div id="rincian-biaya" aria-live="polite"></div>

    <div class="langkah-tombol">
        <button type="button" class="btn btn-outline-secondary" data-kembali><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</button>
        <button type="button" class="btn btn-primary btn-lg" data-lanjut>Lanjut ke Pembayaran <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>

</section>
