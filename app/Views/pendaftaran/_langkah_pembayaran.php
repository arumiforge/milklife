<?php
/**
 * Langkah 5: Pembayaran (rancangan 7; K90). Data rekening dari
 * `wizard.pembayaran`, total dari rincian `POST pendaftaran/periksa`; diisi
 * `aset/js/pendaftaran/pembayaran.js`. Pendaftaran baru masuk sistem saat "Kirim Pendaftaran":
 * isian dan bukti transfer dikirim bersama ke `POST pendaftaran/simpan`.
 * Tanpa bukti transfer tidak ada yang tersimpan; isian tetap di draf peramban,
 * jadi official bisa kembali lain waktu dan tinggal mengunggah bukti.
 * Setelah terkirim, draf dikosongkan dan tampil tombol cetak bukti
 * `/bukti/<kode>/<ID>` (Tahap 5), dengan ilustrasi G9-04 `berhasil.webp`
 * bila berkasnya sudah ada, dan hiasan konfeti G9-12 `pola/konfeti.webp` di
 * belakang isinya (F9-19, juga hanya bila berkasnya ada).
 *
 * Bila Cloudflare Turnstile aktif (K122), kotak pemeriksaan keamanan tampil
 * di atas tombol Kirim; `aset/js/pendaftaran/turnstile.js` memasangnya dan
 * `pembayaran.js` mengirim tokennya.
 *
 * @var array<string, mixed>|null $wizard
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$turnstile = $wizard['turnstile'] ?? null;
$berhasil  = gambar_opsional('keadaan/berhasil.webp', ['class' => 'mx-auto mb-4 h-40 w-auto sm:h-48', 'data-gambar-berhasil' => true]);
?>
<section class="langkah" id="langkah-5" data-langkah="5" aria-labelledby="judul-langkah-5" hidden>
    <?= view('pendaftaran/_kepala_langkah', ['langkah' => 5, 'judul' => 'Pembayaran'], ['saveData' => false]) ?>

    <div id="bayar-isi">
        <p class="langkah-keterangan mb-5 text-gray-600">Pendaftaran belum terkirim. Transfer dulu, lalu unggah bukti transfernya. Isian Anda tersimpan di perangkat ini. Bila belum sempat transfer, buka lagi halaman ini nanti dan unggah buktinya.</p>

        <div class="<?= K::RINGKASAN ?>" role="alert" hidden></div>

        <div class="<?= K::KARTU ?> kartu-rekening">
            <img id="bayar-logo" class="logo-bank mb-4 block max-h-14 max-w-40 object-contain" alt="" hidden>
            <dl class="data-rekening mb-3 grid gap-0.5 [&_dd]:flex [&_dd]:flex-wrap [&_dd]:items-center [&_dd]:gap-2 [&_dd]:font-semibold [&_dd]:break-words [&_dt]:mt-2 [&_dt]:text-sm [&_dt]:text-gray-500">
                <dt>Bank</dt>
                <dd id="bayar-bank"></dd>
                <dt>Nomor rekening</dt>
                <dd>
                    <span class="nomor-rekening text-xl tracking-wider sm:text-2xl" id="bayar-rekening"></span>
                    <button type="button" class="<?= K::GARIS_KECIL ?>" id="bayar-salin">
                        <?= ikon('copy', 'size-4') ?> <span>Salin</span>
                    </button>
                </dd>
                <dt>Atas nama</dt>
                <dd id="bayar-pemilik"></dd>
                <dt>Total yang dibayar</dt>
                <dd class="total-bayar text-2xl !font-extrabold text-oranye-600" id="bayar-total"></dd>
            </dl>
            <p class="text-sm text-gray-500" id="bayar-rincian"></p>
        </div>

        <ol class="cara-bayar my-5 list-decimal space-y-1.5 ps-5 text-gray-800">
            <li>Transfer sejumlah total di atas ke rekening ini.</li>
            <li>Pilih foto atau tangkapan layar bukti transfer di bawah.</li>
            <li>Tekan <strong>Kirim pendaftaran</strong>. Setelah terkirim, Anda bisa mencetak bukti pendaftaran.</li>
        </ol>

        <div class="<?= K::KARTU ?> unggah-bukti-bayar">
            <label class="<?= K::LABEL ?>" for="bayar-berkas">Bukti transfer <span class="text-red-700" aria-hidden="true">*</span></label>
            <input class="<?= K::BERKAS ?>" type="file" id="bayar-berkas" data-jalur="bukti_bayar" accept="image/jpeg,image/png,image/webp" required aria-describedby="bayar-berkas-keterangan">
            <div class="<?= K::UMPAN ?>"></div>
            <div class="<?= K::BANTU ?>" id="bayar-berkas-keterangan">JPG, PNG, atau WEBP, paling besar 10 MB.</div>
        </div>

        <?php if ($turnstile !== null) : ?>
            <div class="<?= K::KARTU ?> pemeriksaan-keamanan">
                <p class="<?= K::LABEL ?> mb-2">Pemeriksaan keamanan</p>
                <div id="turnstile-kirim" class="cf-turnstile" data-jalur="turnstile" data-sitekey="<?= esc($turnstile['kunci_situs'], 'attr') ?>" data-action="<?= esc($turnstile['aksi'], 'attr') ?>"></div>
                <div class="<?= K::UMPAN ?>"></div>
                <div class="<?= K::BANTU ?>">Tunggu sampai kotak ini bertanda centang hijau sebelum menekan Kirim pendaftaran.</div>
            </div>
        <?php endif ?>

        <div class="langkah-tombol mt-6 flex flex-wrap items-center justify-between gap-3">
            <button type="button" class="<?= K::KEDUA ?>" data-kembali><?= ikon('arrow-left', 'size-5') ?> Kembali</button>
            <button type="button" class="<?= K::UTAMA_BESAR ?>" id="tombol-kirim" disabled>
                <?= ikon('send', 'size-5') ?> <span>Kirim pendaftaran</span>
            </button>
        </div>
    </div>

    <div id="selesai" class="pendaftaran-selesai relative isolate overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 py-8 text-center shadow-lembut sm:px-8" hidden>
        <?= gambar_opsional('pola/konfeti.webp', ['class' => 'pointer-events-none absolute inset-x-0 top-0 -z-10 h-64 w-full select-none object-cover object-top opacity-80', 'data-hiasan' => 'konfeti']) ?>
        <?php if ($berhasil !== '') : ?>
            <?= $berhasil ?>
        <?php else : ?>
            <?= ikon('trophy', 'mx-auto mb-4 block size-14 text-green-700') ?>
        <?php endif ?>
        <h3 class="text-2xl font-extrabold text-primary-900">Pendaftaran terkirim</h3>
        <p class="mt-3 text-gray-700">Pendaftaran <strong id="selesai-sekolah"></strong> tersimpan dengan ID
            <strong class="id-pendaftaran text-lg tracking-wide break-all text-primary-900" id="selesai-id"></strong>
            <button type="button" class="<?= K::GARIS_KECIL ?> align-middle" id="selesai-salin">
                <?= ikon('copy', 'size-4') ?> <span>Salin ID</span>
            </button>
            Catat ID ini untuk Cek Pendaftaran nanti.</p>
        <p class="mt-3 text-gray-700">Status pembayaran tertulis <strong>Menunggu</strong> sampai panitia memeriksa bukti transfer. Setelah itu, statusnya menjadi <strong>Lunas</strong>.</p>
        <div class="tombol-bayar mt-6 flex flex-wrap justify-center gap-3 [&>a]:flex-[1_1_15rem]">
            <a class="<?= K::UTAMA_BESAR ?>" id="cetak-bukti" target="_blank" rel="noopener">
                <?= ikon('printer', 'size-5') ?> Cetak bukti pendaftaran
            </a>
            <a class="<?= K::TOMBOL ?> min-h-12 border border-green-700 bg-white px-6 py-3 text-base text-green-800 hover:bg-green-50 focus:ring-green-200" id="bayar-wa" target="_blank" rel="noopener noreferrer" hidden>
                <?= ikon('message-circle', 'size-5') ?> Konfirmasi lewat WA
            </a>
        </div>
        <p class="mt-4 text-sm text-gray-500" id="selesai-keterangan"></p>
        <a class="<?= K::TAUTAN ?> mt-2" id="daftar-lagi" href="<?= site_url('pendaftaran') ?>">Buat pendaftaran baru</a>
    </div>
</section>
