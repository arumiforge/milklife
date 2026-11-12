<?php
/**
 * Rekap panitia (F6-06): tab jenis rekap, saringan status (K60), tabel, dan
 * tombol unduh Excel dan PDF yang membawa saringan yang sama. Data dari
 * `RekapController::index()`; angkanya dari `Rekap::susun()`. Rekap keuangan
 * ditambah tabel tim tanpa pembayar biaya tim (K68).
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
$tabel = static function (array $bagian, string $id, string $keterangan) use ($sel): string {
    $html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0" id="' . esc($id, 'attr') . '">'
        . '<caption class="visually-hidden">' . esc($keterangan) . '</caption><thead><tr><th scope="col" class="text-end">No</th>';

    foreach ($bagian['kolom'] as $kunci => $kolom) {
        $html .= '<th scope="col" data-kolom="' . esc($kunci, 'attr') . '"' . ($kolom['jenis'] === 'teks' ? '' : ' class="text-end"') . '>' . esc($kolom['judul']) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($bagian['baris'] as $no => $baris) {
        $html .= '<tr><td class="text-end text-body-secondary">' . ($no + 1) . '</td>';

        foreach ($bagian['kolom'] as $kunci => $kolom) {
            $html .= $sel($kolom, $baris[$kunci] ?? null);
        }

        $html .= '</tr>';
    }

    $html .= '</tbody>';

    if (($bagian['jumlah'] ?? null) !== null) {
        $html .= '<tfoot class="fw-semibold"><tr><td></td>';

        foreach ($bagian['kolom'] as $kunci => $kolom) {
            $html .= $sel($kolom, $bagian['jumlah'][$kunci] ?? null);
        }

        $html .= '</tr></tfoot>';
    }

    return $html . '</table></div>';
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
                <?= $tabel($rekap, 'tabel-rekap', 'Rekap ' . Rekap::JENIS[$jenis]) ?>
            <?php endif ?>
        </div>
    </div>

    <?php if (isset($rekap['tim_tanpa_pembayar'])) : ?>
        <section class="card mt-4" aria-labelledby="judul-tim-tanpa-pembayar">
            <div class="card-body">
                <h2 id="judul-tim-tanpa-pembayar" class="h5 mb-1">Tim tanpa pembayar biaya tim</h2>
                <?php if ($rekap['tim_tanpa_pembayar']['baris'] === []) : ?>
                    <p class="text-body-secondary mb-0" id="tim-tanpa-pembayar-kosong">Semua tim beregu sudah punya pembayar biaya tim.</p>
                <?php else : ?>
                    <p class="small text-body-secondary mb-3">
                        Pendaftaran yang membayar biaya tim ini sudah dihapus atau diubah, dan biayanya tidak dipindah otomatis.
                        Tagihkan lewat Ubah di salah satu pendaftaran anggotanya dengan mencentang "Bebankan biaya tim ke pendaftaran ini".
                    </p>
                    <?= $tabel($rekap['tim_tanpa_pembayar'], 'tabel-tim-tanpa-pembayar', 'Tim tanpa pembayar biaya tim') ?>
                <?php endif ?>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
