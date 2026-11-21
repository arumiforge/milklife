<?php
/**
 * Rekap panitia (F6-06): tab jenis rekap, saringan status (K60), tabel, dan
 * tombol unduh Excel dan PDF yang membawa saringan yang sama. Data dari
 * `RekapController::index()`; angkanya dari `Rekap::susun()`. Rekap keuangan
 * ditambah tabel tim tanpa pembayar biaya tim (K68). Tata letak `admin_tw`;
 * tabelnya disusun di server, tanpa skrip halaman.
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
        return '<td class="px-4 py-3"></td>';
    }

    return match ($kolom['jenis']) {
        'rupiah' => '<td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">' . esc(rupiah((int) $nilai)) . '</td>',
        'angka'  => '<td class="px-4 py-3 text-right tabular-nums">' . (int) $nilai . '</td>',
        default  => '<td class="px-4 py-3">' . esc((string) $nilai) . '</td>',
    };
};
$tabel = static function (array $bagian, string $id, string $keterangan) use ($sel): string {
    $html = '<div class="relative overflow-x-auto rounded-2xl border border-gray-200 bg-white"><table class="w-full text-left text-sm text-gray-700" id="' . esc($id, 'attr') . '">'
        . '<caption class="sr-only">' . esc($keterangan) . '</caption>'
        . '<thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700"><tr><th scope="col" class="px-4 py-3 text-right">No</th>';

    foreach ($bagian['kolom'] as $kunci => $kolom) {
        $html .= '<th scope="col" data-kolom="' . esc($kunci, 'attr') . '" class="whitespace-nowrap px-4 py-3' . ($kolom['jenis'] === 'teks' ? '' : ' text-right') . '">' . esc($kolom['judul']) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($bagian['baris'] as $no => $baris) {
        $html .= '<tr class="border-t border-gray-100 hover:bg-gray-50"><td class="px-4 py-3 text-right text-gray-500">' . ($no + 1) . '</td>';

        foreach ($bagian['kolom'] as $kunci => $kolom) {
            $html .= $sel($kolom, $baris[$kunci] ?? null);
        }

        $html .= '</tr>';
    }

    $html .= '</tbody>';

    if (($bagian['jumlah'] ?? null) !== null) {
        $html .= '<tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold text-gray-900"><tr><td class="px-4 py-3"></td>';

        foreach ($bagian['kolom'] as $kunci => $kolom) {
            $html .= $sel($kolom, $bagian['jumlah'][$kunci] ?? null);
        }

        $html .= '</tr></tfoot>';
    }

    return $html . '</table></div>';
};
$kartu = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Rekap <?= esc(Rekap::JENIS[$jenis]) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Rekap<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <section class="<?= $kartu ?> flex flex-wrap items-center justify-between gap-4" id="rekap-gabungan" aria-labelledby="judul-rekap-gabungan">
        <div class="min-w-0 flex-1 basis-64">
            <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900" id="judul-rekap-gabungan"><?= ikon('layers', 'size-5 text-primary-700') ?> Rekap gabungan</h2>
            <p class="mt-1 text-sm text-gray-600">Semua data dalam satu file Excel, dipisah per sheet: Pendaftaran, Atlet, dan semua rekap di bawah (termasuk Tim Tanpa Pembayar).</p>
        </div>
        <?= komponen('tombol', ['label' => 'Unduh Rekap Gabungan', 'href' => site_url('admin/rekap/gabungan/excel'), 'ikon' => 'file-spreadsheet', 'atribut' => ['id' => 'excel-gabungan']]) ?>
    </section>

    <nav aria-label="Jenis rekap" class="min-w-0">
        <ul class="-mx-4 flex gap-1 overflow-x-auto border-b border-gray-200 px-4 sm:mx-0 sm:px-0" id="jenis-rekap">
            <?php foreach (Rekap::JENIS as $kode => $judul) : ?>
                <?php $kini = $kode === $jenis; ?>
                <li class="shrink-0">
                    <a class="-mb-px inline-flex min-h-11 items-center whitespace-nowrap border-b-2 px-3 text-sm font-semibold <?= $kini ? 'border-primary-700 text-primary-700' : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-900' ?>" href="<?= site_url('admin/rekap/' . $kode) . ($kode === 'keuangan' ? '' : $kueri) ?>"<?= $kini ? ' aria-current="page"' : '' ?>>
                        <?= esc($judul) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <section class="<?= $kartu ?> space-y-4" aria-label="<?= esc('Rekap ' . Rekap::JENIS[$jenis], 'attr') ?>">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <?php if ($keuangan) : ?>
                <p class="text-sm text-gray-600" id="keterangan-status">Rekap keuangan selalu memuat pendaftaran Lunas dan Menunggu.</p>
            <?php else : ?>
                <nav class="inline-flex flex-wrap rounded-lg border border-gray-300 bg-white p-0.5" id="saringan-status" aria-label="Saring menurut status pembayaran">
                    <?php foreach (['' => 'Semua'] + Rekap::STATUS as $kode => $label) : ?>
                        <?php $aktif = (string) $status === $kode ?>
                        <a class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-semibold <?= $aktif ? 'bg-primary-700 text-white' : 'text-gray-700 hover:bg-gray-100 hover:text-primary-700' ?>" href="<?= site_url($alamat) . ($kode === '' ? '' : '?status=' . $kode) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>><?= $label ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif ?>
            <div class="flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url($alamat . '/excel') . ($keuangan ? '' : $kueri), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-spreadsheet', 'kelas' => 'min-h-11', 'atribut' => ['id' => 'excel-rekap']]) ?>
                <?= komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url($alamat . '/pdf') . ($keuangan ? '' : $kueri), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-text', 'kelas' => 'min-h-11', 'atribut' => ['id' => 'pdf-rekap']]) ?>
            </div>
        </div>

        <?php if ($rekap['baris'] === []) : ?>
            <p class="py-6 text-center text-gray-600" id="rekap-kosong">
                <?php if ($status !== null && ! $keuangan) : ?>
                    Belum ada data berstatus <?= esc(Rekap::STATUS[$status]) ?>. Pilih Semua untuk melihat seluruh pendaftaran.
                <?php else : ?>
                    Belum ada pendaftaran. Rekap terisi setelah sekolah mendaftar lewat halaman Pendaftaran.
                <?php endif ?>
            </p>
        <?php else : ?>
            <?= $tabel($rekap, 'tabel-rekap', 'Rekap ' . Rekap::JENIS[$jenis]) ?>
        <?php endif ?>
    </section>

    <?php if (isset($rekap['tim_tanpa_pembayar'])) : ?>
        <section class="<?= $kartu ?>" aria-labelledby="judul-tim-tanpa-pembayar">
            <h2 id="judul-tim-tanpa-pembayar" class="text-lg font-bold text-gray-900">Regu tanpa pembayar biaya regu</h2>
            <?php if ($rekap['tim_tanpa_pembayar']['baris'] === []) : ?>
                <p class="mt-1 text-gray-600" id="tim-tanpa-pembayar-kosong">Semua regu beregu sudah punya pembayar biaya regu.</p>
            <?php else : ?>
                <p class="mb-4 mt-1 text-sm text-gray-600">
                    Pendaftaran yang membayar biaya regu ini sudah dihapus atau diubah, dan biayanya tidak dipindah otomatis.
                    Tagihkan lewat Ubah di salah satu pendaftaran anggotanya dengan mencentang "Bebankan biaya regu ke pendaftaran ini".
                </p>
                <?= $tabel($rekap['tim_tanpa_pembayar'], 'tabel-tim-tanpa-pembayar', 'Regu tanpa pembayar biaya regu') ?>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
