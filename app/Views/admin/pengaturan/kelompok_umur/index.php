<?php
/**
 * Daftar kelompok umur: nama, batas tahun lahir, jenjang yang boleh ikut,
 * biaya individu, awalan nomor dada, dan status aktif, tata letak Tailwind
 * `admin_tw`. Data dari `KelompokUmurController::index()`
 * (`KelompokUmurModel::daftarLengkap()`).
 *
 * Tombol hapus memakai jendela konfirmasi bila kelompok umur boleh dihapus.
 * Bila tidak boleh, tombol langsung mengirim dan server menjelaskan sebabnya
 * lewat pesan kilat, supaya admin tidak diminta mengonfirmasi hal yang pasti
 * ditolak.
 *
 * Di atas tabel: sakelar "Pendaftar memilih kelompok umur" (K134) dan
 * ringkasan tahun lahir yang masuk lebih dari satu kelompok umur aktif dalam
 * satu jenjang (`KelompokUmurModel::irisanAktif()`).
 *
 * @var CodeIgniter\View\View                                                          $this
 * @var list<array<string, mixed>>                                                     $daftar
 * @var bool                                                                           $bolehPilih
 * @var list<array{jenjang: string, tahun: list<int>, kelompok_umur: list<string>}>   $irisan
 */

use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\KelompokUmurAtlet;
use App\Models\KelompokUmurModel;

$belumDiisi = static fn (string $teks = 'Belum diisi'): string => '<span class="inline-flex items-center gap-1 whitespace-nowrap font-medium text-yellow-800">' . ikon('circle-alert', 'size-4') . ' ' . esc($teks) . '</span>';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Kelompok Umur<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kelompok Umur<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <p class="max-w-3xl text-gray-700">
            Kelompok umur atlet ditentukan dari jenjang sekolah lalu tahun lahirnya. Tiap kelompok umur aktif perlu batas tahun lahir,
            jenjang sekolah yang boleh ikut, biaya per nomor individu, dan awalan nomor atlet (BIB).
            Atlet tidak bisa pindah ke kelompok umur jenjang lain: atlet SMP/MTs tidak bisa ikut kelompok umur yang hanya untuk SD/MI.
        </p>
        <?php if ($daftar !== []) : ?>
            <?= komponen('tombol', ['label' => 'Tambah kelompok umur', 'href' => site_url('admin/pengaturan/kelompok-umur/tambah'), 'ikon' => 'plus', 'kelas' => 'shrink-0 self-start']) ?>
        <?php endif ?>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-pilih-ku">
        <h2 id="judul-pilih-ku" class="text-lg font-bold text-gray-900">Tahun lahir di dua kelompok umur</h2>
        <p class="mt-1 max-w-3xl text-sm text-gray-600">
            Panitia bisa membuat rentang tahun lahir dua kelompok umur dalam satu jenjang beririsan, misalnya KU 10 lahir 2016–2018
            dan KU 12 lahir 2014–2016 untuk SD/MI. Atlet lahir 2016 lalu boleh ikut KU 10 atau KU 12: saat mendaftar, ofisial memilih
            salah satunya untuk setiap atlet itu, dan nomor lomba yang tampil mengikuti pilihan itu. Satu atlet tetap hanya ikut
            satu kelompok umur, dan biaya individu mengikuti kelompok umur yang dipilih.
        </p>
        <form action="<?= site_url('admin/pengaturan/kelompok-umur/pilihan') ?>" method="post" class="mt-3 space-y-3">
            <?= csrf_field() ?>
            <?= komponen('sakelar', [
                'label'   => 'Pendaftar memilih kelompok umur',
                'nama'    => 'pilih_kelompok_umur',
                'centang' => $bolehPilih,
                'bantuan' => 'Menyala: rentang tahun lahir dalam satu jenjang boleh beririsan. Mati: satu tahun lahir hanya boleh masuk satu kelompok umur per jenjang, dan penyimpanan kelompok umur yang beririsan ditolak.',
            ]) ?>
            <?= komponen('tombol', ['label' => 'Simpan pilihan', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
        </form>

        <?php if ($irisan !== []) : ?>
            <div class="mt-4 flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status">
                <?= ikon('signpost', 'mt-0.5 shrink-0') ?>
                <div class="min-w-0">
                    <p class="mb-1 font-semibold">Tahun lahir yang dipilihkan kelompok umurnya oleh pendaftar:</p>
                    <ul class="mb-1 list-disc space-y-0.5 ps-5">
                        <?php foreach ($irisan as $baris) : ?>
                            <?php
                            $awal  = $baris['tahun'][0];
                            $akhir = $baris['tahun'][count($baris['tahun']) - 1];
                            ?>
                            <li>
                                <?= esc(Jenjang::label($baris['jenjang']) ?? $baris['jenjang']) ?>, lahir
                                <?= $awal === $akhir ? $awal : $awal . '–' . $akhir ?>:
                                <?= esc(KelompokUmurAtlet::gabungNama(array_map(static fn (string $n): array => ['nama' => $n], $baris['kelompok_umur']))) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p>
                        Periksa bahwa irisan ini memang dari panitia, bukan salah ketik. Atlet lain tetap masuk kelompok umurnya
                        dengan sendirinya.
                    </p>
                </div>
            </div>
        <?php elseif ($bolehPilih) : ?>
            <p class="mt-4 flex items-start gap-2 text-sm text-gray-600">
                <?= ikon('circle-check', 'mt-0.5 size-4 shrink-0 text-green-700') ?>
                <span>Saat ini tidak ada tahun lahir yang masuk dua kelompok umur dalam satu jenjang, jadi pendaftar belum pernah diminta memilih.</span>
            </p>
        <?php endif ?>
    </section>

    <?php if ($daftar === []) : ?>
        <?= komponen('kosong', [
            'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'],
            'ikon'   => 'users',
            'judul'  => 'Belum ada kelompok umur',
            'teks'   => 'Pendaftaran belum bisa dibuka sebelum ada kelompok umur. Tambahkan kelompok umur pertama, misalnya KU 10 untuk atlet yang lahir 2016 sampai 2018.',
            'aksi'   => komponen('tombol', ['label' => 'Tambah kelompok umur', 'href' => site_url('admin/pengaturan/kelompok-umur/tambah'), 'ikon' => 'plus']),
        ]) ?>
    <?php else : ?>
        <section aria-labelledby="judul-daftar">
            <h2 id="judul-daftar" class="sr-only">Daftar kelompok umur</h2>
            <div class="relative overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-lembut">
                <table class="w-full text-left text-sm text-gray-700" id="tabel-kelompok-umur" data-tabel-klien>
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-end">No</th>
                            <th scope="col" class="px-4 py-3">Kelompok umur</th>
                            <th scope="col" class="px-4 py-3">Tahun lahir</th>
                            <th scope="col" class="px-4 py-3">Jenjang yang boleh ikut</th>
                            <th scope="col" class="px-4 py-3 text-end">Biaya per nomor</th>
                            <th scope="col" class="px-4 py-3">Awalan nomor atlet (BIB)</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-end"><span class="sr-only">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 0; ?>
                        <?php foreach ($daftar as $ku) : ?>
                            <?php
                            $tahunLahir = KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'], $ku['tahun_lahir_akhir']);
                            $bisaHapus  = $ku['alasan_tidak_bisa_dihapus'] === null;
                            $aktif      = (int) $ku['aktif'] === 1;
                            ?>
                            <tr data-id="<?= (int) $ku['id'] ?>" class="border-t border-gray-100<?= $aktif ? '' : ' text-gray-500' ?>">
                                <td class="px-4 py-3 text-end text-gray-500"><?= ++$no ?></td>
                                <th scope="row" class="whitespace-nowrap px-4 py-3 font-semibold <?= $aktif ? 'text-gray-900' : 'text-gray-500' ?>"><?= esc($ku['nama']) ?></th>
                                <td class="whitespace-nowrap px-4 py-3"><?= $tahunLahir === '' ? $belumDiisi() : esc($tahunLahir) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($ku['jenjang'] === []) : ?>
                                        <?= $belumDiisi('Belum dipilih') ?>
                                    <?php else : ?>
                                        <span class="flex flex-wrap gap-1">
                                            <?php foreach ($ku['jenjang'] as $kode) : ?>
                                                <?= komponen('lencana', ['label' => (string) Jenjang::label($kode), 'warna' => 'abu']) ?>
                                            <?php endforeach; ?>
                                        </span>
                                    <?php endif ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-end"><?= $ku['biaya_individu'] === null ? $belumDiisi() : esc(rupiah($ku['biaya_individu'])) ?></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <?= $ku['awalan'] === [] ? $belumDiisi() : esc(implode(', ', array_column($ku['awalan'], 'awalan'))) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($aktif) : ?>
                                        <?= komponen('lencana', ['label' => 'Aktif', 'warna' => 'hijau']) ?>
                                    <?php else : ?>
                                        <?= komponen('lencana', ['label' => 'Mati', 'warna' => 'abu']) ?>
                                    <?php endif ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a class="inline-flex min-h-11 items-center gap-1.5 whitespace-nowrap rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100" href="<?= site_url('admin/pengaturan/kelompok-umur/' . (int) $ku['id'] . '/ubah') ?>">
                                            <?= ikon('pencil', 'size-4') ?>
                                            Ubah<span class="sr-only"> <?= esc($ku['nama']) ?></span>
                                        </a>
                                        <form action="<?= site_url('admin/pengaturan/kelompok-umur/' . (int) $ku['id'] . '/hapus') ?>" method="post"
                                            <?php if ($bisaHapus) : ?>
                                                data-konfirmasi="<?= esc('Kelompok umur ' . $ku['nama'] . ' akan dihapus beserta jenjang dan awalan nomor atletnya. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                                data-konfirmasi-judul="Hapus kelompok umur?"
                                                data-konfirmasi-tombol="Hapus kelompok umur"
                                            <?php endif ?>>
                                            <?= csrf_field() ?>
                                            <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 whitespace-nowrap rounded-lg border border-red-300 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100">
                                                <?= ikon('trash-2', 'size-4') ?>
                                                Hapus<span class="sr-only"> <?= esc($ku['nama']) ?></span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <p class="text-sm text-gray-600">
            Kelompok umur yang sudah punya nomor lomba, atlet, atau nomor atlet (BIB) tidak bisa dihapus.
            Ubah statusnya menjadi mati supaya tidak muncul di pendaftaran.
        </p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
