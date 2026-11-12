<?php
/**
 * Daftar nomor lomba, dikelompokkan per kelompok umur, dengan saringan
 * kelompok umur dan "belum lengkap saja". Data dari
 * `NomorLombaController::index()`.
 *
 * Di ponsel, kolom jenis, kategori, tim, dan biaya diringkas di bawah nama
 * nomor supaya tabel tidak perlu digulir mendatar; di layar ≥ md tampil
 * sebagai kolom sendiri.
 *
 * @var CodeIgniter\View\View                                        $this
 * @var array<int, array{id: int, nama: string, aktif: bool}>        $kelompokUmur
 * @var array<int, array{id: int, nama: string, aktif: bool, nomor: list<array<string, mixed>>}> $kelompok
 * @var int                                                          $jumlahSemua
 * @var array<int, int>                                              $jumlahPerKu
 * @var int                                                          $jumlahBelum
 * @var int                                                          $jumlahTampil
 * @var int                                                          $saringKu
 * @var bool                                                         $saringBelumLengkap
 */

use App\Models\NomorLombaModel;

$alamat = 'admin/pengaturan/nomor-lomba';

/**
 * Ringkasan tim satu nomor beregu, misalnya "4 inti, paling banyak 6 · paling sedikit 4 putra dan 4 putri".
 *
 * @param array<string, mixed> $nomor
 */
$ringkasTim = static function (array $nomor): string {
    if ($nomor['jenis'] !== 'beregu') {
        return '';
    }

    $bagian = [];

    if ($nomor['jumlah_inti'] !== null) {
        $bagian[] = $nomor['jumlah_inti'] . ' inti' . ($nomor['batas_anggota'] !== null ? ', paling banyak ' . $nomor['batas_anggota'] : '');
    }

    if ($nomor['kategori'] === 'mix' && $nomor['jumlah_putra'] !== null && $nomor['jumlah_putri'] !== null) {
        $bagian[] = 'minimal ' . $nomor['jumlah_putra'] . ' putra dan ' . $nomor['jumlah_putri'] . ' putri';
    }

    return implode(' · ', $bagian);
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Nomor Lomba<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Nomor Lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php if ($kelompokUmur === []) : ?>
        <section class="card kartu-kosong" aria-labelledby="judul-kosong">
            <div class="card-body text-center px-3 py-5">
                <span class="ikon-bulat mb-3"><i class="bi bi-people" aria-hidden="true"></i></span>
                <h2 id="judul-kosong" class="h4">Belum ada kelompok umur</h2>
                <p class="text-body-secondary mx-auto">
                    Setiap nomor lomba milik satu kelompok umur. Buat kelompok umur dulu, lalu kembali ke sini untuk menambah nomor lombanya.
                </p>
                <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/kelompok-umur') ?>">
                    <i class="bi bi-people" aria-hidden="true"></i> Buka Kelompok Umur
                </a>
            </div>
        </section>
    <?php elseif ($jumlahSemua === 0) : ?>
        <section class="card kartu-kosong" aria-labelledby="judul-kosong">
            <div class="card-body text-center px-3 py-5">
                <span class="ikon-bulat mb-3"><i class="bi bi-trophy" aria-hidden="true"></i></span>
                <h2 id="judul-kosong" class="h4">Belum ada nomor lomba</h2>
                <p class="text-body-secondary mx-auto">
                    Tambahkan nomor individu dan beregu untuk tiap kelompok umur. Sekolah hanya bisa memilih nomor yang ada di sini.
                </p>
                <a class="btn btn-primary" href="<?= site_url($alamat . '/tambah') ?>">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah nomor lomba
                </a>
            </div>
        </section>
    <?php else : ?>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <p class="text-body-secondary mb-0 pengaturan-pengantar">
                <?= $jumlahSemua ?> nomor lomba di <?= count($kelompokUmur) ?> kelompok umur.
                Nomor yang tidak aktif tidak muncul di pendaftaran.
            </p>
            <a class="btn btn-primary" href="<?= site_url($alamat . '/tambah' . ($saringKu !== 0 ? '?kelompok-umur=' . $saringKu : '')) ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah nomor lomba
            </a>
        </div>

        <?php if ($jumlahBelum > 0) : ?>
            <div class="alert alert-warning d-flex align-items-start gap-2" role="status" id="ringkasan-belum-lengkap">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0" aria-hidden="true"></i>
                <div>
                    <strong><?= $jumlahBelum ?> nomor beregu belum lengkap.</strong>
                    Isi jumlah inti, batas anggota, dan biaya beregu (untuk nomor mix juga jumlah putra dan putri minimal) supaya pendaftaran bisa dibuka.
                    <?php if (! $saringBelumLengkap) : ?>
                        <a class="alert-link" href="<?= site_url($alamat) ?>?belum-lengkap=1">Tampilkan yang belum lengkap saja</a>
                    <?php endif ?>
                </div>
            </div>
        <?php endif ?>

        <form class="card mb-4" method="get" action="<?= site_url($alamat) ?>" id="saringan" role="search" aria-label="Saring nomor lomba">
            <div class="card-body row g-3 align-items-end mx-0">
                <div class="col-12 col-md-6 col-xl-4 ps-0">
                    <label for="saring-kelompok-umur" class="form-label">Kelompok umur</label>
                    <select class="form-select" id="saring-kelompok-umur" name="kelompok-umur">
                        <option value="">Semua kelompok umur (<?= $jumlahSemua ?> nomor)</option>
                        <?php foreach ($kelompokUmur as $id => $ku) : ?>
                            <option value="<?= $id ?>"<?= $saringKu === $id ? ' selected' : '' ?>>
                                <?= esc($ku['nama']) ?> (<?= $jumlahPerKu[$id] ?? 0 ?> nomor)<?= $ku['aktif'] ? '' : ' – tidak aktif' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto ps-0">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="saring-belum-lengkap" name="belum-lengkap" value="1"<?= $saringBelumLengkap ? ' checked' : '' ?>>
                        <label class="form-check-label" for="saring-belum-lengkap">Belum lengkap saja (<?= $jumlahBelum ?>)</label>
                    </div>
                </div>
                <div class="col-auto ps-0 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
                    <?php if ($saringKu !== 0 || $saringBelumLengkap) : ?>
                        <a class="btn btn-link" href="<?= site_url($alamat) ?>">Hapus saringan</a>
                    <?php endif ?>
                </div>
            </div>
        </form>

        <?php if ($jumlahTampil === 0 && $saringBelumLengkap) : ?>
            <section class="card kartu-kosong" aria-labelledby="judul-kosong">
                <div class="card-body text-center px-3 py-5">
                    <span class="ikon-bulat mb-3"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <h2 id="judul-kosong" class="h4">Semua nomor sudah lengkap</h2>
                    <p class="text-body-secondary mx-auto">Tidak ada nomor beregu yang belum lengkap<?= $saringKu !== 0 ? ' di ' . esc($kelompokUmur[$saringKu]['nama']) : '' ?>.</p>
                    <a class="btn btn-outline-primary" href="<?= site_url($alamat) ?>">Tampilkan semua nomor</a>
                </div>
            </section>
        <?php endif ?>

        <?php foreach ($kelompok as $kuId => $ku) : ?>
            <section class="card mb-4 kelompok-nomor-lomba" id="ku-<?= $kuId ?>" aria-labelledby="judul-ku-<?= $kuId ?>">
                <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <h2 id="judul-ku-<?= $kuId ?>" class="h5 mb-0">
                        <?= esc($ku['nama']) ?>
                        <span class="badge rounded-pill text-bg-light border fw-semibold align-middle"><?= $jumlahPerKu[$kuId] ?? 0 ?> nomor</span>
                        <?php if (! $ku['aktif']) : ?>
                            <span class="badge rounded-pill text-bg-secondary align-middle">Kelompok umur tidak aktif</span>
                        <?php endif ?>
                    </h2>
                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url($alamat . '/tambah?kelompok-umur=' . $kuId) ?>">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah nomor<span class="visually-hidden"> di <?= esc($ku['nama']) ?></span>
                    </a>
                </div>

                <?php if ($ku['nomor'] === []) : ?>
                    <div class="card-body text-body-secondary">
                        Belum ada nomor lomba di kelompok umur ini.
                    </div>
                <?php else : ?>
                    <div class="tabel-gulir">
                        <table class="table table-hover align-middle mb-0">
                            <caption class="visually-hidden">Nomor lomba <?= esc($ku['nama']) ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="d-none d-md-table-cell text-end">Urutan</th>
                                    <th scope="col">Nomor</th>
                                    <th scope="col" class="d-none d-md-table-cell">Jenis</th>
                                    <th scope="col" class="d-none d-md-table-cell">Kategori</th>
                                    <th scope="col" class="d-none d-md-table-cell">Tim</th>
                                    <th scope="col" class="d-none d-md-table-cell text-end">Biaya beregu</th>
                                    <th scope="col">Aktif</th>
                                    <th scope="col" class="text-end"><span class="visually-hidden">Tindakan</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ku['nomor'] as $nomor) : ?>
                                    <?php
                                    $id          = (int) $nomor['id'];
                                    $namaLengkap = NomorLombaModel::namaLengkap($nomor);
                                    $tim         = $ringkasTim($nomor);
                                    $biaya       = $nomor['jenis'] === 'beregu' ? rupiah($nomor['biaya_beregu']) : '';
                                    $belum       = $nomor['kekurangan'] !== [];
                                    ?>
                                    <tr id="nomor-<?= $id ?>" data-id="<?= $id ?>"<?= $belum ? ' data-belum-lengkap="1"' : '' ?>>
                                        <td class="d-none d-md-table-cell text-end text-body-secondary"><?= (int) $nomor['urutan'] ?></td>
                                        <td>
                                            <span class="fw-semibold"><?= esc($nomor['nama']) ?></span>
                                            <span class="d-md-none text-body-secondary"><?= esc($nomor['kategori']) ?></span>
                                            <span class="d-block d-md-none small text-body-secondary">
                                                <?= esc(NomorLombaModel::JENIS[$nomor['jenis']] ?? $nomor['jenis']) ?><?= $tim !== '' ? ' · ' . esc($tim) : '' ?><?= $biaya !== '' ? ' · ' . $biaya . ' per tim' : '' ?>
                                            </span>
                                            <?php if ($belum) : ?>
                                                <span class="d-block small text-warning-emphasis mt-1">
                                                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                                    Belum lengkap: <?= esc(implode(', ', $nomor['kekurangan'])) ?> belum diisi.
                                                </span>
                                            <?php endif ?>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?= esc(NomorLombaModel::JENIS[$nomor['jenis']] ?? $nomor['jenis']) ?></td>
                                        <td class="d-none d-md-table-cell"><?= esc(NomorLombaModel::KATEGORI[$nomor['kategori']] ?? $nomor['kategori']) ?></td>
                                        <td class="d-none d-md-table-cell"><?= $tim !== '' ? esc($tim) : '<span class="text-body-secondary">–</span>' ?></td>
                                        <td class="d-none d-md-table-cell text-end text-nowrap"><?= $biaya !== '' ? $biaya : '<span class="text-body-secondary">–</span>' ?></td>
                                        <td>
                                            <?php if ((int) $nomor['aktif'] === 1) : ?>
                                                <span class="badge rounded-pill text-bg-success">Aktif</span>
                                            <?php else : ?>
                                                <span class="badge rounded-pill text-bg-secondary">Tidak aktif</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a class="btn btn-sm text-nowrap <?= $belum ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= site_url($alamat . '/' . $id . '/ubah') ?>"
                                                    aria-label="<?= esc(($belum ? 'Lengkapi ' : 'Ubah ') . $namaLengkap, 'attr') ?>">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    <span class="d-none d-lg-inline"><?= $belum ? 'Lengkapi' : 'Ubah' ?></span>
                                                </a>
                                                <?php if (! $nomor['dipakai']) : ?>
                                                    <form method="post" action="<?= site_url($alamat . '/' . $id . '/hapus') ?>"
                                                        data-konfirmasi="Nomor <?= esc($namaLengkap . ' di ' . $ku['nama'], 'attr') ?> akan dihapus dan tidak bisa dikembalikan."
                                                        data-konfirmasi-tombol="Hapus nomor">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="<?= esc('Hapus ' . $namaLengkap, 'attr') ?>">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                <?php endif ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </section>
        <?php endforeach; ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script>
    // Saringan langsung diterapkan saat diganti; tombol Tampilkan tetap ada bila skrip mati.
    (function () {
        var saringan = document.getElementById('saringan');
        if (!saringan) {
            return;
        }
        saringan.addEventListener('change', function () {
            saringan.requestSubmit ? saringan.requestSubmit() : saringan.submit();
        });
    })();
</script>
<?= $this->endSection() ?>
