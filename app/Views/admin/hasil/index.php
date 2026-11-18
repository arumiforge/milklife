<?php
/**
 * Daftar hasil lomba (F8-04): semua nomor per kelompok umur dengan keadaan
 * hasilnya (babak, Final terisi, terbit). Data dari `HasilController::index()`.
 *
 * @var CodeIgniter\View\View                                                        $this
 * @var list<array{id: int, nama: string, nomor: list<array<string, mixed>>}>       $kelompok
 * @var array<int, string>                                                           $kelompokUmur id => nama
 * @var int                                                                          $saring
 */

use App\Libraries\Hasil\JenisHasil;

$keadaan = static function (array $n): array {
    if ($n['hasil_terbit_pada'] !== null) {
        return ['Terbit', 'text-bg-success', 'Terbit sejak ' . tanggal_id((string) $n['hasil_terbit_pada'], true)];
    }

    if ((int) $n['jumlah_babak'] === 0) {
        return ['Belum ada babak', 'text-bg-light border', ''];
    }

    if ((int) $n['ada_final'] === 0) {
        return ['Final belum dibuat', 'text-bg-warning', ''];
    }

    $isi   = (int) $n['terisi_final'];
    $semua = (int) $n['peserta_final'];

    return $semua > 0 && $isi === $semua
        ? ['Final terisi', 'text-bg-primary', $isi . ' peserta']
        : ['Final diisi', 'text-bg-warning', $isi . ' dari ' . $semua . ' peserta'];
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Hasil Lomba<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Hasil Lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <p class="mb-1">Pilih nomor lomba, buat babaknya (Penyisihan, …, Final), lalu isi prestasinya. Peringkat diurutkan otomatis; juara I–III dari babak Final.</p>
                <p class="text-body-secondary small mb-0">Hasil baru tampil di halaman depan setelah ditekan <strong>Terbitkan</strong> di halaman nomor itu.</p>
            </div>
            <form method="get" action="<?= site_url('admin/hasil') ?>" class="d-flex gap-2 align-items-end" id="saring-hasil">
                <div>
                    <label for="kelompok-umur" class="form-label small mb-1">Kelompok umur</label>
                    <select class="form-select" id="kelompok-umur" name="kelompok-umur">
                        <option value="">Semua</option>
                        <?php foreach ($kelompokUmur as $id => $nama) : ?>
                            <option value="<?= (int) $id ?>"<?= $saring === (int) $id ? ' selected' : '' ?>><?= esc($nama) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
            </form>
        </div>
    </div>

    <?php if ($kelompok === []) : ?>
        <div class="alert alert-info">Belum ada nomor lomba. Tambahkan dulu di Pengaturan &gt; Nomor lomba.</div>
    <?php endif ?>

    <?php foreach ($kelompok as $ku) : ?>
        <div class="card mb-3" id="hasil-ku-<?= (int) $ku['id'] ?>">
            <div class="card-header bg-transparent"><h2 class="h6 fw-bold mb-0"><?= esc($ku['nama']) ?></h2></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <caption class="visually-hidden">Hasil lomba <?= esc($ku['nama']) ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Nomor</th>
                            <th scope="col" class="d-none d-md-table-cell">Jenis hasil</th>
                            <th scope="col" class="d-none d-md-table-cell text-end">Babak</th>
                            <th scope="col">Keadaan</th>
                            <th scope="col" class="text-end"><span class="visually-hidden">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ku['nomor'] as $n) : ?>
                            <?php [$label, $kelas, $rinci] = $keadaan($n); ?>
                            <tr id="hasil-nomor-<?= (int) $n['id'] ?>">
                                <td>
                                    <a class="fw-semibold" href="<?= site_url('admin/hasil/' . (int) $n['id']) ?>"><?= esc($n['nama_lengkap']) ?></a>
                                    <span class="d-block d-md-none small text-body-secondary"><?= esc(JenisHasil::LABEL[$n['jenis_hasil']] ?? '') ?> · <?= (int) $n['jumlah_babak'] ?> babak</span>
                                </td>
                                <td class="d-none d-md-table-cell"><?= esc(JenisHasil::LABEL[$n['jenis_hasil']] ?? '') ?></td>
                                <td class="d-none d-md-table-cell text-end"><?= (int) $n['jumlah_babak'] ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $kelas ?>"><?= esc($label) ?></span>
                                    <?php if ($rinci !== '') : ?><span class="d-block small text-body-secondary"><?= esc($rinci) ?></span><?php endif ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= site_url('admin/hasil/' . (int) $n['id']) ?>" aria-label="<?= esc('Kelola hasil ' . $n['nama_lengkap'] . ' ' . $ku['nama'], 'attr') ?>">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i><span class="d-none d-lg-inline"> Kelola</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
