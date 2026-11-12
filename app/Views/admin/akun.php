<?php
/**
 * Halaman Akun (`/admin/akun`): dua formulir untuk akun yang sedang masuk.
 *
 * 1. Nama dan nama pengguna → `POST admin/akun`. Kotak "Kata sandi saat ini"
 *    hanya wajib bila nama pengguna diganti.
 * 2. Kata sandi → `POST admin/akun/sandi`.
 *
 * Hanya satu formulir yang dikirim tiap kali, jadi ringkasan isian salah
 * (`admin/_galat_isian`) tampil di atas formulir yang bersangkutan saja.
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
 * Atribut `class` dan `aria-describedby` untuk satu kotak.
 *
 * @param array<string, string> $galat
 */
$atributKotak = static function (string $kotak, array $galat, bool $adaPetunjuk): string {
    $kelas  = 'form-control' . (isset($galat[$kotak]) ? ' is-invalid' : '');
    $uraian = [];

    if ($adaPetunjuk) {
        $uraian[] = $kotak . '-petunjuk';
    }
    if (isset($galat[$kotak])) {
        $uraian[] = $kotak . '-galat';
    }

    return 'class="' . $kelas . '"' . ($uraian !== [] ? ' aria-describedby="' . implode(' ', $uraian) . '"' : '');
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Akun<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Akun<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-body-secondary mb-4">
        Perubahan di halaman ini hanya berlaku untuk akun Anda sendiri.
    </p>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <section class="card h-100" aria-labelledby="judul-data-akun">
                <div class="card-body p-4">
                    <h2 id="judul-data-akun" class="h5 mb-1">Nama dan nama pengguna</h2>
                    <p class="text-body-secondary small mb-4">Nama tampil di kepala panel admin. Nama pengguna dipakai untuk masuk.</p>

                    <?= view('admin/_galat_isian', ['galat' => $galatAkun], ['saveData' => false]) ?>

                    <form action="<?= site_url('admin/akun') ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama</label>
                            <input type="text" id="nama" name="nama" maxlength="100" autocomplete="name"
                                value="<?= esc($isianAkun['nama'] ?? '', 'attr') ?>" required
                                <?= $atributKotak('nama', $galatAkun, false) ?>>
                            <?php if (isset($galatAkun['nama'])) : ?>
                                <div id="nama-galat" class="invalid-feedback"><?= esc($galatAkun['nama']) ?></div>
                            <?php endif ?>
                        </div>

                        <div class="mb-3">
                            <label for="nama_pengguna" class="form-label">Nama pengguna</label>
                            <input type="text" id="nama_pengguna" name="nama_pengguna" maxlength="30"
                                autocomplete="username" autocapitalize="none" spellcheck="false" required
                                value="<?= esc($isianAkun['nama_pengguna'] ?? '', 'attr') ?>"
                                <?= $atributKotak('nama_pengguna', $galatAkun, true) ?>>
                            <div id="nama_pengguna-petunjuk" class="form-text">3–30 karakter: huruf kecil, angka, titik, atau garis bawah, tanpa spasi.</div>
                            <?php if (isset($galatAkun['nama_pengguna'])) : ?>
                                <div id="nama_pengguna-galat" class="invalid-feedback"><?= esc($galatAkun['nama_pengguna']) ?></div>
                            <?php endif ?>
                        </div>

                        <div class="mb-4">
                            <label for="sandi_akun" class="form-label">Kata sandi saat ini</label>
                            <input type="password" id="sandi_akun" name="sandi_akun" maxlength="255" autocomplete="current-password"
                                <?= $atributKotak('sandi_akun', $galatAkun, true) ?>>
                            <div id="sandi_akun-petunjuk" class="form-text">Hanya perlu diisi bila Anda mengganti nama pengguna.</div>
                            <?php if (isset($galatAkun['sandi_akun'])) : ?>
                                <div id="sandi_akun-galat" class="invalid-feedback"><?= esc($galatAkun['sandi_akun']) ?></div>
                            <?php endif ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                            Simpan nama
                        </button>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="card h-100" aria-labelledby="judul-kata-sandi">
                <div class="card-body p-4">
                    <h2 id="judul-kata-sandi" class="h5 mb-1">Kata sandi</h2>
                    <p class="text-body-secondary small mb-4">Setelah diganti, Anda tetap masuk di perangkat ini. Perangkat lain yang masuk dengan akun ini akan diminta masuk lagi.</p>

                    <?= view('admin/_galat_isian', ['galat' => $galatSandi], ['saveData' => false]) ?>

                    <form action="<?= site_url('admin/akun/sandi') ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <?php // Untuk pengelola kata sandi di peramban: kata sandi ini milik nama pengguna tersebut. Tidak ikut dikirim. ?>
                        <input type="text" class="visually-hidden" autocomplete="username" tabindex="-1" aria-hidden="true" readonly
                            value="<?= esc((string) $admin['nama_pengguna'], 'attr') ?>">

                        <?php foreach ($kotakSandi as $kotak => [$label, $isiOtomatis, $petunjuk]) : ?>
                            <div class="<?= $kotak === 'ulangi_sandi' ? 'mb-4' : 'mb-3' ?>">
                                <label for="<?= $kotak ?>" class="form-label"><?= esc($label) ?></label>
                                <input type="password" id="<?= $kotak ?>" name="<?= $kotak ?>" required
                                    maxlength="<?= $kotak === 'sandi_saat_ini' ? 255 : AdminModel::SANDI_MAKSIMAL ?>"
                                    <?= $kotak !== 'sandi_saat_ini' ? 'minlength="' . AdminModel::SANDI_MINIMAL . '"' : '' ?>
                                    autocomplete="<?= $isiOtomatis ?>"
                                    <?= $atributKotak($kotak, $galatSandi, true) ?>>
                                <div id="<?= $kotak ?>-petunjuk" class="form-text"><?= esc($petunjuk) ?></div>
                                <?php if (isset($galatSandi[$kotak])) : ?>
                                    <div id="<?= $kotak ?>-galat" class="invalid-feedback"><?= esc($galatSandi[$kotak]) ?></div>
                                <?php endif ?>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key" aria-hidden="true"></i>
                            Ganti kata sandi
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
