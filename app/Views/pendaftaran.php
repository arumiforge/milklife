<?php
/**
 * Wizard pendaftaran (`/pendaftaran`, rancangan 7). Pengendali:
 * `App\Controllers\PendaftaranController::index`.
 *
 * Tampilan Tailwind (F9-05, tata letak `publik_tw`). Halaman dirender server
 * sebagai kerangka lima langkah; `aset/js/pendaftaran.js` bersama
 * bagian-bagiannya di `aset/js/pendaftaran/` mengisi dan menjalankannya dari
 * data JSON `#data-pendaftaran`. Tanpa JavaScript, halaman hanya menampilkan
 * pesan agar JavaScript dinyalakan.
 *
 * Penunjuk langkah memakai `komponen('langkah')` mode tombol; laci bantuan
 * memakai drawer Flowbite (`pendaftaran/_laci_bantuan`). Kelas bersama di
 * `App\Libraries\Pendaftaran\KelasTampilan`; elemen buatan JS memakai `W.KELAS`
 * (`pendaftaran/dasar.js`). Ikon dari JS memakai sprite Lucide
 * (`data-sprite` di `#wizard-pendaftaran`).
 *
 * @var CodeIgniter\View\View                 $this
 * @var string                                $namaAcara
 * @var App\Libraries\BukaTutupPendaftaran    $bukaTutup
 * @var array<string, mixed>|null             $wizard    null bila pendaftaran belum terbuka
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$langkah = ['Data Sekolah', 'Ofisial', 'Atlet', 'Biaya', 'Pembayaran'];
$sprite  = base_url('aset/vendor/lucide/lucide.svg') . (($versi = @filemtime(FCPATH . 'aset/vendor/lucide/lucide.svg')) === false ? '' : '?v=' . $versi);
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Pendaftaran – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Daftarkan sekolah dan atlet secara daring.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', ['judul' => 'Pendaftaran', 'ikon' => 'pencil-line', 'teks' => $bukaTutup->kalimat()], ['saveData' => false]) ?>

<div class="halaman-pendaftaran mx-auto max-w-4xl px-4 pb-12">
    <?php if ($wizard === null) : ?>
        <?= $this->include('pendaftaran/_tertutup') ?>
    <?php else : ?>
        <?= view('tata_letak/_bilah_kemajuan_tw', [], ['saveData' => false]) ?>

        <noscript>
            <div class="mt-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
                Halaman pendaftaran membutuhkan JavaScript. Nyalakan JavaScript di peramban Anda, lalu muat ulang halaman ini.
            </div>
        </noscript>

        <div id="wizard-pendaftaran" class="wizard scroll-mt-20" data-sprite="<?= esc($sprite, 'attr') ?>" hidden>
            <nav class="relative -mt-6 mb-7 rounded-2xl bg-white px-2 pt-4 pb-3 shadow-angkat sm:px-4" aria-label="Langkah pendaftaran">
                <?= komponen('langkah', ['daftar' => $langkah, 'tombol' => true]) ?>
            </nav>

            <div id="peringatan-draf" class="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status" hidden>
                Isian belum bisa disimpan di peramban ini. Jangan muat ulang halaman sampai pendaftaran tersimpan.
            </div>

            <?= $this->include('pendaftaran/_langkah_sekolah') ?>
            <?= $this->include('pendaftaran/_langkah_penanggung_jawab') ?>
            <?= $this->include('pendaftaran/_langkah_atlet') ?>
            <?= $this->include('pendaftaran/_langkah_biaya') ?>
            <?= $this->include('pendaftaran/_langkah_pembayaran') ?>

            <p class="mulai-ulang mt-8 text-center">
                <button type="button" class="<?= K::TAUTAN ?> text-sm" id="tombol-mulai-ulang">
                    <?= ikon('rotate-ccw', 'size-4') ?> Kosongkan isian dan mulai dari awal
                </button>
            </p>
        </div>

        <?= $this->include('pendaftaran/_laci_bantuan') ?>

        <script type="application/json" id="data-pendaftaran"><?= json_encode($wizard, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?php if ($wizard !== null) : ?>
    <?= $this->section('kepala') ?>
    <meta name="csrf-kepala" content="<?= esc(config('Security')->headerName, 'attr') ?>">
    <meta name="csrf-token" content="<?= esc(csrf_hash(), 'attr') ?>">
    <?= $this->endSection() ?>

    <?= $this->section('skrip') ?>
    <?php if (($wizard['turnstile'] ?? null) !== null) : ?>
        <?php // Dipasang `pendaftaran/turnstile.js` saat langkah 5 tampil (K122). ?>
        <script src="<?= esc($wizard['turnstile']['skrip']) ?>?render=explicit" async defer></script>
    <?php endif ?>
    <?php // Bilah muat, jendela konfirmasi (`window.Konfirmasi`, jendela Flowbite), dan pemilih tanggal lahir Tanggal, Bulan, Tahun (K89). ?>
    <script src="<?= aset('js/kemajuan.js') ?>"></script>
    <script src="<?= aset('js/konfirmasi.js') ?>"></script>
    <script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
    <?php
    // Wizard dipecah per bagian (F7-30), tanpa alat bangun. Urutan ini wajib:
    // `pendaftaran/dasar.js` membuat `window.Pendaftaran`, setiap bagian memakai
    // bagian sebelumnya, dan `pendaftaran.js` terakhir menjalankan wizard.
    $bagianWizard = ['dasar', 'draf', 'langkah', 'sekolah', 'ofisial', 'atlet', 'regu', 'atlet-formulir', 'atlet-daftar', 'biaya', 'turnstile', 'pembayaran'];
    ?>
    <?php foreach ($bagianWizard as $bagian) : ?>
        <script src="<?= aset('js/pendaftaran/' . $bagian . '.js') ?>"></script>
    <?php endforeach ?>
    <script src="<?= aset('js/pendaftaran.js') ?>"></script>
    <script src="<?= aset('js/bantuan.js') ?>"></script>
    <?= $this->endSection() ?>
<?php endif ?>
