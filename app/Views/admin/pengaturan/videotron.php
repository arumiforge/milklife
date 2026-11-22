<?php
/**
 * Pengaturan > Videotron (F9-13, F11-09, `Admin\Pengaturan\VideotronController`):
 * sakelar semua alamat videotron (`/layar` dan turunannya), lalu isi dan lama
 * tiap tampilan papan putar `/layar/putar`. Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View                               $this
 * @var array{tampil: bool, lama: string, isi: list<string>} $isian
 * @var array<string, string>                               $galat
 * @var array<string, string>                               $pilihanIsi kode => label (`PapanVideotron::ISI`)
 * @var string|null                                         $latar      jalur gambar latar unggahan (`PapanVideotron::latar()`)
 */

use App\Libraries\Unggahan;


$kartu = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Pengaturan videotron<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Videotron<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Tampilan hasil untuk videotron di arena. Operator videotron membuka daftar di /layar, lalu memilih satu tampilan:
        per seri, per sesi, klasemen, per kelompok umur, atau per nomor lomba. Tampilan itu tidak berganti sendiri.
        Hanya hasil yang sudah diterbitkan yang tampil.
    </p>

    <div class="space-y-4">
        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= site_url('admin/pengaturan/videotron') ?>" method="post" enctype="multipart/form-data" novalidate class="space-y-4">
            <?= csrf_field() ?>

            <section class="<?= $kartu ?>" aria-labelledby="judul-tampil-papan">
                <h2 id="judul-tampil-papan" class="mb-3 text-lg font-bold text-gray-900">Tampilkan videotron</h2>
                <label for="tampil" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                    <input type="checkbox" role="switch" id="tampil" name="tampil" value="1" class="peer sr-only"
                        aria-describedby="tampil-keterangan"<?= $isian['tampil'] ? ' checked' : '' ?>>
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                    <span class="text-sm font-semibold text-gray-900">Tampilkan videotron di /layar</span>
                </label>
                <p id="tampil-keterangan" class="mt-1 break-words text-sm text-gray-600">
                    Bila dimatikan, daftar /layar dan semua tampilannya tidak bisa dibuka.
                    <?php if ($isian['tampil']) : ?>
                        Kirim alamat daftar ini ke operator videotron: <a href="<?= site_url('layar') ?>" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline"><?= esc(site_url('layar')) ?></a>.
                    <?php endif ?>
                </p>
            </section>

            <section class="<?= $kartu ?>" aria-labelledby="judul-latar-videotron" data-latar-videotron>
                <h2 id="judul-latar-videotron" class="mb-1 text-lg font-bold text-gray-900">Gambar latar</h2>
                <p class="mb-3 text-sm text-gray-600">Dipakai semua tampilan videotron. Bila kosong, videotron memakai latar bawaan yang bergerak pelan.</p>
                <?php if ($latar !== null) : ?>
                    <div class="mb-3 aspect-video w-full max-w-sm overflow-hidden rounded-lg border border-gray-200 bg-primary-950">
                        <img src="<?= esc(base_url($latar), 'attr') ?>" alt="Gambar latar videotron saat ini" class="size-full object-cover">
                    </div>
                <?php endif ?>
                <label for="latar" class="mb-1 block text-sm font-semibold text-gray-900"><?= $latar === null ? 'Unggah gambar latar' : 'Ganti gambar latar' ?></label>
                <input type="file" class="block w-full max-w-md cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 <?= isset($galat['latar']) ? 'border-red-500 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' ?>" id="latar" name="latar"
                    accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"<?= isset($galat['latar']) ? ' aria-invalid="true"' : '' ?>
                    aria-describedby="latar-bantuan<?= isset($galat['latar']) ? ' latar-galat' : '' ?>">
                <?php if (isset($galat['latar'])) : ?>
                    <p id="latar-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['latar']) ?></p>
                <?php endif ?>
                <p id="latar-bantuan" class="mt-1.5 text-sm text-gray-600">JPG, PNG, atau WEBP, paling besar <?= esc(Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR)) ?>. Pilih gambar gelap dan tenang, misalnya 1920 × 1080 piksel, supaya tulisan putih tetap terbaca. Gambar tampil tanpa gerakan.</p>
                <?php if ($latar !== null) : ?>
                    <label class="mt-2 inline-flex min-h-11 items-center gap-2" for="hapus_latar">
                        <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" value="1" id="hapus_latar" name="hapus_latar"
                            data-konfirmasi-centang="Gambar latar dihapus dari server saat pengaturan disimpan, dan videotron kembali memakai latar bawaan." data-konfirmasi-tombol="Hapus gambar">
                        <span class="text-sm text-gray-900">Hapus gambar latar, kembali ke latar bawaan</span>
                    </label>
                <?php endif ?>
            </section>

            <h2 class="pt-2 text-lg font-bold text-gray-900">Papan putar</h2>
            <p class="text-sm text-gray-700">
                Papan yang berganti sendiri di <?php if ($isian['tampil']) : ?><a href="<?= site_url('layar/putar') ?>" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline">/layar/putar</a><?php else : ?>/layar/putar<?php endif ?>.
                Hanya dipakai bila panitia ingin videotron berjalan tanpa operator.
            </p>

            <section class="<?= $kartu ?>">
                <fieldset aria-describedby="isi-keterangan<?= isset($galat['isi']) ? ' isi-galat' : '' ?>">
                    <legend class="text-lg font-bold text-gray-900">Isi papan putar</legend>
                    <p id="isi-keterangan" class="mb-3 mt-1 text-sm text-gray-600">
                        Berganti sendiri dengan urutan ini. Isi yang belum punya data dilewati.
                    </p>
                    <div class="space-y-1">
                        <?php foreach ($pilihanIsi as $kode => $label) : ?>
                            <div>
                                <label class="inline-flex min-h-11 items-center gap-2" for="isi-<?= esc($kode, 'attr') ?>">
                                    <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" id="isi-<?= esc($kode, 'attr') ?>" name="isi[]" value="<?= esc($kode, 'attr') ?>"<?= isset($galat['isi']) ? ' aria-invalid="true"' : '' ?><?= in_array($kode, $isian['isi'], true) ? ' checked' : '' ?>>
                                    <span class="text-sm text-gray-900"><?= esc($label) ?></span>
                                </label>
                                <?php if (in_array($kode, ['jadwal', 'berlangsung'], true)) : ?>
                                    <span class="-mt-2 block ps-6 text-sm text-gray-600">Dari tanggal dan jam mulai babak di <a href="<?= site_url('admin/jadwal-pertandingan') ?>" class="font-semibold text-primary-700 underline">Jadwal pertandingan</a>.</span>
                                <?php endif ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($galat['isi'])) : ?>
                        <p id="isi-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['isi']) ?></p>
                    <?php endif ?>
                </fieldset>
            </section>

            <section class="<?= $kartu ?>" aria-labelledby="judul-lama-tampilan">
                <h2 id="judul-lama-tampilan" class="mb-4 text-lg font-bold text-gray-900">Lama tiap tampilan</h2>
                <label for="lama" class="mb-1.5 block text-sm font-semibold text-gray-900">Lama (detik)</label>
                <input type="number" class="block w-full max-w-40 rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm <?= isset($galat['lama']) ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100' ?>" id="lama" name="lama"
                    value="<?= esc($isian['lama'], 'attr') ?>" min="5" max="120" step="1" inputmode="numeric"<?= isset($galat['lama']) ? ' aria-invalid="true"' : '' ?>
                    aria-describedby="lama-keterangan<?= isset($galat['lama']) ? ' lama-galat' : '' ?>">
                <?php if (isset($galat['lama'])) : ?>
                    <p id="lama-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['lama']) ?></p>
                <?php endif ?>
                <p id="lama-keterangan" class="mt-1.5 text-sm text-gray-600">5 sampai 120 detik. Tabel yang panjang dibagi beberapa halaman. Tiap halaman tampil selama ini.</p>
            </section>

            <?= komponen('tombol', ['label' => 'Simpan pengaturan videotron', 'tipe' => 'submit', 'ikon' => 'check']) ?>
        </form>

        <details class="group rounded-2xl border border-gray-200 bg-white shadow-lembut">
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 p-5 font-semibold text-gray-900 sm:px-6 [&::-webkit-details-marker]:hidden">
                Bila tulisan di videotron terlalu besar atau terlalu kecil
                <?= ikon('chevron-down', 'size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180') ?>
            </summary>
            <div class="space-y-2 px-4 pb-4 text-sm text-gray-700 sm:px-5">
                <p>Tampilan menyesuaikan sendiri dengan ukuran layar. Bila perlu, tambahkan pengaturan di belakang alamat tampilan, misalnya <code>/layar/nomor/12</code>:</p>
                <ul class="list-disc space-y-1 ps-5 break-words">
                    <li><code>?skala=1.3</code>: huruf 30% lebih besar (0.5 sampai 3).</li>
                    <li><code>?baris=10</code>: 10 baris tabel per halaman.</li>
                    <li><code>?halaman=2</code>: halaman kedua tabel yang panjang (tampilan tetap).</li>
                    <li><code>?susunan=lebar</code>: paksa susunan <code>lebar</code> (pita LED), <code>datar</code>, <code>tegak</code>, atau <code>kecil</code>.</li>
                    <li>Bisa digabung: <code>?skala=1.2&amp;baris=8</code>.</li>
                </ul>
            </div>
        </details>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
