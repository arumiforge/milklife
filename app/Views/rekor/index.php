<?php
/**
 * Halaman Rekor publik (`/rekor`, F11-06, K172 butir 3), tata letak
 * Tailwind `publik_tw`. Pengendali: `App\Controllers\RekorController`.
 *
 * Satu tabel per kelompok umur berisi rekor yang berlaku tiap nomor,
 * spesifikasi alat, dan cara pencatatan waktu. Di layar sempit atlet dan
 * sekolah pindah ke bawah nama nomor.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var list<array{kelompok_umur: string, rekor: list<array<string, mixed>>}> $kelompok `Rekor::daftar(true)`
 */
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Rekor Kejuaraan – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Rekor kejuaraan per kelompok umur dan nomor lomba, dicatat mulai Seri 2.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', [
    'inggris' => 'Records',
    'judul'   => 'Rekor kejuaraan',
    'ikon'    => 'trophy',
    'teks'    => 'Prestasi terbaik ' . $namaAcara . ' per kelompok umur dan nomor lomba, dicatat mulai Seri 2.',
    'kembali' => site_url('hasil'),
    'labelKembali' => 'Hasil lomba',
], ['saveData' => false]) ?>

<div class="mx-auto max-w-screen-xl space-y-10 px-4 py-10">
    <?php if ($kelompok === []) : ?>
        <div id="rekor-kosong">
            <?= komponen('kosong', [
                'gambar' => 'gambar/keadaan/kosong-hasil.webp',
                'ikon'   => 'hourglass',
                'judul'  => 'Belum ada rekor',
                'teks'   => 'Rekor tercatat setelah hasil Final diterbitkan panitia. Buka lagi halaman ini nanti.',
                'aksi'   => komponen('tombol', ['label' => 'Lihat hasil lomba', 'href' => site_url('hasil'), 'varian' => 'kedua', 'ikon' => 'arrow-left']),
            ]) ?>
        </div>
    <?php else : ?>
        <p class="text-gray-700">Rekor dicatat per spesifikasi alat dan cara pencatatan waktu. Rekor waktu tangan (dengan stopwatch) dan waktu elektronik dicatat terpisah.</p>
    <?php endif ?>

    <?php foreach ($kelompok as $i => $k) : ?>
        <section aria-labelledby="rekor-ku-<?= $i ?>">
            <h2 class="text-2xl font-extrabold text-primary-900" id="rekor-ku-<?= $i ?>"><?= esc($k['kelompok_umur']) ?></h2>
            <div class="relative mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
                <table class="w-full text-left text-sm text-gray-700" data-tabel-rekor>
                    <caption class="sr-only">Rekor <?= esc($k['kelompok_umur']) ?></caption>
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-2 py-3 sm:px-3">Nomor</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Prestasi</th>
                            <th scope="col" class="hidden px-2 py-3 sm:px-3 md:table-cell">Atlet</th>
                            <th scope="col" class="hidden px-2 py-3 sm:px-3 lg:table-cell">Seri</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($k['rekor'] as $r) : ?>
                            <tr class="border-t border-gray-100" data-rekor="<?= $r['id'] ?>">
                                <td class="px-2 py-3 sm:px-3">
                                    <span class="font-semibold text-gray-900"><?= esc($r['nomor']) ?></span>
                                    <?php if ($r['berat_alat'] !== '' || $r['cara'] !== '') : ?>
                                        <span class="block text-gray-500"><?= esc(implode(' · ', array_filter([$r['berat_alat'], $r['cara']]))) ?></span>
                                    <?php endif ?>
                                    <span class="block text-gray-600 md:hidden"><?= esc($r['atlet']) ?> · <?= esc($r['sekolah']) ?></span>
                                </td>
                                <td class="whitespace-nowrap px-2 py-3 font-bold text-gray-900 sm:px-3"><?= esc($r['teks']) ?></td>
                                <td class="hidden px-2 py-3 sm:px-3 md:table-cell">
                                    <span class="font-semibold text-gray-900"><?= esc($r['atlet']) ?></span>
                                    <span class="block text-gray-500"><?= esc($r['sekolah']) ?></span>
                                </td>
                                <td class="hidden whitespace-nowrap px-2 py-3 sm:px-3 lg:table-cell">
                                    <?= esc($r['seri']) ?>
                                    <?php if ($r['tanggal'] !== null) : ?><span class="block text-gray-500"><?= esc(tanggal_id((string) $r['tanggal'])) ?></span><?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
