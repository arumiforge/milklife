<?php
/**
 * Formulir ubah pendaftaran (F6-04; rancangan 9). Data dari
 * `PendaftaranController::ubah()`:
 *
 * - `isian`: `UbahPendaftaran::isian()` (data tersimpan, atau kiriman yang
 *   ditampilkan ulang).
 * - `induk`: `DataIndukPendaftaran::untukWizard()` (kelompok umur dan nomor
 *   lomba aktif, aturan, jenjang, kabupaten).
 * - `nonaktif`: `UbahPendaftaran::nonaktifTerpakai()`, nama nomor lomba dan
 *   kelompok umur yang sudah dimatikan tetapi masih dipakai pendaftaran ini
 *   (K98). Nomor itu tetap tercentang dengan tanda "sudah ditutup" dan boleh
 *   dipertahankan; atlet baru tidak bisa memilihnya.
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
 * @var array{nomor_lomba: array<int, string>, kelompok_umur: array<int, string>} $nonaktif
 * @var list<array{jalur: string, pesan: string}> $galat
 *
 * Regu tanpa pembayar (K68): kotak centang `bebankan_tim[]` ("nomor:regu")
 * per regu yang ditandai (`isian['tim_tanpa_pembayar']`). Setiap nomor
 * beregu punya pilihan regu dan inti/cadangan, `atlet[<k>][regu][<id>][...]`
 * (K105), yang tampil bila nomornya dicentang.
 *
 * Tata letak `admin_tw` (F9-15). Kotak yang salah ditandai
 * `aria-invalid="true"` (gaya merahnya dari varian `aria-invalid:`), jadi
 * skrip cukup memasang atau melepas atribut itu. Tanggal lahir memakai
 * pemilih tanggal K89 (`aset/js/pilih-tanggal.js`).
 */

use App\Libraries\Panel\UbahPendaftaran;
use App\Libraries\Pendaftaran\Regu;

$p      = $isian['pendaftaran'];
$alamat = 'admin/pendaftaran/' . (int) $p['id'];
$lunas  = $p['status'] === 'lunas';

// Galat per isian (jalur → pesan) dan ringkasan untuk admin/_galat_isian_tw.
$galatIsian = [];
$ringkasan  = [];
$polaIsian  = '/^(sekolah\.(jenjang|kabupaten|npsn|nama)|penanggung_jawab\.\d+\.(nama|nomor_wa|foto)|atlet\.\d+\.(nama|nisn|tanggal_lahir|jenis_kelamin|kelompok_umur_id|nomor|regu))$/D';

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

// Kelas Tailwind kotak isian; tanda salah dari `aria-invalid="true"`.
$kotak      = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm aria-invalid:border-red-500 aria-invalid:bg-red-50 aria-invalid:text-red-900 aria-invalid:focus:border-red-500 aria-invalid:focus:ring-red-100';
$kotakKecil = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 aria-invalid:border-red-500 aria-invalid:bg-red-50 aria-invalid:text-red-900';
$centang    = 'mt-0.5 size-5 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500 aria-invalid:border-red-500 aria-invalid:bg-red-50';
$label      = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan    = 'mt-1.5 text-sm text-gray-600';
$lencanaAbu = 'inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800';

/**
 * Tanda dan keterangan galat untuk satu isian: `tanda` (atribut
 * `aria-invalid`), `aria` (`aria-invalid` dan `aria-describedby`), `pesan`.
 *
 * @param array<string, string> $galatIsian
 */
$tandai = static function (array $galatIsian, string $jalur, string $idKotak): array {
    $pesan = $galatIsian[$jalur] ?? null;

    return [
        'tanda' => $pesan !== null ? ' aria-invalid="true"' : '',
        'aria'  => $pesan !== null ? ' aria-invalid="true" aria-describedby="' . esc($idKotak . '-galat', 'attr') . '"' : '',
        'pesan' => $pesan !== null ? '<p id="' . esc($idKotak . '-galat', 'attr') . '" class="mt-1.5 text-sm font-medium text-red-700">' . esc($pesan) . '</p>' : '',
    ];
};

/**
 * Satu kartu penanggung jawab. `$k` kunci baris (angka, atau `__k__` untuk templat).
 *
 * @param array<string, mixed> $orang
 */
$kartuPj = static function (string $k, ?int $urut, array $orang) use ($tandai, $galatIsian, $alamat, $kotak, $label, $bantuan): string {
    $jalur  = 'penanggung_jawab.' . $k;
    $idDasar = 'penanggung-jawab-' . $k;
    $nama   = $tandai($galatIsian, $jalur . '.nama', $idDasar . '-nama');
    $wa     = $tandai($galatIsian, $jalur . '.nomor_wa', $idDasar . '-nomor-wa');
    $foto   = $tandai($galatIsian, $jalur . '.foto', $idDasar . '-foto');
    $nameDasar = 'penanggung_jawab[' . $k . ']';

    ob_start(); ?>
    <li class="kartu-pj" data-kunci="<?= esc($k, 'attr') ?>">
        <div class="h-full rounded-xl border border-gray-200 p-4">
            <div class="mb-3 flex items-start justify-between gap-2">
                <h3 class="font-bold text-gray-900">Ofisial <span class="urut-kartu"><?= $urut === null ? '' : $urut ?></span></h3>
                <?= komponen('tombol', ['label' => 'Hapus', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'trash-2', 'kelas' => 'hapus-kartu min-h-11 text-red-700!']) ?>
            </div>
            <input type="hidden" name="<?= $nameDasar ?>[id]" value="<?= $orang['id'] === null ? '' : (int) $orang['id'] ?>">
            <div class="flex items-start gap-3">
                <div class="pratinjau-foto w-24 shrink-0">
                    <?php if ($orang['foto'] !== '') : ?>
                        <img class="block h-auto w-full rounded-lg border border-gray-200" src="<?= esc($orang['foto'], 'attr') ?>" alt="Foto baru">
                    <?php elseif ($orang['ada_foto']) : ?>
                        <img class="block h-auto w-full rounded-lg border border-gray-200" loading="lazy" src="<?= site_url($alamat . '/foto/' . (int) $orang['id']) ?>" alt="<?= esc('Foto ' . $orang['nama'], 'attr') ?>">
                    <?php else : ?>
                        <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '1x1', 'label' => 'Foto ofisial'], ['saveData' => false]) ?>
                    <?php endif ?>
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div>
                        <label class="<?= $label ?>" for="<?= $idDasar ?>-nama">Nama</label>
                        <input type="text" class="<?= $kotak ?>" id="<?= $idDasar ?>-nama" name="<?= $nameDasar ?>[nama]"
                            value="<?= esc($orang['nama'], 'attr') ?>" maxlength="100" required data-isian="nama"<?= $nama['aria'] ?>>
                        <?= $nama['pesan'] ?>
                    </div>
                    <div>
                        <label class="<?= $label ?>" for="<?= $idDasar ?>-nomor-wa">Nomor WA</label>
                        <input type="tel" class="<?= $kotak ?>" id="<?= $idDasar ?>-nomor-wa" name="<?= $nameDasar ?>[nomor_wa]"
                            value="<?= esc($orang['nomor_wa'], 'attr') ?>" inputmode="tel" required data-isian="nomor_wa"<?= $wa['aria'] ?>>
                        <?= $wa['pesan'] ?>
                    </div>
                    <div>
                        <label class="<?= $label ?>" for="<?= $idDasar ?>-foto"><?= $orang['ada_foto'] ? 'Ganti foto' : 'Foto' ?></label>
                        <input type="file" class="pilih-foto block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-900 file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-primary-100 aria-invalid:border-red-500 aria-invalid:bg-red-50" id="<?= $idDasar ?>-foto"
                            accept="image/*" data-isian="foto"<?= $foto['aria'] ?>>
                        <input type="hidden" name="<?= $nameDasar ?>[foto]" value="<?= esc($orang['foto'], 'attr') ?>" class="isi-foto">
                        <?= $foto['pesan'] ?>
                        <p class="<?= $bantuan ?>"><?= $orang['ada_foto'] ? 'Biarkan kosong bila foto tidak diganti.' : 'Foto wajib diisi.' ?></p>
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
// Pilihan regu dan peran untuk satu nomor beregu (K105, K106). Mode Ketentuan
// admin: daftar nama dari Pengaturan sebanyak batas regu nomor itu; mode Bebas:
// nama diketik. Nama tersimpan selalu tetap bisa dipilih. `$tanda` atribut
// `aria-invalid` bila pilihan regu salah.
$pilihanRegu = static function (string $nameDasar, string $idDasar, int $nomorId, ?int $maksimal, array $terpilih, string $tanda, bool $tampil) use ($aturan, $kotakKecil): string {
    $bebas = ($aturan['regu_mode'] ?? Regu::MODE_KETENTUAN) === Regu::MODE_BEBAS;
    $label = Regu::dibolehkan($maksimal, ($aturan['regu_label'] ?? []) ?: null);
    $regu  = (string) ($terpilih['regu'] ?? '');
    $peran = (string) ($terpilih['peran'] ?? '');

    if ($regu !== '' && Regu::sah($regu) && ! in_array($regu, $label, true)) {
        $label[] = $regu;
    }

    ob_start(); ?>
    <div class="pilihan-regu-admin mb-2 ms-7 mt-1 grid max-w-sm grid-cols-2 gap-2" data-regu-untuk="<?= $nomorId ?>"<?= $tampil ? '' : ' hidden' ?>>
        <div>
            <label class="mb-0.5 block text-xs font-semibold text-gray-700" for="<?= $idDasar ?>-regu-<?= $nomorId ?>"><?= esc(Regu::awalan()) ?></label>
            <?php if ($bebas) : ?>
                <input type="text" class="<?= $kotakKecil ?>" id="<?= $idDasar ?>-regu-<?= $nomorId ?>" name="<?= $nameDasar ?>[regu][<?= $nomorId ?>][regu]"
                    value="<?= esc($regu, 'attr') ?>" maxlength="<?= Regu::PANJANG_MAKSIMAL ?>" placeholder="mis. A atau Merah" data-isian="regu"<?= $tanda ?>>
            <?php else : ?>
                <select class="<?= $kotakKecil ?>" id="<?= $idDasar ?>-regu-<?= $nomorId ?>" name="<?= $nameDasar ?>[regu][<?= $nomorId ?>][regu]" data-isian="regu"<?= $tanda ?>>
                    <option value="">Pilih <?= esc(mb_strtolower(Regu::awalan())) ?></option>
                    <?php foreach ($label as $l) : ?>
                        <option value="<?= esc($l, 'attr') ?>"<?= $l === $regu ? ' selected' : '' ?>><?= esc(Regu::label($l)) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif ?>
        </div>
        <div>
            <label class="mb-0.5 block text-xs font-semibold text-gray-700" for="<?= $idDasar ?>-peran-<?= $nomorId ?>">Sebagai</label>
            <select class="<?= $kotakKecil ?>" id="<?= $idDasar ?>-peran-<?= $nomorId ?>" name="<?= $nameDasar ?>[regu][<?= $nomorId ?>][peran]" data-isian="regu"<?= $tanda ?>>
                <option value="">Pilih</option>
                <?php foreach (Regu::PERAN as $nilai => $teks) : ?>
                    <option value="<?= $nilai ?>"<?= $nilai === $peran ? ' selected' : '' ?>><?= $teks ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
};

$kartuAtlet = static function (string $k, ?int $urut, array $atlet) use ($tandai, $galatIsian, $induk, $nomorPerKu, $nomorAktif, $nonaktif, $pilihanRegu, $kotak, $centang, $label, $bantuan, $lencanaAbu): string {
    $jalur     = 'atlet.' . $k;
    $idDasar   = 'atlet-' . $k;
    $nameDasar = 'atlet[' . $k . ']';
    $nama      = $tandai($galatIsian, $jalur . '.nama', $idDasar . '-nama');
    $nisn      = $tandai($galatIsian, $jalur . '.nisn', $idDasar . '-nisn');
    $lahir     = $tandai($galatIsian, $jalur . '.tanggal_lahir', $idDasar . '-tanggal-lahir');
    $jk        = $tandai($galatIsian, $jalur . '.jenis_kelamin', $idDasar . '-jenis-kelamin');
    $pilihKu   = $tandai($galatIsian, $jalur . '.kelompok_umur_id', $idDasar . '-kelompok-umur');
    $kuPilihan = $atlet['kelompok_umur_id'] ?? $atlet['ku_lama'] ?? null;
    $nomor     = $tandai($galatIsian, $jalur . '.nomor', $idDasar . '-nomor');
    $reguSalah = $tandai($galatIsian, $jalur . '.regu', $idDasar . '-nomor');
    $dipilih   = array_map('intval', $atlet['nomor']);
    $reguAtlet = $atlet['regu'] ?? [];
    $tertutup  = array_values(array_filter($dipilih, static fn (int $id): bool => ! isset($nomorAktif[$id])));

    ob_start(); ?>
    <li class="kartu-atlet rounded-xl border border-gray-200 p-4" data-kunci="<?= esc($k, 'attr') ?>"<?= isset($nonaktif['kelompok_umur'][(int) ($atlet['ku_lama'] ?? 0)]) ? ' data-ku-lama="' . (int) $atlet['ku_lama'] . '"' : '' ?>>
        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
            <h3 class="flex flex-wrap items-center gap-2 font-bold text-gray-900">
                <span>Atlet <span class="urut-kartu"><?= $urut === null ? '' : $urut ?></span></span>
                <?php if ($atlet['nomor_dada'] !== null) : ?>
                    <span class="<?= $lencanaAbu ?>">Nomor atlet (BIB) <?= esc($atlet['nomor_dada']) ?></span>
                <?php elseif ($atlet['id'] === null) : ?>
                    <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-800">Baru</span>
                <?php endif ?>
            </h3>
            <?= komponen('tombol', ['label' => 'Hapus atlet', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'trash-2', 'kelas' => 'hapus-kartu min-h-11 text-red-700!']) ?>
        </div>
        <input type="hidden" name="<?= $nameDasar ?>[id]" value="<?= $atlet['id'] === null ? '' : (int) $atlet['id'] ?>">
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-12">
            <div class="col-span-2 xl:col-span-4">
                <label class="<?= $label ?>" for="<?= $idDasar ?>-nama">Nama lengkap</label>
                <input type="text" class="<?= $kotak ?> uppercase" id="<?= $idDasar ?>-nama" name="<?= $nameDasar ?>[nama]"
                    value="<?= esc($atlet['nama'], 'attr') ?>" maxlength="100" required data-isian="nama"<?= $nama['aria'] ?>>
                <?= $nama['pesan'] ?>
            </div>
            <div class="col-span-2 xl:col-span-3">
                <label class="<?= $label ?>" for="<?= $idDasar ?>-nisn">NISN</label>
                <input type="text" class="<?= $kotak ?>" id="<?= $idDasar ?>-nisn" name="<?= $nameDasar ?>[nisn]"
                    value="<?= esc($atlet['nisn'], 'attr') ?>" inputmode="numeric" pattern="\d{10}" maxlength="10" required data-isian="nisn"<?= $nisn['aria'] ?>>
                <?= $nisn['pesan'] ?>
            </div>
            <div class="col-span-2 sm:col-span-1 xl:col-span-3">
                <label class="<?= $label ?>" for="<?= $idDasar ?>-tanggal-lahir">Tanggal lahir</label>
                <input type="date" class="<?= $kotak ?>" id="<?= $idDasar ?>-tanggal-lahir" name="<?= $nameDasar ?>[tanggal_lahir]"
                    value="<?= esc($atlet['tanggal_lahir'], 'attr') ?>" required data-isian="tanggal_lahir"<?= $lahir['aria'] ?>>
                <?= $lahir['pesan'] ?>
            </div>
            <div class="col-span-2 sm:col-span-1 xl:col-span-2">
                <label class="<?= $label ?>" for="<?= $idDasar ?>-jenis-kelamin">Jenis kelamin</label>
                <select class="<?= $kotak ?>" id="<?= $idDasar ?>-jenis-kelamin" name="<?= $nameDasar ?>[jenis_kelamin]" required data-isian="jenis_kelamin"<?= $jk['aria'] ?>>
                    <option value="">Pilih</option>
                    <option value="L"<?= $atlet['jenis_kelamin'] === 'L' ? ' selected' : '' ?>>Laki-laki</option>
                    <option value="P"<?= $atlet['jenis_kelamin'] === 'P' ? ' selected' : '' ?>>Perempuan</option>
                </select>
                <?= $jk['pesan'] ?>
            </div>
        </div>

        <div class="pilih-ku-admin mt-4" hidden>
            <label class="<?= $label ?>" for="<?= $idDasar ?>-kelompok-umur">Kelompok umur</label>
            <select class="<?= $kotak ?> sm:max-w-sm" id="<?= $idDasar ?>-kelompok-umur" name="<?= $nameDasar ?>[kelompok_umur_id]"
                data-isian="kelompok_umur_id" data-awal="<?= $kuPilihan === null ? '' : (int) $kuPilihan ?>" disabled<?= $pilihKu['aria'] ?>></select>
            <p class="<?= $bantuan ?>">
                Tahun lahir atlet ini masuk lebih dari satu kelompok umur untuk jenjang sekolahnya, jadi kelompok umurnya dipilih.
                Bila diganti, nomor lomba dipilih ulang dari kelompok umur baru. Nomor atlet (BIB) yang sudah ada tidak berubah;
                awalannya ikut kelompok umur baru setelah <strong>Urutkan nomor atlet</strong> di halaman Atlet.
            </p>
            <?= $pilihKu['pesan'] ?>
        </div>

        <fieldset class="mt-4" id="<?= $idDasar ?>-nomor">
            <legend class="mb-1 text-sm font-semibold text-gray-900">Nomor lomba</legend>
            <p class="keterangan-ku mb-2 text-sm text-gray-600" aria-live="polite"></p>
            <?php foreach ($tertutup as $id) : ?>
                <div class="py-1">
                    <div class="flex items-start gap-2">
                        <input class="<?= $centang ?>" type="checkbox" name="<?= $nameDasar ?>[nomor][]" value="<?= $id ?>"
                            id="<?= $idDasar ?>-nomor-<?= $id ?>" checked data-isian="nomor"<?= $nomor['tanda'] ?>>
                        <label class="text-sm text-gray-800" for="<?= $idDasar ?>-nomor-<?= $id ?>">
                            <?= esc($nonaktif['nomor_lomba'][$id] ?? 'Nomor lomba') ?>
                            <span class="inline-flex items-center rounded-md bg-gray-700 px-2 py-0.5 text-xs font-semibold text-white">Sudah ditutup</span>
                        </label>
                    </div>
                    <?php if (isset($reguAtlet[$id])) : ?>
                        <?= $pilihanRegu($nameDasar, $idDasar, $id, null, $reguAtlet[$id], $reguSalah['tanda'], true) ?>
                    <?php endif ?>
                </div>
            <?php endforeach; ?>
            <?php foreach ($induk['kelompok_umur'] as $ku) : ?>
                <?php if (empty($nomorPerKu[(int) $ku['id']])) {
                    continue;
                } ?>
                <div class="kelompok-nomor mb-2" data-ku="<?= (int) $ku['id'] ?>">
                    <p class="mb-1 text-sm font-semibold text-gray-700"><?= esc($ku['nama']) ?></p>
                    <div class="flex flex-wrap gap-x-6">
                        <?php foreach ($nomorPerKu[(int) $ku['id']] as $n) : ?>
                            <div class="pilihan-nomor py-1" data-ku="<?= (int) $n['kelompok_umur_id'] ?>"
                                data-kategori="<?= esc($n['kategori'], 'attr') ?>" data-jenis="<?= esc($n['jenis'], 'attr') ?>">
                                <div class="flex items-start gap-2">
                                    <input class="<?= $centang ?>" type="checkbox" name="<?= $nameDasar ?>[nomor][]" value="<?= (int) $n['id'] ?>"
                                        id="<?= $idDasar ?>-nomor-<?= (int) $n['id'] ?>"<?= in_array((int) $n['id'], $dipilih, true) ? ' checked' : '' ?> data-isian="nomor"<?= $nomor['tanda'] ?>>
                                    <label class="text-sm text-gray-800" for="<?= $idDasar ?>-nomor-<?= (int) $n['id'] ?>">
                                        <?= esc($n['nama_lengkap']) ?>
                                        <?php if ($n['jenis'] === 'beregu') : ?>
                                            <span class="<?= $lencanaAbu ?>">Beregu</span>
                                        <?php endif ?>
                                    </label>
                                </div>
                                <?php if ($n['jenis'] === 'beregu') : ?>
                                    <?= $pilihanRegu($nameDasar, $idDasar, (int) $n['id'], $n['regu_maksimal'] ?? null, $reguAtlet[(int) $n['id']] ?? [], $reguSalah['tanda'], in_array((int) $n['id'], $dipilih, true)) ?>
                                <?php endif ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?= $nomor['pesan'] ?>
            <?= $reguSalah['pesan'] ?>
        </fieldset>
    </li>
    <?php

    return (string) ob_get_clean();
};

$pjKosong    = ['id' => null, 'nama' => '', 'nomor_wa' => '', 'foto' => '', 'ada_foto' => false];
$atletKosong = ['id' => null, 'nama' => '', 'nisn' => '', 'tanggal_lahir' => '', 'jenis_kelamin' => '', 'kelompok_umur_id' => null, 'nomor' => [], 'regu' => [], 'nomor_dada' => null];

$dataSkrip = [
    'jenjang'                => $induk['jenjang'],
    'kelompok_umur'          => $induk['kelompok_umur'],
    'kelompok_umur_nonaktif' => (object) $nonaktif['kelompok_umur'],
    'aturan'                 => $aturan,
];

$s = $isian['sekolah'];
$galatSekolah = [
    'jenjang'   => $tandai($galatIsian, 'sekolah.jenjang', 'sekolah-jenjang'),
    'kabupaten' => $tandai($galatIsian, 'sekolah.kabupaten', 'sekolah-kabupaten'),
    'npsn'      => $tandai($galatIsian, 'sekolah.npsn', 'sekolah-npsn'),
    'nama'      => $tandai($galatIsian, 'sekolah.nama', 'sekolah-nama'),
];
$kartu = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Ubah Pendaftaran <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Ubah Pendaftaran<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [
    App\Libraries\Panel\AsalRincian::untuk((int) $p['id']),
    ['label' => $p['id_pendaftaran'], 'alamat' => $alamat],
    ['label' => 'Ubah'],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6" id="ubah-pendaftaran" data-status="<?= esc($p['status'], 'attr') ?>">

    <div class="flex flex-wrap items-center gap-2">
        <h2 class="text-xl font-bold text-gray-900"><?= esc($p['id_pendaftaran']) ?></h2>
        <?php if ($lunas) : ?>
            <?= komponen('lencana', ['label' => 'Lunas', 'warna' => 'hijau', 'ikon' => 'circle-check']) ?>
        <?php else : ?>
            <?= komponen('lencana', ['label' => 'Menunggu Konfirmasi Pembayaran', 'warna' => 'kuning', 'ikon' => 'hourglass']) ?>
        <?php endif ?>
        <span class="text-gray-600">Total tersimpan <?= esc(rupiah($p['total'])) ?></span>
    </div>

    <div class="flex items-start gap-3 rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900" id="keterangan-ubah">
        <?= ikon('info', 'mt-0.5 shrink-0') ?>
        <ul class="list-disc space-y-1 ps-4">
            <li>Aturan pendaftaran tetap berlaku: NISN tidak boleh dipakai pendaftaran lain, kelompok umur mengikuti tahun lahir dan jenjang sekolah, dan regu beregu baru harus lengkap (tepat sejumlah inti). Regu yang sudah terdaftar boleh kurang inti, tetapi tidak lebih; regu yang kurang inti ditandai di rincian.</li>
            <li>Biaya nomor lomba dan regu yang tidak berubah tetap seperti saat mendaftar; nomor lomba dan regu baru memakai tarif sekarang (biaya beregu dihitung per regu).</li>
            <?php if ($lunas) : ?>
                <li>Pendaftaran ini sudah Lunas. Bila totalnya berubah, status kembali menjadi Menunggu Konfirmasi Pembayaran. Bila totalnya tetap, atlet baru langsung mendapat nomor atlet (BIB).</li>
            <?php endif ?>
            <li>Nomor atlet (BIB) yang sudah dibagikan tetap dipegang atletnya.</li>
        </ul>
    </div>

    <?= view('admin/_galat_isian_tw', ['galat' => $ringkasan], ['saveData' => false]) ?>
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" tabindex="-1" id="galat-pratinjau" hidden></div>

    <form action="<?= site_url($alamat) ?>" method="post" id="formulir-ubah" class="space-y-6" novalidate>
        <?= csrf_field('csrf-ubah') ?>
        <input type="hidden" name="setuju_menunggu" value="0">

        <section class="<?= $kartu ?>" aria-labelledby="judul-sekolah">
            <h2 id="judul-sekolah" class="mb-3 text-lg font-bold text-gray-900">Sekolah</h2>
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-12">
                <div class="col-span-2 md:col-span-1 xl:col-span-3">
                    <label class="<?= $label ?>" for="sekolah-npsn">NPSN</label>
                    <input type="text" class="<?= $kotak ?>" id="sekolah-npsn" name="sekolah[npsn]"
                        value="<?= esc($s['npsn'], 'attr') ?>" inputmode="numeric" maxlength="8" data-isian="npsn"<?= $galatSekolah['npsn']['aria'] ?>>
                    <?= $galatSekolah['npsn']['pesan'] ?>
                    <p class="<?= $bantuan ?>">Kosongkan bila sekolah belum punya NPSN. NPSN yang ada di data sekolah menentukan nama, jenjang, dan kabupaten.</p>
                </div>
                <div class="col-span-2 md:col-span-1 xl:col-span-5">
                    <label class="<?= $label ?>" for="sekolah-nama">Nama sekolah</label>
                    <input type="text" class="<?= $kotak ?>" id="sekolah-nama" name="sekolah[nama]"
                        value="<?= esc($s['nama'], 'attr') ?>" maxlength="150" data-isian="nama"<?= $galatSekolah['nama']['aria'] ?>>
                    <?= $galatSekolah['nama']['pesan'] ?>
                </div>
                <div class="col-span-2 sm:col-span-1 xl:col-span-2">
                    <label class="<?= $label ?>" for="sekolah-jenjang">Jenjang</label>
                    <select class="<?= $kotak ?>" id="sekolah-jenjang" name="sekolah[jenjang]" data-isian="jenjang"<?= $galatSekolah['jenjang']['aria'] ?>>
                        <option value="">Pilih</option>
                        <?php foreach ($induk['jenjang'] as $kode => $labelJenjang) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"<?= $s['jenjang'] === $kode ? ' selected' : '' ?>><?= esc($labelJenjang) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $galatSekolah['jenjang']['pesan'] ?>
                </div>
                <div class="col-span-2 sm:col-span-1 xl:col-span-2">
                    <label class="<?= $label ?>" for="sekolah-kabupaten">Kabupaten/Kota</label>
                    <select class="<?= $kotak ?>" id="sekolah-kabupaten" name="sekolah[kabupaten]" data-isian="kabupaten"<?= $galatSekolah['kabupaten']['aria'] ?>>
                        <option value="">Pilih</option>
                        <?php foreach ($induk['kabupaten'] as $kabupaten) : ?>
                            <option value="<?= esc($kabupaten, 'attr') ?>"<?= $s['kabupaten'] === $kabupaten ? ' selected' : '' ?>><?= esc($kabupaten) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $galatSekolah['kabupaten']['pesan'] ?>
                </div>
            </div>
        </section>

        <section class="<?= $kartu ?>" aria-labelledby="judul-penanggung-jawab">
            <h2 id="judul-penanggung-jawab" class="text-lg font-bold text-gray-900">Ofisial</h2>
            <p class="mb-3 text-sm text-gray-600">
                Minimal <?= (int) $aturan['penanggung_jawab_minimal'] ?>, paling banyak <?= (int) $aturan['penanggung_jawab_maksimal'] ?> orang. Setiap ofisial wajib berfoto.
            </p>
            <ul class="mb-4 grid gap-4 lg:grid-cols-2" id="daftar-pj">
                <?php foreach ($isian['penanggung_jawab'] as $i => $orang) : ?>
                    <?= $kartuPj((string) $i, $i + 1, $orang) ?>
                <?php endforeach; ?>
            </ul>
            <?= komponen('tombol', ['label' => 'Tambah ofisial', 'varian' => 'kedua', 'ikon' => 'user-plus', 'atribut' => ['id' => 'tambah-pj']]) ?>
        </section>

        <section class="<?= $kartu ?>" aria-labelledby="judul-atlet">
            <h2 id="judul-atlet" class="text-lg font-bold text-gray-900">Atlet</h2>
            <p class="mb-3 text-sm text-gray-600">
                Setiap atlet ikut minimal satu nomor lomba, paling banyak <?= (int) $aturan['nomor_individu_maksimal'] ?> nomor individu.
                <?php if ($aturan['atlet_per_nomor_maksimal'] !== null) : ?>
                    Satu nomor individu paling banyak <?= (int) $aturan['atlet_per_nomor_maksimal'] ?> atlet per sekolah.
                <?php endif ?>
            </p>
            <ol class="mb-4 space-y-4" id="daftar-atlet">
                <?php foreach ($isian['atlet'] as $i => $atlet) : ?>
                    <?= $kartuAtlet((string) $i, $i + 1, $atlet) ?>
                <?php endforeach; ?>
            </ol>
            <?= komponen('tombol', ['label' => 'Tambah atlet', 'varian' => 'kedua', 'ikon' => 'plus', 'atribut' => ['id' => 'tambah-atlet']]) ?>
        </section>

        <?php if ($isian['tim_tanpa_pembayar'] !== []) : ?>
            <section class="rounded-2xl border border-yellow-300 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-tim-tanpa-pembayar" id="tim-tanpa-pembayar">
                <h2 id="judul-tim-tanpa-pembayar" class="text-lg font-bold text-gray-900">Regu tanpa pembayar biaya regu</h2>
                <p class="mb-3 text-sm text-gray-600">
                    Pendaftaran yang membayar biaya regu ini sudah dihapus atau diubah. Biayanya tidak dipindah otomatis.
                    Centang untuk menagihkannya ke pendaftaran ini dengan tarif saat ini; total dihitung ulang saat disimpan.
                </p>
                <?php foreach ($isian['tim_tanpa_pembayar'] as $kunci => $tim) : ?>
                    <?php $idKotak = 'bebankan-tim-' . Regu::idAman((string) $kunci) ?>
                    <div class="flex items-start gap-2 py-1">
                        <input class="<?= $centang ?>" type="checkbox" name="<?= esc(UbahPendaftaran::KUNCI_BEBANKAN, 'attr') ?>[]" value="<?= esc((string) $kunci, 'attr') ?>"
                            id="<?= esc($idKotak, 'attr') ?>"<?= $tim['dibebankan'] ? ' checked' : '' ?>>
                        <label class="text-sm text-gray-800" for="<?= esc($idKotak, 'attr') ?>">
                            Bebankan biaya regu ke pendaftaran ini: <?= esc($tim['nomor_lomba']) ?>, <?= esc(rupiah((int) $tim['tarif'])) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif ?>

        <div class="flex flex-wrap gap-2">
            <?= komponen('tombol', ['label' => 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check', 'kelas' => 'disabled:opacity-60', 'atribut' => ['id' => 'simpan-ubah']]) ?>
            <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url($alamat), 'varian' => 'kedua']) ?>
        </div>
    </form>

    <template id="templat-pj"><?= $kartuPj('__k__', null, $pjKosong) ?></template>
    <template id="templat-atlet"><?= $kartuAtlet('__k__', null, $atletKosong) ?></template>
</div>

<script type="application/json" id="data-ubah"><?= json_encode($dataSkrip, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<script src="<?= aset('js/admin-ubah-pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
