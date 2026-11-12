<?php
/**
 * Rekap panitia (F6-06): tab jenis rekap, saringan status (K60), tabel, dan
 * tombol unduh Excel dan PDF yang membawa saringan yang sama. Data dari
 * `RekapController::index()`; angkanya dari `Rekap::susun()`.
 *
 * @var CodeIgniter\View\View                                                                                                                         $this
 * @var string                                                                                                                                        $jenis
 * @var string|null                                                                                                                                   $status
 * @var array{kolom: array<string, array{judul: string, jenis: string}>, baris: list<array<string, mixed>>, jumlah: array<string, mixed>|null} $rekap
 */

use App\Libraries\Panel\Rekap;

$alamat   = 'admin/rekap/' . $jenis;
$kueri    = $status === null ? '' : '?status=' . $status;
$keuangan = $jenis === 'keuangan';
$sel      = static function (array $kolom, mixed $nilai): string {
    if ($nilai === null || $nilai === '') {
        return '<td></td>';
    }

    return match ($kolom['jenis']) {
        'rupiah' => '<td class="text-end text-nowrap">' . esc(rupiah((int) $nilai)) . '</td>',
        'angka'  => '<td class="text-end">' . (int) $nilai . '</td>',
        default  => '<td>' . esc((string) $nilai) . '</td>',
    };
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Rekap <?= esc(Rekap::JENIS[$jenis]) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Rekap<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <ul class="nav nav-tabs mb-3" id="jenis-rekap">
        <?php foreach (Rekap::JENIS as $kode => $judul) : ?>
            <li class="nav-item">
                <a class="nav-link<?= $kode === $jenis ? ' active' : '' ?>" href="<?= site_url('admin/rekap/' . $kode) . ($kode === 'keuangan' ? '' : $kueri) ?>"<?= $kode === $jenis ? ' aria-current="page"' : '' ?>>
                    <?= esc($judul) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <?php if ($keuangan) : ?>
                    <p class="text-body-secondary mb-0" id="keterangan-status">Rekap keuangan selalu memuat pendaftaran Lunas dan Menunggu.</p>
                <?php else : ?>
                    <nav class="btn-group" id="saringan-status" aria-label="Saring menurut status pembayaran">
                        <?php foreach (['' => 'Semua'] + Rekap::STATUS as $kode => $label) : ?>
                            <?php $aktif = (string) $status === $kode ?>
                            <a class="btn btn-sm <?= $aktif ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= site_url($alamat) . ($kode === '' ? '' : '?status=' . $kode) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>><?= $label ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif ?>
                <div class="d-flex gap-2">
                    <a id="excel-rekap" class="btn btn-sm btn-outline-primary" href="<?= site_url($alamat . '/excel') . ($keuangan ? '' : $kueri) ?>">
                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Unduh Excel
                    </a>
                    <a id="pdf-rekap" class="btn btn-sm btn-outline-primary" href="<?= site_url($alamat . '/pdf') . ($keuangan ? '' : $kueri) ?>">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Unduh PDF
                    </a>
                </div>
            </div>

            <?php if ($rekap['baris'] === []) : ?>
                <p class="text-body-secondary text-center py-4 mb-0" id="rekap-kosong">
                    <?php if ($status !== null && ! $keuangan) : ?>
                        Belum ada data berstatus <?= esc(Rekap::STATUS[$status]) ?>. Pilih Semua untuk melihat seluruh pendaftaran.
                    <?php else : ?>
                        Belum ada pendaftaran. Rekap terisi setelah sekolah mendaftar lewat halaman Pendaftaran.
                    <?php endif ?>
                </p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="tabel-rekap">
                        <caption class="visually-hidden">Rekap <?= esc(Rekap::JENIS[$jenis]) ?></caption>
                        <thead>
                            <tr>
                                <?php foreach ($rekap['kolom'] as $kunci => $kolom) : ?>
                                    <th scope="col" data-kolom="<?= esc($kunci, 'attr') ?>"<?= $kolom['jenis'] === 'teks' ? '' : ' class="text-end"' ?>><?= esc($kolom['judul']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rekap['baris'] as $baris) : ?>
                                <tr>
                                    <?php foreach ($rekap['kolom'] as $kunci => $kolom) : ?>
                                        <?= $sel($kolom, $baris[$kunci] ?? null) ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if ($rekap['jumlah'] !== null) : ?>
                            <tfoot class="fw-semibold">
                                <tr>
                                    <?php foreach ($rekap['kolom'] as $kunci => $kolom) : ?>
                                        <?= $sel($kolom, $rekap['jumlah'][$kunci] ?? null) ?>
                                    <?php endforeach; ?>
                                </tr>
                            </tfoot>
                        <?php endif ?>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
