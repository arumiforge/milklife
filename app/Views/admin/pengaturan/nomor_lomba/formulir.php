<?php
/**
 * Formulir tambah dan ubah nomor lomba. Data dari `NomorLombaController`.
 *
 * Isian tim (jumlah inti, batas anggota, biaya beregu) hanya tampil untuk
 * nomor beregu; jumlah putra dan putri minimal hanya untuk nomor mix. Pilihan
 * mix dimatikan untuk nomor individu. Semua aturan itu diperiksa lagi di
 * server; skrip di bawah hanya untuk kenyamanan.
 *
 * Nomor yang sudah dipakai atlet atau tim: kelompok umur, jenis, dan
 * kategorinya ditampilkan terkunci (nilainya tetap dikirim lewat isian
 * tersembunyi) dan tombol hapus tidak tampil.
 *
 * @var CodeIgniter\View\View                                 $this
 * @var array<string, string>                                 $isian
 * @var array<string, mixed>|null                             $nomor        baris yang diubah; null untuk nomor baru
 * @var bool                                                  $dipakai
 * @var list<string>                                          $kekurangan   isian tim yang belum diisi (nomor yang diubah)
 * @var array<int, array{id: int, nama: string, aktif: bool}> $kelompokUmur
 * @var array<string, string>                                 $galat
 */

use App\Models\NomorLombaModel;

$alamat  = 'admin/pengaturan/nomor-lomba';
$baru    = $nomor === null;
$aksi    = $baru ? site_url($alamat) : site_url($alamat . '/' . (int) $nomor['id']);
$kembali = site_url($alamat) . ($isian['kelompok_umur_id'] !== '' ? '?kelompok-umur=' . (int) $isian['kelompok_umur_id'] : '');

$beregu = $isian['jenis'] === 'beregu';
$mix    = $beregu && $isian['kategori'] === 'mix';

$salah = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$jelas = static fn (string $kotak, string $bantuan = ''): string => trim(
    ($bantuan !== '' ? $bantuan . ' ' : '') . (isset($galat[$kotak]) ? $kotak . '-galat' : ''),
);
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<div id="' . $kotak . '-galat" class="invalid-feedback d-block">' . esc($galat[$kotak]) . '</div>'
    : '';
$kunci = $dipakai ? ' disabled' : '';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= $baru ? 'Tambah Nomor Lomba' : 'Ubah Nomor Lomba' ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Nomor Lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row">
        <div class="col-12 col-xl-9 col-xxl-7">
            <div class="card">
                <div class="card-body p-3 p-sm-4">
                    <h2 class="h4 mb-1"><?= $baru ? 'Tambah nomor lomba' : 'Ubah ' . esc(NomorLombaModel::namaLengkap($nomor)) ?></h2>
                    <p class="text-body-secondary mb-4">
                        <a href="<?= $kembali ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar nomor lomba</a>
                    </p>

                    <?php if ($dipakai) : ?>
                        <div class="alert alert-info d-flex align-items-start gap-2" role="note" id="keterangan-dipakai">
                            <i class="bi bi-info-circle-fill flex-shrink-0" aria-hidden="true"></i>
                            <div>
                                Nomor ini sudah dipakai atlet atau tim, jadi kelompok umur, jenis, dan kategorinya dikunci, dan nomor ini tidak bisa dihapus.
                                Bila tidak dipakai lagi, matikan saja supaya tidak muncul di pendaftaran.
                            </div>
                        </div>
                    <?php endif ?>

                    <?php if ($kekurangan !== [] && $galat === []) : ?>
                        <div class="alert alert-warning d-flex align-items-start gap-2" role="status" id="keterangan-belum-lengkap">
                            <i class="bi bi-exclamation-circle-fill flex-shrink-0" aria-hidden="true"></i>
                            <div>
                                Nomor beregu ini belum lengkap: <?= esc(implode(', ', $kekurangan)) ?> belum diisi.
                                Lengkapi isian tim di bawah, lalu simpan.
                            </div>
                        </div>
                    <?php endif ?>

                    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

                    <form action="<?= $aksi ?>" method="post" novalidate id="formulir-nomor-lomba">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="kelompok_umur_id" class="form-label">Kelompok umur</label>
                            <select class="form-select<?= $salah('kelompok_umur_id') ?>" id="kelompok_umur_id" name="kelompok_umur_id"<?= $kunci ?>
                                <?= $jelas('kelompok_umur_id') !== '' ? 'aria-describedby="' . $jelas('kelompok_umur_id') . '"' : '' ?>>
                                <option value="">Pilih kelompok umur</option>
                                <?php foreach ($kelompokUmur as $id => $ku) : ?>
                                    <option value="<?= $id ?>"<?= $isian['kelompok_umur_id'] === (string) $id ? ' selected' : '' ?>>
                                        <?= esc($ku['nama']) ?><?= $ku['aktif'] ? '' : ' (tidak aktif)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($dipakai) : ?>
                                <input type="hidden" name="kelompok_umur_id" value="<?= esc($isian['kelompok_umur_id'], 'attr') ?>">
                            <?php endif ?>
                            <?= $pesan('kelompok_umur_id') ?>
                        </div>

                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama nomor</label>
                            <input type="text" class="form-control<?= $salah('nama') ?>" id="nama" name="nama" maxlength="<?= NomorLombaModel::NAMA_MAKSIMAL ?>"
                                value="<?= esc($isian['nama'], 'attr') ?>" aria-describedby="<?= $jelas('nama', 'nama-bantuan') ?>">
                            <div id="nama-bantuan" class="form-text">Tanpa putra/putri; kategorinya dipilih di bawah. Contoh: Lari 60 m.</div>
                            <?= $pesan('nama') ?>
                        </div>

                        <fieldset class="mb-3" id="jenis" tabindex="-1"<?= $jelas('jenis') !== '' ? ' aria-describedby="' . $jelas('jenis') . '"' : '' ?>>
                            <legend class="form-label fs-6 mb-2">Jenis</legend>
                            <?php foreach (NomorLombaModel::JENIS as $kode => $label) : ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input<?= $salah('jenis') ?>" type="radio" name="jenis" id="jenis-<?= $kode ?>" value="<?= $kode ?>"
                                        <?= $isian['jenis'] === $kode ? 'checked' : '' ?><?= $kunci ?>>
                                    <label class="form-check-label" for="jenis-<?= $kode ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($dipakai) : ?>
                                <input type="hidden" name="jenis" value="<?= esc($isian['jenis'], 'attr') ?>">
                            <?php endif ?>
                            <?= $pesan('jenis') ?>
                        </fieldset>

                        <fieldset class="mb-3" id="kategori" tabindex="-1" aria-describedby="<?= $jelas('kategori', 'kategori-bantuan') ?>">
                            <legend class="form-label fs-6 mb-2">Kategori</legend>
                            <?php foreach (NomorLombaModel::KATEGORI as $kode => $label) : ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input<?= $salah('kategori') ?>" type="radio" name="kategori" id="kategori-<?= $kode ?>" value="<?= $kode ?>"
                                        <?= $isian['kategori'] === $kode ? 'checked' : '' ?><?= $kunci !== '' || ($kode === 'mix' && $isian['jenis'] === 'individu') ? ' disabled' : '' ?>>
                                    <label class="form-check-label" for="kategori-<?= $kode ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($dipakai) : ?>
                                <input type="hidden" name="kategori" value="<?= esc($isian['kategori'], 'attr') ?>">
                            <?php endif ?>
                            <div id="kategori-bantuan" class="form-text">Mix hanya untuk nomor beregu: satu tim berisi putra dan putri.</div>
                            <?= $pesan('kategori') ?>
                        </fieldset>

                        <fieldset class="border rounded-3 p-3 mb-3" id="bagian-tim"<?= $beregu ? '' : ' hidden' ?>>
                            <legend class="float-none w-auto px-2 mb-0 fs-6 fw-semibold">Tim beregu</legend>
                            <p class="form-text mt-0">Wajib diisi untuk nomor beregu. Tim yang didaftarkan sekolah harus berisi paling sedikit sejumlah anggota inti.</p>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label for="jumlah_inti" class="form-label">Jumlah anggota inti</label>
                                    <div class="input-group has-validation">
                                        <input type="text" inputmode="numeric" class="form-control<?= $salah('jumlah_inti') ?>" id="jumlah_inti" name="jumlah_inti"
                                            value="<?= esc($isian['jumlah_inti'], 'attr') ?>"<?= $jelas('jumlah_inti') !== '' ? ' aria-describedby="' . $jelas('jumlah_inti') . '"' : '' ?>>
                                        <span class="input-group-text">orang</span>
                                    </div>
                                    <?= $pesan('jumlah_inti') ?>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="batas_anggota" class="form-label">Batas anggota</label>
                                    <div class="input-group has-validation">
                                        <input type="text" inputmode="numeric" class="form-control<?= $salah('batas_anggota') ?>" id="batas_anggota" name="batas_anggota"
                                            value="<?= esc($isian['batas_anggota'], 'attr') ?>" aria-describedby="<?= $jelas('batas_anggota', 'batas_anggota-bantuan') ?>">
                                        <span class="input-group-text">orang</span>
                                    </div>
                                    <div id="batas_anggota-bantuan" class="form-text">Paling banyak, termasuk cadangan. Sama dengan jumlah inti bila tanpa cadangan.</div>
                                    <?= $pesan('batas_anggota') ?>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="biaya_beregu" class="form-label">Biaya beregu</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" inputmode="numeric" class="form-control<?= $salah('biaya_beregu') ?>" id="biaya_beregu" name="biaya_beregu"
                                            value="<?= esc($isian['biaya_beregu'], 'attr') ?>" aria-describedby="<?= $jelas('biaya_beregu', 'biaya_beregu-bantuan') ?>">
                                    </div>
                                    <div id="biaya_beregu-bantuan" class="form-text">Per tim, tanpa titik. Contoh: 100000.</div>
                                    <?= $pesan('biaya_beregu') ?>
                                </div>
                            </div>

                            <div id="bagian-mix" class="mt-3"<?= $mix ? '' : ' hidden' ?>>
                                <p class="fw-semibold mb-1">Nomor mix</p>
                                <p class="form-text mt-0">Jumlah putra dan putri paling sedikit dalam satu tim. Jumlah keduanya tidak boleh lebih dari jumlah inti.</p>
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label for="jumlah_putra" class="form-label">Putra minimal</label>
                                        <div class="input-group has-validation">
                                            <input type="text" inputmode="numeric" class="form-control<?= $salah('jumlah_putra') ?>" id="jumlah_putra" name="jumlah_putra"
                                                value="<?= esc($isian['jumlah_putra'], 'attr') ?>"<?= $jelas('jumlah_putra') !== '' ? ' aria-describedby="' . $jelas('jumlah_putra') . '"' : '' ?>>
                                            <span class="input-group-text">orang</span>
                                        </div>
                                        <?= $pesan('jumlah_putra') ?>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label for="jumlah_putri" class="form-label">Putri minimal</label>
                                        <div class="input-group has-validation">
                                            <input type="text" inputmode="numeric" class="form-control<?= $salah('jumlah_putri') ?>" id="jumlah_putri" name="jumlah_putri"
                                                value="<?= esc($isian['jumlah_putri'], 'attr') ?>"<?= $jelas('jumlah_putri') !== '' ? ' aria-describedby="' . $jelas('jumlah_putri') . '"' : '' ?>>
                                            <span class="input-group-text">orang</span>
                                        </div>
                                        <?= $pesan('jumlah_putri') ?>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-6">
                                <label for="urutan" class="form-label">Urutan</label>
                                <input type="text" inputmode="numeric" class="form-control<?= $salah('urutan') ?>" id="urutan" name="urutan"
                                    value="<?= esc($isian['urutan'], 'attr') ?>" aria-describedby="<?= $jelas('urutan', 'urutan-bantuan') ?>">
                                <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu. Kosongkan untuk menaruhnya paling akhir di kelompok umurnya.</div>
                                <?= $pesan('urutan') ?>
                            </div>
                            <div class="col-12 col-sm-6 d-flex align-items-sm-center">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="aktif" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="aktif" name="aktif" value="1"<?= $isian['aktif'] === '1' ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="aktif">Aktif: muncul di pendaftaran</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan
                            </button>
                            <a class="btn btn-outline-primary" href="<?= $kembali ?>">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (! $baru && ! $dipakai) : ?>
                <div class="card mt-4" id="bagian-hapus">
                    <div class="card-body p-3 p-sm-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h2 class="h6 mb-1">Hapus nomor ini</h2>
                            <p class="text-body-secondary small mb-0">Bisa dihapus karena belum dipakai atlet atau tim.</p>
                        </div>
                        <form method="post" action="<?= site_url($alamat . '/' . (int) $nomor['id'] . '/hapus') ?>"
                            data-konfirmasi="Nomor <?= esc(NomorLombaModel::namaLengkap($nomor) . ' di ' . ($kelompokUmur[(int) $nomor['kelompok_umur_id']]['nama'] ?? ''), 'attr') ?> akan dihapus dan tidak bisa dikembalikan."
                            data-konfirmasi-tombol="Hapus nomor">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash" aria-hidden="true"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script>
    // Tampilkan isian tim hanya untuk nomor beregu, dan isian mix hanya untuk
    // nomor beregu mix. Pilihan mix dimatikan untuk nomor individu.
    (function () {
        var formulir = document.getElementById('formulir-nomor-lomba');
        if (!formulir) {
            return;
        }
        var bagianTim = document.getElementById('bagian-tim');
        var bagianMix = document.getElementById('bagian-mix');
        var pilihanMix = document.getElementById('kategori-mix');
        var terkunci = <?= $dipakai ? 'true' : 'false' ?>;

        function nilai(nama) {
            var dipilih = formulir.querySelector('input[name="' + nama + '"]:checked')
                || formulir.querySelector('input[type="hidden"][name="' + nama + '"]');
            return dipilih ? dipilih.value : '';
        }

        function perbarui() {
            var beregu = nilai('jenis') === 'beregu';
            if (!terkunci) {
                pilihanMix.disabled = !beregu;
                if (!beregu && pilihanMix.checked) {
                    pilihanMix.checked = false;
                    document.getElementById('kategori-putra').checked = true;
                }
            }
            bagianTim.hidden = !beregu;
            bagianMix.hidden = !(beregu && nilai('kategori') === 'mix');
        }

        formulir.addEventListener('change', function (peristiwa) {
            if (peristiwa.target.name === 'jenis' || peristiwa.target.name === 'kategori') {
                perbarui();
            }
        });
        perbarui();
    })();
</script>
<?= $this->endSection() ?>
