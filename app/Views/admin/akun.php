<?php
/**
 * Halaman Akun (`/admin/akun`): dua formulir untuk akun yang sedang masuk,
 * tata letak Tailwind `admin_tw`.
 *
 * 1. Nama dan nama pengguna → `POST admin/akun`. Kotak "Kata sandi saat ini"
 *    hanya wajib bila nama pengguna diganti.
 * 2. Kata sandi → `POST admin/akun/sandi`.
 *
 * Hanya satu formulir yang dikirim tiap kali, jadi ringkasan isian salah
 * (`admin/_galat_isian_tw`) tampil di atas formulir yang bersangkutan saja.
 * Kotak kata sandi tidak pernah diisi ulang.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, mixed>   $admin      baris admin yang sedang masuk
 * @var array<string, string>  $isianAkun  nilai kotak nama dan nama pengguna
 * @var array<string, string>  $galatAkun  pesan per kotak formulir 1
 * @var array<string, string>  $galatSandi pesan per kotak formulir 2
 */

use App\Models\AdminModel;

$kotakSandi = [
    'sandi_saat_ini' => ['Kata sandi saat ini', 'current-password', 'Kata sandi yang Anda pakai untuk masuk sekarang.'],
    'sandi_baru'     => ['Kata sandi baru', 'new-password', 'Paling sedikit ' . AdminModel::SANDI_MINIMAL . ' karakter. Gabungkan huruf, angka, dan tanda baca supaya sulit ditebak.'],
    'ulangi_sandi'   => ['Ulangi kata sandi baru', 'new-password', 'Ketik sekali lagi kata sandi baru yang sama.'],
];

/**
 * Atribut `class`, `aria-invalid`, dan `aria-describedby` untuk satu kotak.
 *
 * @param array<string, string> $galat
 */
$atributKotak = static function (string $kotak, array $galat, bool $adaPetunjuk): string {
    $salah  = isset($galat[$kotak]);
    $kelas  = 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
        . ($salah
            ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
            : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
    $uraian = [];

    if ($adaPetunjuk) {
        $uraian[] = $kotak . '-petunjuk';
    }
    if ($salah) {
        $uraian[] = $kotak . '-galat';
    }

    return 'class="' . $kelas . '"' . ($salah ? ' aria-invalid="true"' : '') . ($uraian !== [] ? ' aria-describedby="' . implode(' ', $uraian) . '"' : '');
};
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$petunjuk = 'mt-1.5 text-sm text-gray-600';
$pesan   = static fn (string $kotak, array $galat): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Akun<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Akun<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="max-w-3xl text-gray-700">
        Perubahan di halaman ini hanya berlaku untuk akun Anda sendiri.
    </p>

    <div class="grid gap-6 xl:grid-cols-2 xl:items-start">
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-data-akun">
            <div>
                <h2 id="judul-data-akun" class="text-lg font-bold text-gray-900">Nama dan nama pengguna</h2>
                <p class="mt-1 text-sm text-gray-600">Nama tampil di kepala panel admin. Nama pengguna dipakai untuk masuk.</p>
            </div>

            <?= view('admin/_galat_isian_tw', ['galat' => $galatAkun], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/akun') ?>" method="post" class="space-y-5" novalidate>
                <?= csrf_field() ?>

                <div>
                    <label for="nama" class="<?= $label ?>">Nama</label>
                    <input type="text" id="nama" name="nama" maxlength="100" autocomplete="name"
                        value="<?= esc($isianAkun['nama'] ?? '', 'attr') ?>" required
                        <?= $atributKotak('nama', $galatAkun, false) ?>>
                    <?= $pesan('nama', $galatAkun) ?>
                </div>

                <div>
                    <label for="nama_pengguna" class="<?= $label ?>">Nama pengguna</label>
                    <input type="text" id="nama_pengguna" name="nama_pengguna" maxlength="30"
                        autocomplete="username" autocapitalize="none" spellcheck="false" required
                        value="<?= esc($isianAkun['nama_pengguna'] ?? '', 'attr') ?>"
                        <?= $atributKotak('nama_pengguna', $galatAkun, true) ?>>
                    <p id="nama_pengguna-petunjuk" class="<?= $petunjuk ?>">3–30 karakter: huruf kecil, angka, titik, atau garis bawah, tanpa spasi.</p>
                    <?= $pesan('nama_pengguna', $galatAkun) ?>
                </div>

                <div>
                    <label for="sandi_akun" class="<?= $label ?>">Kata sandi saat ini</label>
                    <input type="password" id="sandi_akun" name="sandi_akun" maxlength="255" autocomplete="current-password"
                        <?= $atributKotak('sandi_akun', $galatAkun, true) ?>>
                    <p id="sandi_akun-petunjuk" class="<?= $petunjuk ?>">Hanya perlu diisi bila Anda mengganti nama pengguna.</p>
                    <?= $pesan('sandi_akun', $galatAkun) ?>
                </div>

                <?= komponen('tombol', ['label' => 'Simpan nama', 'tipe' => 'submit', 'ikon' => 'check']) ?>
            </form>
        </section>

        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-kata-sandi">
            <div>
                <h2 id="judul-kata-sandi" class="text-lg font-bold text-gray-900">Kata sandi</h2>
                <p class="mt-1 text-sm text-gray-600">Setelah diganti, Anda tetap masuk di perangkat ini. Perangkat lain yang masuk dengan akun ini akan diminta masuk lagi.</p>
            </div>

            <?= view('admin/_galat_isian_tw', ['galat' => $galatSandi], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/akun/sandi') ?>" method="post" class="space-y-5" novalidate>
                <?= csrf_field() ?>

                <?php // Untuk pengelola kata sandi di peramban: kata sandi ini milik nama pengguna tersebut. Tidak ikut dikirim. ?>
                <input type="text" class="sr-only" autocomplete="username" tabindex="-1" aria-hidden="true" readonly
                    value="<?= esc((string) $admin['nama_pengguna'], 'attr') ?>">

                <?php foreach ($kotakSandi as $kotak => [$teksLabel, $isiOtomatis, $teksPetunjuk]) : ?>
                    <div>
                        <label for="<?= $kotak ?>" class="<?= $label ?>"><?= esc($teksLabel) ?></label>
                        <input type="password" id="<?= $kotak ?>" name="<?= $kotak ?>" required
                            maxlength="<?= $kotak === 'sandi_saat_ini' ? 255 : AdminModel::SANDI_MAKSIMAL ?>"
                            <?= $kotak !== 'sandi_saat_ini' ? 'minlength="' . AdminModel::SANDI_MINIMAL . '"' : '' ?>
                            autocomplete="<?= $isiOtomatis ?>"
                            <?= $atributKotak($kotak, $galatSandi, true) ?>>
                        <p id="<?= $kotak ?>-petunjuk" class="<?= $petunjuk ?>"><?= esc($teksPetunjuk) ?></p>
                        <?= $pesan($kotak, $galatSandi) ?>
                    </div>
                <?php endforeach; ?>

                <?= komponen('tombol', ['label' => 'Ganti kata sandi', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'key-round']) ?>
            </form>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
