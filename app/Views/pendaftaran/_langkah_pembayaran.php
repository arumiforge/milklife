<?php
/**
 * Langkah 5: Pembayaran (rancangan 7). Data rekening dari `wizard.pembayaran`,
 * ID, total, dan alamat WA dari jawaban `POST pendaftaran/simpan`; diisi
 * `pendaftaran.js`. Bukti transfer diunggah ke `<alamat bukti>/bukti-bayar`
 * (K83). "Pendaftaran Selesai" mengosongkan draf dan menampilkan tombol
 * cetak bukti ke `/bukti/<kode>/<ID>` (Tahap 5).
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
            <li>Unggah foto atau tangkapan layar bukti transfer di bawah. Bukti tidak perlu dikirim lewat WA. Bila belum sempat, bukti bisa diunggah nanti lewat menu Cek Pendaftaran.</li>
            <li>Tekan <strong>Pendaftaran Selesai</strong> untuk mencetak bukti pendaftaran.</li>
        </ol>

        <form class="kartu-isian unggah-bukti-bayar" id="bayar-unggah" enctype="multipart/form-data" novalidate>
            <label class="form-label fw-semibold" for="bayar-berkas">Bukti transfer</label>
            <div class="input-group">
                <input class="form-control" type="file" id="bayar-berkas" name="bukti" accept="image/jpeg,image/png,image/webp" aria-describedby="bayar-berkas-keterangan">
                <button type="submit" class="btn btn-primary" id="bayar-kirim-bukti"><i class="bi bi-upload" aria-hidden="true"></i> <span>Unggah</span></button>
            </div>
            <div class="form-text" id="bayar-berkas-keterangan">JPG, PNG, atau WEBP, paling besar 10 MB.</div>
            <div class="mt-2 mb-0" id="bayar-pesan-bukti" role="status" hidden></div>
        </form>

        <div class="tombol-bayar">
            <a class="btn btn-outline-success btn-lg" id="bayar-wa" target="_blank" rel="noopener noreferrer" hidden>
                <i class="bi bi-whatsapp" aria-hidden="true"></i> Konfirmasi lewat WA
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
