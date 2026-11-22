<?php
/**
 * Lembar juri: pilih babak (F9-09, K140 butir 7d), tata letak Tailwind
 * `admin_tw`. Babak dikelompokkan per hari jadwal (F9-07); hari ini paling
 * atas dan terbuka. Data dari `Admin\JuriController::index()`.
 *
 * @var CodeIgniter\View\View                     $this
 * @var array<string, list<array<string, mixed>>> $hari    'Y-m-d' (atau '' belum dijadwalkan) => babak (`LembarJuri::daftar()`)
 * @var string|null                               $hariIni tanggal hari ini bila ada babaknya
 */

use App\Libraries\Hasil\JadwalPertandingan;

// ikon-sprite: chevron-right chevron-down clipboard-pen calendar-x trophy
$pertama = array_key_first($hari);
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Lembar juri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <p class="text-gray-700">
        Pilih babak, lalu isi prestasi tiap peserta dari ponsel. Setiap baris tersimpan sendiri begitu diisi.
        Beberapa juri boleh mengisi babak yang berbeda bersamaan. Peringkat dan tombol Terbitkan ada di
        <a href="<?= site_url('admin/hasil') ?>" class="font-semibold text-primary-700 underline">Hasil lomba</a>.
    </p>

    <?php if ($hari === []) : ?>
        <?= komponen('kosong', ['padat' => true, 'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'], 'ikon' => 'calendar-x', 'judul' => 'Belum ada babak', 'teks' => 'Lembar juri dibuat dari babak tiap nomor lomba. Tambahkan babak (misalnya Penyisihan dan Final) di Hasil lomba, lalu kembali ke sini.', 'aksi' => komponen('tombol', ['label' => 'Buka Hasil lomba', 'href' => site_url('admin/hasil'), 'ikon' => 'trophy'])]) ?>
    <?php endif ?>

    <?php foreach ($hari as $tanggal => $daftar) : ?>
        <details class="group rounded-2xl border border-gray-200 bg-white shadow-lembut" data-hari-juri="<?= esc($tanggal === '' ? 'belum' : $tanggal, 'attr') ?>"<?= $tanggal === ($hariIni ?? $pertama) ? ' open' : '' ?>>
            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 sm:px-5">
                <span>
                    <span class="block text-lg font-bold text-gray-900"><?= $tanggal === '' ? 'Belum dijadwalkan' : esc(JadwalPertandingan::namaHari($tanggal)) ?></span>
                    <span class="block text-sm text-gray-600"><?= count($daftar) ?> babak<?= $tanggal !== '' && $tanggal === $hariIni ? ' · hari ini' : '' ?></span>
                </span>
                <?= ikon('chevron-down', 'size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180') ?>
            </summary>
            <ul class="divide-y divide-gray-100 border-t border-gray-100">
                <?php foreach ($daftar as $b) : ?>
                    <li>
                        <a href="<?= site_url('admin/juri/babak/' . $b['id']) ?>" class="flex min-h-14 items-center gap-3 px-4 py-2.5 hover:bg-gray-50 focus:bg-gray-50 sm:px-5" data-babak-juri="<?= $b['id'] ?>">
                            <span class="w-14 shrink-0 text-base font-bold text-gray-900"><?= $b['jam'] === null ? '–' : esc($b['jam']) ?></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold break-words text-gray-900"><?= esc($b['nomor_lengkap']) ?> <span class="font-normal text-gray-600"><?= esc($b['kelompok_umur']) ?></span></span>
                                <span class="block text-sm text-gray-600"><?= esc($b['babak']) ?><?= $b['lokasi'] === '' ? '' : ' · ' . esc($b['lokasi']) ?> · <?= $b['terisi'] ?>/<?= $b['peserta'] ?> terisi</span>
                            </span>
                            <?php if ($b['terbit']) : ?>
                                <?= komponen('lencana', ['label' => 'Terbit', 'warna' => 'hijau']) ?>
                            <?php elseif ($b['peserta'] > 0 && $b['terisi'] === $b['peserta']) : ?>
                                <?= komponen('lencana', ['label' => 'Lengkap', 'warna' => 'biru']) ?>
                            <?php endif ?>
                            <?= ikon('chevron-right', 'size-5 shrink-0 text-gray-400') ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </details>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
