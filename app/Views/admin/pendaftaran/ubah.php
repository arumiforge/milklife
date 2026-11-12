<?php
/**
 * Formulir ubah pendaftaran (F6-04; rancangan 9). Data dari
 * `PendaftaranController::ubah()`:
 *
 * - `isian`: `UbahPendaftaran::isian()` (data tersimpan, atau kiriman yang
 *   ditampilkan ulang).
 * - `induk`: `DataIndukPendaftaran::untukWizard()` (kelompok umur dan nomor
 *   lomba aktif, aturan, jenjang, kabupaten).
 * - `galat`: list `{jalur, pesan}` dari `UbahPendaftaran`.
 *
 * Nama isian sama dengan kiriman wizard (`sekolah[npsn]`,
 * `penanggung_jawab[<k>][nama]`, `atlet[<k>][nomor][]`, …) ditambah `id`
 * untuk baris tersimpan; urutan baris menentukan nomor urut di galat
 * (`atlet.2.nisn` = kartu atlet ketiga). Foto baru dikirim sebagai data URL
 * di `penanggung_jawab[<k>][foto]`, diperkecil di peramban oleh
 * `aset/js/admin-ubah-pendaftaran.js`. Skrip yang sama menambah dan
 * menghapus kartu, menyaring pilihan nomor lomba menurut kelompok umur dan
 * jenis kelamin, dan menampilkan jendela konfirmasi (K32, K59) dari
 * pratinjau server sebelum menyimpan.
 *
 * @var CodeIgniter\View\View                    $this
 * @var array<string, mixed>                     $isian
 * @var array<string, mixed>                     $induk
 * @var list<array{jalur: string, pesan: string}> $galat
 *
 * Tim tanpa pembayar (K68): kotak centang `bebankan_tim[]` per tim yang
 * ditandai (`isian['tim_tanpa_pembayar']`).
 */

use App\Libraries\Panel\UbahPendaftaran;

$p      = $isian['pendaftaran'];
$alamat = 'admin/pendaftaran/' . (int) $p['id'];
$lunas  = $p['status'] === 'lunas';

// Galat per isian (jalur → pesan) dan ringkasan untuk admin/_galat_isian.
$galatIsian = [];
$ringkasan  = [];
$polaIsian  = '/^(sekolah\.(jenjang|kabupaten|npsn|nama)|penanggung_jawab\.\d+\.(nama|nomor_wa|foto)|atlet\.\d+\.(nama|nisn|tanggal_lahir|jenis_kelamin|nomor))$/D';

foreach ($galat as $i => $g) {
    $jalur = (string) $g['jalur'];

    if (preg_match($polaIsian, $jalur) === 1) {
        $galatIsian[$jalur] = isset($galatIsian[$jalur]) ? $galatIsian[$jalur] . ' ' . $g['pesan'] : (string) $g['pesan'];
        $ringkasan[str_replace(['.', '_'], '-', $jalur)] = $galatIsian[$jalur];
    } else {
        $ringkasan[(string) $i] = (string) $g['pesan'];
    }
}

$nomorPerKu = [];

foreach ($induk['nomor_lomba'] as $nomor) {
    $nomorPerKu[(int) $nomor['kelompok_umur_id']][] = $nomor;
}

$nomorAktif = array_column($induk['nomor_lomba'], null, 'id');
$aturan     = $induk['aturan'];

/**
 * Atribut kelas dan keterangan galat untuk satu isian.
 *
 * @param array<string, string> $galatIsian
 */
$tandai = static function (array $galatIsian, string $jalur, string $idKotak): array {
    $pesan = $galatIsian[$jalur] ?? null;

    return [
        'kelas' => $pesan !== null ? ' is-invalid' : '',
        'aria'  => $pesan !== null ? ' aria-describedby="' . esc($idKotak . '-galat', 'attr') . '"' : '',
        'pesan' => $pesan !== null ? '<div id="' . esc($idKotak . '-galat', 'attr') . '" class="invalid-feedback d-block">' . esc($pesan) . '</div>' : '',
    ];
};

/**
 * Satu kartu penanggung jawab. `$k` kunci baris (angka, atau `__k__` untuk templat).
 *
 * @param array<string, mixed> $orang
 */
$kartuPj = static function (string $k, ?int $urut, array $orang) use ($tandai, $galatIsian, $alamat): string {
    $jalur  = 'penanggung_jawab.' . $k;
    $idDasar = 'penanggung-jawab-' . $k;
    $nama   = $tandai($galatIsian, $jalur . '.nama', $idDasar . '-nama');
    $wa     = $tandai($galatIsian, $jalur . '.nomor_wa', $idDasar . '-nomor-wa');
    $foto   = $tandai($galatIsian, $jalur . '.foto', $idDasar . '-foto');
    $nameDasar = 'penanggung_jawab[' . $k . ']';

    ob_start(); ?>
    <li class="col-12 col-lg-6 kartu-pj" data-kunci="<?= esc($k, 'attr') ?>">
        <div class="border rounded p-3 h-100">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h3 class="h6 mb-0">Penanggung jawab <span class="urut-kartu"><?= $urut === null ? '' : $urut ?></span></h3>
                <button type="button" class="btn btn-sm btn-outline-danger hapus-kartu">
                    <i class="bi bi-trash" aria-hidden="true"></i> Hapus
                </button>
            </div>
            <input type="hidden" name="<?= $nameDasar ?>[id]" value="<?= $orang['id'] === null ? '' : (int) $orang['id'] ?>">
            <div class="d-flex gap-3 align-items-start">
                <div class="flex-shrink-0 pratinjau-foto" style="width: 6rem;">
                    <?php if ($orang['foto'] !== '') : ?>
                        <img class="img-fluid rounded border" src="<?= esc($orang['foto'], 'attr') ?>" alt="Foto baru">
                    <?php elseif ($orang['ada_foto']) : ?>
                        <img class="img-fluid rounded border" loading="lazy" src="<?= site_url($alamat . '/foto/' . (int) $orang['id']) ?>" alt="<?= esc('Foto ' . $orang['nama'], 'attr') ?>">
                    <?php else : ?>
                        <?= view('tata_letak/_tanpa_gambar', ['rasio' => '1x1', 'label' => 'Foto penanggung jawab'], ['saveData' => false]) ?>
                    <?php endif ?>
                </div>
                <div class="flex-grow-1">
                    <div class="mb-2">
                        <label class="form-label" for="<?= $idDasar ?>-nama">Nama</label>
                        <input type="text" class="form-control<?= $nama['kelas'] ?>" id="<?= $idDasar ?>-nama" name="<?= $nameDasar ?>[nama]"
                            value="<?= esc($orang['nama'], 'attr') ?>" maxlength="100" required data-isian="nama"<?= $nama['aria'] ?>>
                        <?= $nama['pesan'] ?>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="<?= $idDasar ?>-nomor-wa">Nomor WA</label>
                        <input type="tel" class="form-control<?= $wa['kelas'] ?>" id="<?= $idDasar ?>-nomor-wa" name="<?= $nameDasar ?>[nomor_wa]"
                            value="<?= esc($orang['nomor_wa'], 'attr') ?>" inputmode="tel" required data-isian="nomor_wa"<?= $wa['aria'] ?>>
                        <?= $wa['pesan'] ?>
                    </div>
                    <div>
                        <label class="form-label" for="<?= $idDasar ?>-foto"><?= $orang['ada_foto'] ? 'Ganti foto' : 'Foto' ?></label>
                        <input type="file" class="form-control form-control-sm pilih-foto<?= $foto['kelas'] ?>" id="<?= $idDasar ?>-foto"
                            accept="image/*" data-isian="foto"<?= $foto['aria'] ?>>
                        <input type="hidden" name="<?= $nameDasar ?>[foto]" value="<?= esc($orang['foto'], 'attr') ?>" class="isi-foto">
                        <?= $foto['pesan'] ?>
                        <div class="form-text"><?= $orang['ada_foto'] ? 'Biarkan kosong bila foto tidak diganti.' : 'Foto wajib diisi.' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </li>
    <?php

    return (string) ob_get_clean();
};

/**
 * Satu kartu atlet.
 *
 * @param array<string, mixed> $atlet
 */
$kartuAtlet = static function (string $k, ?int $urut, array $atlet) use ($tandai, $galatIsian, $induk, $nomorPerKu, $nomorAktif): string {
    $jalur     = 'atlet.' . $k;
    $idDasar   = 'atlet-' . $k;
    $nameDasar = 'atlet[' . $k . ']';
    $nama      = $tandai($galatIsian, $jalur . '.nama', $idDasar . '-nama');
    $nisn      = $tandai($galatIsian, $jalur . '.nisn', $idDasar . '-nisn');
    $lahir     = $tandai($galatIsian, $jalur . '.tanggal_lahir', $idDasar . '-tanggal-lahir');
    $jk        = $tandai($galatIsian, $jalur . '.jenis_kelamin', $idDasar . '-jenis-kelamin');
    $nomor     = $tandai($galatIsian, $jalur . '.nomor', $idDasar . '-nomor');
    $dipilih   = array_map('intval', $atlet['nomor']);
    $nonaktif  = array_values(array_filter($dipilih, static fn (int $id): bool => ! isset($nomorAktif[$id])));

    ob_start(); ?>
    <li class="kartu-atlet border rounded p-3 mb-3" data-kunci="<?= esc($k, 'attr') ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <h3 class="h6 mb-0">
                Atlet <span class="urut-kartu"><?= $urut === null ? '' : $urut ?></span>
                <?php if ($atlet['nomor_dada'] !== null) : ?>
                    <span class="badge rounded-pill text-bg-light border ms-1">Nomor dada <?= esc($atlet['nomor_dada']) ?></span>
                <?php elseif ($atlet['id'] === null) : ?>
                    <span class="badge rounded-pill text-bg-info ms-1">Baru</span>
                <?php endif ?>
            </h3>
            <button type="button" class="btn btn-sm btn-outline-danger hapus-kartu">
                <i class="bi bi-trash" aria-hidden="true"></i> Hapus atlet
            </button>
        </div>
        <input type="hidden" name="<?= $nameDasar ?>[id]" value="<?= $atlet['id'] === null ? '' : (int) $atlet['id'] ?>">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="<?= $idDasar ?>-nama">Nama lengkap</label>
                <input type="text" class="form-control text-uppercase<?= $nama['kelas'] ?>" id="<?= $idDasar ?>-nama" name="<?= $nameDasar ?>[nama]"
                    value="<?= esc($atlet['nama'], 'attr') ?>" maxlength="100" required data-isian="nama"<?= $nama['aria'] ?>>
                <?= $nama['pesan'] ?>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="<?= $idDasar ?>-nisn">NISN</label>
                <input type="text" class="form-control<?= $nisn['kelas'] ?>" id="<?= $idDasar ?>-nisn" name="<?= $nameDasar ?>[nisn]"
                    value="<?= esc($atlet['nisn'], 'attr') ?>" inputmode="numeric" pattern="\d{10}" maxlength="10" required data-isian="nisn"<?= $nisn['aria'] ?>>
                <?= $nisn['pesan'] ?>
            </div>
            <div class="col-6 col-md-6 col-xl-3">
                <label class="form-label" for="<?= $idDasar ?>-tanggal-lahir">Tanggal lahir</label>
                <input type="date" class="form-control<?= $lahir['kelas'] ?>" id="<?= $idDasar ?>-tanggal-lahir" name="<?= $nameDasar ?>[tanggal_lahir]"
                    value="<?= esc($atlet['tanggal_lahir'], 'attr') ?>" required data-isian="tanggal_lahir"<?= $lahir['aria'] ?>>
                <?= $lahir['pesan'] ?>
            </div>
            <div class="col-6 col-md-6 col-xl-2">
                <label class="form-label" for="<?= $idDasar ?>-jenis-kelamin">Jenis kelamin</label>
                <select class="form-select<?= $jk['kelas'] ?>" id="<?= $idDasar ?>-jenis-kelamin" name="<?= $nameDasar ?>[jenis_kelamin]" required data-isian="jenis_kelamin"<?= $jk['aria'] ?>>
                    <option value="">Pilih</option>
                    <option value="L"<?= $atlet['jenis_kelamin'] === 'L' ? ' selected' : '' ?>>Laki-laki</option>
                    <option value="P"<?= $atlet['jenis_kelamin'] === 'P' ? ' selected' : '' ?>>Perempuan</option>
                </select>
                <?= $jk['pesan'] ?>
            </div>
        </div>

        <fieldset class="mt-3" id="<?= $idDasar ?>-nomor">
            <legend class="form-label fs-6 mb-1">Nomor lomba</legend>
            <p class="small text-body-secondary mb-2 keterangan-ku" aria-live="polite"></p>
            <?php foreach ($nonaktif as $id) : ?>
                <div class="form-check">
                    <input class="form-check-input<?= $nomor['kelas'] ?>" type="checkbox" name="<?= $nameDasar ?>[nomor][]" value="<?= $id ?>"
                        id="<?= $idDasar ?>-nomor-<?= $id ?>" checked data-isian="nomor">
                    <label class="form-check-label" for="<?= $idDasar ?>-nomor-<?= $id ?>">Nomor lomba yang sudah tidak dibuka (hapus centangnya)</label>
                </div>
            <?php endforeach; ?>
            <?php foreach ($induk['kelompok_umur'] as $ku) : ?>
                <?php if (empty($nomorPerKu[(int) $ku['id']])) {
                    continue;
                } ?>
                <div class="kelompok-nomor mb-2" data-ku="<?= (int) $ku['id'] ?>">
                    <p class="small fw-semibold mb-1"><?= esc($ku['nama']) ?></p>
                    <div class="d-flex flex-wrap column-gap-4">
                        <?php foreach ($nomorPerKu[(int) $ku['id']] as $n) : ?>
                            <div class="form-check pilihan-nomor" data-ku="<?= (int) $n['kelompok_umur_id'] ?>"
                                data-kategori="<?= esc($n['kategori'], 'attr') ?>" data-jenis="<?= esc($n['jenis'], 'attr') ?>">
                                <input class="form-check-input<?= $nomor['kelas'] ?>" type="checkbox" name="<?= $nameDasar ?>[nomor][]" value="<?= (int) $n['id'] ?>"
                                    id="<?= $idDasar ?>-nomor-<?= (int) $n['id'] ?>"<?= in_array((int) $n['id'], $dipilih, true) ? ' checked' : '' ?> data-isian="nomor">
                                <label class="form-check-label" for="<?= $idDasar ?>-nomor-<?= (int) $n['id'] ?>">
                                    <?= esc($n['nama_lengkap']) ?>
                                    <?php if ($n['jenis'] === 'beregu') : ?>
                                        <span class="badge rounded-pill text-bg-light border">Beregu</span>
                                    <?php endif ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?= $nomor['pesan'] ?>
        </fieldset>
    </li>
    <?php

    return (string) ob_get_clean();
};

$pjKosong    = ['id' => null, 'nama' => '', 'nomor_wa' => '', 'foto' => '', 'ada_foto' => false];
$atletKosong = ['id' => null, 'nama' => '', 'nisn' => '', 'tanggal_lahir' => '', 'jenis_kelamin' => '', 'nomor' => [], 'nomor_dada' => null];

$dataSkrip = [
    'jenjang'       => $induk['jenjang'],
    'kelompok_umur' => $induk['kelompok_umur'],
    'aturan'        => $aturan,
];

$s = $isian['sekolah'];
$galatSekolah = [
    'jenjang'   => $tandai($galatIsian, 'sekolah.jenjang', 'sekolah-jenjang'),
    'kabupaten' => $tandai($galatIsian, 'sekolah.kabupaten', 'sekolah-kabupaten'),
    'npsn'      => $tandai($galatIsian, 'sekolah.npsn', 'sekolah-npsn'),
    'nama'      => $tandai($galatIsian, 'sekolah.nama', 'sekolah-nama'),
];
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Ubah Pendaftaran <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Ubah Pendaftaran<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4" id="ubah-pendaftaran" data-status="<?= esc($p['status'], 'attr') ?>">
    <a class="d-inline-flex align-items-center gap-1 mb-3" href="<?= site_url($alamat) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke rincian pendaftaran
    </a>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h2 class="h4 mb-0"><?= esc($p['id_pendaftaran']) ?></h2>
        <?php if ($lunas) : ?>
            <span class="badge rounded-pill text-bg-success"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Lunas</span>
        <?php else : ?>
            <span class="badge rounded-pill text-bg-warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Menunggu Konfirmasi Pembayaran</span>
        <?php endif ?>
        <span class="text-body-secondary">Total tersimpan <?= esc(rupiah($p['total'])) ?></span>
    </div>

    <div class="alert alert-info" id="keterangan-ubah">
        <ul class="mb-0 ps-3">
            <li>Aturan pendaftaran tetap berlaku: NISN tidak boleh dipakai pendaftaran lain, kelompok umur mengikuti tahun lahir dan jenjang sekolah, dan tim beregu harus lengkap.</li>
            <li>Biaya nomor lomba dan tim yang tidak berubah tetap seperti saat mendaftar; nomor lomba dan tim baru memakai tarif sekarang.</li>
            <?php if ($lunas) : ?>
                <li>Pendaftaran ini sudah Lunas. Bila totalnya berubah, status kembali menjadi Menunggu Konfirmasi Pembayaran. Bila totalnya tetap, atlet baru langsung mendapat nomor dada.</li>
            <?php endif ?>
            <li>Nomor dada yang sudah dibagikan tetap dipegang atletnya.</li>
        </ul>
    </div>

    <?= view('admin/_galat_isian', ['galat' => $ringkasan], ['saveData' => false]) ?>
    <div class="alert alert-danger d-none" role="alert" tabindex="-1" id="galat-pratinjau"></div>

    <form action="<?= site_url($alamat) ?>" method="post" id="formulir-ubah" novalidate>
        <?= csrf_field('csrf-ubah') ?>
        <input type="hidden" name="setuju_menunggu" value="0">

        <section class="card mb-4" aria-labelledby="judul-sekolah">
            <div class="card-body p-3 p-lg-4">
                <h2 id="judul-sekolah" class="h5 mb-3">Sekolah</h2>
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="sekolah-npsn">NPSN</label>
                        <input type="text" class="form-control<?= $galatSekolah['npsn']['kelas'] ?>" id="sekolah-npsn" name="sekolah[npsn]"
                            value="<?= esc($s['npsn'], 'attr') ?>" inputmode="numeric" maxlength="8" data-isian="npsn"<?= $galatSekolah['npsn']['aria'] ?>>
                        <?= $galatSekolah['npsn']['pesan'] ?>
                        <div class="form-text">Kosongkan bila sekolah belum punya NPSN. NPSN yang ada di data sekolah menentukan nama, jenjang, dan kabupaten.</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-5">
                        <label class="form-label" for="sekolah-nama">Nama sekolah</label>
                        <input type="text" class="form-control<?= $galatSekolah['nama']['kelas'] ?>" id="sekolah-nama" name="sekolah[nama]"
                            value="<?= esc($s['nama'], 'attr') ?>" maxlength="150" data-isian="nama"<?= $galatSekolah['nama']['aria'] ?>>
                        <?= $galatSekolah['nama']['pesan'] ?>
                    </div>
                    <div class="col-6 col-xl-2">
                        <label class="form-label" for="sekolah-jenjang">Jenjang</label>
                        <select class="form-select<?= $galatSekolah['jenjang']['kelas'] ?>" id="sekolah-jenjang" name="sekolah[jenjang]" data-isian="jenjang"<?= $galatSekolah['jenjang']['aria'] ?>>
                            <option value="">Pilih</option>
                            <?php foreach ($induk['jenjang'] as $kode => $label) : ?>
                                <option value="<?= esc($kode, 'attr') ?>"<?= $s['jenjang'] === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $galatSekolah['jenjang']['pesan'] ?>
                    </div>
                    <div class="col-6 col-xl-2">
                        <label class="form-label" for="sekolah-kabupaten">Kabupaten/Kota</label>
                        <select class="form-select<?= $galatSekolah['kabupaten']['kelas'] ?>" id="sekolah-kabupaten" name="sekolah[kabupaten]" data-isian="kabupaten"<?= $galatSekolah['kabupaten']['aria'] ?>>
                            <option value="">Pilih</option>
                            <?php foreach ($induk['kabupaten'] as $kabupaten) : ?>
                                <option value="<?= esc($kabupaten, 'attr') ?>"<?= $s['kabupaten'] === $kabupaten ? ' selected' : '' ?>><?= esc($kabupaten) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $galatSekolah['kabupaten']['pesan'] ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4" aria-labelledby="judul-penanggung-jawab">
            <div class="card-body p-3 p-lg-4">
                <h2 id="judul-penanggung-jawab" class="h5 mb-1">Penanggung jawab</h2>
                <p class="small text-body-secondary mb-3">
                    Minimal <?= (int) $aturan['penanggung_jawab_minimal'] ?>, paling banyak <?= (int) $aturan['penanggung_jawab_maksimal'] ?> orang. Setiap penanggung jawab wajib berfoto.
                </p>
                <ul class="list-unstyled row g-3 mb-3" id="daftar-pj">
                    <?php foreach ($isian['penanggung_jawab'] as $i => $orang) : ?>
                        <?= $kartuPj((string) $i, $i + 1, $orang) ?>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn btn-outline-primary" id="tambah-pj">
                    <i class="bi bi-person-plus" aria-hidden="true"></i> Tambah penanggung jawab
                </button>
            </div>
        </section>

        <section class="card mb-4" aria-labelledby="judul-atlet">
            <div class="card-body p-3 p-lg-4">
                <h2 id="judul-atlet" class="h5 mb-1">Atlet</h2>
                <p class="small text-body-secondary mb-3">
                    Setiap atlet ikut minimal satu nomor lomba, paling banyak <?= (int) $aturan['nomor_individu_maksimal'] ?> nomor individu.
                    <?php if ($aturan['atlet_per_nomor_maksimal'] !== null) : ?>
                        Satu nomor individu paling banyak <?= (int) $aturan['atlet_per_nomor_maksimal'] ?> atlet per sekolah.
                    <?php endif ?>
                </p>
                <ol class="list-unstyled mb-3" id="daftar-atlet">
                    <?php foreach ($isian['atlet'] as $i => $atlet) : ?>
                        <?= $kartuAtlet((string) $i, $i + 1, $atlet) ?>
                    <?php endforeach; ?>
                </ol>
                <button type="button" class="btn btn-outline-primary" id="tambah-atlet">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah atlet
                </button>
            </div>
        </section>

        <?php if ($isian['tim_tanpa_pembayar'] !== []) : ?>
            <section class="card border-warning mb-4" aria-labelledby="judul-tim-tanpa-pembayar" id="tim-tanpa-pembayar">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-tim-tanpa-pembayar" class="h5 mb-1">Tim tanpa pembayar biaya tim</h2>
                    <p class="small text-body-secondary mb-3">
                        Pendaftaran yang membayar biaya tim ini sudah dihapus atau diubah. Biayanya tidak dipindah otomatis.
                        Centang untuk menagihkannya ke pendaftaran ini dengan tarif saat ini; total dihitung ulang saat disimpan.
                    </p>
                    <?php foreach ($isian['tim_tanpa_pembayar'] as $nomorId => $tim) : ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?= esc(UbahPendaftaran::KUNCI_BEBANKAN, 'attr') ?>[]" value="<?= (int) $nomorId ?>"
                                id="bebankan-tim-<?= (int) $nomorId ?>"<?= $tim['dibebankan'] ? ' checked' : '' ?>>
                            <label class="form-check-label" for="bebankan-tim-<?= (int) $nomorId ?>">
                                Bebankan biaya tim ke pendaftaran ini: <?= esc($tim['nomor_lomba']) ?>, <?= esc(rupiah((int) $tim['tarif'])) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif ?>

        <div class="d-flex flex-wrap gap-2 mb-5">
            <button type="submit" class="btn btn-primary" id="simpan-ubah">
                <i class="bi bi-check2" aria-hidden="true"></i> Simpan perubahan
            </button>
            <a class="btn btn-outline-primary" href="<?= site_url($alamat) ?>">Batal</a>
        </div>
    </form>

    <template id="templat-pj"><?= $kartuPj('__k__', null, $pjKosong) ?></template>
    <template id="templat-atlet"><?= $kartuAtlet('__k__', null, $atletKosong) ?></template>
</div>

<script type="application/json" id="data-ubah"><?= json_encode($dataSkrip, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/admin-ubah-pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
