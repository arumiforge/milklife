<?php
/**
 * Isi hasil satu babak (F8-04, K124): prestasi, keterangan DNS/DNF/DQ, dan
 * (khusus Final) koreksi peringkat; atur peserta; ubah nama atau tanda Final;
 * hapus babak. Data dari `HasilController::babak()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>       $babak      `HasilLomba::babak()`
 * @var list<array<string, mixed>> $hasil      `HasilLomba::hasilBabak()`
 * @var array<string, array<string, mixed>> $calon `HasilLomba::calon()`
 * @var array<int|string, string>  $galat      id hasil => pesan
 * @var array<int|string, mixed>   $isian      isian yang dikembalikan saat ada galat
 * @var array<string, string>      $galatBabak galat ubah babak
 * @var array{tanggal: string|null, jam: string|null, lokasi: string}|null $jadwal `JadwalPertandingan::babak()` (F9-07)
 * @var array<string, string>      $galatJadwal galat jadwal babak
 * @var array<string, mixed>       $isianJadwal isian jadwal yang dikembalikan saat ada galat
 */

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Peringkat;

$nomor  = $babak['nomor'];
$beregu = $nomor['jenis'] === 'beregu';
$final  = $babak['final'];
$contoh = ['waktu' => '12,34', 'jarak' => '3,45', 'poin' => '1250'][$nomor['jenis_hasil']] ?? '';
$nilai  = static fn (array $h, string $kotak, string $asal): string => (string) ($isian[$h['id']][$kotak] ?? $asal);

// Poin multi-lomba (F9-10): satu kotak prestasi per komponen, poin dihitung saat disimpan.
$komponen    = $nomor['jenis_hasil'] === JenisHasil::MULTI ? $nomor['komponen'] : [];
$multi       = $nomor['jenis_hasil'] === JenisHasil::MULTI;
$nilaiK      = static fn (array $h, string $kode): string => (string) ($isian[$h['id']]['komponen'][$kode] ?? $h['rincian_isian'][$kode] ?? '');
$luarTabel   = 0;
foreach ($hasil as $h) {
    foreach ($h['rincian'] as $r) {
        $luarTabel += $r['luar'] !== null ? 1 : 0;
    }
}

// Peserta sekarang ditambah calon yang belum ikut.
$pilihan = [];
foreach ($hasil as $h) {
    $pilihan[$h['kunci']] = ['nama' => $h['nama'], 'sekolah' => $h['sekolah'], 'nomor_dada' => $h['nomor_dada'], 'ikut' => true];
}
foreach ($calon as $kunci => $c) {
    $pilihan[$kunci] ??= ['nama' => $c['nama'], 'sekolah' => $c['sekolah'], 'nomor_dada' => $c['nomor_dada'], 'ikut' => false];
}
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($babak['nama']) ?> <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [
    ['label' => 'Hasil lomba', 'alamat' => 'admin/hasil'],
    ['label' => $nomor['kelompok_umur'] . ' ' . $nomor['nama'], 'alamat' => 'admin/hasil/nomor/' . $nomor['id']],
    ['label' => $babak['nama']],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?php if ($multi && $komponen === []) : ?>
        <div class="alert alert-warning" role="alert" id="tabel-skor-belum">
            Nomor ini berjenis hasil poin multi-lomba tetapi belum punya tabel skor. Pilih tabel skor dan komponennya di
            <a href="<?= site_url('admin/pengaturan/nomor-lomba/' . $nomor['id'] . '/ubah') ?>">Pengaturan &gt; Nomor Lomba</a>.
        </div>
    <?php endif ?>
    <?php if ($luarTabel > 0) : ?>
        <div class="alert alert-warning" role="alert" id="peringatan-luar-tabel">
            <?= $luarTabel ?> prestasi di luar tabel skor sehingga poinnya 0 (ditandai kuning di bawah). Periksa lagi apakah ada salah ketik, misalnya 0,70 untuk 7,00.
        </div>
    <?php endif ?>
    <p class="teks-info mb-4">
        Prestasi <?= esc(JenisHasil::LABEL[$nomor['jenis_hasil']]) ?>: <?= esc(JenisHasil::KETERANGAN[$nomor['jenis_hasil']]) ?>.
        <?php if ($multi) : ?>Tulis waktu lari seperti 7,00 dan jarak dalam meter seperti 4,25. Poin tiap komponen dicari di tabel skor; prestasi di antara dua baris memakai baris yang lebih buruk. Komponen kosong bernilai 0.<?php endif ?>
        Peringkat dihitung otomatis setelah disimpan; prestasi sama mendapat peringkat sama.
        Pilih DNS (tidak start), DNF (tidak finis), atau DQ (diskualifikasi) untuk peserta tanpa peringkat.
        <?php if ($final) : ?>Di Final peringkat boleh dikoreksi (misalnya dari foto finis); kosongkan supaya dihitung otomatis.<?php endif ?>
    </p>

    <section class="card mb-4" aria-labelledby="judul-hasil">
        <div class="card-body">
            <h2 class="h5" id="judul-hasil"><?= esc($babak['nama']) ?><?php if ($final) : ?> <span class="badge rounded-pill text-bg-primary align-middle">Final</span><?php endif ?></h2>
            <?php if ($hasil === []) : ?>
                <p class="text-body-secondary mb-0" id="peserta-kosong">Babak ini belum punya peserta. Pilih peserta di bagian bawah.</p>
            <?php else : ?>
                <form action="<?= site_url('admin/hasil/babak/' . $babak['id']) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center">Per.</th>
                                    <th scope="col"><?= $beregu ? 'Regu' : 'Atlet' ?></th>
                                    <?php if ($multi) : ?>
                                        <?php foreach ($komponen as $k) : ?><th scope="col"><?= esc($k['nama']) ?></th><?php endforeach ?>
                                        <th scope="col" class="text-end">Total poin</th>
                                    <?php else : ?>
                                        <th scope="col">Prestasi</th>
                                    <?php endif ?>
                                    <th scope="col">Ket.</th>
                                    <?php if ($final) : ?><th scope="col">Koreksi per.</th><?php endif ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hasil as $h) : ?>
                                    <?php $id = 'hasil-' . $h['id']; ?>
                                    <tr>
                                        <td class="text-center fw-semibold"><?= $h['peringkat'] ?? '–' ?></td>
                                        <td>
                                            <?php if (! $beregu) : ?><span class="fw-semibold"><?= esc((string) $h['nomor_dada']) ?></span><?php endif ?>
                                            <?= esc($h['nama']) ?>
                                            <?php if (! $beregu) : ?><div class="small text-body-secondary"><?= esc($h['sekolah']) ?></div><?php endif ?>
                                            <?php if ($h['manual']) : ?><span class="badge rounded-pill text-bg-info">diubah admin</span><?php endif ?>
                                        </td>
                                        <?php if ($multi) : ?>
                                            <?php foreach ($komponen as $i => $k) : ?>
                                                <?php $r = $h['rincian'][$i] ?? null; $idK = $id . '-' . $k['kode']; ?>
                                                <td>
                                                    <label class="visually-hidden" for="<?= esc($idK, 'attr') ?>"><?= esc($k['nama']) ?> <?= esc($h['nama']) ?></label>
                                                    <input type="text" inputmode="decimal" class="form-control form-control-sm kotak-prestasi<?= isset($galat[$h['id']]) ? ' is-invalid' : '' ?><?= $r !== null && $r['luar'] !== null ? ' border-warning' : '' ?>" id="<?= esc($idK, 'attr') ?>"
                                                        name="hasil[<?= $h['id'] ?>][komponen][<?= esc($k['kode'], 'attr') ?>]" placeholder="<?= $k['jenis'] === JenisHasil::WAKTU ? '7,00' : '4,25' ?>" value="<?= esc($nilaiK($h, $k['kode']), 'attr') ?>">
                                                    <?php if ($r !== null && $r['prestasi'] === '') : ?>
                                                        <div class="small text-body-secondary" data-poin-komponen>kosong, 0 poin</div>
                                                    <?php endif ?>
                                                    <?php if ($r !== null && $r['prestasi'] !== '') : ?>
                                                        <div class="small<?= $r['luar'] !== null ? ' text-warning-emphasis fw-semibold' : ' text-body-secondary' ?>" data-poin-komponen><?= $r['poin'] ?> poin<?= $r['luar'] !== null ? ', di luar tabel' : '' ?></div>
                                                    <?php endif ?>
                                                </td>
                                            <?php endforeach ?>
                                            <td class="text-end fw-semibold" data-total-poin>
                                                <?= esc($h['prestasi_teks']) ?>
                                                <?php if (isset($galat[$h['id']])) : ?><div id="<?= $id ?>-galat" class="invalid-feedback d-block text-start fw-normal"><?= esc($galat[$h['id']]) ?></div><?php endif ?>
                                            </td>
                                        <?php else : ?>
                                        <td>
                                            <label class="visually-hidden" for="<?= $id ?>-prestasi">Prestasi <?= esc($h['nama']) ?></label>
                                            <input type="text" inputmode="decimal" class="form-control form-control-sm kotak-prestasi<?= isset($galat[$h['id']]) ? ' is-invalid' : '' ?>" id="<?= $id ?>-prestasi"
                                                name="hasil[<?= $h['id'] ?>][prestasi]" placeholder="<?= esc($contoh, 'attr') ?>" value="<?= esc($nilai($h, 'prestasi', $h['prestasi_teks']), 'attr') ?>"
                                                <?= isset($galat[$h['id']]) ? 'aria-describedby="' . $id . '-galat"' : '' ?>>
                                            <?php if (isset($galat[$h['id']])) : ?><div id="<?= $id ?>-galat" class="invalid-feedback d-block"><?= esc($galat[$h['id']]) ?></div><?php endif ?>
                                        </td>
                                        <?php endif ?>
                                        <td>
                                            <label class="visually-hidden" for="<?= $id ?>-keterangan">Keterangan <?= esc($h['nama']) ?></label>
                                            <select class="form-select form-select-sm" id="<?= $id ?>-keterangan" name="hasil[<?= $h['id'] ?>][keterangan]">
                                                <option value="">–</option>
                                                <?php foreach (Peringkat::KETERANGAN as $kode => $arti) : ?>
                                                    <option value="<?= $kode ?>" title="<?= esc($arti, 'attr') ?>"<?= $nilai($h, 'keterangan', (string) $h['keterangan']) === $kode ? ' selected' : '' ?>><?= $kode ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <?php if ($final) : ?>
                                            <td>
                                                <label class="visually-hidden" for="<?= $id ?>-peringkat">Koreksi peringkat <?= esc($h['nama']) ?></label>
                                                <input type="text" inputmode="numeric" class="form-control form-control-sm kotak-peringkat" id="<?= $id ?>-peringkat" name="hasil[<?= $h['id'] ?>][peringkat]"
                                                    value="<?= esc($nilai($h, 'peringkat', $h['manual'] ? (string) $h['peringkat'] : ''), 'attr') ?>">
                                            </td>
                                        <?php endif ?>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> Simpan hasil</button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('admin/hasil/nomor/' . $nomor['id']) ?>">Kembali ke <?= esc($nomor['nama']) ?></a>
                </form>
            <?php endif ?>
        </div>
    </section>

    <section class="card mb-4" id="peserta" aria-labelledby="judul-peserta">
        <div class="card-body">
            <h2 class="h5" id="judul-peserta">Peserta babak</h2>
            <p class="small text-body-secondary">Centang <?= $beregu ? 'regu' : 'atlet' ?> yang ikut babak ini. Calon peserta: <?= $beregu ? 'regu' : 'atlet' ?> dari pendaftaran Lunas di nomor ini. Peserta yang dikeluarkan kehilangan prestasinya di babak ini.</p>
            <?php if ($pilihan === []) : ?>
                <p class="text-body-secondary mb-0" id="calon-kosong">Belum ada <?= $beregu ? 'regu' : 'atlet' ?> dari pendaftaran Lunas di nomor ini.</p>
            <?php else : ?>
                <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/peserta') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row row-cols-1 row-cols-md-2 g-1 mb-3">
                        <?php foreach ($pilihan as $kunci => $p) : ?>
                            <?php $idKotak = 'peserta-' . substr(md5($kunci), 0, 10); ?>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="peserta[]" id="<?= $idKotak ?>" value="<?= esc($kunci, 'attr') ?>"<?= $p['ikut'] ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $idKotak ?>">
                                        <?php if ($p['nomor_dada'] !== null) : ?><span class="fw-semibold"><?= esc((string) $p['nomor_dada']) ?></span><?php endif ?>
                                        <?= esc($p['nama']) ?><?= $beregu ? '' : ' <span class="small text-body-secondary">' . esc($p['sekolah']) . '</span>' ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-people" aria-hidden="true"></i> Simpan peserta</button>
                </form>
            <?php endif ?>
        </div>
    </section>

    <?php
    // Jadwal babak (F9-07): sama dengan baris babak ini di Jadwal pertandingan.
    $jadwal      ??= ['tanggal' => null, 'jam' => null, 'lokasi' => ''];
    $galatJadwal ??= [];
    $isianJadwal ??= [];
    $nilaiJadwal = static fn (string $kotak, string $asal): string => is_string($isianJadwal[$kotak] ?? null) ? $isianJadwal[$kotak] : $asal;
    ?>
    <section class="card mb-4" id="jadwal-babak" aria-labelledby="judul-jadwal-babak">
        <div class="card-body">
            <h2 class="h5" id="judul-jadwal-babak">Jadwal babak</h2>
            <p class="small text-body-secondary">
                Tanggal, jam mulai (WIB), dan lokasi babak ini di halaman Jadwal. Semua babak bisa diisi sekaligus di
                <a href="<?= site_url('admin/jadwal-pertandingan') ?>">Jadwal pertandingan</a>.
            </p>
            <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/jadwal') ?>" method="post" class="row g-3 align-items-start" novalidate>
                <?= csrf_field() ?>
                <div class="col-12 col-md-4">
                    <label for="jadwal-tanggal" class="form-label">Tanggal</label>
                    <input type="date" class="form-control<?= isset($galatJadwal['tanggal']) ? ' is-invalid' : '' ?>" id="jadwal-tanggal" name="tanggal" min="<?= (int) date('Y') - 1 ?>-01-01" max="<?= (int) date('Y') + 2 ?>-12-31" value="<?= esc($nilaiJadwal('tanggal', (string) $jadwal['tanggal']), 'attr') ?>">
                    <?php if (isset($galatJadwal['tanggal'])) : ?><div class="invalid-feedback d-block"><?= esc($galatJadwal['tanggal']) ?></div><?php endif ?>
                </div>
                <div class="col-6 col-md-3">
                    <label for="jadwal-jam" class="form-label">Jam mulai</label>
                    <input type="time" class="form-control<?= isset($galatJadwal['jam_mulai']) ? ' is-invalid' : '' ?>" id="jadwal-jam" name="jam_mulai" value="<?= esc($nilaiJadwal('jam_mulai', (string) $jadwal['jam']), 'attr') ?>">
                    <?php if (isset($galatJadwal['jam_mulai'])) : ?><div class="invalid-feedback d-block"><?= esc($galatJadwal['jam_mulai']) ?></div><?php endif ?>
                </div>
                <div class="col-12 col-md-5">
                    <label for="jadwal-lokasi" class="form-label">Lokasi</label>
                    <input type="text" class="form-control<?= isset($galatJadwal['lokasi']) ? ' is-invalid' : '' ?>" id="jadwal-lokasi" name="lokasi" maxlength="<?= JadwalPertandingan::LOKASI_MAKSIMAL ?>" placeholder="Boleh kosong, misalnya Lintasan utama" value="<?= esc($nilaiJadwal('lokasi', $jadwal['lokasi']), 'attr') ?>">
                    <?php if (isset($galatJadwal['lokasi'])) : ?><div class="invalid-feedback d-block"><?= esc($galatJadwal['lokasi']) ?></div><?php endif ?>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-calendar-check" aria-hidden="true"></i> Simpan jadwal babak</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card mb-4" id="ubah-babak" aria-labelledby="judul-ubah-babak">
        <div class="card-body">
            <h2 class="h5" id="judul-ubah-babak">Ubah babak</h2>
            <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/ubah') ?>" method="post" class="row g-3 align-items-end" novalidate>
                <?= csrf_field() ?>
                <div class="col-12 col-md-6">
                    <label for="nama" class="form-label">Nama babak</label>
                    <input type="text" class="form-control<?= isset($galatBabak['nama']) ? ' is-invalid' : '' ?>" id="nama" name="nama" maxlength="<?= HasilLomba::NAMA_BABAK_MAKSIMAL ?>" value="<?= esc($babak['nama'], 'attr') ?>">
                    <?php if (isset($galatBabak['nama'])) : ?><div class="invalid-feedback d-block"><?= esc($galatBabak['nama']) ?></div><?php endif ?>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input<?= isset($galatBabak['final']) ? ' is-invalid' : '' ?>" type="checkbox" role="switch" id="final" name="final" value="1"<?= $final ? ' checked' : '' ?>>
                        <label class="form-check-label" for="final">Babak Final</label>
                    </div>
                    <?php if (isset($galatBabak['final'])) : ?><div class="invalid-feedback d-block"><?= esc($galatBabak['final']) ?></div><?php endif ?>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-outline-primary">Simpan babak</button>
                </div>
            </form>
            <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/hapus') ?>" method="post" class="mt-3"
                data-konfirmasi="<?= esc('Babak ' . $babak['nama'] . ' beserta semua prestasi pesertanya akan dihapus. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                data-konfirmasi-judul="Hapus babak?" data-konfirmasi-tombol="Hapus babak">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Hapus babak</button>
            </form>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
