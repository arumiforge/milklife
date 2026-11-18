<?php
/**
 * Isian hasil satu babak (F8-04): prestasi, keterangan DNS/DNF/DQ, koreksi
 * peringkat (hanya Final), keluarkan peserta, tambah peserta, dan ubah atau
 * hapus babak. Data dari `HasilController::babak()`.
 *
 * Semua baris dikirim dalam satu formulir; bila ada yang salah, tidak ada
 * yang tersimpan dan isian ditampilkan ulang dengan pesan per kotak.
 *
 * @var CodeIgniter\View\View                   $this
 * @var array<string, mixed>                    $nomor
 * @var array<string, mixed>                    $babak
 * @var list<array<string, mixed>>              $semuaBabak
 * @var list<array<string, mixed>>              $baris        `HasilLomba::barisBabak()`
 * @var list<array<string, mixed>>              $bisaDitambah `HasilLomba::pesertaBisaDitambah()`
 * @var array<int|string, array<string, mixed>> $isian        kiriman yang ditampilkan ulang
 * @var array<int, array<string, string>>       $galat
 */

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\Prestasi;

$id     = (int) $babak['id'];
$final  = (int) $babak['final'] === 1;
$beregu = $nomor['jenis'] === 'beregu';
$jenis  = (string) $nomor['jenis_hasil'];
$judul  = $nomor['nama_lengkap'] . ' – ' . $nomor['nama_kelompok_umur'];
$nilai  = static function (array $h, string $kotak) use ($isian): string {
    if (isset($isian[$h['id']][$kotak]) && is_scalar($isian[$h['id']][$kotak])) {
        return (string) $isian[$h['id']][$kotak];
    }

    return match ($kotak) {
        'prestasi'   => (string) $h['prestasi_teks'],
        'keterangan' => (string) ($h['keterangan'] ?? ''),
        'peringkat'  => $h['peringkat_manual'] ? (string) $h['peringkat'] : '',
        default      => '',
    };
};
$finalLain = in_array(1, array_map(static fn (array $b): int => (int) $b['id'] === (int) $babak['id'] ? 0 : (int) $b['final'], $semuaBabak), true);
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($babak['nama'] . ' – ' . $judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Hasil Lomba<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [
    ['label' => 'Hasil lomba', 'alamat' => 'admin/hasil?kelompok-umur=' . (int) $nomor['kelompok_umur_id']],
    ['label' => $judul, 'alamat' => 'admin/hasil/' . (int) $nomor['id']],
    ['label' => $babak['nama']],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h4 mb-1"><?= esc($babak['nama']) ?><?php if ($final) : ?> <span class="badge rounded-pill text-bg-primary align-middle fs-6">Final</span><?php endif ?></h2>
            <p class="text-body-secondary mb-2"><?= esc($judul) ?> · hasil <?= esc(mb_strtolower(JenisHasil::LABEL[$jenis] ?? '')) ?></p>
            <p class="mb-0 small" id="bantuan-prestasi">
                Tulis <?= esc(Prestasi::CONTOH[$jenis] ?? '') ?>.
                <?php if ($jenis === JenisHasil::POIN) : ?>Isi poin akhir Tri Lomba yang sudah dikonversi panitia.<?php endif ?>
                Peringkat diurutkan otomatis setelah disimpan; prestasi sama mendapat peringkat sama.
                <?php if ($final) : ?>Juara I–III diambil dari babak ini. Kolom <strong>Ubah peringkat</strong> hanya diisi bila peringkat perlu dikoreksi (misalnya dari foto finis); kosongkan supaya otomatis.<?php endif ?>
            </p>
        </div>
    </div>

    <div class="card mb-3" id="isian-hasil">
        <div class="card-header bg-transparent"><h3 class="h6 fw-bold mb-0">Hasil <?= count($baris) ?> peserta</h3></div>
        <?php if ($baris === []) : ?>
            <div class="card-body text-body-secondary">Belum ada peserta di babak ini. Pilih pesertanya di bagian <a href="#tambah-peserta">Tambah peserta</a> di bawah.</div>
        <?php else : ?>
            <form method="post" action="<?= site_url('admin/hasil/babak/' . $id) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="tabel-isian-hasil">
                        <caption class="visually-hidden">Isian hasil <?= esc($babak['nama']) ?></caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">Per&shy;ingkat</th>
                                <th scope="col"><?= $beregu ? 'Regu' : 'Peserta' ?></th>
                                <th scope="col"><?= esc(Prestasi::JUDUL[$jenis] ?? 'Prestasi') ?></th>
                                <th scope="col">Keterangan</th>
                                <?php if ($final) : ?><th scope="col">Ubah peringkat</th><?php endif ?>
                                <th scope="col" class="text-center">Keluarkan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($baris as $h) : ?>
                                <?php
                                $hid  = (int) $h['id'];
                                $nama = 'hasil[' . $hid . ']';
                                $g    = $galat[$hid] ?? [];
                                ?>
                                <tr id="hasil-<?= $hid ?>"<?= $g !== [] ? ' class="table-danger"' : '' ?>>
                                    <td class="text-center text-nowrap">
                                        <?= $h['peringkat'] ?? '<span class="text-body-secondary">–</span>' ?>
                                        <?php if ($h['peringkat_manual']) : ?><span class="d-block small text-body-secondary">diubah admin</span><?php endif ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?= esc($h['nama']) ?></span>
                                        <span class="d-block small text-body-secondary"><?= esc($beregu ? implode(', ', array_column($h['anggota'], 'nama')) : trim($h['nomor_dada'] . ' · ' . $h['sekolah'], ' ·')) ?></span>
                                    </td>
                                    <td class="kolom-prestasi">
                                        <input type="text" class="form-control form-control-sm<?= isset($g['prestasi']) ? ' is-invalid' : '' ?>" inputmode="<?= $jenis === JenisHasil::POIN ? 'numeric' : 'decimal' ?>"
                                            name="<?= $nama ?>[prestasi]" id="prestasi-<?= $hid ?>" value="<?= esc($nilai($h, 'prestasi'), 'attr') ?>" size="8" autocomplete="off"
                                            aria-label="<?= esc((Prestasi::JUDUL[$jenis] ?? 'Prestasi') . ' ' . $h['nama'], 'attr') ?>"<?= isset($g['prestasi']) ? ' aria-describedby="prestasi-' . $hid . '-galat"' : '' ?>>
                                        <?php if (isset($g['prestasi'])) : ?><div class="invalid-feedback d-block" id="prestasi-<?= $hid ?>-galat"><?= esc($g['prestasi']) ?></div><?php endif ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm<?= isset($g['keterangan']) ? ' is-invalid' : '' ?>" name="<?= $nama ?>[keterangan]" aria-label="<?= esc('Keterangan ' . $h['nama'], 'attr') ?>">
                                            <option value="">–</option>
                                            <?php foreach (Peringkat::KETERANGAN as $kode => $arti) : ?>
                                                <option value="<?= $kode ?>"<?= $nilai($h, 'keterangan') === $kode ? ' selected' : '' ?>><?= $kode ?> (<?= $arti ?>)</option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php if (isset($g['keterangan'])) : ?><div class="invalid-feedback d-block"><?= esc($g['keterangan']) ?></div><?php endif ?>
                                    </td>
                                    <?php if ($final) : ?>
                                        <td>
                                            <input type="text" class="form-control form-control-sm<?= isset($g['peringkat']) ? ' is-invalid' : '' ?>" inputmode="numeric" size="3" autocomplete="off"
                                                name="<?= $nama ?>[peringkat]" id="peringkat-<?= $hid ?>" value="<?= esc($nilai($h, 'peringkat'), 'attr') ?>" placeholder="otomatis"
                                                aria-label="<?= esc('Ubah peringkat ' . $h['nama'], 'attr') ?>"<?= isset($g['peringkat']) ? ' aria-describedby="peringkat-' . $hid . '-galat"' : '' ?>>
                                            <?php if (isset($g['peringkat'])) : ?><div class="invalid-feedback d-block" id="peringkat-<?= $hid ?>-galat"><?= esc($g['peringkat']) ?></div><?php endif ?>
                                        </td>
                                    <?php endif ?>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="<?= $nama ?>[keluarkan]" value="1"<?= $nilai($h, 'keluarkan') === '1' ? ' checked' : '' ?> aria-label="<?= esc('Keluarkan ' . $h['nama'] . ' dari babak ini', 'attr') ?>">
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan hasil</button>
                    <a class="btn btn-outline-primary" href="<?= site_url('admin/hasil/' . (int) $nomor['id']) ?>">Kembali ke <?= esc($nomor['nama_lengkap']) ?></a>
                </div>
            </form>
        <?php endif ?>
    </div>

    <div class="card mb-3" id="tambah-peserta">
        <div class="card-body">
            <h3 class="h6 fw-bold">Tambah peserta</h3>
            <?php if ($bisaDitambah === []) : ?>
                <p class="text-body-secondary mb-0">Semua peserta Lunas nomor ini sudah ada di babak ini. Peserta dari pendaftaran yang belum Lunas tidak bisa ditambahkan; tandai Lunas dulu di menu Pendaftaran.</p>
            <?php else : ?>
                <p class="text-body-secondary small">Hanya peserta dari pendaftaran Lunas. Hasil di babak lain ditampilkan untuk membantu memilih yang lolos.</p>
                <form method="post" action="<?= site_url('admin/hasil/babak/' . $id . '/peserta') ?>">
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-2" id="tabel-tambah-peserta">
                            <caption class="visually-hidden">Peserta yang bisa ditambahkan</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center"><input class="form-check-input" type="checkbox" data-centang-semua="#tabel-tambah-peserta" aria-label="Centang semua peserta"></th>
                                    <th scope="col"><?= $beregu ? 'Regu' : 'Peserta' ?></th>
                                    <th scope="col" class="d-none d-md-table-cell">Hasil di babak lain</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bisaDitambah as $i => $p) : ?>
                                    <tr>
                                        <td class="text-center"><input class="form-check-input" type="checkbox" name="peserta[]" value="<?= esc($p['kunci'], 'attr') ?>" id="peserta-<?= $i ?>"></td>
                                        <td>
                                            <label for="peserta-<?= $i ?>" class="fw-semibold"><?= esc($p['nama']) ?></label>
                                            <span class="d-block small text-body-secondary"><?= esc($beregu ? implode(', ', array_column($p['anggota'], 'nama')) : trim($p['nomor_dada'] . ' · ' . $p['sekolah'], ' ·')) ?></span>
                                            <?php if ($p['catatan'] !== '') : ?><span class="d-block d-md-none small"><?= esc($p['catatan']) ?></span><?php endif ?>
                                        </td>
                                        <td class="d-none d-md-table-cell small"><?= $p['catatan'] !== '' ? esc($p['catatan']) : '<span class="text-body-secondary">–</span>' ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> Tambahkan yang dicentang</button>
                </form>
            <?php endif ?>
        </div>
    </div>

    <div class="card mb-3" id="ubah-babak">
        <div class="card-body">
            <h3 class="h6 fw-bold">Ubah babak</h3>
            <form method="post" action="<?= site_url('admin/hasil/babak/' . $id . '/ubah') ?>" class="row g-2 align-items-end" novalidate>
                <?= csrf_field() ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="nama-babak" class="form-label">Nama babak</label>
                    <input type="text" class="form-control" id="nama-babak" name="nama" maxlength="60" value="<?= esc($babak['nama'], 'attr') ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="final-babak" name="final" value="1"<?= $final ? ' checked' : '' ?><?= $finalLain ? ' disabled' : '' ?>>
                        <label class="form-check-label" for="final-babak">Babak Final</label>
                    </div>
                    <?php if ($finalLain) : ?><div class="form-text">Nomor ini sudah punya babak Final lain.</div><?php endif ?>
                </div>
                <div class="col-12 col-lg-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-outline-primary">Simpan babak</button>
                </div>
            </form>
            <form method="post" action="<?= site_url('admin/hasil/babak/' . $id . '/hapus') ?>" class="mt-3"
                data-konfirmasi="Babak <?= esc($babak['nama'], 'attr') ?> beserta <?= count($baris) ?> hasil pesertanya akan dihapus dan tidak bisa dikembalikan."
                data-konfirmasi-tombol="Hapus babak">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash" aria-hidden="true"></i> Hapus babak ini</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/centang-semua.js') ?>"></script>
<?= $this->endSection() ?>
