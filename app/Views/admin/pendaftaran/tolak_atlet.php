<?php
/**
 * Tolak atlet dari satu pendaftaran (K116). Data dari
 * `TolakAtletController` (`TolakAtlet::formulir()`).
 *
 * Atlet yang kelompok umurnya tidak cocok lagi dengan pengaturan sekarang
 * diberi tanda dan tercentang saat halaman pertama dibuka. Penyimpanan
 * memakai jendela konfirmasi `aset/js/konfirmasi.js`. Tata letak `admin_tw`
 * (F9-15).
 *
 * @var CodeIgniter\View\View $this
 * @var array<string, mixed>  $form
 * @var list<int>             $pilih     id atlet yang tercentang
 * @var string                $alasan
 * @var list<string>          $galat
 * @var int                   $alasanMin
 * @var int                   $alasanMax
 */

use App\Libraries\Pendaftaran\Regu;

$p       = $form['pendaftaran'];
$alamat  = 'admin/pendaftaran/' . (int) $p['id'];
$lunas   = $p['status'] !== 'menunggu';
$kosong  = $form['atlet'] === [];
$takCocok = count(array_filter($form['atlet'], static fn (array $a): bool => ! $a['cocok']));

$th = 'border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap text-gray-600';
$td = 'px-4 py-3 align-top';

$kalimatKonfirmasi = 'Atlet yang dicentang akan dikeluarkan dari pendaftaran ' . $p['id_pendaftaran'] . ' beserta nomor lombanya, '
    . 'dan biaya pendaftaran dihitung ulang. NISN-nya bisa didaftarkan lagi. Alasan dan rinciannya tampil di Cek Pendaftaran '
    . 'sampai atlet itu didaftarkan lagi. Tindakan ini tidak bisa dibatalkan.';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Tolak Atlet <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Tolak Atlet<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [
    App\Libraries\Panel\AsalRincian::untuk((int) $p['id']),
    ['label' => $p['id_pendaftaran'], 'alamat' => $alamat],
    ['label' => 'Tolak atlet'],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6" id="tolak-atlet" data-id="<?= (int) $p['id'] ?>">

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-tolak">
        <div>
            <h2 id="judul-tolak" class="text-xl font-bold text-gray-900"><?= esc($p['id_pendaftaran']) ?></h2>
            <p class="text-lg text-gray-900"><?= esc($p['nama_sekolah']) ?> <span class="text-base text-gray-500">· <?= esc($p['jenjang_label']) ?></span></p>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900" role="note">
            <?= ikon('info', 'mt-0.5 shrink-0') ?>
            <div>
                Atlet yang ditolak dikeluarkan dari pendaftaran ini, dan NISN-nya bisa didaftarkan lagi oleh sekolah.
                Sekolah melihat alasan, kelompok umur yang sesuai, dan perubahan biayanya di Cek Pendaftaran,
                sampai atlet itu didaftarkan lagi. Uang yang sudah terlanjur dikirim diselesaikan langsung dengan panitia.
            </div>
        </div>
    </section>

    <?php if ($galat !== []) : ?>
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" id="galat-tolak" tabindex="-1">
            <?= ikon('circle-alert', 'mt-0.5 shrink-0') ?>
            <ul class="list-disc space-y-0.5 ps-5">
                <?php foreach ($galat as $pesan) : ?>
                    <li><?= esc($pesan) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if ($lunas) : ?>
        <div class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status" id="tolak-lunas">
            <?= ikon('triangle-alert', 'mt-0.5 shrink-0') ?>
            <p>
                Pendaftaran ini sudah Lunas, jadi atletnya tidak bisa ditolak.
                Kembalikan ke Menunggu dulu di <a class="font-semibold underline hover:no-underline" href="<?= site_url($alamat) ?>">rincian pendaftaran</a> bila atletnya memang perlu ditolak.
            </p>
        </div>
    <?php elseif ($kosong) : ?>
        <p class="text-gray-600">Pendaftaran ini tidak punya atlet.</p>
    <?php else : ?>
        <form action="<?= site_url($alamat . '/tolak-atlet') ?>" method="post" class="space-y-6"
            data-konfirmasi="<?= esc($kalimatKonfirmasi, 'attr') ?>"
            data-konfirmasi-judul="Tolak atlet?"
            data-konfirmasi-tombol="Tolak atlet">
            <?= csrf_field() ?>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-pilih-atlet">
                <div class="px-4 pb-3 pt-4 sm:px-5">
                    <h2 id="judul-pilih-atlet" class="text-lg font-bold text-gray-900">Pilih atlet yang ditolak</h2>
                    <p class="text-sm text-gray-600">
                        <?php if ($takCocok > 0) : ?>
                            <?= $takCocok ?> atlet kelompok umurnya tidak cocok lagi dengan pengaturan kelompok umur sekarang dan sudah dicentang. Periksa lagi sebelum menolak.
                        <?php else : ?>
                            Semua atlet cocok dengan pengaturan kelompok umur sekarang.
                        <?php endif ?>
                    </p>
                </div>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-700" id="tabel-tolak-atlet">
                        <thead>
                            <tr>
                                <th scope="col" class="<?= $th ?>"><span class="sr-only">Pilih</span></th>
                                <th scope="col" class="<?= $th ?>">Nama</th>
                                <th scope="col" class="<?= $th ?>">NISN</th>
                                <th scope="col" class="<?= $th ?>">Tanggal lahir</th>
                                <th scope="col" class="<?= $th ?>">Kelompok umur</th>
                                <th scope="col" class="<?= $th ?>">Nomor lomba</th>
                                <th scope="col" class="<?= $th ?> text-end">Biaya individu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($form['atlet'] as $a) : ?>
                                <?php $idKotak = 'tolak-' . (int) $a['id']; ?>
                                <tr data-id="<?= (int) $a['id'] ?>" class="<?= $a['cocok'] ? '' : 'bg-yellow-50' ?>"<?= $a['cocok'] ? '' : ' data-ku-tidak-cocok' ?>>
                                    <td class="<?= $td ?>">
                                        <input class="size-5 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500" type="checkbox" name="atlet[]" value="<?= (int) $a['id'] ?>" id="<?= $idKotak ?>"
                                            <?= in_array((int) $a['id'], $pilih, true) ? 'checked' : '' ?>>
                                    </td>
                                    <th scope="row" class="<?= $td ?> font-semibold text-gray-900"><label for="<?= $idKotak ?>"><?= esc($a['nama']) ?></label></th>
                                    <td class="<?= $td ?> whitespace-nowrap"><?= esc($a['nisn']) ?></td>
                                    <td class="<?= $td ?> whitespace-nowrap"><?= esc(tanggal_id($a['tanggal_lahir'])) ?></td>
                                    <td class="<?= $td ?>">
                                        <span class="whitespace-nowrap"><?= esc($a['kelompok_umur']) ?></span>
                                        <?php if (! $a['cocok']) : ?>
                                            <div class="mt-1 flex items-start gap-1 text-sm text-red-800">
                                                <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                                <span><?= $a['sesuai_id'] === null ? esc($a['sesuai']) : 'Seharusnya ' . esc($a['sesuai']) ?></span>
                                            </div>
                                        <?php endif ?>
                                    </td>
                                    <td class="<?= $td ?>">
                                        <ul class="space-y-1">
                                            <?php foreach ($a['nomor_lomba'] as $n) : ?>
                                                <li>
                                                    <?= esc($n['nama']) ?>
                                                    <?php if ($n['jenis'] === 'beregu' && $n['regu'] !== null) : ?>
                                                        <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800"><?= esc(Regu::label((string) $n['regu'])) ?> · <?= esc(Regu::labelPeran($n['peran'])) ?></span>
                                                    <?php endif ?>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </td>
                                    <td class="<?= $td ?> whitespace-nowrap text-end"><?= esc(rupiah($a['biaya_individu'])) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="space-y-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-alasan">
                <h2 id="judul-alasan" class="text-lg font-bold text-gray-900"><label for="alasan">Alasan penolakan</label></h2>
                <p class="text-sm text-gray-600" id="alasan-petunjuk">Dibaca sekolah di Cek Pendaftaran. Tulis alasannya dan apa yang perlu dilakukan sekolah.</p>
                <textarea class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm" id="alasan" name="alasan" rows="3" minlength="<?= $alasanMin ?>" maxlength="<?= $alasanMax ?>" aria-describedby="alasan-petunjuk" required><?= esc($alasan) ?></textarea>
                <p class="text-sm text-gray-600">
                    Biaya regu dihitung ulang seperti di Ubah: regu yang tidak punya anggota lagi di pendaftaran ini tidak ditagih.
                    Bila semua atlet ditolak, pendaftaran tetap ada tanpa atlet dengan total Rp 0, supaya ofisial dan bukti bayarnya tetap tersimpan.
                </p>
            </section>

            <div class="flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Tolak atlet', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'user-x']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url($alamat), 'varian' => 'kedua']) ?>
            </div>
        </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
