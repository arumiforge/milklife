<?php
/**
 * Hasil satu nomor lomba di panel admin (F8-04, K124), tata letak Tailwind
 * `admin_tw`: semua babak dan hasilnya, tambah babak, Terbitkan/Tarik
 * kembali, unduh PDF dan Excel. Data dari `HasilController::nomor()`.
 *
 * Tabel tiap babak dari `hasil/_tabel_tw.php` (sama dengan halaman publik)
 * dengan tanda "diubah admin" pada peringkat yang dikoreksi.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak `HasilLomba::semuaBabak()`
 * @var int                        $calon banyak calon peserta (atlet atau regu Lunas)
 * @var array<string, string>      $galat galat formulir tambah babak
 * @var array<string, mixed>       $isian isian formulir tambah babak yang dikembalikan
 */

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JenisHasil;

$beregu   = $nomor['jenis'] === 'beregu';
$adaFinal = in_array(true, array_column($babak, 'final'), true);
$isi      = (string) ($isian['isi'] ?? ($babak === [] ? 'semua' : 'lolos'));
$kelas    = static fn (string $kotak): string => 'block w-full rounded-lg border p-2.5 text-base sm:text-sm focus:ring-4 '
    . (isset($galat[$kotak]) ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$tanda    = static fn (string $kotak, string $bantuan = ''): string => isset($galat[$kotak])
    ? ' aria-invalid="true" aria-describedby="' . trim($bantuan . ' ' . $kotak . '-galat') . '"'
    : ($bantuan === '' ? '' : ' aria-describedby="' . $bantuan . '"');
$pesan    = static fn (string $kotak): string => isset($galat[$kotak]) ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>' : '';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Hasil <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Hasil lomba', 'alamat' => 'admin/hasil'], ['label' => $nomor['kelompok_umur'] . ' ' . $nomor['nama']]]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <div class="flex flex-wrap items-start gap-4">
        <div class="min-w-0 flex-1 basis-72">
            <p class="text-gray-700">
                <?= esc($nomor['kelompok_umur']) ?> · <?= $beregu ? 'Beregu' : 'Individu' ?> · Jenis hasil <strong><?= esc(JenisHasil::LABEL[$nomor['jenis_hasil']]) ?></strong> (<?= esc(JenisHasil::KETERANGAN[$nomor['jenis_hasil']]) ?>)
            </p>
            <p class="text-sm text-gray-600"><?= $calon ?> <?= $beregu ? 'regu' : 'atlet' ?> dari pendaftaran Lunas. Jenis hasil diubah di Pengaturan, Nomor Lomba.</p>
        </div>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Unduh hasil">
            <?= komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url('admin/hasil/nomor/' . $nomor['id'] . '/pdf'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-text', 'kelas' => 'min-h-11']) ?>
            <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/hasil/nomor/' . $nomor['id'] . '/excel'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-spreadsheet', 'kelas' => 'min-h-11']) ?>
        </div>
    </div>

    <section class="flex flex-wrap items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="terbit" aria-labelledby="judul-terbit">
        <div class="min-w-0 flex-1 basis-64">
            <h2 class="text-lg font-bold text-gray-900" id="judul-terbit">Halaman publik</h2>
            <?php if ($nomor['terbit_pada'] !== null) : ?>
                <p class="mt-1 text-sm text-gray-700"><?= komponen('lencana', ['label' => 'Terbit', 'warna' => 'hijau']) ?> sejak <?= esc(tanggal_id($nomor['terbit_pada'], true)) ?>. Perubahan hasil langsung tampil di halaman publik.</p>
            <?php else : ?>
                <p class="mt-1 text-sm text-gray-700"><?= komponen('lencana', ['label' => 'Belum terbit', 'warna' => 'abu']) ?> Hasil nomor ini belum tampil di halaman publik dan belum dihitung di klasemen publik.</p>
            <?php endif ?>
        </div>
        <?php if ($nomor['terbit_pada'] !== null) : ?>
            <form action="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/tarik') ?>" method="post"
                data-konfirmasi="<?= esc('Hasil ' . $nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' akan hilang dari halaman publik dan klasemen publik sampai diterbitkan lagi.', 'attr') ?>"
                data-konfirmasi-judul="Tarik kembali hasil?" data-konfirmasi-tombol="Tarik kembali">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Tarik kembali', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'eye-off']) ?>
            </form>
        <?php else : ?>
            <form action="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/terbitkan') ?>" method="post"
                data-konfirmasi="<?= esc('Hasil semua babak ' . $nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' akan tampil di halaman publik dan juaranya dihitung di klasemen publik.' . ($adaFinal ? '' : ' Nomor ini belum punya babak Final.'), 'attr') ?>"
                data-konfirmasi-judul="Terbitkan hasil?" data-konfirmasi-tombol="Terbitkan" data-konfirmasi-jenis="utama">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Terbitkan', 'tipe' => 'submit', 'ikon' => 'megaphone']) ?>
            </form>
        <?php endif ?>
    </section>

    <?php if ($babak === []) : ?>
        <p class="text-gray-600" id="babak-kosong">Belum ada babak. Tambahkan di bawah; nomor yang langsung final cukup satu babak Final.</p>
    <?php endif ?>

    <?php foreach ($babak as $b) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-6" aria-labelledby="judul-babak-<?= $b['id'] ?>">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="me-auto flex min-w-0 flex-wrap items-center gap-2 text-lg font-bold break-words text-gray-900" id="judul-babak-<?= $b['id'] ?>">
                    <?= esc($b['nama']) ?>
                    <?php if ($b['final']) : ?><?= komponen('lencana', ['label' => 'Final', 'warna' => 'biru']) ?><?php endif ?>
                </h2>
                <?= komponen('tombol', ['label' => 'Isi hasil', 'href' => site_url('admin/hasil/babak/' . $b['id']), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'square-pen', 'kelas' => 'min-h-11']) ?>
            </div>
            <div class="mt-4"><?= view('hasil/_tabel_tw', ['nomor' => $nomor, 'babak' => $b, 'admin' => true], ['saveData' => false]) ?></div>
        </section>
    <?php endforeach ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="tambah-babak" aria-labelledby="judul-tambah-babak">
        <h2 class="text-lg font-bold text-gray-900" id="judul-tambah-babak">Tambah babak</h2>
        <form action="<?= site_url('admin/hasil/nomor/' . $nomor['id'] . '/babak') ?>" method="post" class="mt-4 space-y-5" novalidate>
            <?= csrf_field() ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="nama" class="mb-1.5 block text-sm font-semibold text-gray-900">Nama babak</label>
                    <input type="text" class="<?= $kelas('nama') ?>" id="nama" name="nama" maxlength="<?= HasilLomba::NAMA_BABAK_MAKSIMAL ?>"
                        value="<?= esc((string) ($isian['nama'] ?? ($adaFinal ? '' : ($babak === [] ? 'Final' : ''))), 'attr') ?>"<?= $tanda('nama', 'nama-bantuan') ?>>
                    <p id="nama-bantuan" class="mt-1.5 text-sm text-gray-600">Contoh: Penyisihan seri 1, Semifinal, Final.</p>
                    <?= $pesan('nama') ?>
                </div>
                <div class="sm:pt-7">
                    <label for="final" class="inline-flex min-h-11 cursor-pointer items-center gap-3<?= $adaFinal ? ' cursor-not-allowed opacity-60' : '' ?>">
                        <input type="checkbox" role="switch" id="final" name="final" value="1" class="peer sr-only"<?= ($isian['final'] ?? (! $adaFinal && $babak === [])) ? ' checked' : '' ?><?= $adaFinal ? ' disabled' : '' ?><?= $tanda('final', $adaFinal ? 'final-bantuan' : '') ?>>
                        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                        <span class="text-sm font-semibold text-gray-900">Babak Final: juara I–III diambil dari sini</span>
                    </label>
                    <?php if ($adaFinal) : ?><p id="final-bantuan" class="text-sm text-gray-600">Nomor ini sudah punya Final.</p><?php endif ?>
                    <?= $pesan('final') ?>
                </div>
            </div>
            <fieldset>
                <legend class="mb-1.5 block text-sm font-semibold text-gray-900">Peserta</legend>
                <div class="flex flex-col">
                    <?php foreach (HasilLomba::ISI_BABAK as $kode => $label) : ?>
                        <?php if ($kode === 'lolos' && $babak === []) {
                            continue;
                        } ?>
                        <label class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-900" for="isi-<?= $kode ?>">
                            <input class="size-4 border-gray-300 text-primary-700 focus:ring-primary-300" type="radio" name="isi" id="isi-<?= $kode ?>" value="<?= $kode ?>"<?= $isi === $kode ? ' checked' : '' ?>>
                            <?= esc($label) ?><?= $kode === 'semua' ? ' (' . $calon . ($beregu ? ' regu' : ' atlet') . ')' : '' ?>
                        </label>
                    <?php endforeach ?>
                </div>
                <?= $pesan('isi') ?>
            </fieldset>
            <?php if ($babak !== []) : ?>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="dari_babak" class="mb-1.5 block text-sm font-semibold text-gray-900">Lolos dari babak</label>
                        <select class="<?= $kelas('dari_babak') ?>" id="dari_babak" name="dari_babak"<?= $tanda('dari_babak') ?>>
                            <?php foreach ($babak as $b) : ?>
                                <option value="<?= $b['id'] ?>"<?= (string) ($isian['dari_babak'] ?? '') === (string) $b['id'] ? ' selected' : '' ?>><?= esc($b['nama']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?= $pesan('dari_babak') ?>
                    </div>
                    <div>
                        <label for="jumlah_lolos" class="mb-1.5 block text-sm font-semibold text-gray-900">Peringkat teratas yang lolos</label>
                        <input type="text" inputmode="numeric" class="<?= $kelas('jumlah_lolos') ?>" id="jumlah_lolos" name="jumlah_lolos"
                            value="<?= esc((string) ($isian['jumlah_lolos'] ?? ''), 'attr') ?>"<?= $tanda('jumlah_lolos', 'jumlah_lolos-bantuan') ?>>
                        <p id="jumlah_lolos-bantuan" class="mt-1.5 text-sm text-gray-600">Misalnya 8: peringkat 1 sampai 8 ikut babak baru. Peserta lain bisa ditambah sesudahnya.</p>
                        <?= $pesan('jumlah_lolos') ?>
                    </div>
                </div>
            <?php endif ?>
            <?= komponen('tombol', ['label' => 'Tambah babak', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'plus']) ?>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
