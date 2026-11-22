<?php
/**
 * Formulir tambah dan ubah nomor lomba, tata letak Tailwind `admin_tw`. Data
 * dari `NomorLombaController`.
 *
 * Isian tim (jumlah inti, batas anggota, biaya beregu) hanya tampil untuk
 * nomor beregu; jumlah putra dan putri minimal hanya untuk nomor mix. Pilihan
 * mix dimatikan untuk nomor individu. Semua aturan itu diperiksa lagi di
 * server; skrip di bawah hanya untuk kenyamanan.
 *
 * Nomor yang sudah dipakai atlet atau tim: kelompok umur, jenis, dan
 * kategorinya ditampilkan terkunci (nilainya tetap dikirim lewat isian
 * tersembunyi), isian tim yang sudah terisi hanya-baca, dan tombol hapus
 * tidak tampil (F6-07).
 *
 * @var CodeIgniter\View\View                                 $this
 * @var array<string, string>                                 $isian
 * @var array<string, mixed>|null                             $nomor        baris yang diubah; null untuk nomor baru
 * @var bool                                                  $dipakai
 * @var list<string>                                          $kekurangan   isian tim yang belum diisi (nomor yang diubah)
 * @var array<int, array{id: int, nama: string, aktif: bool}> $kelompokUmur
 * @var array<int, array{nama: string, komponen: list<array{kode: string, nama: string, jenis: string}>}> $tabelSkor pilihan tabel skor (F9-10)
 * @var array<string, string>                                 $galat
 */

use App\Libraries\Hasil\JenisHasil;
use App\Models\NomorLombaModel;

$alamat  = 'admin/pengaturan/nomor-lomba';
$baru    = $nomor === null;
$aksi    = $baru ? site_url($alamat) : site_url($alamat . '/' . (int) $nomor['id']);
$kembali = site_url($alamat) . ($isian['kelompok_umur_id'] !== '' ? '?kelompok-umur=' . (int) $isian['kelompok_umur_id'] : '');

$beregu = $isian['jenis'] === 'beregu';
$mix    = $beregu && $isian['kategori'] === 'mix';

// Kotak yang salah ditandai merah dan `aria-invalid="true"`.
$kelasKotak = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 disabled:cursor-not-allowed disabled:bg-gray-100 [&[readonly]]:bg-gray-100 sm:text-sm '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
// `$bulat` mengganti sudut bulat untuk kotak yang bersambung dengan satuan (`rounded-s-lg`, `rounded-e-lg`).
$kotak = static fn (string $nama, string $tambah = '', string $bulat = 'rounded-lg'): string => 'class="' . trim(str_replace('rounded-lg', $bulat, $kelasKotak(isset($galat[$nama]))) . ' ' . $tambah) . '"'
    . (isset($galat[$nama]) ? ' aria-invalid="true"' : '');
$pilihan = static fn (string $nama): string => 'class="size-4 ' . (isset($galat[$nama]) ? 'border-red-500' : 'border-gray-300') . ' text-primary-700 focus:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-50"'
    . (isset($galat[$nama]) ? ' aria-invalid="true"' : '');
$jelas = static fn (string $kotak, string $bantuan = ''): string => trim(
    ($bantuan !== '' ? $bantuan . ' ' : '') . (isset($galat[$kotak]) ? $kotak . '-galat' : ''),
);
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$satuan = static fn (string $teks, string $sisi = 'e'): string => '<span class="inline-flex items-center border border-gray-300 bg-gray-100 px-3 text-sm text-gray-700 '
    . ($sisi === 'e' ? 'rounded-e-lg border-s-0' : 'rounded-s-lg border-e-0') . '">' . $teks . '</span>';
$kunci = $dipakai ? ' disabled' : '';
// Isian tim yang sudah terisi ikut terkunci; yang masih kosong (data awal) boleh dilengkapi.
$tetap = static fn (string $kotak): string => $dipakai && (string) ($nomor[$kotak] ?? '') !== '' ? ' readonly' : '';
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$legenda = 'mb-2 text-sm font-semibold text-gray-900';
$bantuan = 'mt-1.5 text-sm text-gray-600';
$opsi    = 'inline-flex min-h-11 items-center gap-2 text-sm text-gray-900';
$bagian  = 'rounded-xl border border-gray-200 p-4 sm:p-5';
$judulBagian = 'px-1 text-sm font-bold text-gray-900';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= $baru ? 'Tambah Nomor Lomba' : 'Ubah Nomor Lomba' ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Nomor Lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="space-y-6">
        <section class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6">
            <div>
                <h2 class="text-xl font-bold break-words text-gray-900"><?= $baru ? 'Tambah nomor lomba' : 'Ubah ' . esc(NomorLombaModel::namaLengkap($nomor)) ?></h2>
                <a class="tautan-kembali mt-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline" href="<?= $kembali ?>"><?= ikon('arrow-left', 'size-4') ?> Kembali ke daftar nomor lomba</a>
            </div>

            <?php if ($dipakai) : ?>
                <div class="flex items-start gap-3 rounded-xl border border-primary-200 bg-primary-100 p-4 text-sm text-primary-900" role="note" id="keterangan-dipakai">
                    <?= ikon('info', 'mt-0.5 shrink-0') ?>
                    <div class="min-w-0">
                        Nomor ini sudah dipakai atlet atau tim, jadi kelompok umur, jenis, kategori, jumlah inti, batas anggota, dan jumlah putra/putri minimalnya dikunci, dan nomor ini tidak bisa dihapus.
                        Nama, biaya beregu, urutan, dan aktif tetap bisa diubah; biaya baru hanya berlaku untuk pendaftaran berikutnya.
                        Bila tidak dipakai lagi, matikan saja supaya tidak muncul di pendaftaran.
                    </div>
                </div>
            <?php endif ?>

            <?php if ($kekurangan !== [] && $galat === []) : ?>
                <div class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status" id="keterangan-belum-lengkap">
                    <?= ikon('circle-alert', 'mt-0.5 shrink-0') ?>
                    <div class="min-w-0">
                        Nomor beregu ini belum lengkap: <?= esc(implode(', ', $kekurangan)) ?> belum diisi.
                        Lengkapi isian tim di bawah, lalu simpan.
                    </div>
                </div>
            <?php endif ?>

            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= $aksi ?>" method="post" novalidate id="formulir-nomor-lomba" class="space-y-5" data-terkunci="<?= $dipakai ? '1' : '0' ?>">
                <?= csrf_field() ?>

                <div>
                    <label for="kelompok_umur_id" class="<?= $label ?>">Kelompok umur</label>
                    <select <?= $kotak('kelompok_umur_id', 'min-h-11') ?> id="kelompok_umur_id" name="kelompok_umur_id"<?= $kunci ?>
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

                <div>
                    <label for="nama" class="<?= $label ?>">Nama nomor</label>
                    <input type="text" <?= $kotak('nama') ?> id="nama" name="nama" maxlength="<?= NomorLombaModel::NAMA_MAKSIMAL ?>"
                        value="<?= esc($isian['nama'], 'attr') ?>" aria-describedby="<?= $jelas('nama', 'nama-bantuan') ?>">
                    <p id="nama-bantuan" class="<?= $bantuan ?>">Tanpa putra/putri; kategorinya dipilih di bawah. Contoh: Lari 60 m.</p>
                    <?= $pesan('nama') ?>
                </div>

                <fieldset id="jenis" tabindex="-1"<?= $jelas('jenis') !== '' ? ' aria-describedby="' . $jelas('jenis') . '"' : '' ?>>
                    <legend class="<?= $legenda ?>">Jenis</legend>
                    <div class="flex flex-wrap gap-x-6">
                        <?php foreach (NomorLombaModel::JENIS as $kode => $teks) : ?>
                            <label class="<?= $opsi ?>" for="jenis-<?= $kode ?>">
                                <input <?= $pilihan('jenis') ?> type="radio" name="jenis" id="jenis-<?= $kode ?>" value="<?= $kode ?>"
                                    <?= $isian['jenis'] === $kode ? 'checked' : '' ?><?= $kunci ?>>
                                <?= $teks ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($dipakai) : ?>
                        <input type="hidden" name="jenis" value="<?= esc($isian['jenis'], 'attr') ?>">
                    <?php endif ?>
                    <?= $pesan('jenis') ?>
                </fieldset>

                <fieldset id="kategori" tabindex="-1" aria-describedby="<?= $jelas('kategori', 'kategori-bantuan') ?>">
                    <legend class="<?= $legenda ?>">Kategori</legend>
                    <div class="flex flex-wrap gap-x-6">
                        <?php foreach (NomorLombaModel::KATEGORI as $kode => $teks) : ?>
                            <label class="<?= $opsi ?>" for="kategori-<?= $kode ?>">
                                <input <?= $pilihan('kategori') ?> type="radio" name="kategori" id="kategori-<?= $kode ?>" value="<?= $kode ?>"
                                    <?= $isian['kategori'] === $kode ? 'checked' : '' ?><?= $kunci !== '' || ($kode === 'mix' && $isian['jenis'] === 'individu') ? ' disabled' : '' ?>>
                                <?= $teks ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($dipakai) : ?>
                        <input type="hidden" name="kategori" value="<?= esc($isian['kategori'], 'attr') ?>">
                    <?php endif ?>
                    <p id="kategori-bantuan" class="<?= $bantuan ?>">Mixed hanya untuk nomor beregu: satu tim berisi putra dan putri.</p>
                    <?= $pesan('kategori') ?>
                </fieldset>

                <fieldset id="jenis_hasil" tabindex="-1" aria-describedby="<?= $jelas('jenis_hasil', 'jenis_hasil-bantuan') ?>">
                    <legend class="<?= $legenda ?>">Jenis hasil</legend>
                    <div class="flex flex-wrap gap-x-6">
                        <?php foreach (JenisHasil::LABEL as $kode => $teks) : ?>
                            <label class="<?= $opsi ?>" for="jenis_hasil-<?= $kode ?>">
                                <input <?= $pilihan('jenis_hasil') ?> type="radio" name="jenis_hasil" id="jenis_hasil-<?= $kode ?>" value="<?= $kode ?>"
                                    <?= ($isian['jenis_hasil'] ?? '') === $kode ? 'checked' : '' ?>>
                                <?= $teks ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p id="jenis_hasil-bantuan" class="<?= $bantuan ?>">Untuk hasil lomba: waktu (lari, jalan cepat, estafet; makin kecil makin baik), jarak (lompat, lempar, tolak), poin (poin akhir ditulis langsung), atau poin multi-lomba (Trilomba: prestasi tiap komponen diisi, poinnya dihitung dari tabel skor).</p>
                    <?= $pesan('jenis_hasil') ?>
                </fieldset>

                <?php $multi = ($isian['jenis_hasil'] ?? '') === JenisHasil::MULTI; ?>
                <fieldset class="<?= $bagian ?>" id="bagian-multi"<?= $multi ? '' : ' hidden' ?>>
                    <legend class="<?= $judulBagian ?>">Tabel skor poin multi-lomba</legend>
                    <?php if ($tabelSkor === []) : ?>
                        <p class="text-sm text-gray-600" id="tabel-skor-kosong">Belum ada tabel skor. Pasang tabel bawaan panitia di <a class="font-semibold text-primary-700 underline" href="<?= site_url('admin/pengaturan/tabel-skor') ?>">Pengaturan &gt; Tabel Skor</a>, lalu kembali ke sini.</p>
                    <?php else : ?>
                        <div class="mb-3">
                            <label for="tabel_skor_id" class="<?= $label ?>">Tabel skor</label>
                            <select <?= $kotak('tabel_skor_id', 'min-h-11') ?> id="tabel_skor_id" name="tabel_skor_id" aria-describedby="<?= $jelas('tabel_skor_id') ?>">
                                <option value="">Pilih tabel skor</option>
                                <?php foreach ($tabelSkor as $idTabel => $t) : ?>
                                    <option value="<?= $idTabel ?>"<?= (string) $idTabel === ($isian['tabel_skor_id'] ?? '') ? ' selected' : '' ?>><?= esc($t['nama']) ?></option>
                                <?php endforeach ?>
                            </select>
                            <?= $pesan('tabel_skor_id') ?>
                        </div>
                        <?php $dicentang = array_filter(explode(',', (string) ($isian['komponen_skor'] ?? ''))); ?>
                        <?php foreach ($tabelSkor as $idTabel => $t) : ?>
                            <fieldset class="komponen-skor mb-2" id="komponen-skor-<?= $idTabel ?>" data-tabel="<?= $idTabel ?>"<?= (string) $idTabel === ($isian['tabel_skor_id'] ?? '') ? '' : ' hidden' ?>>
                                <legend class="mb-1 text-sm font-semibold text-gray-900">Komponen <?= esc($t['nama']) ?></legend>
                                <div class="flex flex-wrap gap-x-6">
                                    <?php foreach ($t['komponen'] as $k) : ?>
                                        <?php $idKotak = 'komponen-' . $idTabel . '-' . $k['kode']; ?>
                                        <label class="<?= $opsi ?>" for="<?= esc($idKotak, 'attr') ?>">
                                            <input <?= str_replace('class="size-4 ', 'class="size-4 rounded ', $pilihan('komponen_skor')) ?> type="checkbox" name="komponen_skor[<?= $idTabel ?>][]" id="<?= esc($idKotak, 'attr') ?>" value="<?= esc($k['kode'], 'attr') ?>"
                                                <?= (string) $idTabel === ($isian['tabel_skor_id'] ?? '') && in_array($k['kode'], $dicentang, true) ? 'checked' : '' ?>>
                                            <?= esc($k['nama']) ?>
                                        </label>
                                    <?php endforeach ?>
                                </div>
                            </fieldset>
                        <?php endforeach ?>
                        <p class="<?= $bantuan ?>">Tidak dicentang sama sekali = semua komponen tabel dipakai. Poin dicari di baris <?= esc($isian['kategori'] === 'putri' ? 'putri' : 'putra') ?> sesuai kategori nomor ini.</p>
                        <?= $pesan('komponen_skor') ?>
                    <?php endif ?>
                </fieldset>

                <?php // Aturan nomor lompat/lempar (F9-09): percobaan dan pemecah seri. ?>
                <fieldset class="<?= $bagian ?>" id="bagian-percobaan"<?= ($isian['jenis_hasil'] ?? '') === JenisHasil::JARAK ? '' : ' hidden' ?>>
                    <legend class="<?= $judulBagian ?>">Percobaan lompat/lempar</legend>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="jumlah_percobaan" class="<?= $label ?>">Banyak percobaan</label>
                            <input type="number" <?= $kotak('jumlah_percobaan') ?> id="jumlah_percobaan" name="jumlah_percobaan" min="1" max="<?= App\Libraries\Hasil\Percobaan::MAKSIMAL ?>" inputmode="numeric" placeholder="3"
                                value="<?= esc($isian['jumlah_percobaan'] ?? '', 'attr') ?>" aria-describedby="<?= $jelas('jumlah_percobaan', 'jumlah_percobaan-bantuan') ?>">
                            <p id="jumlah_percobaan-bantuan" class="<?= $bantuan ?>">Kosong = 3. Juri mengisi jarak tiap percobaan di Lembar juri; prestasi = jarak sah terjauh.</p>
                            <?= $pesan('jumlah_percobaan') ?>
                        </div>
                        <div class="md:col-span-2 md:pt-6">
                            <?= komponen('sakelar', [
                                'label'   => 'Prestasi sama dipisah prestasi terbaik kedua',
                                'nama'    => 'pemecah_seri',
                                'centang' => ($isian['pemecah_seri'] ?? '1') !== '0',
                                'bantuan' => 'Bila jarak terbaik sama, yang jarak terbaik keduanya lebih jauh di atas (lalu ketiga, dan seterusnya). Hanya berlaku bila percobaannya diisi; matikan supaya prestasi sama tetap berperingkat sama.',
                            ]) ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="<?= $bagian ?>" id="bagian-tim"<?= $beregu ? '' : ' hidden' ?>>
                    <legend class="<?= $judulBagian ?>">Tim beregu</legend>
                    <p class="mb-4 text-sm text-gray-600">Wajib diisi untuk nomor beregu. Satu sekolah boleh mendaftarkan beberapa regu (Regu A, B, C, …); setiap regu berisi tepat sejumlah anggota inti, ditambah cadangan sampai batas anggota.</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="jumlah_inti" class="<?= $label ?>">Jumlah anggota inti</label>
                            <div class="flex">
                                <input type="text" inputmode="numeric" <?= $kotak('jumlah_inti', '', 'rounded-s-lg') ?> id="jumlah_inti" name="jumlah_inti"
                                    value="<?= esc($isian['jumlah_inti'], 'attr') ?>"<?= $tetap('jumlah_inti') ?><?= $jelas('jumlah_inti') !== '' ? ' aria-describedby="' . $jelas('jumlah_inti') . '"' : '' ?>>
                                <?= $satuan('orang') ?>
                            </div>
                            <?= $pesan('jumlah_inti') ?>
                        </div>
                        <div>
                            <label for="batas_anggota" class="<?= $label ?>">Batas anggota</label>
                            <div class="flex">
                                <input type="text" inputmode="numeric" <?= $kotak('batas_anggota', '', 'rounded-s-lg') ?> id="batas_anggota" name="batas_anggota"
                                    value="<?= esc($isian['batas_anggota'], 'attr') ?>"<?= $tetap('batas_anggota') ?> aria-describedby="<?= $jelas('batas_anggota', 'batas_anggota-bantuan') ?>">
                                <?= $satuan('orang') ?>
                            </div>
                            <p id="batas_anggota-bantuan" class="<?= $bantuan ?>">Paling banyak per regu, termasuk cadangan. Sama dengan jumlah inti bila tanpa cadangan.</p>
                            <?= $pesan('batas_anggota') ?>
                        </div>
                        <div>
                            <label for="regu_maksimal" class="<?= $label ?>">Regu per sekolah paling banyak</label>
                            <div class="flex">
                                <input type="text" inputmode="numeric" <?= $kotak('regu_maksimal', '', 'rounded-s-lg') ?> id="regu_maksimal" name="regu_maksimal"
                                    value="<?= esc($isian['regu_maksimal'] ?? '', 'attr') ?>" aria-describedby="<?= $jelas('regu_maksimal', 'regu_maksimal-bantuan') ?>">
                                <?= $satuan('regu') ?>
                            </div>
                            <p id="regu_maksimal-bantuan" class="<?= $bantuan ?>">Contoh: 3 berarti paling banyak tiga regu per sekolah. Nama regunya diatur di Pengaturan, Pendaftaran. Kosongkan bila tidak dibatasi.</p>
                            <?= $pesan('regu_maksimal') ?>
                        </div>
                        <div>
                            <label for="biaya_beregu" class="<?= $label ?>">Biaya beregu</label>
                            <div class="flex">
                                <?= $satuan('Rp', 's') ?>
                                <input type="text" inputmode="numeric" <?= $kotak('biaya_beregu', '', 'rounded-e-lg') ?> id="biaya_beregu" name="biaya_beregu"
                                    value="<?= esc($isian['biaya_beregu'], 'attr') ?>" aria-describedby="<?= $jelas('biaya_beregu', 'biaya_beregu-bantuan') ?>">
                            </div>
                            <p id="biaya_beregu-bantuan" class="<?= $bantuan ?>">Per regu, tanpa titik. Contoh: 100000.</p>
                            <?= $pesan('biaya_beregu') ?>
                        </div>
                    </div>

                    <div id="bagian-mix" class="mt-5"<?= $mix ? '' : ' hidden' ?>>
                        <p class="mb-1 text-sm font-semibold text-gray-900">Nomor mixed</p>
                        <p class="mb-3 text-sm text-gray-600">Jumlah putra dan putri paling sedikit di antara anggota inti satu regu. Jumlah keduanya tidak boleh lebih dari jumlah inti.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="jumlah_putra" class="<?= $label ?>">Putra minimal</label>
                                <div class="flex">
                                    <input type="text" inputmode="numeric" <?= $kotak('jumlah_putra', '', 'rounded-s-lg') ?> id="jumlah_putra" name="jumlah_putra"
                                        value="<?= esc($isian['jumlah_putra'], 'attr') ?>"<?= $tetap('jumlah_putra') ?><?= $jelas('jumlah_putra') !== '' ? ' aria-describedby="' . $jelas('jumlah_putra') . '"' : '' ?>>
                                    <?= $satuan('orang') ?>
                                </div>
                                <?= $pesan('jumlah_putra') ?>
                            </div>
                            <div>
                                <label for="jumlah_putri" class="<?= $label ?>">Putri minimal</label>
                                <div class="flex">
                                    <input type="text" inputmode="numeric" <?= $kotak('jumlah_putri', '', 'rounded-s-lg') ?> id="jumlah_putri" name="jumlah_putri"
                                        value="<?= esc($isian['jumlah_putri'], 'attr') ?>"<?= $tetap('jumlah_putri') ?><?= $jelas('jumlah_putri') !== '' ? ' aria-describedby="' . $jelas('jumlah_putri') . '"' : '' ?>>
                                    <?= $satuan('orang') ?>
                                </div>
                                <?= $pesan('jumlah_putri') ?>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2 sm:items-start">
                    <div>
                        <label for="urutan" class="<?= $label ?>">Urutan</label>
                        <input type="text" inputmode="numeric" <?= $kotak('urutan') ?> id="urutan" name="urutan"
                            value="<?= esc($isian['urutan'], 'attr') ?>" aria-describedby="<?= $jelas('urutan', 'urutan-bantuan') ?>">
                        <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu. Kosongkan untuk menaruhnya paling akhir di kelompok umurnya.</p>
                        <?= $pesan('urutan') ?>
                    </div>
                    <div class="sm:pt-6">
                        <?= komponen('sakelar', ['label' => 'Aktif: muncul di pendaftaran', 'nama' => 'aktif', 'centang' => $isian['aktif'] === '1']) ?>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= komponen('tombol', ['label' => 'Simpan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                    <?= komponen('tombol', ['label' => 'Batal', 'href' => $kembali, 'varian' => 'kedua', 'ikon' => 'x']) ?>
                </div>
            </form>
        </section>

        <?php if (! $baru && ! $dipakai) : ?>
            <section class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-200 bg-white p-5 shadow-lembut sm:p-6" id="bagian-hapus" aria-labelledby="judul-bagian-hapus">
                <div>
                    <h2 id="judul-bagian-hapus" class="text-base font-bold text-gray-900">Hapus nomor ini</h2>
                    <p class="mt-0.5 text-sm text-gray-600">Bisa dihapus karena belum dipakai atlet atau tim.</p>
                </div>
                <form method="post" action="<?= site_url($alamat . '/' . (int) $nomor['id'] . '/hapus') ?>"
                    data-konfirmasi="Nomor <?= esc(NomorLombaModel::namaLengkap($nomor) . ' di ' . ($kelompokUmur[(int) $nomor['kelompok_umur_id']]['nama'] ?? ''), 'attr') ?> akan dihapus dan tidak bisa dikembalikan."
                    data-konfirmasi-tombol="Hapus nomor">
                    <?= csrf_field() ?>
                    <?= komponen('tombol', ['label' => 'Hapus', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'trash-2']) ?>
                </form>
            </section>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/admin-nomor-lomba-formulir.js') ?>"></script>
<?= $this->endSection() ?>
