<?php
/**
 * Pengaturan > Cadangan (F11-11, K174, `Admin\Pengaturan\CadanganController`):
 * unduh cadangan lengkap, salinan cadangan harian ke Cloudflare R2 (F11-19,
 * K185: pengaturan, periksa sambungan, isi folder di bucket), impor cadangan
 * yang mengganti semua data dan pengaturan, daftar cadangan otomatis sebelum
 * impor, dan riwayat cadangan. Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View                                                      $this
 * @var array<string, string>                                                      $galat    pesan per kotak formulir impor
 * @var int                                                                        $batas    batas unggah server (bita), 0 = tidak diketahui
 * @var list<array{nama: string, ukuran: int, waktu: int}>                         $otomatis cadangan otomatis, terbaru di atas
 * @var list<array{nama_admin: string, label: string, ringkasan: string, dibuat_pada: string|null}> $riwayat
 * @var array{pendaftaran: int, atlet: int}                                        $isi      data sekarang
 * @var array{id_akun: string, bucket: string, kunci_id: string, folder: string, kirim_waktu: string|null, kirim_berhasil: bool|null, kirim_pesan: string|null, diubah_pada: string|null}|null $r2 pengaturan salinan R2
 * @var array<string, string>                                                      $galatR2  pesan per kotak formulir R2, `r2` = pesan umum
 * @var array{id_akun: string, bucket: string, kunci_id: string, folder: string}   $isianR2
 * @var array{berkas: list<array{nama: string, ukuran: int, waktu: int, unduh: string}>, terpotong: bool}|null $isiR2 isi folder di bucket
 * @var string|null                                                                $galatIsiR2
 */

use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Cadangan\SalinanR2;
use App\Libraries\Unggahan;

$kartu    = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
$label    = 'mb-1.5 block text-sm font-semibold text-gray-900';
$petunjuk = 'mt-1.5 text-sm text-gray-600';
$pesan    = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$kelasKotak = static fn (string $kotak): string => isset($galat[$kotak])
    ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
    : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100';
$uraian = static fn (string $kotak): string => $kotak . '-petunjuk' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '');
$batasTeks = $batas > 0 ? Unggahan::ukuranTeks($batas) : null;

// Formulir salinan R2 memakai pesan galatnya sendiri.
$pesanR2 = static fn (string $kotak): string => isset($galatR2[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galatR2[$kotak]) . '</p>'
    : '';
$kelasR2 = static fn (string $kotak): string => isset($galatR2[$kotak])
    ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
    : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100';
$uraianR2 = static fn (string $kotak): string => $kotak . '-petunjuk' . (isset($galatR2[$kotak]) ? ' ' . $kotak . '-galat' : '');
$kotakR2  = 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:max-w-md sm:text-sm';
$bukaFormR2 = $r2 === null || array_diff(array_keys($galatR2), ['sandi_hapus_r2']) !== [];
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Cadangan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Cadangan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Satu berkas cadangan berisi semua data web ini: pendaftaran, atlet, atlet yang diganti dan ditolak, hasil lomba, babak, seri, riwayat tindakan admin,
        semua pengaturan, serta semua foto, bukti bayar, dan berkas unggahan. Akun admin dan kata sandinya tidak ikut.
    </p>

    <section class="<?= $kartu ?>" aria-labelledby="judul-unduh-cadangan">
        <h2 id="judul-unduh-cadangan" class="mb-1 text-lg font-bold text-gray-900">Unduh cadangan</h2>
        <p class="mb-3 text-sm text-gray-600">
            Sekarang ada <?= number_format($isi['pendaftaran'], 0, ',', '.') ?> pendaftaran dan <?= number_format($isi['atlet'], 0, ',', '.') ?> atlet.
            Berkas berisi data pribadi siswa, jadi simpan di tempat yang aman dan jangan dibagikan.
            Berkas ini juga bisa dimasukkan ke server baru yang masih kosong dengan perintah <code class="rounded bg-gray-100 px-1">php spark pendaftaran:impor</code>.
        </p>
        <form action="<?= site_url('admin/pengaturan/cadangan/unduh') ?>" method="post">
            <?= csrf_field() ?>
            <?= komponen('tombol', ['label' => 'Unduh cadangan', 'tipe' => 'submit', 'ikon' => 'download', 'atribut' => ['id' => 'unduh-cadangan']]) ?>
        </form>
    </section>

    <section id="salinan-r2" class="<?= $kartu ?>" aria-labelledby="judul-salinan-r2">
        <h2 id="judul-salinan-r2" class="mb-1 text-lg font-bold text-gray-900">Salinan di Cloudflare R2</h2>
        <p class="mb-3 text-sm text-gray-600">
            Cadangan harian yang dibuat server (<code class="rounded bg-gray-100 px-1">php spark cadangan:buat</code>) ikut dikirim ke bucket Cloudflare R2 panitia,
            jadi tetap ada walau server rusak. Isi folder di bawah menunjukkan cadangan yang sudah sampai.
        </p>

        <?php if (isset($galatR2['r2'])) : ?>
            <?= komponen('peringatan', ['jenis' => 'bahaya', 'isi' => esc($galatR2['r2']), 'kelas' => 'mb-4']) ?>
        <?php endif ?>

        <?php if ($r2 === null) : ?>
            <p class="mb-3 text-sm text-gray-700" data-r2-belum>Belum diatur. Cadangan harian hanya tersimpan di server.</p>
        <?php else : ?>
            <dl class="mb-4 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-[max-content_1fr]" data-r2-status>
                <dt class="font-semibold text-gray-900">Bucket</dt>
                <dd class="break-all text-gray-700"><?= esc($r2['bucket']) ?></dd>
                <dt class="font-semibold text-gray-900">Folder</dt>
                <dd class="break-all text-gray-700"><?= esc($r2['folder']) ?>/</dd>
                <dt class="font-semibold text-gray-900">Kiriman terakhir</dt>
                <dd class="break-words text-gray-700" data-r2-kiriman>
                    <?php if ($r2['kirim_waktu'] === null) : ?>
                        Belum ada. Cadangan harian berikutnya akan dikirim.
                    <?php else : ?>
                        <span class="font-semibold <?= $r2['kirim_berhasil'] ? 'text-green-800' : 'text-red-700' ?>"><?= $r2['kirim_berhasil'] ? 'Berhasil' : 'Gagal' ?></span>,
                        <?= esc(date('d-m-Y H:i', (int) strtotime($r2['kirim_waktu']))) ?> · <?= esc((string) $r2['kirim_pesan']) ?>
                    <?php endif ?>
                </dd>
            </dl>

            <form action="<?= site_url('admin/pengaturan/cadangan/r2/periksa') ?>" method="post" class="mb-4">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Periksa sambungan', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'rotate-ccw', 'atribut' => ['id' => 'periksa-r2']]) ?>
            </form>

            <h3 class="mb-2 font-semibold text-gray-900">Isi folder <?= esc($r2['folder']) ?>/</h3>
            <?php if ($galatIsiR2 !== null) : ?>
                <?= komponen('peringatan', ['jenis' => 'peringatan', 'judul' => 'Isi bucket tidak bisa dibaca', 'isi' => esc($galatIsiR2), 'kelas' => 'mb-4']) ?>
            <?php elseif ($isiR2 === null || $isiR2['berkas'] === []) : ?>
                <p class="mb-4 text-sm text-gray-600" data-r2-kosong>Folder ini masih kosong.</p>
            <?php else : ?>
                <ul class="mb-2 divide-y divide-gray-200" data-r2-isi>
                    <?php foreach ($isiR2['berkas'] as $objek) : ?>
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span class="min-w-0 break-all text-sm">
                                <span class="font-semibold text-gray-900"><?= esc(date('d-m-Y H:i', $objek['waktu'])) ?></span>
                                <span class="text-gray-600">· <?= esc(Unggahan::ukuranTeks($objek['ukuran'])) ?> · <?= esc($objek['nama']) ?></span>
                            </span>
                            <?= komponen('tombol', ['label' => 'Unduh', 'href' => $objek['unduh'], 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'download', 'kelas' => 'min-h-11', 'atribut' => ['rel' => 'noopener noreferrer', 'aria-label' => 'Unduh dari R2 ' . $objek['nama']]]) ?>
                        </li>
                    <?php endforeach ?>
                </ul>
                <p class="mb-4 text-sm text-gray-600">
                    <?= count($isiR2['berkas']) ?> berkas<?= $isiR2['terpotong'] ? ' (hanya 1.000 berkas pertama yang tampil)' : '' ?>.
                    Tombol Unduh berlaku <?= SalinanR2::LAMA_UNDUH / 60 ?> menit; muat ulang halaman bila sudah lewat.
                </p>
            <?php endif ?>
        <?php endif ?>

        <details class="mt-2"<?= $bukaFormR2 ? ' open' : '' ?> data-r2-formulir>
            <summary class="min-h-11 cursor-pointer py-2 font-semibold text-primary-700"><?= $r2 === null ? 'Atur salinan R2' : 'Ubah pengaturan R2' ?></summary>

            <div class="mt-2 space-y-2 text-sm text-gray-700">
                <p>Siapkan dulu di dasbor Cloudflare, bagian <strong>R2</strong>:</p>
                <ol class="list-decimal space-y-1 ps-5">
                    <li>Bucket untuk cadangan dengan akses publik mati.</li>
                    <li>Token di <strong>Manage API tokens</strong> dengan izin <strong>Object Read &amp; Write</strong> hanya untuk bucket itu. Salin Access Key ID dan Secret Access Key yang tampil sekali saja.</li>
                    <li>Aturan hapus otomatis di bucket (<strong>Object lifecycle rules</strong>), misalnya 90 hari. Web ini tidak pernah menghapus berkas di bucket.</li>
                </ol>
            </div>

            <form action="<?= site_url('admin/pengaturan/cadangan/r2') ?>" method="post" novalidate class="mt-4 space-y-4" autocomplete="off">
                <?= csrf_field() ?>

                <div>
                    <label for="r2-id-akun" class="<?= $label ?>">ID akun Cloudflare</label>
                    <input type="text" id="r2-id-akun" name="id_akun" value="<?= esc($isianR2['id_akun']) ?>" maxlength="200" required spellcheck="false"
                        class="<?= $kotakR2 ?> <?= $kelasR2('id_akun') ?>"<?= isset($galatR2['id_akun']) ? ' aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('id_akun') ?>">
                    <?= $pesanR2('id_akun') ?>
                    <p id="id_akun-petunjuk" class="<?= $petunjuk ?>">32 huruf dan angka di halaman R2. Boleh juga tempel alamat S3 API bucket.</p>
                </div>

                <div>
                    <label for="r2-bucket" class="<?= $label ?>">Nama bucket</label>
                    <input type="text" id="r2-bucket" name="bucket" value="<?= esc($isianR2['bucket']) ?>" maxlength="63" required spellcheck="false"
                        class="<?= $kotakR2 ?> <?= $kelasR2('bucket') ?>"<?= isset($galatR2['bucket']) ? ' aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('bucket') ?>">
                    <?= $pesanR2('bucket') ?>
                    <p id="bucket-petunjuk" class="<?= $petunjuk ?>">Persis seperti di daftar bucket.</p>
                </div>

                <div>
                    <label for="r2-kunci-id" class="<?= $label ?>">Access Key ID</label>
                    <input type="text" id="r2-kunci-id" name="kunci_id" value="<?= esc($isianR2['kunci_id']) ?>" maxlength="128" required spellcheck="false"
                        class="<?= $kotakR2 ?> <?= $kelasR2('kunci_id') ?>"<?= isset($galatR2['kunci_id']) ? ' aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('kunci_id') ?>">
                    <?= $pesanR2('kunci_id') ?>
                    <p id="kunci_id-petunjuk" class="<?= $petunjuk ?>">Dari halaman token R2.</p>
                </div>

                <div>
                    <label for="r2-rahasia" class="<?= $label ?>">Secret Access Key</label>
                    <input type="password" id="r2-rahasia" name="rahasia" maxlength="128" autocomplete="new-password" spellcheck="false"<?= $r2 === null ? ' required' : '' ?>
                        <?= $r2 === null ? '' : 'placeholder="Tersimpan. Kosongkan bila tidak diganti."' ?>
                        class="<?= $kotakR2 ?> <?= $kelasR2('rahasia') ?>"<?= isset($galatR2['rahasia']) ? ' aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('rahasia') ?>">
                    <?= $pesanR2('rahasia') ?>
                    <p id="rahasia-petunjuk" class="<?= $petunjuk ?>">Disimpan terenkripsi dan tidak pernah ditampilkan lagi.<?= $r2 === null ? '' : ' Kosongkan bila tidak diganti.' ?></p>
                </div>

                <div>
                    <label for="r2-folder" class="<?= $label ?>">Folder di bucket</label>
                    <input type="text" id="r2-folder" name="folder" value="<?= esc($isianR2['folder']) ?>" maxlength="63" spellcheck="false"
                        class="<?= $kotakR2 ?> <?= $kelasR2('folder') ?>"<?= isset($galatR2['folder']) ? ' aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('folder') ?>">
                    <?= $pesanR2('folder') ?>
                    <p id="folder-petunjuk" class="<?= $petunjuk ?>">Bawaannya nama alamat web ini (<?= esc(SalinanR2::folderBawaan()) ?>), jadi staging dan produksi bisa memakai bucket yang sama tanpa tercampur.</p>
                </div>

                <div>
                    <label for="r2-sandi" class="<?= $label ?>">Kata sandi Anda</label>
                    <input type="password" id="r2-sandi" name="sandi" maxlength="255" autocomplete="current-password" required
                        class="block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:max-w-sm sm:text-sm <?= $kelasR2('sandi_r2') ?>"
                        <?= isset($galatR2['sandi_r2']) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('sandi_r2') ?>">
                    <?= $pesanR2('sandi_r2') ?>
                    <p id="sandi_r2-petunjuk" class="<?= $petunjuk ?>">Kata sandi yang Anda pakai untuk masuk ke panel ini.</p>
                </div>

                <p class="text-sm text-gray-600">Sebelum disimpan, web menulis berkas <?= esc(SalinanR2::BERKAS_UJI) ?> ke folder itu dan membaca isinya. Pengaturan hanya disimpan bila keduanya berhasil.</p>
                <?= komponen('tombol', ['label' => 'Periksa dan simpan', 'tipe' => 'submit', 'ikon' => 'save', 'atribut' => ['id' => 'simpan-r2']]) ?>
            </form>
        </details>

        <?php if ($r2 !== null) : ?>
            <details class="mt-2"<?= isset($galatR2['sandi_hapus_r2']) ? ' open' : '' ?>>
                <summary class="min-h-11 cursor-pointer py-2 font-semibold text-primary-700">Matikan salinan R2</summary>
                <form action="<?= site_url('admin/pengaturan/cadangan/r2/hapus') ?>" method="post" novalidate class="mt-2 space-y-4"
                    data-konfirmasi="Cadangan harian berikutnya hanya tersimpan di server. Berkas yang sudah ada di bucket tidak dihapus."
                    data-konfirmasi-judul="Matikan salinan R2?"
                    data-konfirmasi-tombol="Matikan">
                    <?= csrf_field() ?>
                    <div>
                        <label for="r2-sandi-hapus" class="<?= $label ?>">Kata sandi Anda</label>
                        <input type="password" id="r2-sandi-hapus" name="sandi" maxlength="255" autocomplete="current-password" required
                            class="block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:max-w-sm sm:text-sm <?= $kelasR2('sandi_hapus_r2') ?>"
                            <?= isset($galatR2['sandi_hapus_r2']) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= $uraianR2('sandi_hapus_r2') ?>">
                        <?= $pesanR2('sandi_hapus_r2') ?>
                        <p id="sandi_hapus_r2-petunjuk" class="<?= $petunjuk ?>">Pengaturan dan token dihapus dari web ini. Berkas di bucket tetap ada.</p>
                    </div>
                    <?= komponen('tombol', ['label' => 'Matikan salinan', 'tipe' => 'submit', 'varian' => 'bahaya', 'atribut' => ['id' => 'hapus-r2']]) ?>
                </form>
            </details>
        <?php endif ?>
    </section>

    <section class="<?= $kartu ?>" aria-labelledby="judul-impor-cadangan">
        <h2 id="judul-impor-cadangan" class="mb-3 text-lg font-bold text-gray-900">Impor cadangan</h2>

        <?= komponen('peringatan', [
            'jenis' => 'peringatan',
            'judul' => 'Impor mengganti semua data dan pengaturan',
            'isi'   => esc('Semua pendaftaran, hasil, pengaturan, dan berkas unggahan di web ini diganti isi cadangan. Sebelum mengganti, web menyimpan cadangan data sekarang di server, di daftar cadangan otomatis di bawah.'),
            'kelas' => 'mb-4',
        ]) ?>

        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= site_url('admin/pengaturan/cadangan/impor') ?>" method="post" enctype="multipart/form-data" novalidate class="mt-4 space-y-4"
            data-konfirmasi="Semua data, pengaturan, dan berkas unggahan di web ini akan diganti isi cadangan. Data sekarang disimpan dulu sebagai cadangan otomatis di server."
            data-konfirmasi-judul="Ganti semua data?"
            data-konfirmasi-tombol="Impor dan ganti">
            <?= csrf_field() ?>

            <div>
                <label for="berkas" class="<?= $label ?>">Berkas cadangan (.zip)</label>
                <input type="file" id="berkas" name="berkas" accept=".zip,application/zip" required
                    class="block w-full cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 <?= $kelasKotak('berkas') ?>"
                    <?= $batas > 0 ? 'data-ukuran-maksimal="' . $batas . '"' : '' ?><?= isset($galat['berkas']) ? ' aria-invalid="true"' : '' ?>
                    aria-describedby="<?= $uraian('berkas') ?>">
                <?= $pesan('berkas') ?>
                <p id="berkas-petunjuk" class="<?= $petunjuk ?>">
                    Berkas yang diunduh dari halaman ini<?= $batasTeks !== null ? ', paling besar ' . esc($batasTeks) . ' (batas unggah server)' : '' ?>.
                    Impor bisa makan beberapa menit; jangan tutup halaman sampai selesai.
                </p>
            </div>

            <div>
                <label for="sandi" class="<?= $label ?>">Kata sandi Anda</label>
                <input type="password" id="sandi" name="sandi" maxlength="255" autocomplete="current-password" required
                    class="block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:max-w-sm sm:text-sm <?= $kelasKotak('sandi') ?>"
                    <?= isset($galat['sandi']) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= $uraian('sandi') ?>">
                <?= $pesan('sandi') ?>
                <p id="sandi-petunjuk" class="<?= $petunjuk ?>">Kata sandi yang Anda pakai untuk masuk ke panel ini, untuk memastikan impor memang Anda yang meminta.</p>
            </div>

            <?= komponen('tombol', ['label' => 'Impor dan ganti semua data', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'upload', 'atribut' => ['id' => 'impor-cadangan']]) ?>
        </form>

        <details class="mt-4 text-sm text-gray-700">
            <summary class="min-h-11 cursor-pointer py-2 font-semibold text-primary-700">Berkas terlalu besar untuk diunggah?</summary>
            <p class="mt-1">
                Minta pengelola server menyalin berkas cadangan ke server, lalu menjalankan perintah ini di folder web:
            </p>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-100 p-3 text-xs"><code>php spark cadangan:pulihkan &lt;jalur-berkas.zip&gt;</code></pre>
            <p class="mt-2">Hasilnya sama dengan impor di halaman ini, termasuk cadangan otomatis data sekarang, tanpa batas ukuran unggah dan batas waktu.</p>
        </details>
    </section>

    <section class="<?= $kartu ?>" aria-labelledby="judul-cadangan-otomatis">
        <h2 id="judul-cadangan-otomatis" class="mb-1 text-lg font-bold text-gray-900">Cadangan otomatis</h2>
        <p class="mb-3 text-sm text-gray-600">
            Dibuat setiap kali cadangan diimpor, berisi data sebelum diganti. Disimpan <?= CadanganLengkap::SIMPAN_OTOMATIS ?> yang terakhir.
            Bila impor ternyata keliru, unduh cadangan yang tepat di sini, lalu impor lagi.
        </p>
        <?php if ($otomatis === []) : ?>
            <p class="text-sm text-gray-600" data-cadangan-otomatis-kosong>Belum ada cadangan otomatis.</p>
        <?php else : ?>
            <ul class="divide-y divide-gray-200" data-cadangan-otomatis>
                <?php foreach ($otomatis as $butir) : ?>
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="min-w-0 break-all text-sm">
                            <span class="font-semibold text-gray-900"><?= esc(date('d-m-Y H:i', $butir['waktu'])) ?></span>
                            <span class="text-gray-600">· <?= esc(Unggahan::ukuranTeks($butir['ukuran'])) ?> · <?= esc($butir['nama']) ?></span>
                        </span>
                        <?= komponen('tombol', ['label' => 'Unduh', 'href' => site_url('admin/pengaturan/cadangan/' . $butir['nama'] . '/unduh'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'download', 'kelas' => 'min-h-11', 'atribut' => ['aria-label' => 'Unduh cadangan otomatis ' . date('d-m-Y H:i', $butir['waktu'])]]) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <section class="<?= $kartu ?>" aria-labelledby="judul-riwayat-cadangan">
        <h2 id="judul-riwayat-cadangan" class="mb-3 text-lg font-bold text-gray-900">Riwayat cadangan</h2>
        <?php if ($riwayat === []) : ?>
            <p class="text-sm text-gray-600">Belum ada cadangan yang diunduh atau diimpor, dan salinan R2 belum pernah diatur.</p>
        <?php else : ?>
            <ul class="divide-y divide-gray-200 text-sm" data-riwayat-cadangan>
                <?php foreach ($riwayat as $butir) : ?>
                    <li class="py-2">
                        <p class="font-semibold text-gray-900"><?= esc($butir['label']) ?> · <?= esc($butir['nama_admin']) ?> · <?= esc($butir['dibuat_pada'] === null ? '' : date('d-m-Y H:i', (int) strtotime($butir['dibuat_pada']))) ?></p>
                        <p class="break-words text-gray-700"><?= esc($butir['ringkasan']) ?></p>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
