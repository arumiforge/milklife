<?php
/**
 * Langkah 5: Pembayaran (rancangan 7). Data rekening dari `wizard.pembayaran`,
 * ID, total, dan alamat WA dari jawaban `POST pendaftaran/simpan`; diisi
 * `pendaftaran.js`. "Pendaftaran Selesai" mengosongkan draf dan menampilkan
 * tombol cetak bukti ke `/bukti/<ID>` (Tahap 5).
 */
?>
<section class="langkah" id="langkah-5" data-langkah="5" aria-labelledby="judul-langkah-5" hidden>
    <h2 id="judul-langkah-5" tabindex="-1">5. Pembayaran</h2>

    <div class="alert alert-success tersimpan-kotak">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <div>
            <p class="mb-1">Pendaftaran <strong id="bayar-sekolah"></strong> sudah tersimpan.</p>
            <p class="mb-0">ID pendaftaran: <strong class="id-pendaftaran" id="bayar-id"></strong>. Catat ID ini untuk memeriksa pendaftaran.</p>
        </div>
    </div>

    <div id="bayar-isi">
        <div class="kartu-isian kartu-rekening">
            <img id="bayar-logo" class="logo-bank" alt="" hidden>
            <dl class="data-rekening">
                <dt>Bank</dt>
                <dd id="bayar-bank"></dd>
                <dt>Nomor rekening</dt>
                <dd>
                    <span class="nomor-rekening" id="bayar-rekening"></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="bayar-salin">
                        <i class="bi bi-copy" aria-hidden="true"></i> <span>Salin</span>
                    </button>
                </dd>
                <dt>Atas nama</dt>
                <dd id="bayar-pemilik"></dd>
                <dt>Total yang dibayar</dt>
                <dd class="total-bayar" id="bayar-total"></dd>
            </dl>
            <p class="small text-body-secondary mb-0" id="bayar-rincian"></p>
        </div>

        <ol class="cara-bayar">
            <li>Transfer sebesar total di atas ke rekening tersebut.</li>
            <li>Tekan <strong>Kirim Konfirmasi Pembayaran</strong>. WA terbuka dengan pesan yang sudah terisi; lampirkan foto bukti transfer, lalu kirim.</li>
            <li>Tekan <strong>Pendaftaran Selesai</strong> untuk mencetak bukti pendaftaran.</li>
        </ol>

        <div class="tombol-bayar">
            <a class="btn btn-success btn-lg" id="bayar-wa" target="_blank" rel="noopener noreferrer" hidden>
                <i class="bi bi-whatsapp" aria-hidden="true"></i> Kirim Konfirmasi Pembayaran
            </a>
            <button type="button" class="btn btn-primary btn-lg" id="bayar-selesai">
                <i class="bi bi-flag" aria-hidden="true"></i> Pendaftaran Selesai
            </button>
        </div>
    </div>

    <div id="selesai" class="pendaftaran-selesai" hidden>
        <span class="ikon-bulat mb-3"><i class="bi bi-trophy" aria-hidden="true"></i></span>
        <h3 class="h4">Pendaftaran selesai</h3>
        <p>Terima kasih. Status pembayaran berubah menjadi Lunas setelah panitia memeriksa transfer Anda.</p>
        <a class="btn btn-primary btn-lg" id="cetak-bukti" target="_blank" rel="noopener">
            <i class="bi bi-printer" aria-hidden="true"></i> Cetak Bukti Pendaftaran
        </a>
        <p class="small text-body-secondary mt-3" id="selesai-keterangan"></p>
        <a class="btn btn-link" id="daftar-lagi" href="<?= site_url('pendaftaran') ?>">Daftarkan pendaftaran baru</a>
    </div>
</section>
