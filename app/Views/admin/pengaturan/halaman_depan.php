<?php
/**
 * Formulir isi halaman depan (`HalamanDepanController`).
 *
 * @var CodeIgniter\View\View                                                      $this
 * @var array<string, string>                                                      $isian
 * @var array<string, string>                                                      $galat
 * @var array<string, array{label: string, rasio: string, saran: string, jalur: string}> $gambar kunci => keterangan dan jalur tersimpan ('' bila belum ada)
 * @var list<string>                                                               $gambarPerluDipilihLagi
 */

use App\Libraries\Unggahan;

$kotak = static function (string $nama, string $label, string $bantuan, array $isian, array $galat, int $baris = 0, array $atribut = []): string {
    $salah    = isset($galat[$nama]);
    $jelaskan = trim(($bantuan !== '' ? $nama . '-bantuan ' : '') . ($salah ? $nama . '-galat' : ''));
    $tambahan = $jelaskan !== '' ? ' aria-describedby="' . $jelaskan . '"' : '';
    foreach ($atribut as $kunci => $nilai) {
        $tambahan .= ' ' . $kunci . '="' . esc($nilai, 'attr') . '"';
    }
    $kelas = 'form-control' . ($salah ? ' is-invalid' : '');

    $masukan = $baris > 0
        ? '<textarea class="' . $kelas . '" id="' . $nama . '" name="' . $nama . '" rows="' . $baris . '"' . $tambahan . '>' . esc($isian[$nama] ?? '') . '</textarea>'
        : '<input type="text" class="' . $kelas . '" id="' . $nama . '" name="' . $nama . '" value="' . esc($isian[$nama] ?? '', 'attr') . '"' . $tambahan . '>';

    return '<div class="mb-3">'
        . '<label for="' . $nama . '" class="form-label">' . esc($label) . '</label>'
        . $masukan
        . ($salah ? '<div id="' . $nama . '-galat" class="invalid-feedback">' . esc($galat[$nama]) . '</div>' : '')
        . ($bantuan !== '' ? '<div id="' . $nama . '-bantuan" class="form-text">' . esc($bantuan) . '</div>' : '')
        . '</div>';
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Halaman Depan – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Halaman Depan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-body-secondary mb-4">
        Isi yang tampil di halaman depan web pendaftaran. Semua boleh diisi bertahap.
        Nama, seri, tahun, tempat, dan tanggal acara diatur di <a href="<?= site_url('admin/pengaturan/acara') ?>">Data Acara</a>.
    </p>

    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

    <?php if ($gambarPerluDipilihLagi !== []) : ?>
        <div class="alert alert-warning" role="status">
            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            <?= esc(implode(', ', $gambarPerluDipilihLagi)) ?> yang tadi dipilih belum tersimpan karena ada isian yang perlu diperbaiki.
            Pilih gambarnya lagi sebelum menyimpan.
        </div>
    <?php endif ?>

    <form action="<?= site_url('admin/pengaturan/halaman-depan') ?>" method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <section class="card mb-4" aria-labelledby="judul-gambar">
            <div class="card-body p-3 p-md-4">
                <h2 id="judul-gambar" class="h5 mb-1">Gambar dan logo</h2>
                <p class="small text-body-secondary mb-3">
                    JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR) ?> per gambar.
                    Biarkan kotak unggah kosong bila gambarnya tidak ingin diganti.
                </p>

                <div class="row g-4">
                    <?php foreach ($gambar as $nama => $butir) : ?>
                        <?php $salah = isset($galat[$nama]); ?>
                        <div class="<?= $nama === 'gambar_kepala' ? 'col-12' : 'col-12 col-md-6 col-xxl-4' ?>">
                            <fieldset class="h-100 border rounded p-3" data-kotak-gambar="<?= esc($nama, 'attr') ?>">
                                <legend class="float-none w-auto fs-6 fw-semibold px-1 mb-2"><?= esc($butir['label']) ?></legend>

                                <div class="mb-3" style="max-width: <?= $nama === 'gambar_kepala' ? '40rem' : ($butir['rasio'] === '1x1' ? '12rem' : '20rem') ?>;" data-pratinjau="<?= esc($nama, 'attr') ?>" data-rasio="<?= esc($butir['rasio'], 'attr') ?>">
                                    <?php if ($butir['jalur'] !== '') : ?>
                                        <div class="ratio ratio-<?= esc($butir['rasio'], 'attr') ?> border rounded bg-white">
                                            <img src="<?= esc(base_url($butir['jalur']), 'attr') ?>" alt="<?= esc($butir['label'] . ' saat ini', 'attr') ?>"
                                                class="<?= $nama === 'gambar_kepala' ? 'rounded' : 'p-2' ?>" style="object-fit: <?= $nama === 'gambar_kepala' ? 'cover' : 'contain' ?>;">
                                        </div>
                                    <?php else : ?>
                                        <?= view('tata_letak/_tanpa_gambar', ['rasio' => $butir['rasio'], 'label' => $butir['label']], ['saveData' => false]) ?>
                                    <?php endif ?>
                                </div>

                                <label for="<?= esc($nama, 'attr') ?>" class="form-label small mb-1"><?= $butir['jalur'] === '' ? 'Unggah gambar' : 'Ganti gambar' ?></label>
                                <input type="file" class="form-control<?= $salah ? ' is-invalid' : '' ?>" id="<?= esc($nama, 'attr') ?>" name="<?= esc($nama, 'attr') ?>"
                                    accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"
                                    aria-describedby="<?= esc($nama, 'attr') ?>-bantuan<?= $salah ? ' ' . esc($nama, 'attr') . '-galat' : '' ?>">
                                <?php if ($salah) : ?>
                                    <div id="<?= esc($nama, 'attr') ?>-galat" class="invalid-feedback"><?= esc($galat[$nama]) ?></div>
                                <?php endif ?>
                                <div id="<?= esc($nama, 'attr') ?>-bantuan" class="form-text"><?= esc($butir['saran']) ?></div>

                                <?php if ($butir['jalur'] !== '') : ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="hapus_<?= esc($nama, 'attr') ?>" name="hapus_<?= esc($nama, 'attr') ?>">
                                        <label class="form-check-label" for="hapus_<?= esc($nama, 'attr') ?>">Hapus gambar ini</label>
                                    </div>
                                <?php endif ?>
                            </fieldset>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <section class="card h-100" aria-labelledby="judul-teks">
                    <div class="card-body p-3 p-md-4">
                        <h2 id="judul-teks" class="h5 mb-3">Teks</h2>
                        <?= $kotak('judul', 'Judul', 'Judul besar di bagian atas, misalnya "Ayo ikut MilkLife Athletics Challenge!".', $isian, $galat, 0, ['maxlength' => '150']) ?>
                        <?= $kotak('teks', 'Teks pengantar', 'Beberapa kalimat di bawah judul. Tekan Enter untuk membuat paragraf baru.', $isian, $galat, 6, ['maxlength' => '2000']) ?>
                        <?= $kotak('footer', 'Teks kaki halaman', 'Tampil di bagian paling bawah, misalnya nama penyelenggara dan tahun.', $isian, $galat, 3, ['maxlength' => '500']) ?>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-6">
                <section class="card h-100" aria-labelledby="judul-wa">
                    <div class="card-body p-3 p-md-4">
                        <h2 id="judul-wa" class="h5 mb-3">Tombol WA</h2>
                        <?= $kotak('nomor_wa_tanya', 'Nomor WA tanya admin', 'Nomor yang dihubungi lewat tombol "Tanya admin", misalnya 0812-3456-7890.', $isian, $galat, 0, ['inputmode' => 'tel', 'autocomplete' => 'off']) ?>
                        <?= $kotak('pesan_tanya', 'Pesan tanya admin', 'Pesan yang sudah terketik saat pengunjung menekan "Tanya admin". Pengunjung masih bisa mengubahnya sebelum dikirim.', $isian, $galat, 4, ['maxlength' => '1000']) ?>
                        <?= $kotak('pesan_bagikan', 'Pesan bagikan', 'Pesan yang dibagikan lewat tombol "Bagikan ke WA". Tulis juga alamat web pendaftaran supaya penerima bisa langsung membukanya.', $isian, $galat, 4, ['maxlength' => '1000']) ?>
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4 mb-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan isi halaman depan
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script>
(function () {
    'use strict';

    // Pratinjau gambar yang baru dipilih, sebelum disimpan.
    document.querySelectorAll('[data-pratinjau]').forEach(function (wadah) {
        var nama = wadah.getAttribute('data-pratinjau');
        var berkas = document.getElementById(nama);
        berkas.addEventListener('change', function () {
            if (!berkas.files || !berkas.files[0] || !/^image\//.test(berkas.files[0].type)) {
                return;
            }
            var bingkai = document.createElement('div');
            bingkai.className = 'ratio ratio-' + wadah.getAttribute('data-rasio') + ' border rounded bg-white';
            var gambar = document.createElement('img');
            gambar.src = URL.createObjectURL(berkas.files[0]);
            gambar.alt = 'Gambar yang baru dipilih (belum disimpan)';
            gambar.style.objectFit = nama === 'gambar_kepala' ? 'cover' : 'contain';
            gambar.className = nama === 'gambar_kepala' ? 'rounded' : 'p-2';
            bingkai.appendChild(gambar);
            wadah.replaceChildren(bingkai);
        });
    });
}());
</script>
<?= $this->endSection() ?>
