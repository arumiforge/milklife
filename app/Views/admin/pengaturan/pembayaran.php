<?php
/**
 * Formulir pengaturan pembayaran (`PembayaranController`), tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian                isian formulir (tersimpan atau yang baru diketik)
 * @var array<string, string>  $galat                nama kotak => pesan
 * @var string                 $logo                 jalur logo bank tersimpan yang berkasnya ada, atau ''
 * @var bool                   $logoPerluDipilihLagi logo baru sah tetapi belum tersimpan karena isian lain salah
 * @var array<string, string>  $penanda              penanda templat => keterangan
 * @var array<string, string>  $dataContoh           penanda => isi karangan untuk contoh
 * @var string                 $contohPesan          hasil templat dengan data karangan
 * @var string                 $templatAwal          templat pesan bawaan
 * @var string                 $nomorWaTersimpan     nomor WA tersimpan (bentuk baku) untuk tautan uji coba
 * @var int                    $batasTerkecil        batas bukti bayar per pendaftaran terkecil yang boleh diisi
 * @var int                    $batasTerbesar        batas bukti bayar per pendaftaran terbesar yang boleh diisi
 */

use App\Libraries\Unggahan;

$kelasKotak = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');

$kotakTeks = static function (string $nama, string $label, string $bantuan, array $isian, array $galat, array $atribut = []) use ($kelasKotak): string {
    $salah   = isset($galat[$nama]);
    $jelaskan = trim(($bantuan !== '' ? $nama . '-bantuan ' : '') . ($salah ? $nama . '-galat' : ''));
    $tambahan = '';
    foreach ($atribut as $kunci => $nilai) {
        $tambahan .= ' ' . $kunci . '="' . esc($nilai, 'attr') . '"';
    }

    return '<div>'
        . '<label for="' . $nama . '" class="mb-1.5 block text-sm font-semibold text-gray-900">' . esc($label) . '</label>'
        . '<input type="text" class="' . $kelasKotak($salah) . '" id="' . $nama . '" name="' . $nama . '"'
        . ' value="' . esc($isian[$nama] ?? '', 'attr') . '"' . ($salah ? ' aria-invalid="true"' : '') . ($jelaskan !== '' ? ' aria-describedby="' . $jelaskan . '"' : '') . $tambahan . '>'
        . ($salah ? '<p id="' . $nama . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$nama]) . '</p>' : '')
        . ($bantuan !== '' ? '<p id="' . $nama . '-bantuan" class="mt-1.5 text-sm text-gray-600">' . esc($bantuan) . '</p>' : '')
        . '</div>';
};

$kartu = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Pembayaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pembayaran<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="max-w-3xl text-gray-700">
        Rekening ini tampil di langkah terakhir pendaftaran. Ofisial sekolah mentransfer ke rekening ini,
        lalu menekan tombol WA untuk mengirim pesan konfirmasi ke nomor di bawah.
    </p>

    <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

    <?php if ($logoPerluDipilihLagi) : ?>
        <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => esc('Logo bank yang tadi dipilih belum tersimpan karena ada isian yang perlu diperbaiki. Pilih logonya lagi sebelum menyimpan.')]) ?>
    <?php endif ?>

    <form action="<?= site_url('admin/pengaturan/pembayaran') ?>" method="post" enctype="multipart/form-data" novalidate class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="<?= $kartu ?> min-w-0" aria-labelledby="judul-rekening">
                <h2 id="judul-rekening" class="mb-4 text-lg font-bold text-gray-900">Rekening tujuan transfer</h2>

                <div class="space-y-4">
                    <?= $kotakTeks('bank', 'Nama bank', 'Contoh: BRI, BCA, atau Bank Jateng.', $isian, $galat, ['maxlength' => '50', 'autocomplete' => 'off']) ?>
                    <?= $kotakTeks('nomor_rekening', 'Nomor rekening', 'Boleh diketik dengan spasi atau tanda hubung; yang disimpan hanya angkanya.', $isian, $galat, ['inputmode' => 'numeric', 'autocomplete' => 'off']) ?>
                    <?= $kotakTeks('pemilik_rekening', 'Nama pemilik rekening', 'Tulis persis seperti di buku tabungan, supaya ofisial yakin rekeningnya benar.', $isian, $galat, ['maxlength' => '100', 'autocomplete' => 'off']) ?>

                    <div>
                        <span class="mb-1.5 block text-sm font-semibold text-gray-900" id="logo_bank-label">Logo bank</span>
                        <div class="mb-2 max-w-64" data-pratinjau="logo_bank">
                            <?php if ($logo !== '') : ?>
                                <div class="aspect-video overflow-hidden rounded-lg border border-gray-200 bg-white">
                                    <img src="<?= esc(base_url($logo), 'attr') ?>" alt="Logo bank saat ini" class="size-full object-contain p-2">
                                </div>
                            <?php else : ?>
                                <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '16x9', 'label' => 'Logo bank'], ['saveData' => false]) ?>
                            <?php endif ?>
                        </div>
                        <label for="logo_bank" class="mb-1 block text-sm text-gray-700"><?= $logo === '' ? 'Unggah logo' : 'Ganti logo' ?></label>
                        <input type="file" class="block w-full cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 <?= isset($galat['logo_bank']) ? 'border-red-500 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' ?>" id="logo_bank" name="logo_bank"
                            accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"<?= isset($galat['logo_bank']) ? ' aria-invalid="true"' : '' ?>
                            aria-describedby="logo_bank-bantuan<?= isset($galat['logo_bank']) ? ' logo_bank-galat' : '' ?>">
                        <?php if (isset($galat['logo_bank'])) : ?>
                            <p id="logo_bank-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['logo_bank']) ?></p>
                        <?php endif ?>
                        <p id="logo_bank-bantuan" class="mt-1.5 text-sm text-gray-600">
                            JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR) ?>. Boleh dikosongkan.
                        </p>
                        <?php if ($logo !== '') : ?>
                            <label class="mt-2 inline-flex min-h-11 items-center gap-2" for="hapus_logo_bank">
                                <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" value="1" id="hapus_logo_bank" name="hapus_logo_bank"
                                    data-konfirmasi-centang="Logo bank dihapus dari server saat pengaturan disimpan, dan tidak bisa dikembalikan." data-konfirmasi-tombol="Hapus logo">
                                <span class="text-sm text-gray-900">Hapus logo ini</span>
                            </label>
                        <?php endif ?>
                    </div>
                </div>
            </section>

            <section class="<?= $kartu ?> min-w-0" aria-labelledby="judul-konfirmasi">
                <h2 id="judul-konfirmasi" class="mb-4 text-lg font-bold text-gray-900">Konfirmasi lewat WA</h2>

                <div class="space-y-4">
                    <?= $kotakTeks('nomor_wa', 'Nomor WA pemilik rekening', 'Nomor yang menerima pesan konfirmasi dan bukti transfer, misalnya 0812-3456-7890.', $isian, $galat, ['inputmode' => 'tel', 'autocomplete' => 'off']) ?>

                    <div>
                        <div class="mb-1.5 flex flex-wrap items-end justify-between gap-2">
                            <label for="templat_pesan" class="block text-sm font-semibold text-gray-900">Isi pesan konfirmasi</label>
                            <?= komponen('tombol', ['label' => 'Pakai pesan awal', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'rotate-ccw', 'kelas' => 'min-h-11', 'atribut' => ['id' => 'pakai-pesan-awal', 'data-pesan-awal' => $templatAwal]]) ?>
                        </div>
                        <textarea class="<?= $kelasKotak(isset($galat['templat_pesan'])) ?>" id="templat_pesan" name="templat_pesan" rows="10"
                            maxlength="2000"<?= isset($galat['templat_pesan']) ? ' aria-invalid="true"' : '' ?> aria-describedby="templat_pesan-bantuan<?= isset($galat['templat_pesan']) ? ' templat_pesan-galat' : '' ?>"><?= esc($isian['templat_pesan']) ?></textarea>
                        <?php if (isset($galat['templat_pesan'])) : ?>
                            <p id="templat_pesan-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['templat_pesan']) ?></p>
                        <?php endif ?>
                        <p id="templat_pesan-bantuan" class="mt-1.5 text-sm text-gray-600">
                            Penanda di bawah diganti dengan data pendaftaran saat ofisial menekan tombol WA. Tekan penanda untuk menambahkannya ke pesan.
                        </p>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-bold text-gray-900">Penanda yang bisa dipakai</h3>
                        <ul class="flex flex-wrap gap-2" id="daftar-penanda">
                            <?php foreach ($penanda as $kode => $keterangan) : ?>
                                <li class="min-w-0">
                                    <button type="button" class="min-h-11 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-start hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-100" data-penanda="<?= esc($kode, 'attr') ?>"
                                        title="Tambahkan <?= esc($kode, 'attr') ?> ke pesan">
                                        <code class="text-sm font-semibold text-primary-800"><?= esc($kode) ?></code>
                                        <span class="block text-xs text-gray-600"><?= esc($keterangan) ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Contoh hasil pesan</h3>
                        <p class="mb-2 text-sm text-gray-600">Dengan data karangan, supaya terlihat apa yang diterima pemilik rekening.</p>
                        <div class="whitespace-pre-wrap break-words rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-900" id="contoh-pesan"
                            data-contoh="<?= esc(json_encode($dataContoh, JSON_UNESCAPED_UNICODE), 'attr') ?>" aria-live="polite"><?= esc($contohPesan) ?></div>

                        <?php if ($nomorWaTersimpan !== '' && $galat === []) : ?>
                            <p class="mt-2 text-sm">
                                <a href="https://wa.me/<?= esc($nomorWaTersimpan, 'attr') ?>?text=<?= rawurlencode($contohPesan) ?>" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1 font-semibold text-primary-700 underline">
                                    <?= ikon('message-circle', 'size-4') ?> Coba buka contoh pesan di WA
                                </a>
                                <span class="text-gray-600">(memakai nomor dan pesan yang sudah tersimpan)</span>
                            </p>
                        <?php endif ?>
                    </div>
                </div>
            </section>
        </div>

        <section class="<?= $kartu ?>" aria-labelledby="judul-bukti-transfer">
            <h2 id="judul-bukti-transfer" class="mb-4 text-lg font-bold text-gray-900">Bukti transfer</h2>

            <?php $salahBatas = isset($galat['batas_bukti_bayar']); ?>
            <div class="max-w-xs">
                <label for="batas_bukti_bayar" class="mb-1.5 block text-sm font-semibold text-gray-900">Bukti bayar paling banyak per pendaftaran</label>
                <div class="flex">
                    <input type="number" class="<?= $kelasKotak($salahBatas) ?> rounded-e-none" id="batas_bukti_bayar" name="batas_bukti_bayar"
                        value="<?= esc($isian['batas_bukti_bayar'], 'attr') ?>" min="<?= $batasTerkecil ?>" max="<?= $batasTerbesar ?>" step="1" inputmode="numeric" required<?= $salahBatas ? ' aria-invalid="true"' : '' ?>
                        aria-describedby="batas_bukti_bayar-bantuan<?= $salahBatas ? ' batas_bukti_bayar-galat' : '' ?>">
                    <span class="inline-flex items-center rounded-e-lg border border-s-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-700">gambar</span>
                </div>
                <?php if ($salahBatas) : ?>
                    <p id="batas_bukti_bayar-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['batas_bukti_bayar']) ?></p>
                <?php endif ?>
            </div>
            <p id="batas_bukti_bayar-bantuan" class="mt-1.5 max-w-3xl text-sm text-gray-600">
                Berlaku untuk foto bukti transfer yang diunggah ofisial lewat Cek Pendaftaran maupun yang diunggah admin di rincian pendaftaran.
                Isi <?= $batasTerkecil ?> sampai <?= $batasTerbesar ?>. Bila batas diturunkan, gambar yang sudah terunggah tetap tersimpan;
                pendaftaran itu hanya tidak bisa menambah gambar lagi.
            </p>
        </section>

        <div class="flex flex-wrap gap-2">
            <?= komponen('tombol', ['label' => 'Simpan pengaturan pembayaran', 'tipe' => 'submit', 'ikon' => 'check']) ?>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/admin-pengaturan-pembayaran.js') ?>"></script>
<?= $this->endSection() ?>
