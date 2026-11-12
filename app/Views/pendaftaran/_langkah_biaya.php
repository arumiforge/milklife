<?php
/**
 * Langkah 4: Biaya (rancangan 7; K45 rincian selalu tampil). Rincian dihitung
 * server (`POST pendaftaran/periksa`) dan ditulis `pendaftaran.js` ke
 * `#rincian-biaya`. Data tersimpan saat official mengonfirmasi di jendela
 * `#konfirmasi-simpan` (`POST pendaftaran/simpan`).
 */
?>
<section class="langkah" id="langkah-4" data-langkah="4" aria-labelledby="judul-langkah-4" hidden>
    <h2 id="judul-langkah-4" tabindex="-1">4. Biaya</h2>
    <p class="langkah-keterangan">Periksa lagi daftar atlet dan biayanya. Setelah disimpan, data tidak bisa diubah lewat halaman ini.</p>

    <div class="ringkasan-galat alert alert-danger" role="alert" hidden></div>

    <div id="rincian-biaya" aria-live="polite"></div>

    <div class="langkah-tombol">
        <button type="button" class="btn btn-outline-secondary" data-kembali><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</button>
        <button type="button" class="btn btn-primary btn-lg" id="tombol-simpan" disabled>
            <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan Pendaftaran
        </button>
    </div>

    <div class="modal fade" id="konfirmasi-simpan" tabindex="-1" aria-labelledby="konfirmasi-simpan-judul" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5" id="konfirmasi-simpan-judul">Simpan pendaftaran?</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Pendaftaran <strong id="konfirmasi-sekolah"></strong> dengan <strong id="konfirmasi-atlet"></strong> akan disimpan.
                        Total yang harus dibayar <strong id="konfirmasi-total"></strong>.</p>
                    <p class="mb-0">Setelah disimpan, data tidak bisa diubah lewat halaman ini. Bila ada yang perlu diubah, hubungi panitia.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Periksa lagi</button>
                    <button type="button" class="btn btn-primary" id="konfirmasi-ya">Ya, simpan</button>
                </div>
            </div>
        </div>
    </div>
</section>
