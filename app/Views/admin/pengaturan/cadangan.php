<?php
/**
 * Pengaturan > Cadangan (F11-11, K174, `Admin\Pengaturan\CadanganController`):
 * unduh cadangan lengkap, impor cadangan yang mengganti semua data dan
 * pengaturan, daftar cadangan otomatis sebelum impor, dan riwayat unduh dan
 * impor. Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View                                                      $this
 * @var array<string, string>                                                      $galat    pesan per kotak formulir impor
 * @var int                                                                        $batas    batas unggah server (bita), 0 = tidak diketahui
 * @var list<array{nama: string, ukuran: int, waktu: int}>                         $otomatis cadangan otomatis, terbaru di atas
 * @var list<array{nama_admin: string, label: string, ringkasan: string, dibuat_pada: string|null}> $riwayat
 * @var array{pendaftaran: int, atlet: int}                                        $isi      data sekarang
 */

use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Unggahan;

$kartu    = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
$label    = 'mb-1.5 block text-sm font-semibold text-gray-900';
$petunjuk = 'mt-1.5 text-sm text-gray-600';
$pesan    = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$kelasKotak = static fn (string $kotak): string => isset($galat[$kotak])
    ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
    : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100';
$uraian = static fn (string $kotak): string => $kotak . '-petunjuk' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '');
$batasTeks = $batas > 0 ? Unggahan::ukuranTeks($batas) : null;
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Cadangan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Cadangan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Satu berkas cadangan berisi semua data web ini: pendaftaran, atlet, atlet yang diganti dan ditolak, hasil lomba, babak, seri, riwayat tindakan admin,
        semua pengaturan, serta semua foto, bukti bayar, dan berkas unggahan. Akun admin dan kata sandinya tidak ikut.
    </p>

    <section class="<?= $kartu ?>" aria-labelledby="judul-unduh-cadangan">
        <h2 id="judul-unduh-cadangan" class="mb-1 text-lg font-bold text-gray-900">Unduh cadangan</h2>
        <p class="mb-3 text-sm text-gray-600">
            Sekarang ada <?= number_format($isi['pendaftaran'], 0, ',', '.') ?> pendaftaran dan <?= number_format($isi['atlet'], 0, ',', '.') ?> atlet.
            Berkas berisi data pribadi siswa, jadi simpan di tempat yang aman dan jangan dibagikan.
            Berkas ini juga bisa dimasukkan ke server baru yang masih kosong dengan perintah <code class="rounded bg-gray-100 px-1">php spark pendaftaran:impor</code>.
        </p>
        <form action="<?= site_url('admin/pengaturan/cadangan/unduh') ?>" method="post">
            <?= csrf_field() ?>
            <?= komponen('tombol', ['label' => 'Unduh cadangan', 'tipe' => 'submit', 'ikon' => 'download', 'atribut' => ['id' => 'unduh-cadangan']]) ?>
        </form>
    </section>

    <section class="<?= $kartu ?>" aria-labelledby="judul-impor-cadangan">
        <h2 id="judul-impor-cadangan" class="mb-3 text-lg font-bold text-gray-900">Impor cadangan</h2>

        <?= komponen('peringatan', [
            'jenis' => 'peringatan',
            'judul' => 'Impor mengganti semua data dan pengaturan',
            'isi'   => esc('Semua pendaftaran, hasil, pengaturan, dan berkas unggahan di web ini diganti isi cadangan. Sebelum mengganti, web menyimpan cadangan data sekarang di server, di daftar cadangan otomatis di bawah.'),
            'kelas' => 'mb-4',
        ]) ?>

        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= site_url('admin/pengaturan/cadangan/impor') ?>" method="post" enctype="multipart/form-data" novalidate class="mt-4 space-y-4"
            data-konfirmasi="Semua data, pengaturan, dan berkas unggahan di web ini akan diganti isi cadangan. Data sekarang disimpan dulu sebagai cadangan otomatis di server."
            data-konfirmasi-judul="Ganti semua data?"
            data-konfirmasi-tombol="Impor dan ganti">
            <?= csrf_field() ?>

            <div>
                <label for="berkas" class="<?= $label ?>">Berkas cadangan (.zip)</label>
                <input type="file" id="berkas" name="berkas" accept=".zip,application/zip" required
                    class="block w-full cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 <?= $kelasKotak('berkas') ?>"
                    <?= $batas > 0 ? 'data-ukuran-maksimal="' . $batas . '"' : '' ?><?= isset($galat['berkas']) ? ' aria-invalid="true"' : '' ?>
                    aria-describedby="<?= $uraian('berkas') ?>">
                <?= $pesan('berkas') ?>
                <p id="berkas-petunjuk" class="<?= $petunjuk ?>">
                    Berkas yang diunduh dari halaman ini<?= $batasTeks !== null ? ', paling besar ' . esc($batasTeks) . ' (batas unggah server)' : '' ?>.
                    Impor bisa makan beberapa menit; jangan tutup halaman sampai selesai.
                </p>
            </div>

            <div>
                <label for="sandi" class="<?= $label ?>">Kata sandi Anda</label>
                <input type="password" id="sandi" name="sandi" maxlength="255" autocomplete="current-password" required
                    class="block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:max-w-sm sm:text-sm <?= $kelasKotak('sandi') ?>"
                    <?= isset($galat['sandi']) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= $uraian('sandi') ?>">
                <?= $pesan('sandi') ?>
                <p id="sandi-petunjuk" class="<?= $petunjuk ?>">Kata sandi yang Anda pakai untuk masuk ke panel ini, untuk memastikan impor memang Anda yang meminta.</p>
            </div>

            <?= komponen('tombol', ['label' => 'Impor dan ganti semua data', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'upload', 'atribut' => ['id' => 'impor-cadangan']]) ?>
        </form>

        <details class="mt-4 text-sm text-gray-700">
            <summary class="min-h-11 cursor-pointer py-2 font-semibold text-primary-700">Berkas terlalu besar untuk diunggah?</summary>
            <p class="mt-1">
                Minta pengelola server menyalin berkas cadangan ke server, lalu menjalankan perintah ini di folder web:
            </p>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-100 p-3 text-xs"><code>php spark cadangan:pulihkan &lt;jalur-berkas.zip&gt;</code></pre>
            <p class="mt-2">Hasilnya sama dengan impor di halaman ini, termasuk cadangan otomatis data sekarang, tanpa batas ukuran unggah dan batas waktu.</p>
        </details>
    </section>

    <section class="<?= $kartu ?>" aria-labelledby="judul-cadangan-otomatis">
        <h2 id="judul-cadangan-otomatis" class="mb-1 text-lg font-bold text-gray-900">Cadangan otomatis</h2>
        <p class="mb-3 text-sm text-gray-600">
            Dibuat setiap kali cadangan diimpor, berisi data sebelum diganti. Disimpan <?= CadanganLengkap::SIMPAN_OTOMATIS ?> yang terakhir.
            Bila impor ternyata keliru, unduh cadangan yang tepat di sini, lalu impor lagi.
        </p>
        <?php if ($otomatis === []) : ?>
            <p class="text-sm text-gray-600" data-cadangan-otomatis-kosong>Belum ada cadangan otomatis.</p>
        <?php else : ?>
            <ul class="divide-y divide-gray-200" data-cadangan-otomatis>
                <?php foreach ($otomatis as $butir) : ?>
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="min-w-0 break-all text-sm">
                            <span class="font-semibold text-gray-900"><?= esc(date('d-m-Y H:i', $butir['waktu'])) ?></span>
                            <span class="text-gray-600">· <?= esc(Unggahan::ukuranTeks($butir['ukuran'])) ?> · <?= esc($butir['nama']) ?></span>
                        </span>
                        <?= komponen('tombol', ['label' => 'Unduh', 'href' => site_url('admin/pengaturan/cadangan/' . $butir['nama'] . '/unduh'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'download', 'kelas' => 'min-h-11', 'atribut' => ['aria-label' => 'Unduh cadangan otomatis ' . date('d-m-Y H:i', $butir['waktu'])]]) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <section class="<?= $kartu ?>" aria-labelledby="judul-riwayat-cadangan">
        <h2 id="judul-riwayat-cadangan" class="mb-3 text-lg font-bold text-gray-900">Riwayat unduh dan impor</h2>
        <?php if ($riwayat === []) : ?>
            <p class="text-sm text-gray-600">Belum ada cadangan yang diunduh atau diimpor.</p>
        <?php else : ?>
            <ul class="divide-y divide-gray-200 text-sm" data-riwayat-cadangan>
                <?php foreach ($riwayat as $butir) : ?>
                    <li class="py-2">
                        <p class="font-semibold text-gray-900"><?= esc($butir['label']) ?> · <?= esc($butir['nama_admin']) ?> · <?= esc($butir['dibuat_pada'] === null ? '' : date('d-m-Y H:i', (int) strtotime($butir['dibuat_pada']))) ?></p>
                        <p class="break-words text-gray-700"><?= esc($butir['ringkasan']) ?></p>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
