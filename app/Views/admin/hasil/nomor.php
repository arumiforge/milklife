<?php
/**
 * Hasil satu nomor lomba di panel admin (F8-04, K124): semua babak dan
 * hasilnya, tambah babak, Terbitkan/Tarik kembali, unduh PDF dan Excel.
 * Data dari `HasilController::nomor()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak `HasilLomba::semuaBabak()`
 * @var int                        $calon banyak calon peserta (atlet atau regu Lunas)
 * @var array<string, string>      $galat galat formulir tambah babak
 * @var array<string, mixed>       $isian isian formulir tambah babak yang dikembalikan
 */

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JenisHasil;

$beregu   = $nomor['jenis'] === 'beregu';
$adaFinal = in_array(true, array_column($babak, 'final'), true);
$isi      = (string) ($isian['isi'] ?? ($babak === [] ? 'semua' : 'lolos'));
$salah    = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$pesan    = static fn (string $kotak): string => isset($galat[$kotak]) ? '<div id="' . $kotak . '-galat" class="invalid-feedback d-block">' . esc($galat[$kotak]) . '</div>' : '';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Hasil <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [['label' => 'Hasil lomba', 'alamat' => 'admin/hasil'], ['label' => $nomor['kelompok_umur'] . ' ' . $nomor['nama']]]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="me-auto">
            <p class="mb-0 text-body-secondary">
                <?= esc($nomor['kelompok_umur']) ?> · <?= $beregu ? 'Beregu' : 'Individu' ?> · Jenis hasil <strong><?= esc(JenisHasil::LABEL[$nomor['jenis_hasil']]) ?></strong> (<?= esc(JenisHasil::KETERANGAN[$nomor['jenis_hasil']]) ?>)
            </p>
            <p class="mb-0 small text-body-secondary"><?= $calon ?> <?= $beregu ? 'regu' : 'atlet' ?> dari pendaftaran Lunas. Jenis hasil diubah di Pengaturan, Nomor Lomba.</p>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Unduh hasil">
            <a class="btn btn-outline-primary" href="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
            <a class="btn btn-outline-success" href="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/excel') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel</a>
        </div>
    </div>

    <section class="card mb-4" id="terbit" aria-labelledby="judul-terbit">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <div class="me-auto">
                <h2 class="h6 mb-1" id="judul-terbit">Halaman publik</h2>
                <?php if ($nomor['terbit_pada'] !== null) : ?>
                    <p class="mb-0"><span class="badge rounded-pill text-bg-success">Terbit</span> sejak <?= esc(tanggal_id($nomor['terbit_pada'], true)) ?>. Perubahan hasil langsung tampil di halaman publik.</p>
                <?php else : ?>
                    <p class="mb-0"><span class="badge rounded-pill text-bg-secondary">Belum terbit</span> Hasil nomor ini belum tampil di halaman publik dan belum dihitung di klasemen publik.</p>
                <?php endif ?>
            </div>
            <?php if ($nomor['terbit_pada'] !== null) : ?>
                <form action="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/tarik') ?>" method="post"
                    data-konfirmasi="<?= esc('Hasil ' . $nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' akan hilang dari halaman publik dan klasemen publik sampai diterbitkan lagi.', 'attr') ?>"
                    data-konfirmasi-judul="Tarik kembali hasil?" data-konfirmasi-tombol="Tarik kembali">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-eye-slash" aria-hidden="true"></i> Tarik kembali</button>
                </form>
            <?php else : ?>
                <form action="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/terbitkan') ?>" method="post"
                    data-konfirmasi="<?= esc('Hasil semua babak ' . $nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' akan tampil di halaman publik dan juaranya dihitung di klasemen publik.' . ($adaFinal ? '' : ' Nomor ini belum punya babak Final.'), 'attr') ?>"
                    data-konfirmasi-judul="Terbitkan hasil?" data-konfirmasi-tombol="Terbitkan" data-konfirmasi-jenis="utama">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-megaphone" aria-hidden="true"></i> Terbitkan</button>
                </form>
            <?php endif ?>
        </div>
    </section>

    <?php if ($babak === []) : ?>
        <p class="text-body-secondary" id="babak-kosong">Belum ada babak. Tambahkan di bawah; nomor yang langsung final cukup satu babak Final.</p>
    <?php endif ?>

    <?php foreach ($babak as $b) : ?>
        <section class="card mb-4" aria-labelledby="judul-babak-<?= $b['id'] ?>">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <h2 class="h5 mb-0 me-auto" id="judul-babak-<?= $b['id'] ?>">
                        <?= esc($b['nama']) ?>
                        <?php if ($b['final']) : ?><span class="badge rounded-pill text-bg-primary align-middle">Final</span><?php endif ?>
                    </h2>
                    <a class="btn btn-sm btn-primary" href="<?= site_url('admin/hasil/babak/' . $b['id']) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Isi hasil</a>
                </div>
                <?= view('hasil/_tabel', ['nomor' => $nomor, 'babak' => $b, 'admin' => true], ['saveData' => false]) ?>
            </div>
        </section>
    <?php endforeach ?>

    <section class="card mb-4" id="tambah-babak" aria-labelledby="judul-tambah-babak">
        <div class="card-body">
            <h2 class="h5" id="judul-tambah-babak">Tambah babak</h2>
            <form action="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/babak') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="nama" class="form-label">Nama babak</label>
                        <input type="text" class="form-control<?= $salah('nama') ?>" id="nama" name="nama" maxlength="<?= HasilLomba::NAMA_BABAK_MAKSIMAL ?>"
                            value="<?= esc((string) ($isian['nama'] ?? ($adaFinal ? '' : ($babak === [] ? 'Final' : ''))), 'attr') ?>" aria-describedby="nama-bantuan">
                        <div id="nama-bantuan" class="form-text">Contoh: Penyisihan seri 1, Semifinal, Final.</div>
                        <?= $pesan('nama') ?>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-md-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input<?= $salah('final') ?>" type="checkbox" role="switch" id="final" name="final" value="1"<?= ($isian['final'] ?? (! $adaFinal && $babak === [])) ? ' checked' : '' ?><?= $adaFinal ? ' disabled' : '' ?>>
                            <label class="form-check-label" for="final">Babak Final: juara I–III diambil dari sini</label>
                            <?php if ($adaFinal) : ?><div class="form-text">Nomor ini sudah punya Final.</div><?php endif ?>
                            <?= $pesan('final') ?>
                        </div>
                    </div>
                    <fieldset class="col-12">
                        <legend class="form-label fs-6">Peserta</legend>
                        <?php foreach (HasilLomba::ISI_BABAK as $kode => $label) : ?>
                            <?php if ($kode === 'lolos' && $babak === []) {
                                continue;
                            } ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="isi" id="isi-<?= $kode ?>" value="<?= $kode ?>"<?= $isi === $kode ? ' checked' : '' ?>>
                                <label class="form-check-label" for="isi-<?= $kode ?>"><?= esc($label) ?><?= $kode === 'semua' ? ' (' . $calon . ($beregu ? ' regu' : ' atlet') . ')' : '' ?></label>
                            </div>
                        <?php endforeach ?>
                        <?= $pesan('isi') ?>
                    </fieldset>
                    <?php if ($babak !== []) : ?>
                        <div class="col-12 col-sm-6">
                            <label for="dari_babak" class="form-label">Lolos dari babak</label>
                            <select class="form-select<?= $salah('dari_babak') ?>" id="dari_babak" name="dari_babak">
                                <?php foreach ($babak as $b) : ?>
                                    <option value="<?= $b['id'] ?>"<?= (string) ($isian['dari_babak'] ?? '') === (string) $b['id'] ? ' selected' : '' ?>><?= esc($b['nama']) ?></option>
                                <?php endforeach ?>
                            </select>
                            <?= $pesan('dari_babak') ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="jumlah_lolos" class="form-label">Peringkat teratas yang lolos</label>
                            <input type="text" inputmode="numeric" class="form-control<?= $salah('jumlah_lolos') ?>" id="jumlah_lolos" name="jumlah_lolos"
                                value="<?= esc((string) ($isian['jumlah_lolos'] ?? ''), 'attr') ?>" aria-describedby="jumlah_lolos-bantuan">
                            <div id="jumlah_lolos-bantuan" class="form-text">Misalnya 8: peringkat 1 sampai 8 ikut babak baru. Peserta lain bisa ditambah sesudahnya.</div>
                            <?= $pesan('jumlah_lolos') ?>
                        </div>
                    <?php endif ?>
                </div>
                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah babak</button>
            </form>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
