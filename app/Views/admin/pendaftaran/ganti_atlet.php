<?php
/**
 * Ganti atlet di pendaftaran Lunas (F11-01, K172). Data dari
 * `GantiAtletController` (`GantiAtlet::formulir()`).
 *
 * Bila atlet tidak bisa diganti, alasannya tampil tanpa formulir.
 * Penyimpanan memakai jendela konfirmasi `aset/js/konfirmasi.js`.
 * Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View $this
 * @var array<string, mixed>  $form
 * @var array<string, string> $baru   isian atlet pengganti yang dikirim
 * @var string                $alasan
 * @var array<string, string> $galat  per isian (`nama`, `nisn`, `tanggal_lahir`, `alasan`) atau `umum`
 * @var int                   $alasanMin
 * @var int                   $alasanMax
 */

use App\Libraries\Pendaftaran\Regu;

$p      = $form['pendaftaran'];
$a      = $form['atlet'];
$alamat = 'admin/pendaftaran/' . (int) $p['id'];
$jk     = $a['jenis_kelamin'] === 'P' ? 'Perempuan' : 'Laki-laki';

$kalimatKonfirmasi = $a['nama'] . ' akan dikeluarkan dari pendaftaran ' . $p['id_pendaftaran']
    . ' dan diganti atlet baru. Nomor lomba, regu, dan tempat di seri yang belum dikunci pindah ke atlet baru, '
    . 'yang mendapat nomor atlet (BIB) baru. Nomor atlet lama tidak dipakai lagi. Tindakan ini tidak bisa dibatalkan.';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Ganti atlet <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Ganti atlet<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [
    App\Libraries\Panel\AsalRincian::untuk((int) $p['id']),
    ['label' => $p['id_pendaftaran'], 'alamat' => $alamat],
    ['label' => 'Ganti atlet'],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4" id="ganti-atlet" data-id="<?= (int) $p['id'] ?>" data-atlet="<?= (int) $a['id'] ?>">

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-atlet-lama">
        <div>
            <h2 id="judul-atlet-lama" class="text-lg font-bold text-gray-900">Atlet yang diganti</h2>
            <p class="text-sm text-gray-600"><?= esc($p['id_pendaftaran']) ?> · <?= esc($p['nama_sekolah']) ?></p>
        </div>
        <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-gray-500">Nama</dt><dd class="font-semibold text-gray-900"><?= esc($a['nama']) ?></dd></div>
            <div><dt class="text-gray-500">NISN</dt><dd class="text-gray-900"><?= esc($a['nisn']) ?></dd></div>
            <div><dt class="text-gray-500">Nomor atlet (BIB)</dt><dd class="text-gray-900"><?= esc($a['nomor_dada'] ?? '-') ?></dd></div>
            <div><dt class="text-gray-500">Kelompok umur</dt><dd class="text-gray-900"><?= esc($a['kelompok_umur']) ?></dd></div>
            <div><dt class="text-gray-500">Jenis kelamin</dt><dd class="text-gray-900"><?= esc($jk) ?></dd></div>
            <div>
                <dt class="text-gray-500">Nomor lomba yang diwariskan</dt>
                <dd class="text-gray-900">
                    <?php if ($form['nomor_lomba'] === []) : ?>
                        -
                    <?php else : ?>
                        <ul class="space-y-1" id="nomor-diwariskan">
                            <?php foreach ($form['nomor_lomba'] as $n) : ?>
                                <li>
                                    <?= esc($n['nama']) ?>
                                    <?php if ($n['jenis'] === 'beregu' && $n['regu'] !== null) : ?>
                                        <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800"><?= esc(Regu::label((string) $n['regu'])) ?> · <?= esc(Regu::labelPeran($n['peran'])) ?></span>
                                    <?php endif ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </dd>
            </div>
        </dl>
    </section>

    <?php if ($form['hambatan'] !== []) : ?>
        <div class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status" id="ganti-terhambat">
            <?= ikon('triangle-alert', 'mt-0.5 shrink-0') ?>
            <ul class="space-y-1">
                <?php foreach ($form['hambatan'] as $pesan) : ?>
                    <li><?= esc($pesan) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php else : ?>
        <?php if (isset($galat['umum'])) : ?>
            <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" id="galat-ganti" tabindex="-1">
                <?= ikon('circle-alert', 'mt-0.5 shrink-0') ?>
                <p><?= esc($galat['umum']) ?></p>
            </div>
        <?php endif ?>

        <form action="<?= site_url($alamat . '/ganti-atlet/' . (int) $a['id']) ?>" method="post" class="space-y-4" novalidate
            data-konfirmasi="<?= esc($kalimatKonfirmasi, 'attr') ?>"
            data-konfirmasi-judul="Ganti atlet?"
            data-konfirmasi-tombol="Ganti atlet">
            <?= csrf_field() ?>

            <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-pengganti">
                <div>
                    <h2 id="judul-pengganti" class="text-lg font-bold text-gray-900">Atlet pengganti</h2>
                    <p class="text-sm text-gray-600">
                        Atlet pengganti dari sekolah yang sama, <?= esc(mb_strtolower($jk)) ?>, dan masuk <?= esc($a['kelompok_umur']) ?>.
                        NISN-nya belum boleh terdaftar.
                    </p>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <?= komponen('isian', ['label' => 'Nama lengkap', 'nama' => 'nama', 'nilai' => $baru['nama'] ?? '', 'galat' => $galat['nama'] ?? null, 'wajib' => true, 'atribut' => ['autocomplete' => 'off', 'maxlength' => 100]]) ?>
                    <?= komponen('isian', ['label' => 'NISN', 'nama' => 'nisn', 'nilai' => $baru['nisn'] ?? '', 'galat' => $galat['nisn'] ?? null, 'wajib' => true, 'bantuan' => 'Tepat 10 angka.', 'atribut' => ['inputmode' => 'numeric', 'maxlength' => 10, 'autocomplete' => 'off']]) ?>
                    <?= komponen('isian', ['label' => 'Tanggal lahir', 'nama' => 'tanggal_lahir', 'tipe' => 'date', 'nilai' => $baru['tanggal_lahir'] ?? '', 'galat' => $galat['tanggal_lahir'] ?? null, 'wajib' => true]) ?>
                </div>
                <?= komponen('isian', ['label' => 'Alasan penggantian', 'nama' => 'alasan', 'tipe' => 'textarea', 'nilai' => $alasan, 'galat' => $galat['alasan'] ?? null, 'wajib' => true, 'bantuan' => 'Misalnya "cedera lutut saat latihan". Dicatat di riwayat pendaftaran.', 'atribut' => ['minlength' => $alasanMin, 'maxlength' => $alasanMax]]) ?>
            </section>

            <div class="flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Ganti atlet', 'tipe' => 'submit', 'varian' => 'utama', 'ikon' => 'user-round-check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url($alamat), 'varian' => 'kedua', 'ikon' => 'x']) ?>
            </div>
        </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
