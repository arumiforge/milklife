<?php
/**
 * Halaman publik hasil lomba (F8-04, K124), tata letak baru `publik_tw`
 * (F9-03): nomor yang sudah diterbitkan admin per kelompok umur, dengan
 * juara I–III dari babak Final. Pengendali: `App\Controllers\HasilController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var list<array{id: int, nama: string, nomor: list<array<string, mixed>>}> $kelompokUmur `HasilLomba::daftarNomor(true)`
 * @var array<int, list<array<string, mixed>>> $juara id nomor => juara (`HasilLomba::juara(true)`)
 */
$romawi = [1 => 'I', 2 => 'II', 3 => 'III'];
$medali = [1 => 'bg-yellow-100 text-yellow-800', 2 => 'bg-gray-200 text-gray-800', 3 => 'bg-oranye-100 text-oranye-800'];
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Hasil Lomba – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', [
    'judul' => 'Hasil Lomba',
    'ikon'  => 'trophy',
    'teks'  => 'Hasil resmi ' . $namaAcara . ' yang sudah diterbitkan panitia, per kelompok umur dan nomor lomba.',
    'aksi'  => $kelompokUmur === [] ? null : komponen('tombol', ['label' => 'Klasemen sekolah', 'href' => site_url('hasil/klasemen'), 'ikon' => 'award', 'atribut' => ['data-tautan-klasemen' => true]]),
], ['saveData' => false]) ?>

<div class="mx-auto max-w-screen-xl space-y-10 px-4 py-10">
    <?php if ($kelompokUmur === []) : ?>
        <div id="hasil-kosong">
            <?= komponen('kosong', [
                'gambar' => 'gambar/keadaan/kosong-hasil.webp',
                'ikon'   => 'hourglass',
                'judul'  => 'Hasil belum diterbitkan',
                'teks'   => 'Panitia belum menerbitkan hasil lomba. Hasil tiap nomor muncul di sini setelah diumumkan. Silakan kembali lagi nanti.',
                'aksi'   => komponen('tombol', ['label' => 'Kembali ke beranda', 'href' => site_url('/'), 'varian' => 'kedua', 'ikon' => 'arrow-left']),
            ]) ?>
        </div>
    <?php endif ?>

    <?php foreach ($kelompokUmur as $ku) : ?>
        <section aria-labelledby="hasil-ku-<?= $ku['id'] ?>">
            <h2 class="text-2xl font-extrabold text-primary-900" id="hasil-ku-<?= $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
            <ul class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($ku['nomor'] as $n) : ?>
                    <li class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut" data-hasil-nomor="<?= (int) $n['id'] ?>">
                        <h3 class="text-lg font-bold"><a class="text-primary-800 hover:underline" href="<?= site_url('hasil/' . $n['id']) ?>"><?= esc($n['nama']) ?></a></h3>
                        <?php if (($juara[$n['id']] ?? []) === []) : ?>
                            <p class="mt-2 text-sm text-gray-600">Juara belum ditetapkan. Lihat hasil semua babak.</p>
                        <?php else : ?>
                            <ol class="mt-3 space-y-2 text-sm">
                                <?php foreach ($juara[$n['id']] as $j) : ?>
                                    <li class="flex items-start gap-2">
                                        <span class="<?= $medali[$j['peringkat']] ?? 'bg-gray-100 text-gray-800' ?> shrink-0 rounded-md px-2 py-0.5 text-xs font-bold">Juara <?= $romawi[$j['peringkat']] ?? $j['peringkat'] ?></span>
                                        <span class="min-w-0 break-words"><span class="font-semibold text-gray-900"><?= esc($j['nama']) ?></span><?= $n['jenis'] === 'beregu' ? '' : ' <span class="block text-gray-500">' . esc($j['sekolah']) . '</span>' ?></span>
                                    </li>
                                <?php endforeach ?>
                            </ol>
                        <?php endif ?>
                        <a href="<?= site_url('hasil/' . $n['id']) ?>" class="mt-auto inline-flex min-h-11 items-center gap-1 pt-3 text-sm font-semibold text-primary-700 hover:underline">Lihat semua babak<span class="sr-only"> <?= esc($n['nama']) ?></span><?= ikon('arrow-right', 'size-4') ?></a>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
