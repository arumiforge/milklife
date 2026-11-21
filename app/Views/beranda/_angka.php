<?php
/**
 * Bagian angka acara halaman depan (`#angka`, F9-03), seperti poster
 * "Kompetisi ini diikuti": jumlah atlet, sekolah, kabupaten/kota, dan atlet
 * per kelompok umur. Hanya dari pendaftaran Lunas; bagian tidak tampil
 * sebelum ada atletnya (`IsiHalamanDepan::angkaAcara()` = `null`).
 * Latar G9-02 `latar-angka.webp` dipakai bila berkasnya ada (K41).
 *
 * @var array{atlet: int, sekolah: int, kabupaten: int, kelompok_umur: list<array{nama: string, atlet: int}>}|null $angka
 * @var array{nama: string, seri_tahun: string}                                                                      $acara
 */

use App\Libraries\IlustrasiBeranda;

if ($angka === null) {
    return;
}

$latar  = IlustrasiBeranda::alamat('gambar/beranda/latar-angka.webp');
$format = static fn (int $n): string => number_format($n, 0, ',', '.');
?>
<section id="angka" class="scroll-mt-20" aria-labelledby="judul-angka">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <div class="relative isolate overflow-hidden rounded-3xl bg-primary-900 px-5 py-10 text-white shadow-angkat sm:px-10 sm:py-14">
            <?php if ($latar !== null) : ?>
                <img src="<?= esc($latar, 'attr') ?>" alt="" class="absolute inset-0 -z-10 size-full object-cover opacity-25" width="1920" height="900" loading="lazy">
            <?php endif ?>
            <div class="pita-merek absolute inset-x-0 top-0 h-1.5" aria-hidden="true"></div>

            <div class="text-center">
                <h2 id="judul-angka" class="text-lg font-bold text-langit-300 sm:text-xl">Kompetisi ini diikuti</h2>
                <p class="mt-2">
                    <span class="block text-6xl font-extrabold tracking-tight text-oranye-400 sm:text-8xl" data-angka-atlet><?= $format($angka['atlet']) ?></span>
                    <span class="mt-2 block text-lg font-semibold sm:text-xl">
                        atlet dari <strong class="text-oranye-300" data-angka-sekolah><?= $format($angka['sekolah']) ?></strong> sekolah<?php if ($angka['kabupaten'] > 1) : ?>
                            di <strong class="text-oranye-300" data-angka-kabupaten><?= $format($angka['kabupaten']) ?></strong> kabupaten/kota<?php endif ?>
                    </span>
                </p>
                <p class="mt-1 text-sm text-primary-200">Dihitung dari pendaftaran yang sudah lunas.</p>
            </div>

            <?php if ($angka['kelompok_umur'] !== []) : ?>
                <ul class="mt-8 grid grid-cols-2 gap-y-4 rounded-2xl bg-white/10 py-4 sm:grid-cols-3 lg:flex lg:justify-center lg:divide-x lg:divide-white/20" data-angka-ku>
                    <?php foreach ($angka['kelompok_umur'] as $ku) : ?>
                        <li class="min-w-24 px-4 py-1 text-center sm:px-6">
                            <span class="block text-sm font-bold text-langit-300"><?= esc($ku['nama']) ?></span>
                            <span class="block text-3xl font-extrabold text-oranye-400 sm:text-4xl"><?= $format($ku['atlet']) ?></span>
                            <span class="block text-sm text-primary-100">atlet</span>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
    </div>
</section>
