<?php
/**
 * Halaman publik Jadwal pertandingan (`/jadwal`, F9-07), tata letak Tailwind
 * `publik_tw`. Kolom seperti `sumber/foto-acara/contohjadwal1.jpg`: waktu, nomor, putra/putri,
 * kelompok umur, babak; ditambah status otomatis dan tautan hasil.
 *
 * - Tab hari berupa tautan `?hari=<Y-m-d>` (tanpa JavaScript).
 * - Ponsel: kartu per babak; layar lebar: tabel berkepala gradien merek.
 * - Ilustrasi nomor lomba G9-05 hanya bila berkasnya ada (`IlustrasiBeranda::nomor()`, `is_file()`).
 * - Tautan daftar seri (F9-08) tampil bila baris membawa `alamat_seri`.
 *
 * @var CodeIgniter\View\View      $this
 * @var string                     $namaAcara
 * @var list<string>               $hari     'Y-m-d' urut
 * @var string|null                $dipilih  hari yang dibuka
 * @var list<array<string, mixed>> $babak    `JadwalPertandingan::perHari()[$dipilih]`
 * @var DateTimeInterface          $kini
 */

use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\IlustrasiBeranda;

// Ilustrasi G9-05 lewat pemeta nama nomor milik halaman depan (F9-03, K144):
// `null` bila nama tidak cocok atau berkasnya belum ada.
$ilustrasi = static fn (string $nama): ?string => IlustrasiBeranda::nomor($nama);

// ikon-sprite: clock circle-play circle-check
$warnaStatus = ['belum' => 'abu', 'berlangsung' => 'oranye', 'selesai' => 'hijau'];
$ikonStatus  = ['belum' => 'clock', 'berlangsung' => 'circle-play', 'selesai' => 'circle-check'];
$adaLokasi   = in_array(true, array_map(static fn (array $b): bool => $b['lokasi'] !== '', $babak), true);

// Label tab pendek: "Sab, 5 Des".
$labelTab = static function (string $tanggal): string {
    $bagian = explode(', ', JadwalPertandingan::namaHari($tanggal), 2);
    $tgl    = explode(' ', $bagian[1] ?? '');

    return mb_substr($bagian[0], 0, 3) . ', ' . ($tgl[0] ?? '') . ' ' . mb_substr($tgl[1] ?? '', 0, 3);
};

$tindakan = static function (array $b, string $status): string {
    $html = '';

    if ($b['terbit']) {
        $html .= '<a href="' . site_url('hasil/' . $b['nomor_id']) . '" class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2 text-sm font-semibold text-primary-700 hover:bg-primary-100 hover:underline" data-tautan-hasil>'
            . ikon('trophy', 'size-4') . 'Lihat hasil<span class="sr-only"> ' . esc($b['nomor_lengkap'] . ' ' . $b['kelompok_umur']) . '</span></a>';
    }

    if (! empty($b['alamat_seri'])) {
        $html .= '<a href="' . esc($b['alamat_seri'], 'attr') . '" class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2 text-sm font-semibold text-primary-700 hover:bg-primary-100 hover:underline" data-tautan-seri>'
            . ikon('list-ordered', 'size-4') . 'Daftar seri<span class="sr-only"> ' . esc($b['nomor_lengkap'] . ' ' . $b['babak']) . '</span></a>';
    }

    return $html === '' ? '<span class="text-sm text-gray-500">' . ($status === 'selesai' ? '' : 'Hasil menyusul') . '</span>' : $html;
};
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Jadwal pertandingan – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Jadwal pertandingan per hari: nomor lomba, babak, jam, dan lokasi.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="latar-merek border-b border-gray-200/70" aria-labelledby="judul-jadwal-pertandingan">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12 md:flex md:items-center md:gap-8">
        <div class="min-w-0 md:flex-1">
        <?= view('tata_letak/_judul_editorial', ['inggris' => 'Race Day', 'alis' => $namaAcara, 'judul' => 'Jadwal pertandingan', 'ikon' => 'calendar-clock', 'id' => 'judul-jadwal-pertandingan'], ['saveData' => false]) ?>
        <p class="mt-3 max-w-3xl text-lg text-gray-700">Urutan lomba per hari dalam WIB. Jam bisa bergeser mengikuti jalannya lomba.</p>
        </div>
        <?php if (ada_gambar('judul/jadwal.webp')) : ?>
            <div class="hidden shrink-0 md:block" data-judul-ilustrasi><?= gambar_opsional('judul/jadwal.webp', ['class' => 'h-36 w-auto lg:h-44']) ?></div>
        <?php endif ?>
    </div>
</section>

<div class="mx-auto max-w-screen-xl px-4 py-6 sm:py-8">
    <?php if ($dipilih === null) : ?>
        <?= komponen('kosong', ['gambar' => 'gambar/keadaan/kosong-data.webp', 'ikon' => 'calendar-x', 'judul' => 'Jadwal belum diumumkan', 'teks' => 'Jadwal pertandingan tampil di sini setelah panitia mengisinya. Agenda pendaftaran ada di halaman depan.', 'aksi' => komponen('tombol', ['label' => 'Ke halaman depan', 'href' => site_url('/'), 'varian' => 'kedua', 'ikon' => 'house'])]) ?>
    <?php else : ?>
        <nav aria-label="Pilih hari" data-tab-hari>
            <ul class="flex flex-wrap gap-2">
                <?php foreach ($hari as $h) : ?>
                    <?php $tabAktif = $h === $dipilih; ?>
                    <li class="shrink-0">
                        <a href="<?= site_url('jadwal') . '?hari=' . $h ?>" class="<?= $tabAktif ? 'bg-primary-700 text-white' : 'border border-gray-300 bg-white text-gray-800 hover:bg-gray-50' ?> relative inline-flex min-h-11 items-center rounded-full px-4 text-sm font-semibold whitespace-nowrap"<?= $tabAktif ? ' aria-current="page"' : '' ?> data-hari="<?= $h ?>">
                            <span aria-hidden="true"><?= esc($labelTab($h)) ?></span><span class="sr-only"><?= esc(JadwalPertandingan::namaHari($h)) ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </nav>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-primary-900 sm:text-2xl" data-judul-hari><?= esc(JadwalPertandingan::namaHari($dipilih)) ?></h2>
            <?= komponen('tombol', ['label' => 'Unduh jadwal (PDF)', 'href' => site_url('jadwal/' . $dipilih . '/pdf'), 'varian' => 'kedua', 'ikon' => 'download', 'atribut' => ['data-unduh-jadwal' => '']]) ?>
        </div>

        <ol class="mt-4 grid gap-3 md:hidden" data-daftar-kartu-jadwal>
            <?php foreach ($babak as $b) : ?>
                <?php $status = JadwalPertandingan::status($b, $kini); $gambar = $ilustrasi($b['nomor']); ?>
                <li class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut" data-babak="<?= $b['id'] ?>" data-status="<?= $status ?>">
                    <div class="flex items-start gap-3">
                        <div class="w-16 shrink-0 text-lg font-extrabold leading-tight text-oranye-700"><?= $b['jam'] === null ? '–' : esc($b['jam']) ?><span class="block text-xs font-semibold text-gray-600"><?= $b['jam'] === null ? 'Jam menyusul' : 'WIB' ?></span></div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold break-words text-gray-900"><?= esc($b['nomor']) ?></p>
                            <p class="text-sm text-gray-700"><?= esc($b['jenis_kelamin']) ?> · <?= esc($b['kelompok_umur']) ?> · <?= esc($b['babak']) ?></p>
                            <?php if ($b['lokasi'] !== '') : ?><p class="mt-0.5 flex items-center gap-1 text-sm text-gray-600"><?= ikon('map-pin', 'size-4 shrink-0') ?><span class="break-words"><?= esc($b['lokasi']) ?></span></p><?php endif ?>
                        </div>
                        <?php if ($gambar !== null) : ?><img src="<?= esc($gambar, 'attr') ?>" alt="" class="size-12 shrink-0" width="48" height="48" loading="lazy"><?php endif ?>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-2">
                        <?= komponen('lencana', ['label' => JadwalPertandingan::STATUS[$status], 'warna' => $warnaStatus[$status], 'ikon' => $ikonStatus[$status]]) ?>
                        <div class="flex flex-wrap gap-1"><?= $tindakan($b, $status) ?></div>
                    </div>
                </li>
            <?php endforeach ?>
        </ol>

        <div class="mt-4 hidden overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut md:block">
            <table class="w-full text-left text-sm text-gray-800" data-tabel-jadwal>
                <caption class="sr-only">Jadwal pertandingan <?= esc(JadwalPertandingan::namaHari($dipilih)) ?></caption>
                <thead class="bg-linear-to-r from-oranye-600 to-magenta-600 text-white">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-bold whitespace-nowrap">Waktu</th>
                        <th scope="col" class="px-4 py-3 font-bold">Nomor</th>
                        <th scope="col" class="px-4 py-3 font-bold whitespace-nowrap">Putra/Putri</th>
                        <th scope="col" class="px-4 py-3 font-bold">KU</th>
                        <th scope="col" class="px-4 py-3 font-bold">Babak</th>
                        <th scope="col" class="px-4 py-3 font-bold">Status</th>
                        <th scope="col" class="px-4 py-3 font-bold"><span class="sr-only">Tautan</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($babak as $b) : ?>
                        <?php $status = JadwalPertandingan::status($b, $kini); $gambar = $ilustrasi($b['nomor']); ?>
                        <tr class="<?= $status === 'berlangsung' ? 'bg-oranye-50' : 'odd:bg-white even:bg-primary-50' ?>" data-babak="<?= $b['id'] ?>" data-status="<?= $status ?>">
                            <td class="px-4 py-3 text-base font-extrabold whitespace-nowrap text-oranye-700"><?= $b['jam'] === null ? '<span class="text-sm font-semibold text-gray-600">Menyusul</span>' : esc($b['jam']) . ' <span class="text-xs font-semibold text-gray-600">WIB</span>' ?></td>
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-3">
                                    <?php if ($gambar !== null) : ?><img src="<?= esc($gambar, 'attr') ?>" alt="" class="size-10 shrink-0" width="40" height="40" loading="lazy"><?php endif ?>
                                    <span class="font-bold text-gray-900"><?= esc($b['nomor']) ?></span>
                                </span>
                            </td>
                            <td class="px-4 py-3"><?= esc($b['jenis_kelamin']) ?></td>
                            <td class="px-4 py-3"><?= esc($b['kelompok_umur']) ?></td>
                            <td class="px-4 py-3">
                                <?= esc($b['babak']) ?>
                                <?php if ($adaLokasi && $b['lokasi'] !== '') : ?><span class="mt-0.5 flex items-center gap-1 text-xs text-gray-600"><?= ikon('map-pin', 'size-3.5 shrink-0') ?><?= esc($b['lokasi']) ?></span><?php endif ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= komponen('lencana', ['label' => JadwalPertandingan::STATUS[$status], 'warna' => $warnaStatus[$status], 'ikon' => $ikonStatus[$status]]) ?></td>
                            <td class="px-4 py-2"><div class="flex flex-wrap justify-end gap-1"><?= $tindakan($b, $status) ?></div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <p class="mt-8 text-sm text-gray-600">
            Ada pertanyaan tentang jadwal? Lihat halaman <a href="<?= site_url('bantuan') ?>" class="inline-flex min-h-11 items-center font-semibold text-primary-700 underline">Bantuan</a>.
        </p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
