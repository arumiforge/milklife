<?php
/**
 * Langkah 5: Pembayaran (rancangan 7; K90). Data rekening dari
 * `wizard.pembayaran`, total dari rincian `POST pendaftaran/periksa`; diisi
 * `pendaftaran.js`. Pendaftaran baru masuk sistem saat "Kirim Pendaftaran":
 * isian dan bukti transfer dikirim bersama ke `POST pendaftaran/simpan`.
 * Tanpa bukti transfer tidak ada yang tersimpan; isian tetap di draf peramban,
 * jadi official bisa kembali lain waktu dan tinggal mengunggah bukti.
 * Setelah terkirim, draf dikosongkan dan tampil tombol cetak bukti
 * `/bukti/<kode>/<ID>` (Tahap 5).
 */
?>
<section class="langkah" id="langkah-5" data-langkah="5" aria-labelledby="judul-langkah-5" hidden>
    <h2 id="judul-langkah-5" tabindex="-1">5. Pembayaran</h2>

    <div id="bayar-isi">
        <p class="langkah-keterangan">Pendaftaran belum terkirim. Transfer dulu, lalu unggah bukti transfernya. Isian di halaman ini tetap tersimpan di perangkat ini, jadi bila belum sempat transfer, buka lagi halaman ini nanti dan tinggal unggah buktinya.</p>

        <div class="ringkasan-galat alert alert-danger" role="alert" hidden></div>

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
            <li>Pilih foto atau tangkapan layar bukti transfer di bawah.</li>
            <li>Tekan <strong>Kirim Pendaftaran</strong>. Setelah terkirim, bukti pendaftaran bisa dicetak.</li>
        </ol>

        <div class="kartu-isian unggah-bukti-bayar">
            <label class="form-label fw-semibold" for="bayar-berkas">Bukti transfer <span class="text-danger" aria-hidden="true">*</span></label>
            <input class="form-control" type="file" id="bayar-berkas" data-jalur="bukti_bayar" accept="image/jpeg,image/png,image/webp" required aria-describedby="bayar-berkas-keterangan">
            <div class="invalid-feedback"></div>
            <div class="form-text" id="bayar-berkas-keterangan">JPG, PNG, atau WEBP, paling besar 10 MB.</div>
        </div>

        <div class="langkah-tombol">
            <button type="button" class="btn btn-outline-secondary" data-kembali><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</button>
            <button type="button" class="btn btn-primary btn-lg" id="tombol-kirim" disabled>
                <i class="bi bi-send" aria-hidden="true"></i> <span>Kirim Pendaftaran</span>
            </button>
        </div>
    </div>

    <div id="selesai" class="pendaftaran-selesai" hidden>
        <span class="ikon-bulat mb-3"><i class="bi bi-trophy" aria-hidden="true"></i></span>
        <h3 class="h4">Pendaftaran terkirim</h3>
        <p>Pendaftaran <strong id="selesai-sekolah"></strong> tersimpan dengan ID
            <strong class="id-pendaftaran" id="selesai-id"></strong>
            <button type="button" class="btn btn-sm btn-outline-primary" id="selesai-salin">
                <i class="bi bi-copy" aria-hidden="true"></i> <span>Salin ID</span>
            </button>
            Catat ID ini untuk memeriksa pendaftaran.</p>
        <p>Status pembayaran <strong>Menunggu</strong> sampai panitia memeriksa bukti transfer, lalu menjadi <strong>Lunas</strong>.</p>
        <div class="tombol-bayar">
            <a class="btn btn-primary btn-lg" id="cetak-bukti" target="_blank" rel="noopener">
                <i class="bi bi-printer" aria-hidden="true"></i> Cetak Bukti Pendaftaran
            </a>
            <a class="btn btn-outline-success btn-lg" id="bayar-wa" target="_blank" rel="noopener noreferrer" hidden>
                <i class="bi bi-whatsapp" aria-hidden="true"></i> Konfirmasi lewat WA
            </a>
        </div>
        <p class="small text-body-secondary mt-3" id="selesai-keterangan"></p>
        <a class="btn btn-link" id="daftar-lagi" href="<?= site_url('pendaftaran') ?>">Daftarkan pendaftaran baru</a>
    </div>
</section>
