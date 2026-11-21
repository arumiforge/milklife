<?php
/**
 * Pengaturan > Tabel Skor (F9-10): daftar tabel skor multi-lomba, pasang
 * tabel bawaan panitia, tambah dan hapus tabel. Data dari `TabelSkorController::index()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var list<array<string, mixed>> $daftar        `DataTabelSkor::daftar()`
 * @var list<string>               $belumDipasang nama tabel bawaan yang belum terpasang
 * @var array<string, string>      $galat         galat tambah tabel
 * @var array<string, mixed>       $isian         isian tambah tabel yang dikembalikan
 */

use App\Libraries\Hasil\TabelSkor;

$alamat   = 'admin/pengaturan/tabel-skor';
$komponen = array_values((array) ($isian['komponen'] ?? []));
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Tabel Skor<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Tabel Skor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="teks-info mb-4">
        Tabel skor mengubah prestasi (waktu atau jarak) menjadi poin untuk nomor berjenis hasil poin multi-lomba, misalnya Trilomba.
        Prestasi di antara dua baris memakai baris yang lebih buruk; prestasi di luar tabel mendapat 0 poin dan ditandai.
        Setiap perubahan baris langsung menghitung ulang poin dan peringkat hasil lomba yang memakai tabel itu.
    </p>

    <?php if ($belumDipasang !== []) : ?>
        <section class="card mb-4" id="tabel-bawaan" aria-labelledby="judul-bawaan">
            <div class="card-body">
                <h2 class="h5" id="judul-bawaan">Tabel bawaan panitia</h2>
                <p class="mb-3">Belum terpasang: <?= esc(implode(', ', $belumDipasang)) ?>. Isinya dari tabel penilaian panitia (scoring table IAAF/PASI dan World Athletics).</p>
                <form action="<?= site_url($alamat . '/pasang') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-download" aria-hidden="true"></i> Pasang tabel bawaan</button>
                </form>
            </div>
        </section>
    <?php endif ?>

    <?php foreach ($daftar as $t) : ?>
        <section class="card mb-4" id="tabel-<?= $t['id'] ?>" aria-labelledby="judul-tabel-<?= $t['id'] ?>">
            <div class="card-body">
                <h2 class="h5 mb-1" id="judul-tabel-<?= $t['id'] ?>"><?= esc($t['nama']) ?></h2>
                <?php if ($t['keterangan'] !== '') : ?><p class="small text-body-secondary mb-2"><?= esc($t['keterangan']) ?></p><?php endif ?>
                <p class="small mb-3" data-pemakai>
                    <?= $t['nomor'] === [] ? 'Belum dipakai nomor lomba.' : 'Dipakai: ' . esc(implode(', ', $t['nomor'])) . '.' ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-3">
                        <caption class="visually-hidden">Komponen <?= esc($t['nama']) ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Komponen</th>
                                <th scope="col">Jenis</th>
                                <?php foreach (TabelSkor::KATEGORI as $label) : ?><th scope="col"><?= $label ?></th><?php endforeach ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($t['komponen'] as $k) : ?>
                                <tr>
                                    <td class="fw-semibold"><?= esc($k['nama']) ?></td>
                                    <td><?= esc(TabelSkor::JENIS_KOMPONEN[$k['jenis']]) ?></td>
                                    <?php foreach (array_keys(TabelSkor::KATEGORI) as $kategori) : ?>
                                        <td>
                                            <a href="<?= site_url($alamat . '/' . $t['id'] . '/' . $k['kode'] . '/' . $kategori) ?>">
                                                <?= number_format($t['jumlah'][$k['kode']][$kategori] ?? 0, 0, ',', '.') ?> baris<span class="visually-hidden"> <?= esc($k['nama'] . ' ' . $kategori) ?></span>
                                            </a>
                                        </td>
                                    <?php endforeach ?>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($t['nomor'] === []) : ?>
                    <form action="<?= site_url($alamat . '/' . $t['id'] . '/hapus') ?>" method="post"
                        data-konfirmasi="<?= esc('Tabel ' . $t['nama'] . ' beserta semua barisnya akan dihapus. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                        data-konfirmasi-judul="Hapus tabel skor?" data-konfirmasi-tombol="Hapus tabel">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash" aria-hidden="true"></i> Hapus tabel</button>
                    </form>
                <?php endif ?>
            </div>
        </section>
    <?php endforeach ?>

    <?php if ($daftar === [] && $belumDipasang === []) : ?>
        <p class="text-body-secondary" id="tabel-kosong">Belum ada tabel skor.</p>
    <?php endif ?>

    <section class="card mb-4" id="tambah-tabel" aria-labelledby="judul-tambah-tabel">
        <div class="card-body">
            <h2 class="h5" id="judul-tambah-tabel">Tambah tabel</h2>
            <p class="small text-body-secondary">Untuk tabel lain dari panitia. Tulis nama tabel dan komponennya; barisnya diisi sesudahnya lewat Excel (kolom A prestasi, kolom B poin) untuk tiap komponen, putra dan putri.</p>
            <form action="<?= site_url($alamat) ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama tabel</label>
                    <input type="text" class="form-control<?= isset($galat['nama']) ? ' is-invalid' : '' ?>" id="nama" name="nama" maxlength="<?= TabelSkor::NAMA_MAKSIMAL ?>" value="<?= esc((string) ($isian['nama'] ?? ''), 'attr') ?>">
                    <?php if (isset($galat['nama'])) : ?><div class="invalid-feedback d-block"><?= esc($galat['nama']) ?></div><?php endif ?>
                </div>
                <fieldset class="mb-3">
                    <legend class="form-label fs-6">Komponen</legend>
                    <?php for ($i = 0; $i < 5; $i++) : ?>
                        <div class="row g-2 mb-2">
                            <div class="col-7 col-md-5">
                                <label class="visually-hidden" for="komponen-nama-<?= $i ?>">Nama komponen <?= $i + 1 ?></label>
                                <input type="text" class="form-control form-control-sm<?= isset($galat['komponen']) ? ' is-invalid' : '' ?>" id="komponen-nama-<?= $i ?>" name="komponen_nama[]" maxlength="<?= TabelSkor::NAMA_KOMPONEN_MAKSIMAL ?>"
                                    placeholder="<?= ['Lari 60 m', 'Lompat Jauh', 'Tolak Peluru', '', ''][$i] ?>" value="<?= esc((string) ($komponen[$i]['nama'] ?? ''), 'attr') ?>">
                            </div>
                            <div class="col-5 col-md-4">
                                <label class="visually-hidden" for="komponen-jenis-<?= $i ?>">Jenis komponen <?= $i + 1 ?></label>
                                <select class="form-select form-select-sm" id="komponen-jenis-<?= $i ?>" name="komponen_jenis[]">
                                    <?php foreach (TabelSkor::JENIS_KOMPONEN as $kode => $label) : ?>
                                        <option value="<?= $kode ?>"<?= ($komponen[$i]['jenis'] ?? ($i === 0 ? 'waktu' : 'jarak')) === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    <?php endfor ?>
                    <?php if (isset($galat['komponen'])) : ?><div class="invalid-feedback d-block"><?= esc($galat['komponen']) ?></div><?php endif ?>
                </fieldset>
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah tabel</button>
            </form>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
