<?php
/**
 * Tolak atlet dari satu pendaftaran (K116). Data dari
 * `TolakAtletController` (`TolakAtlet::formulir()`).
 *
 * Atlet yang kelompok umurnya tidak cocok lagi dengan pengaturan sekarang
 * diberi tanda dan tercentang saat halaman pertama dibuka. Penyimpanan
 * memakai jendela konfirmasi `aset/js/konfirmasi.js`.
 *
 * @var CodeIgniter\View\View $this
 * @var array<string, mixed>  $form
 * @var list<int>             $pilih     id atlet yang tercentang
 * @var string                $alasan
 * @var list<string>          $galat
 * @var int                   $alasanMin
 * @var int                   $alasanMax
 */

use App\Libraries\Pendaftaran\Regu;

$p       = $form['pendaftaran'];
$alamat  = 'admin/pendaftaran/' . (int) $p['id'];
$lunas   = $p['status'] !== 'menunggu';
$kosong  = $form['atlet'] === [];
$takCocok = count(array_filter($form['atlet'], static fn (array $a): bool => ! $a['cocok']));

$kalimatKonfirmasi = 'Atlet yang dicentang akan dikeluarkan dari pendaftaran ' . $p['id_pendaftaran'] . ' beserta nomor lombanya, '
    . 'dan biaya pendaftaran dihitung ulang. NISN-nya bisa didaftarkan lagi. Alasan dan rinciannya tampil di Cek Pendaftaran '
    . 'sampai atlet itu didaftarkan lagi. Tindakan ini tidak bisa dibatalkan.';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Tolak Atlet <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Tolak Atlet<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [
    App\Libraries\Panel\AsalRincian::untuk((int) $p['id']),
    ['label' => $p['id_pendaftaran'], 'alamat' => $alamat],
    ['label' => 'Tolak atlet'],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4" id="tolak-atlet" data-id="<?= (int) $p['id'] ?>">

    <section class="card mb-4" aria-labelledby="judul-tolak">
        <div class="card-body p-3 p-lg-4">
            <h2 id="judul-tolak" class="h4 mb-1"><?= esc($p['id_pendaftaran']) ?></h2>
            <p class="mb-3 fs-5"><?= esc($p['nama_sekolah']) ?> <span class="text-body-secondary fs-6">· <?= esc($p['jenjang_label']) ?></span></p>

            <div class="alert alert-info d-flex gap-2 mb-0" role="note">
                <i class="bi bi-info-circle flex-shrink-0" aria-hidden="true"></i>
                <div>
                    Atlet yang ditolak dikeluarkan dari pendaftaran ini, dan NISN-nya bisa didaftarkan lagi oleh sekolah.
                    Sekolah melihat alasan, kelompok umur yang sesuai, dan perubahan biayanya di Cek Pendaftaran,
                    sampai atlet itu didaftarkan lagi. Uang yang sudah terlanjur dikirim diselesaikan langsung dengan panitia.
                </div>
            </div>
        </div>
    </section>

    <?php if ($galat !== []) : ?>
        <div class="alert alert-danger" role="alert" id="galat-tolak" tabindex="-1">
            <ul class="mb-0 ps-3">
                <?php foreach ($galat as $pesan) : ?>
                    <li><?= esc($pesan) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if ($lunas) : ?>
        <div class="alert alert-warning" role="status" id="tolak-lunas">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            Pendaftaran ini sudah Lunas, jadi atletnya tidak bisa ditolak.
            Kembalikan ke Menunggu dulu di <a href="<?= site_url($alamat) ?>">rincian pendaftaran</a> bila atletnya memang perlu ditolak.
        </div>
    <?php elseif ($kosong) : ?>
        <p class="text-body-secondary">Pendaftaran ini tidak punya atlet.</p>
    <?php else : ?>
        <form action="<?= site_url($alamat . '/tolak-atlet') ?>" method="post"
            data-konfirmasi="<?= esc($kalimatKonfirmasi, 'attr') ?>"
            data-konfirmasi-judul="Tolak atlet?"
            data-konfirmasi-tombol="Tolak atlet">
            <?= csrf_field() ?>

            <section class="card mb-4" aria-labelledby="judul-pilih-atlet">
                <div class="card-body p-0">
                    <div class="px-3 px-lg-4 pt-3 pt-lg-4 pb-2">
                        <h2 id="judul-pilih-atlet" class="h5 mb-1">Pilih atlet yang ditolak</h2>
                        <p class="small text-body-secondary mb-0">
                            <?php if ($takCocok > 0) : ?>
                                <?= $takCocok ?> atlet kelompok umurnya tidak cocok lagi dengan pengaturan kelompok umur sekarang dan sudah dicentang. Periksa lagi sebelum menolak.
                            <?php else : ?>
                                Semua atlet cocok dengan pengaturan kelompok umur sekarang.
                            <?php endif ?>
                        </p>
                    </div>
                    <div class="tabel-gulir position-relative">
                        <table class="table align-middle mb-0" id="tabel-tolak-atlet">
                            <thead>
                                <tr>
                                    <th scope="col" class="ps-3 ps-lg-4"><span class="visually-hidden">Pilih</span></th>
                                    <th scope="col">Nama</th>
                                    <th scope="col">NISN</th>
                                    <th scope="col">Tanggal lahir</th>
                                    <th scope="col">Kelompok umur</th>
                                    <th scope="col">Nomor lomba</th>
                                    <th scope="col" class="pe-3 pe-lg-4 text-end">Biaya individu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($form['atlet'] as $a) : ?>
                                    <?php $idKotak = 'tolak-' . (int) $a['id']; ?>
                                    <tr data-id="<?= (int) $a['id'] ?>" class="<?= $a['cocok'] ? '' : 'table-warning' ?>">
                                        <td class="ps-3 ps-lg-4">
                                            <input class="form-check-input" type="checkbox" name="atlet[]" value="<?= (int) $a['id'] ?>" id="<?= $idKotak ?>"
                                                <?= in_array((int) $a['id'], $pilih, true) ? 'checked' : '' ?>>
                                        </td>
                                        <th scope="row" class="fw-semibold"><label for="<?= $idKotak ?>"><?= esc($a['nama']) ?></label></th>
                                        <td class="text-nowrap"><?= esc($a['nisn']) ?></td>
                                        <td class="text-nowrap"><?= esc(tanggal_id($a['tanggal_lahir'])) ?></td>
                                        <td>
                                            <span class="text-nowrap"><?= esc($a['kelompok_umur']) ?></span>
                                            <?php if (! $a['cocok']) : ?>
                                                <div class="small text-danger-emphasis ku-tidak-cocok">
                                                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                                    <?= $a['sesuai_id'] === null ? esc($a['sesuai']) : 'Seharusnya ' . esc($a['sesuai']) ?>
                                                </div>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($a['nomor_lomba'] as $n) : ?>
                                                    <li>
                                                        <?= esc($n['nama']) ?>
                                                        <?php if ($n['jenis'] === 'beregu' && $n['regu'] !== null) : ?>
                                                            <span class="badge rounded-pill text-bg-light border"><?= esc(Regu::label((string) $n['regu'])) ?> · <?= esc(Regu::labelPeran($n['peran'])) ?></span>
                                                        <?php endif ?>
                                                    </li>
                                                <?php endforeach ?>
                                            </ul>
                                        </td>
                                        <td class="pe-3 pe-lg-4 text-end text-nowrap"><?= esc(rupiah($a['biaya_individu'])) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="card mb-4" aria-labelledby="judul-alasan">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-alasan" class="h5 mb-2"><label for="alasan">Alasan penolakan</label></h2>
                    <p class="small text-body-secondary mb-2">Dibaca sekolah di Cek Pendaftaran. Tulis alasannya dan apa yang perlu dilakukan sekolah.</p>
                    <textarea class="form-control" id="alasan" name="alasan" rows="3" minlength="<?= $alasanMin ?>" maxlength="<?= $alasanMax ?>" required><?= esc($alasan) ?></textarea>
                    <p class="small text-body-secondary mt-2 mb-0">
                        Biaya regu dihitung ulang seperti di Ubah: regu yang tidak punya anggota lagi di pendaftaran ini tidak ditagih.
                        Bila semua atlet ditolak, pendaftaran tetap ada tanpa atlet dengan total Rp 0, supaya ofisial dan bukti bayarnya tetap tersimpan.
                    </p>
                </div>
            </section>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-person-x" aria-hidden="true"></i> Tolak atlet
                </button>
                <a class="btn btn-outline-secondary" href="<?= site_url($alamat) ?>">Batal</a>
            </div>
        </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
