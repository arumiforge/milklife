<?php
/**
 * Daftar nomor lomba untuk hasil lomba (F8-04, K124), tata letak Tailwind
 * `admin_tw`. Data dari `HasilController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, nomor: list<array{id: int, nama: string, jenis: string, jenis_hasil: string, terbit_pada: string|null, babak: int, final: bool, peserta: int}>}> $kelompokUmur
 */

use App\Libraries\Hasil\JenisHasil;

?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Hasil Lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="max-w-3xl text-gray-700">
        Pilih nomor lomba untuk membuat babak (misalnya Penyisihan seri 1, lalu Final) dan mengisi prestasinya; peringkat dihitung otomatis.
        Juara I–III diambil dari babak Final. Hasil satu nomor tampil di halaman publik setelah ditekan <strong>Terbitkan</strong>.
    </p>

    <?php if ($kelompokUmur === []) : ?>
        <p class="text-gray-600" id="hasil-kosong">Belum ada nomor lomba. Tambahkan di Pengaturan, Nomor Lomba.</p>
    <?php endif ?>

    <?php foreach ($kelompokUmur as $ku) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-ku-<?= $ku['id'] ?>">
            <h2 class="border-b border-gray-100 px-5 py-4 text-lg font-bold text-gray-900 sm:px-6" id="judul-ku-<?= $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($ku['nomor'] as $n) : ?>
                    <li>
                        <a href="<?= site_url('admin/hasil/nomor/' . $n['id']) ?>" class="flex min-h-16 items-center gap-3 px-5 py-3 hover:bg-gray-50 focus:bg-gray-50 sm:px-6">
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold break-words text-primary-700"><?= esc($n['nama']) ?></span>
                                <span class="block text-sm text-gray-600">
                                    <?= esc(JenisHasil::LABEL[$n['jenis_hasil']] ?? $n['jenis_hasil']) ?><?= $n['jenis'] === 'beregu' ? ' · beregu' : '' ?>
                                    · <?= $n['babak'] ?> babak<?= $n['final'] ? ' · Final ' . $n['peserta'] . ' peserta' : '' ?>
                                </span>
                            </span>
                            <?php if ($n['terbit_pada'] !== null) : ?>
                                <?= komponen('lencana', ['label' => 'Terbit', 'warna' => 'hijau']) ?>
                            <?php elseif ($n['final']) : ?>
                                <?= komponen('lencana', ['label' => 'Belum terbit', 'warna' => 'kuning']) ?>
                            <?php endif ?>
                            <?= ikon('chevron-right', 'size-5 shrink-0 text-gray-400') ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
