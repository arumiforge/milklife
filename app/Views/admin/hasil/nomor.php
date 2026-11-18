<?php
/**
 * Hasil satu nomor lomba di panel (F8-04): babak dengan hasilnya, tambah
 * babak, Terbitkan/Tarik kembali, dan unduhan PDF/Excel. Data dari
 * `HasilController::nomor()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak `HasilLomba::susunan()`
 * @var array<string, string>      $galat isian tambah babak
 * @var array{nama: string, final: string} $isian
 */

use App\Libraries\Hasil\JenisHasil;

$id      = (int) $nomor['id'];
$terbit  = $nomor['hasil_terbit_pada'] !== null;
$adaFinal = in_array(1, array_map('intval', array_column($babak, 'final')), true);
$judul   = $nomor['nama_lengkap'] . ' – ' . $nomor['nama_kelompok_umur'];
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Hasil <?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Hasil Lomba<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [
    ['label' => 'Hasil lomba', 'alamat' => 'admin/hasil?kelompok-umur=' . (int) $nomor['kelompok_umur_id']],
    ['label' => $judul],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h2 class="h4 mb-1"><?= esc($nomor['nama_lengkap']) ?></h2>
                    <p class="text-body-secondary mb-2">
                        <?= esc($nomor['nama_kelompok_umur']) ?> · <?= $nomor['jenis'] === 'beregu' ? 'Beregu' : 'Individu' ?> ·
                        hasil <?= esc(mb_strtolower(JenisHasil::LABEL[$nomor['jenis_hasil']] ?? '')) ?> (<?= esc(JenisHasil::KETERANGAN[$nomor['jenis_hasil']] ?? '') ?>)
                    </p>
                    <?php if ($terbit) : ?>
                        <p class="mb-0" id="keadaan-terbit"><span class="badge rounded-pill text-bg-success">Terbit</span> sejak <?= esc(tanggal_id((string) $nomor['hasil_terbit_pada'], true)) ?>. Perubahan hasil langsung tampil di halaman depan.</p>
                    <?php else : ?>
                        <p class="mb-0" id="keadaan-terbit"><span class="badge rounded-pill text-bg-secondary">Belum terbit</span> Hasil ini belum tampil di halaman depan.</p>
                    <?php endif ?>
                </div>
                <div class="d-flex flex-wrap align-items-start gap-2">
                    <?php if ($terbit) : ?>
                        <form method="post" action="<?= site_url('admin/hasil/' . $id . '/tarik') ?>"
                            data-konfirmasi="Hasil <?= esc($judul, 'attr') ?> akan ditarik dari halaman depan. Data hasilnya tetap tersimpan."
                            data-konfirmasi-tombol="Tarik kembali" data-konfirmasi-jenis="utama">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-eye-slash" aria-hidden="true"></i> Tarik kembali</button>
                        </form>
                    <?php else : ?>
                        <form method="post" action="<?= site_url('admin/hasil/' . $id . '/terbitkan') ?>"
                            data-konfirmasi="Hasil <?= esc($judul, 'attr') ?> akan tampil di halaman depan dan dihitung di klasemen.<?= $adaFinal ? '' : ' Babak Final belum ada.' ?>"
                            data-konfirmasi-tombol="Terbitkan" data-konfirmasi-jenis="utama">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-megaphone" aria-hidden="true"></i> Terbitkan</button>
                        </form>
                    <?php endif ?>
                    <a class="btn btn-outline-primary" href="<?= site_url('admin/hasil/' . $id . '/pdf') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
                    <a class="btn btn-outline-primary" href="<?= site_url('admin/hasil/' . $id . '/excel') ?>"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Excel</a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($babak === []) : ?>
        <div class="alert alert-info" id="belum-ada-babak">
            Belum ada babak. Untuk nomor yang langsung final (misalnya lari 1000 m atau Turbo Throw), cukup buat satu babak <strong>Final</strong>.
            Untuk nomor dengan penyisihan, buat babak penyisihan per seri dulu, lalu Final.
        </div>
    <?php endif ?>

    <?php foreach ($babak as $b) : ?>
        <?php $final = (int) $b['final'] === 1; ?>
        <div class="card mb-3" id="babak-<?= (int) $b['id'] ?>">
            <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="h6 fw-bold mb-0">
                    <?= esc($b['nama']) ?>
                    <?php if ($final) : ?><span class="badge rounded-pill text-bg-primary ms-1">Final</span><?php endif ?>
                    <span class="fw-normal text-body-secondary small ms-1"><?= (int) $b['jumlah_terisi'] ?> dari <?= (int) $b['jumlah_peserta'] ?> terisi</span>
                </h3>
                <a class="btn btn-sm btn-primary" href="<?= site_url('admin/hasil/babak/' . (int) $b['id']) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Isi hasil</a>
            </div>
            <?php if ($b['baris'] === []) : ?>
                <div class="card-body text-body-secondary">Belum ada peserta. Tekan Isi hasil untuk memilih pesertanya.</div>
            <?php else : ?>
                <?= view('hasil/_tabel_babak', ['nomor' => $nomor, 'babak' => $b], ['saveData' => false]) ?>
            <?php endif ?>
        </div>
    <?php endforeach ?>

    <div class="card mb-3" id="tambah-babak">
        <div class="card-body">
            <h3 class="h6 fw-bold">Tambah babak</h3>
            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>
            <form method="post" action="<?= site_url('admin/hasil/' . $id . '/babak') ?>" class="row g-2 align-items-end" novalidate>
                <?= csrf_field() ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="nama" class="form-label">Nama babak</label>
                    <input type="text" class="form-control<?= isset($galat['nama']) ? ' is-invalid' : '' ?>" id="nama" name="nama" maxlength="60" list="saran-babak"
                        value="<?= esc($isian['nama'] !== '' ? $isian['nama'] : ($adaFinal || $babak !== [] ? '' : 'Final'), 'attr') ?>" aria-describedby="nama-bantuan<?= isset($galat['nama']) ? ' nama-galat' : '' ?>">
                    <datalist id="saran-babak">
                        <?php for ($seri = 1; $seri <= 6; $seri++) : ?><option value="Penyisihan seri <?= $seri ?>"><?php endfor ?>
                        <option value="Final">
                    </datalist>
                    <div id="nama-bantuan" class="form-text">Misalnya Penyisihan seri 1, Penyisihan seri 2, atau Final.</div>
                    <?php if (isset($galat['nama'])) : ?><div id="nama-galat" class="invalid-feedback d-block"><?= esc($galat['nama']) ?></div><?php endif ?>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="form-check mb-2">
                        <input class="form-check-input<?= isset($galat['final']) ? ' is-invalid' : '' ?>" type="checkbox" id="final" name="final" value="1"<?= $isian['final'] === '1' || (! $adaFinal && $babak === [] && $isian['nama'] === '') ? ' checked' : '' ?><?= $adaFinal ? ' disabled' : '' ?>>
                        <label class="form-check-label" for="final">Babak Final <span class="text-body-secondary">(juara I–III dari babak ini)</span></label>
                    </div>
                    <?php if ($adaFinal) : ?><div class="form-text">Nomor ini sudah punya Final.</div><?php endif ?>
                    <?php if (isset($galat['final'])) : ?><div class="invalid-feedback d-block"><?= esc($galat['final']) ?></div><?php endif ?>
                </div>
                <div class="col-12 col-lg-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah babak</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
