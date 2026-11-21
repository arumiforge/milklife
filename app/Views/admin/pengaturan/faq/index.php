<?php
/**
 * Daftar tanya-jawab Bantuan (`GET /admin/pengaturan/bantuan`, F9-04): satu
 * bagian per kategori, masing-masing dengan tombol tambah sendiri. Tiap baris
 * punya tombol Naik, Turun, Ubah, Sembunyikan/Tampilkan, dan Hapus.
 * Bila daftar masih kosong, ada tombol untuk memasang tanya-jawab bawaan.
 *
 * @var CodeIgniter\View\View                          $this
 * @var array<string, list<array<string, mixed>>>      $perKategori kunci kategori => baris menurut urutan
 * @var int                                            $jumlah      semua tanya-jawab
 */

use App\Models\FaqModel;

$alamat = static fn (string $jalur = ''): string => site_url('admin/pengaturan/bantuan' . ($jalur !== '' ? '/' . $jalur : ''));
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="alert alert-info d-flex gap-2 align-items-start" role="note">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <div>
            Tanya-jawab ini tampil di halaman Bantuan dan di tombol bantuan formulir pendaftaran.
            Tulis jawaban pendek dengan bahasa sehari-hari dan sebut tombol yang perlu ditekan.
            Tanya-jawab yang disembunyikan tetap tersimpan di sini.
        </div>
    </div>

    <?php if ($jumlah === 0) : ?>
        <section class="card mb-4" aria-labelledby="judul-bawaan">
            <div class="card-body p-3 p-lg-4 text-center">
                <span class="ikon-bulat mb-2"><i class="bi bi-question-circle" aria-hidden="true"></i></span>
                <h2 id="judul-bawaan" class="h5">Belum ada tanya-jawab</h2>
                <p class="text-body-secondary mx-auto maks-w-40r">
                    Pasang tanya-jawab bawaan yang sudah disusun dari petunjuk teknis, lalu sesuaikan isinya.
                    Atau tambahkan sendiri lewat tombol di tiap kategori di bawah.
                </p>
                <form action="<?= $alamat('bawaan') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-magic" aria-hidden="true"></i> Pasang tanya-jawab bawaan
                    </button>
                </form>
            </div>
        </section>
    <?php endif ?>

    <div class="row g-4">
        <?php foreach ($perKategori as $kunci => $daftar) : ?>
            <?php
            $kategori = FaqModel::KATEGORI[$kunci] ?? ['label' => $kunci . ' (kategori tidak dikenal)', 'ikon' => 'question-circle', 'keterangan' => 'Ubah tanya-jawab di bawah dan pilih kategori yang ada.'];
            $idBagian = 'kategori-' . str_replace('_', '-', (string) $kunci);
            $terakhir = count($daftar) - 1;
            ?>
            <div class="col-12">
                <section class="card" id="<?= esc($idBagian, 'attr') ?>" aria-labelledby="judul-<?= esc($idBagian, 'attr') ?>">
                    <div class="card-body p-3 p-lg-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h2 id="judul-<?= esc($idBagian, 'attr') ?>" class="h5 mb-1">
                                    <i class="bi bi-<?= esc($kategori['ikon'], 'attr') ?> text-primary" aria-hidden="true"></i>
                                    <?= esc($kategori['label']) ?>
                                    <span class="badge text-bg-light border ms-1"><?= count($daftar) ?></span>
                                </h2>
                                <p class="text-body-secondary small mb-0"><?= esc($kategori['keterangan']) ?></p>
                            </div>
                            <?php if (FaqModel::kategoriSah($kunci)) : ?>
                                <a class="btn btn-primary" href="<?= $alamat('tambah') ?>?kategori=<?= esc((string) $kunci, 'url') ?>">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah tanya-jawab
                                </a>
                            <?php endif ?>
                        </div>

                        <?php if ($daftar === []) : ?>
                            <p class="text-body-secondary border rounded-3 px-3 py-3 mb-0">Belum ada tanya-jawab di kategori ini.</p>
                        <?php else : ?>
                            <div class="tabel-gulir position-relative">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-end">No</th>
                                            <th scope="col">Pertanyaan</th>
                                            <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar as $i => $faq) : ?>
                                            <?php
                                            $id     = (int) $faq['id'];
                                            $aktif  = (int) $faq['aktif'] === 1;
                                            $judul  = (string) $faq['pertanyaan'];
                                            ?>
                                            <tr id="faq-<?= $id ?>" data-id="<?= $id ?>"<?= $aktif ? '' : ' class="text-body-secondary"' ?>>
                                                <td class="text-end text-body-secondary"><?= $i + 1 ?></td>
                                                <td class="min-w-12r">
                                                    <span class="fw-semibold"><?= esc($judul) ?></span>
                                                    <?php if (! $aktif) : ?>
                                                        <span class="badge text-bg-secondary ms-1">Disembunyikan</span>
                                                    <?php endif ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                                        <?php foreach (['naik' => ['arrow-up', 'Naikkan', $i === 0], 'turun' => ['arrow-down', 'Turunkan', $i === $terakhir]] as $arah => [$ikon, $label, $mati]) : ?>
                                                            <form action="<?= $alamat($id . '/' . $arah) ?>" method="post">
                                                                <?= csrf_field() ?>
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $label ?>"<?= $mati ? ' disabled' : '' ?>>
                                                                    <i class="bi bi-<?= $ikon ?>" aria-hidden="true"></i><span class="visually-hidden"> <?= $label ?> <?= esc($judul) ?></span>
                                                                </button>
                                                            </form>
                                                        <?php endforeach ?>
                                                        <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= $alamat($id . '/ubah') ?>">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i> Ubah<span class="visually-hidden"> <?= esc($judul) ?></span>
                                                        </a>
                                                        <form action="<?= $alamat($id . '/aktif') ?>" method="post">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                                                                <i class="bi bi-<?= $aktif ? 'eye-slash' : 'eye' ?>" aria-hidden="true"></i>
                                                                <?= $aktif ? 'Sembunyikan' : 'Tampilkan' ?><span class="visually-hidden"> <?= esc($judul) ?></span>
                                                            </button>
                                                        </form>
                                                        <form action="<?= $alamat($id . '/hapus') ?>" method="post"
                                                            data-konfirmasi="<?= esc('Tanya-jawab "' . $judul . '" akan dihapus. Tindakan ini tidak bisa dibatalkan. Bila hanya ingin menyembunyikannya, tekan Sembunyikan.', 'attr') ?>"
                                                            data-konfirmasi-judul="Hapus tanya-jawab?"
                                                            data-konfirmasi-tombol="Hapus tanya-jawab">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                                                <i class="bi bi-trash" aria-hidden="true"></i> Hapus<span class="visually-hidden"> <?= esc($judul) ?></span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
