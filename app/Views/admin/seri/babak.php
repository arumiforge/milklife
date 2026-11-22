<?php
/**
 * Seri dan lintasan satu babak di panel admin (F9-08), tata letak Tailwind
 * `admin_tw`: bagi otomatis, aturan lolos Q/q ke babak berikutnya, geser
 * peserta antarseri dan lintasan (satu formulir untuk semua), dan kunci
 * daftar seri. Data dari `Admin\SeriController::babak()`.
 *
 * @var CodeIgniter\View\View                  $this
 * @var array<string, mixed>                   $babak       `SeriLintasan::babak()`
 * @var list<array<string, mixed>>             $peserta     `SeriLintasan::peserta()`
 * @var array<int, list<array<string, mixed>>> $perSeri     seri => peserta; 0 = belum dibagi
 * @var array{q: int, q_kecil: int, otomatis: bool, kapasitas: int} $lolos aturan lolos yang berlaku
 * @var array<string, string>                  $tanda       kunci => Q/q (hasil babak ini lengkap)
 * @var array{kurang: list<string>, lebih: list<string>}|null $selisih beda dengan yang lolos dari babak sebelumnya
 * @var bool                                   $daftarUlang sudah ada atlet yang ditandai hadir
 * @var array<int|string, string>              $galat       id hasil => pesan geser
 * @var array<int|string, mixed>               $isian       isian geser yang dikembalikan saat ada galat
 * @var array<string, string>                  $galatLolos  kotak => pesan
 */

use App\Libraries\Hasil\PembagiSeri;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\UnduhanSeri;

$nomor     = $babak['nomor'];
$aturan    = $babak['aturan'];
$lintasan  = $aturan['lintasan'];
$sebutan   = $lintasan ? 'Lintasan' : 'Urutan';
$beregu    = $nomor['jenis'] === 'beregu';
$jadwal    = UnduhanSeri::jadwal($babak);
$bisaBagi  = ! $babak['dikunci'] && ! $babak['ada_hasil'];
$pertama   = $babak['sebelum'] === null;
$teksLolos = SeriLintasan::teksLolos($lolos, (string) $nomor['jenis_hasil']);
$nilai     = static fn (array $p, string $kotak): string => is_string($isian[$p['id']][$kotak] ?? null) ? $isian[$p['id']][$kotak] : (string) ($p[$kotak] ?? '');
$kelasKotak = static fn (bool $salah): string => 'block min-h-11 w-16 rounded-lg border p-2 text-center text-base sm:text-sm focus:ring-4 '
    . ($salah ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100')
    . ' disabled:bg-gray-100 disabled:text-gray-500';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Seri <?= esc($babak['nama']) ?> <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <a href="<?= site_url('admin/seri/nomor/' . $nomor['id']) ?>" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline"><?= ikon('arrow-left', 'size-4') ?><?= esc($nomor['nama'] . ' – ' . $nomor['kelompok_umur']) ?></a>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-ringkas-babak">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="judul-ringkas-babak" class="text-lg font-bold break-words text-gray-900"><?= esc($babak['nama']) ?></h2>
                <p class="text-sm text-gray-600"><?= $jadwal === '' ? 'Jadwal menyusul' : esc($jadwal) ?> · <?= $babak['peserta'] ?> <?= $beregu ? 'regu' : 'peserta' ?><?= $babak['jumlah_seri'] === null ? '' : ' · ' . $babak['jumlah_seri'] . ($lintasan ? ' seri' : ' kelompok') ?></p>
            </div>
            <?= $babak['dikunci']
                ? komponen('lencana', ['label' => 'Dikunci', 'warna' => 'hijau', 'ikon' => 'lock'])
                : komponen('lencana', ['label' => 'Belum dikunci', 'warna' => 'kuning', 'ikon' => 'lock-open']) ?>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <?= komponen('tombol', ['label' => 'Isi hasil babak ini', 'href' => site_url('admin/hasil/babak/' . $babak['id']), 'varian' => 'kedua', 'ikon' => 'trophy']) ?>
            <?php if ($babak['dibagi'] > 0) : ?>
                <?= komponen('tombol', ['label' => 'Unduh PDF nomor ini', 'href' => site_url('admin/seri/nomor/' . $nomor['id'] . '/pdf'), 'varian' => 'kedua', 'ikon' => 'download']) ?>
            <?php endif ?>
        </div>
    </section>

    <?php if ($selisih !== null && ($selisih['kurang'] !== [] || $selisih['lebih'] !== [])) : ?>
        <div data-selisih-lolos>
            <?= komponen('peringatan', ['jenis' => 'peringatan', 'judul' => 'Peserta berbeda dari yang lolos', 'isi' => esc('Menurut hasil ' . $babak['sebelum']['nama'] . ' dan aturan lolosnya, ')
                . ($selisih['kurang'] === [] ? '' : esc('yang lolos tetapi belum ikut: ' . implode(', ', $selisih['kurang']) . '. '))
                . ($selisih['lebih'] === [] ? '' : esc('yang ikut tetapi tidak lolos: ' . implode(', ', $selisih['lebih']) . '. '))
                . esc($bisaBagi ? 'Tekan Bagi otomatis untuk mengisi ulang dari yang lolos, atau biarkan bila memang koreksi panitia.' : 'Biarkan bila memang koreksi panitia.')]) ?>
        </div>
    <?php endif ?>

    <section id="bagi-otomatis" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-bagi-otomatis">
        <h2 id="judul-bagi-otomatis" class="text-lg font-bold text-gray-900">Bagi otomatis</h2>
        <p class="mt-1 text-sm text-gray-600">
            <?php if ($pertama) : ?>
                Peserta diambil dari pendaftaran Lunas nomor ini, dibagi rata ke seri, peserta satu sekolah dipisah ke seri berbeda sebisanya, lalu <?= $lintasan ? 'lintasannya' : 'urutan tampilnya' ?> diacak.
            <?php else : ?>
                Bila hasil <?= esc($babak['sebelum']['nama']) ?> sudah lengkap, peserta diambil dari yang lolos (Q/q) dan diunggulkan dari prestasinya<?= $lintasan ? ' memakai urutan lintasan ' . esc($aturan['urutan']) : '' ?>. Bila belum, peserta yang ada diacak.
            <?php endif ?>
            Pembagian memakai benih acak yang disimpan, jadi bisa diulang persis.
        </p>
        <?php if ($babak['dikunci']) : ?>
            <p class="mt-3 text-sm font-semibold text-gray-800">Daftar seri sudah dikunci. Buka kuncinya di bawah bila perlu dibagi ulang.</p>
        <?php elseif ($babak['ada_hasil']) : ?>
            <p class="mt-3 text-sm font-semibold text-gray-800">Babak ini sudah berisi prestasi, jadi tidak bisa dibagi ulang. Geser peserta satu per satu bila perlu.</p>
        <?php else : ?>
            <form action="<?= site_url('admin/seri/babak/' . $babak['id'] . '/bagi') ?>" method="post" class="mt-4 space-y-4"<?= $babak['dibagi'] > 0 ? ' data-konfirmasi="' . esc('Pembagian seri ' . $babak['nama'] . ' sekarang akan diganti dengan pembagian baru.', 'attr') . '" data-konfirmasi-tombol="Bagi ulang" data-konfirmasi-jenis="utama"' : '' ?>>
                <?= csrf_field() ?>
                <?php if ($pertama) : ?>
                    <label class="flex min-h-11 items-center gap-3 text-sm font-semibold text-gray-900">
                        <input type="checkbox" name="hanya_hadir" value="1" class="size-5 rounded border-gray-300 text-primary-700 focus:ring-primary-300"<?= $daftarUlang ? ' checked' : '' ?>>
                        Hanya yang sudah hadir di daftar ulang
                    </label>
                <?php endif ?>
                <details class="rounded-lg border border-gray-200 p-3">
                    <summary class="min-h-11 cursor-pointer content-center text-sm font-semibold text-gray-900">Pilihan lanjutan</summary>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <?= komponen('isian', ['label' => 'Banyak seri', 'nama' => 'jumlah_seri', 'tipe' => 'number', 'bantuan' => 'Kosong = paling sedikit yang muat (' . ($lintasan ? $aturan['tempat'] . ' lintasan per seri' : ($aturan['tempat'] > 0 ? $aturan['tempat'] . ' per kelompok' : 'satu kelompok')) . ').', 'atribut' => ['min' => 1, 'max' => PembagiSeri::SERI_MAKSIMAL, 'inputmode' => 'numeric']]) ?>
                        <?= komponen('isian', ['label' => 'Benih acak', 'nama' => 'benih', 'tipe' => 'number', 'nilai' => '', 'bantuan' => 'Kosong = benih baru.' . ($babak['benih'] === null ? '' : ' Benih terakhir: ' . $babak['benih'] . ' (isi untuk mengulang pembagian yang sama).'), 'atribut' => ['min' => 1, 'inputmode' => 'numeric']]) ?>
                    </div>
                </details>
                <?= komponen('tombol', ['label' => $babak['dibagi'] > 0 ? 'Bagi ulang' : 'Bagi otomatis', 'tipe' => 'submit', 'ikon' => 'shuffle']) ?>
            </form>
        <?php endif ?>
    </section>

    <?php if ($babak['berikut'] !== null) : ?>
        <section id="aturan-lolos" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-aturan-lolos">
            <h2 id="judul-aturan-lolos" class="text-lg font-bold text-gray-900">Aturan lolos ke <?= esc($babak['berikut']['nama']) ?></h2>
            <p class="mt-1 text-sm text-gray-600">
                Q = sekian terbaik tiap seri, q = sekian prestasi terbaik berikutnya dari semua seri. Kosongkan keduanya supaya dihitung otomatis untuk mengisi penuh <?= esc($babak['berikut']['nama']) ?> (<?= $lolos['kapasitas'] ?> tempat).
                Sekarang: <strong data-lolos-berlaku><?= $teksLolos === '' ? 'tidak ada yang lolos' : esc($teksLolos) ?></strong><?= $lolos['otomatis'] ? ' (otomatis)' : '' ?>.
            </p>
            <form action="<?= site_url('admin/seri/babak/' . $babak['id'] . '/lolos') ?>" method="post" class="mt-4 grid grid-cols-2 gap-4 sm:max-w-md">
                <?= csrf_field() ?>
                <?= komponen('isian', ['label' => 'Q (terbaik tiap seri)', 'nama' => 'lolos_q', 'tipe' => 'number', 'nilai' => $babak['lolos_q'] === null ? '' : (string) $babak['lolos_q'], 'galat' => $galatLolos['lolos_q'] ?? null, 'atribut' => ['min' => 0, 'max' => 99, 'inputmode' => 'numeric', 'placeholder' => (string) $lolos['q']]]) ?>
                <?= komponen('isian', ['label' => 'q (terbaik berikutnya)', 'nama' => 'lolos_q_kecil', 'tipe' => 'number', 'nilai' => $babak['lolos_q_kecil'] === null ? '' : (string) $babak['lolos_q_kecil'], 'galat' => $galatLolos['lolos_q_kecil'] ?? null, 'atribut' => ['min' => 0, 'max' => 99, 'inputmode' => 'numeric', 'placeholder' => (string) $lolos['q_kecil']]]) ?>
                <div class="col-span-2"><?= komponen('tombol', ['label' => 'Simpan aturan lolos', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'save']) ?></div>
            </form>
        </section>
    <?php endif ?>

    <form id="daftar-seri" action="<?= site_url('admin/seri/babak/' . $babak['id'] . '/geser') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <section class="rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-daftar-seri">
            <header class="border-b border-gray-200 p-4 sm:p-5">
                <h2 id="judul-daftar-seri" class="text-lg font-bold text-gray-900"><?= $lintasan ? 'Seri dan lintasan' : 'Kelompok dan urutan tampil' ?></h2>
                <p class="text-sm text-gray-600">
                    <?= $babak['dikunci'] ? 'Daftar ini sudah dikunci.' : 'Geser peserta dengan mengubah angka seri dan ' . mb_strtolower($sebutan) . ', lalu simpan. Kosongkan keduanya untuk mengeluarkan peserta dari seri.' ?>
                    <?php if ($tanda !== []) : ?> Tanda Q/q = lolos ke <?= esc($babak['berikut']['nama']) ?>.<?php endif ?>
                </p>
            </header>

            <?php if ($peserta === []) : ?>
                <div class="p-4 sm:p-5"><?= komponen('kosong', ['padat' => true, 'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'], 'ikon' => 'list-ordered', 'judul' => 'Belum ada peserta', 'teks' => $pertama ? 'Tekan Bagi otomatis untuk mengambil peserta Lunas nomor ini.' : 'Peserta masuk sendiri dari yang lolos setelah hasil ' . $babak['sebelum']['nama'] . ' lengkap.']) ?></div>
            <?php endif ?>

            <?php foreach ($perSeri as $seri => $daftar) : ?>
                <div class="border-b border-gray-100 last:border-b-0" data-seri-admin="<?= $seri ?>">
                    <h3 class="bg-gray-50 px-4 py-2 text-sm font-bold tracking-wide text-oranye-700 uppercase sm:px-5"><?= $seri === 0 ? 'Belum dibagi' : ($lintasan ? 'Seri ' . $seri : 'Kelompok ' . $seri) ?> <span class="font-semibold text-gray-600 normal-case">(<?= count($daftar) ?>)</span></h3>
                    <ul class="divide-y divide-gray-100">
                        <?php foreach ($daftar as $p) : ?>
                            <?php $g = $galat[$p['id']] ?? null; $t = $tanda[$p['kunci']] ?? null; $awal = 'posisi-' . $p['id']; ?>
                            <li class="flex flex-wrap items-center gap-3 px-4 py-2.5 sm:px-5<?= $t !== null ? ' bg-green-50' : '' ?>" data-baris-seri="<?= $p['id'] ?>">
                                <div class="min-w-0 flex-1 basis-48">
                                    <p class="font-semibold break-words text-gray-900">
                                        <?php if (! $beregu && ($p['nomor_dada'] ?? '') !== '') : ?><span class="text-gray-600"><?= esc($p['nomor_dada']) ?></span> · <?php endif ?><?= esc($p['nama']) ?>
                                        <?php if ($t !== null) : ?><span class="ms-1 rounded bg-green-700 px-1.5 py-0.5 text-xs font-bold text-white" data-tanda-lolos="<?= $t ?>"><?= $t ?></span><?php endif ?>
                                    </p>
                                    <p class="text-sm break-words text-gray-600"><?= esc($p['sekolah']) ?><?= $p['prestasi_teks'] !== '' ? ' · ' . esc($p['prestasi_teks']) : '' ?><?= $p['keterangan'] !== null ? ' · ' . esc($p['keterangan']) : '' ?></p>
                                    <?php if ($g !== null) : ?><p id="<?= $awal ?>-galat" class="mt-1 text-sm text-red-700"><?= esc($g) ?></p><?php endif ?>
                                </div>
                                <div class="flex items-end gap-2">
                                    <div>
                                        <label for="<?= $awal ?>-seri" class="block text-xs font-semibold text-gray-700"><?= $lintasan ? 'Seri' : 'Kelompok' ?></label>
                                        <input type="number" id="<?= $awal ?>-seri" name="posisi[<?= $p['id'] ?>][seri]" value="<?= esc($nilai($p, 'seri'), 'attr') ?>" min="1" max="<?= PembagiSeri::SERI_MAKSIMAL ?>" inputmode="numeric" class="<?= $kelasKotak($g !== null) ?>"<?= $babak['dikunci'] ? ' disabled' : '' ?><?= $g !== null ? ' aria-invalid="true" aria-describedby="' . $awal . '-galat"' : '' ?>>
                                    </div>
                                    <div>
                                        <label for="<?= $awal ?>-lintasan" class="block text-xs font-semibold text-gray-700"><?= $sebutan ?></label>
                                        <input type="number" id="<?= $awal ?>-lintasan" name="posisi[<?= $p['id'] ?>][lintasan]" value="<?= esc($nilai($p, 'lintasan'), 'attr') ?>" min="1" max="<?= $lintasan ? $aturan['tempat'] : PembagiSeri::KELOMPOK_MAKSIMAL ?>" inputmode="numeric" class="<?= $kelasKotak($g !== null) ?>"<?= $babak['dikunci'] ? ' disabled' : '' ?><?= $g !== null ? ' aria-invalid="true" aria-describedby="' . $awal . '-galat"' : '' ?>>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endforeach ?>

            <?php if ($peserta !== [] && ! $babak['dikunci']) : ?>
                <footer class="sticky bottom-16 border-t border-gray-200 bg-white/95 p-4 backdrop-blur sm:px-6 lg:bottom-0">
                    <?= komponen('tombol', ['label' => 'Simpan seri dan ' . mb_strtolower($sebutan), 'tipe' => 'submit', 'ikon' => 'save']) ?>
                </footer>
            <?php endif ?>
        </section>
    </form>

    <section id="kunci-seri" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-kunci-seri">
        <h2 id="judul-kunci-seri" class="text-lg font-bold text-gray-900">Kunci daftar seri</h2>
        <?php if ($babak['dikunci']) : ?>
            <p class="mt-1 text-sm text-gray-600">Dikunci sejak <?= esc(tanggal_id(new DateTimeImmutable((string) $babak['dikunci_pada'])) . ' ' . substr((string) $babak['dikunci_pada'], 11, 5)) ?>. Daftar ini yang dicetak untuk juri dan tampil untuk umum bila sakelarnya menyala. Buka kunci hanya bila ada perubahan dari panitia.</p>
            <form action="<?= site_url('admin/seri/babak/' . $babak['id'] . '/buka') ?>" method="post" class="mt-3" data-konfirmasi="<?= esc('Kunci daftar seri ' . $babak['nama'] . ' dibuka. Daftar bisa diubah lagi dan sementara tidak tampil untuk umum.', 'attr') ?>" data-konfirmasi-tombol="Buka kunci" data-konfirmasi-jenis="utama">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Buka kunci', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'lock-open']) ?>
            </form>
        <?php else : ?>
            <p class="mt-1 text-sm text-gray-600">Setelah diperiksa, kunci daftarnya supaya tidak berubah lagi. Daftar yang dikunci tampil untuk umum (bila sakelarnya menyala) dan dipakai juri.</p>
            <form action="<?= site_url('admin/seri/babak/' . $babak['id'] . '/kunci') ?>" method="post" class="mt-3" data-konfirmasi="<?= esc('Daftar seri ' . $babak['nama'] . ' dikunci: tidak bisa diubah lagi sebelum kuncinya dibuka, dipakai juri, dan tampil untuk umum bila sakelarnya menyala.', 'attr') ?>" data-konfirmasi-judul="Kunci daftar seri?" data-konfirmasi-tombol="Kunci daftar seri" data-konfirmasi-jenis="utama">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Kunci daftar seri', 'tipe' => 'submit', 'ikon' => 'lock', 'nonaktif' => $babak['peserta'] === 0]) ?>
            </form>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
