<?php
/**
 * Isi hasil satu babak (F8-04, K124), tata letak Tailwind `admin_tw`:
 * prestasi, keterangan DNS/DNF/DQ, dan (khusus Final) koreksi peringkat; atur
 * peserta; jadwal babak; ubah nama atau tanda Final; hapus babak. Data dari
 * `HasilController::babak()`.
 *
 * Tanggal jadwal memakai pilihan Tanggal, Bulan, Tahun (`aset/js/pilih-tanggal.js`,
 * K89) seperti halaman Jadwal pertandingan; nilainya `YYYY-MM-DD`, dan
 * `JadwalPertandingan::periksa()` juga menerima `05/12/2026`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>       $babak      `HasilLomba::babak()`
 * @var list<array<string, mixed>> $hasil      `HasilLomba::hasilBabak()`
 * @var array<string, array<string, mixed>> $calon `HasilLomba::calon()`
 * @var array<int|string, string>  $galat      id hasil => pesan
 * @var array<int|string, mixed>   $isian      isian yang dikembalikan saat ada galat
 * @var array<string, string>      $galatBabak galat ubah babak
 * @var array{tanggal: string|null, jam: string|null, lokasi: string}|null $jadwal `JadwalPertandingan::babak()` (F9-07)
 * @var array<string, string>      $galatJadwal galat jadwal babak
 * @var array<string, mixed>       $isianJadwal isian jadwal yang dikembalikan saat ada galat
 */

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Peringkat;

$nomor  = $babak['nomor'];
$beregu = $nomor['jenis'] === 'beregu';
$final  = $babak['final'];
$contoh = ['waktu' => '12,34', 'jarak' => '3,45', 'poin' => '1250'][$nomor['jenis_hasil']] ?? '';
$nilai  = static fn (array $h, string $kotak, string $asal): string => (string) ($isian[$h['id']][$kotak] ?? $asal);

// Poin multi-lomba (F9-10): satu kotak prestasi per komponen, poin dihitung saat disimpan.
$komponen    = $nomor['jenis_hasil'] === JenisHasil::MULTI ? $nomor['komponen'] : [];
$multi       = $nomor['jenis_hasil'] === JenisHasil::MULTI;
$nilaiK      = static fn (array $h, string $kode): string => (string) ($isian[$h['id']]['komponen'][$kode] ?? $h['rincian_isian'][$kode] ?? '');
$kelasKotak  = static fn (bool $salah, bool $luar = false): string => 'block w-full min-w-20 rounded-lg border p-2 text-base sm:text-sm focus:ring-4 '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : ($luar ? 'border-yellow-500 bg-yellow-50 text-gray-900 focus:border-yellow-500 focus:ring-yellow-100' : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100'));
$kelasIsian  = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base sm:text-sm focus:ring-4 '
    . ($salah ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$luarTabel   = 0;
foreach ($hasil as $h) {
    foreach ($h['rincian'] as $r) {
        $luarTabel += $r['luar'] !== null ? 1 : 0;
    }
}

// Peserta sekarang ditambah calon yang belum ikut.
$pilihan = [];
foreach ($hasil as $h) {
    $pilihan[$h['kunci']] = ['nama' => $h['nama'], 'sekolah' => $h['sekolah'], 'nomor_dada' => $h['nomor_dada'], 'ikut' => true];
}
foreach ($calon as $kunci => $c) {
    $pilihan[$kunci] ??= ['nama' => $c['nama'], 'sekolah' => $c['sekolah'], 'nomor_dada' => $c['nomor_dada'], 'ikut' => false];
}
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc($babak['nama']) ?> <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [
    ['label' => 'Hasil lomba', 'alamat' => 'admin/hasil'],
    ['label' => $nomor['kelompok_umur'] . ' ' . $nomor['nama'], 'alamat' => 'admin/hasil/nomor/' . $nomor['id']],
    ['label' => $babak['nama']],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?php if ($multi && $komponen === []) : ?>
        <div id="tabel-skor-belum">
            <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => 'Nomor ini berjenis hasil poin multi-lomba tetapi belum punya tabel skor. Pilih tabel skor dan komponennya di '
                . '<a href="' . site_url('admin/pengaturan/nomor-lomba/' . $nomor['id'] . '/ubah') . '" class="font-semibold underline">Pengaturan &gt; Nomor Lomba</a>.']) ?>
        </div>
    <?php endif ?>
    <?php if ($luarTabel > 0) : ?>
        <div id="peringatan-luar-tabel">
            <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => $luarTabel . ' prestasi di luar tabel skor sehingga poinnya 0 (ditandai kuning di bawah). Periksa lagi apakah ada salah ketik, misalnya 0,70 untuk 7,00.']) ?>
        </div>
    <?php endif ?>
    <p class="text-gray-700">
        Prestasi <?= esc(JenisHasil::LABEL[$nomor['jenis_hasil']]) ?>: <?= esc(JenisHasil::KETERANGAN[$nomor['jenis_hasil']]) ?>.
        <?php if ($multi) : ?>Tulis waktu lari seperti 7,00 dan jarak dalam meter seperti 4,25. Poin tiap komponen dicari di tabel skor; prestasi di antara dua baris memakai baris yang lebih buruk. Komponen kosong bernilai 0.<?php endif ?>
        Peringkat dihitung otomatis setelah disimpan; prestasi sama mendapat peringkat sama<?php if ($nomor['jenis_hasil'] === JenisHasil::JARAK && $nomor['pemecah_seri']) : ?>, kecuali bila percobaannya diisi di Lembar juri: yang prestasi terbaik keduanya lebih jauh di atas<?php elseif ($nomor['jenis_hasil'] === JenisHasil::WAKTU) : ?>, kecuali bila waktunya ditulis sampai seperseribu detik (misalnya 12,345 dari foto finis)<?php endif ?>.
        Pilih DNS (tidak start), DNF (tidak finis), atau DQ (diskualifikasi) untuk peserta tanpa peringkat.
        <?php if ($final) : ?>Di Final peringkat boleh dikoreksi (misalnya dari foto finis); kosongkan supaya dihitung otomatis.<?php endif ?>
    </p>
    <div class="flex flex-wrap gap-2">
        <?php // Seri dan lintasan (F9-08). ?>
        <?= komponen('tombol', ['label' => 'Atur seri dan lintasan', 'href' => site_url('admin/seri/babak/' . $babak['id']), 'varian' => 'kedua', 'ikon' => 'list-ordered', 'atribut' => ['id' => 'tautan-seri-babak']]) ?>
        <?php // Lembar juri (F9-09). ?>
        <?= komponen('tombol', ['label' => 'Buka lembar juri', 'href' => site_url('admin/juri/babak/' . $babak['id']), 'varian' => 'kedua', 'ikon' => 'smartphone', 'atribut' => ['id' => 'tautan-lembar-juri']]) ?>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-hasil">
        <h2 class="flex flex-wrap items-center gap-2 text-lg font-bold break-words text-gray-900" id="judul-hasil"><?= esc($babak['nama']) ?><?php if ($final) : ?> <?= komponen('lencana', ['label' => 'Final', 'warna' => 'biru']) ?><?php endif ?></h2>
        <?php if ($hasil === []) : ?>
            <p class="mt-2 text-gray-600" id="peserta-kosong">Babak ini belum punya peserta. Pilih peserta di bagian bawah.</p>
        <?php else : ?>
            <form action="<?= site_url('admin/hasil/babak/' . $babak['id']) ?>" method="post" class="mt-4 space-y-4" novalidate>
                <?= csrf_field() ?>
                <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm text-gray-700">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-center">Per.</th>
                                <th scope="col" class="px-3 py-3"><?= $beregu ? 'Regu' : 'Atlet' ?></th>
                                <?php if ($multi) : ?>
                                    <?php foreach ($komponen as $k) : ?><th scope="col" class="px-3 py-3"><?= esc($k['nama']) ?></th><?php endforeach ?>
                                    <th scope="col" class="px-3 py-3 text-end">Total poin</th>
                                <?php else : ?>
                                    <th scope="col" class="px-3 py-3">Prestasi</th>
                                <?php endif ?>
                                <th scope="col" class="px-3 py-3">Ket.</th>
                                <?php if ($final) : ?><th scope="col" class="px-3 py-3">Koreksi per.</th><?php endif ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hasil as $h) : ?>
                                <?php $id = 'hasil-' . $h['id']; $salah = isset($galat[$h['id']]); ?>
                                <tr class="border-t border-gray-100 align-top">
                                    <td class="px-3 py-3 text-center font-bold text-gray-900"><?= $h['peringkat'] ?? '–' ?></td>
                                    <td class="min-w-40 px-3 py-3">
                                        <?php if (! $beregu) : ?><span class="font-semibold text-gray-600"><?= esc((string) $h['nomor_dada']) ?></span><?php endif ?>
                                        <span class="font-semibold break-words text-gray-900"><?= esc($h['nama']) ?></span>
                                        <?php if (! $beregu) : ?><span class="block text-sm break-words text-gray-600"><?= esc($h['sekolah']) ?></span><?php endif ?>
                                        <?php if ($h['manual']) : ?><?= komponen('lencana', ['label' => 'diubah admin', 'warna' => 'oranye']) ?><?php endif ?>
                                        <?php // Versi baris (F9-09): simpanan ditolak bila baris ini sudah diubah di Lembar juri sejak halaman dibuka. ?>
                                        <input type="hidden" name="hasil[<?= $h['id'] ?>][versi]" value="<?= $h['versi'] ?>">
                                    </td>
                                    <?php if ($multi) : ?>
                                        <?php foreach ($komponen as $i => $k) : ?>
                                            <?php $r = $h['rincian'][$i] ?? null; $idK = $id . '-' . $k['kode']; ?>
                                            <td class="px-3 py-3">
                                                <label class="sr-only" for="<?= esc($idK, 'attr') ?>"><?= esc($k['nama']) ?> <?= esc($h['nama']) ?></label>
                                                <input type="text" inputmode="decimal" class="kotak-prestasi <?= $kelasKotak($salah, $r !== null && $r['luar'] !== null) ?>" id="<?= esc($idK, 'attr') ?>"
                                                    name="hasil[<?= $h['id'] ?>][komponen][<?= esc($k['kode'], 'attr') ?>]" placeholder="<?= $k['jenis'] === JenisHasil::WAKTU ? '7,00' : '4,25' ?>" value="<?= esc($nilaiK($h, $k['kode']), 'attr') ?>"
                                                    <?= $salah ? 'aria-invalid="true" aria-describedby="' . $id . '-galat"' : '' ?>>
                                                <?php if ($r !== null && $r['prestasi'] === '') : ?>
                                                    <p class="mt-1 text-xs text-gray-600" data-poin-komponen>kosong, 0 poin</p>
                                                <?php endif ?>
                                                <?php if ($r !== null && $r['prestasi'] !== '') : ?>
                                                    <p class="mt-1 text-xs<?= $r['luar'] !== null ? ' font-semibold text-yellow-800' : ' text-gray-600' ?>" data-poin-komponen><?= $r['poin'] ?> poin<?= $r['luar'] !== null ? ', di luar tabel' : '' ?></p>
                                                <?php endif ?>
                                            </td>
                                        <?php endforeach ?>
                                        <td class="px-3 py-3 text-end font-semibold text-gray-900" data-total-poin>
                                            <?= esc($h['prestasi_teks']) ?>
                                            <?php if ($salah) : ?><p id="<?= $id ?>-galat" class="mt-1 text-start text-sm font-medium text-red-700"><?= esc($galat[$h['id']]) ?></p><?php endif ?>
                                        </td>
                                    <?php else : ?>
                                    <td class="px-3 py-3">
                                        <label class="sr-only" for="<?= $id ?>-prestasi">Prestasi <?= esc($h['nama']) ?></label>
                                        <input type="text" inputmode="decimal" class="kotak-prestasi <?= $kelasKotak($salah) ?>" id="<?= $id ?>-prestasi"
                                            name="hasil[<?= $h['id'] ?>][prestasi]" placeholder="<?= esc($contoh, 'attr') ?>" value="<?= esc($nilai($h, 'prestasi', $h['prestasi_isian']), 'attr') ?>"
                                            <?= $salah ? 'aria-invalid="true" aria-describedby="' . $id . '-galat"' : '' ?>>
                                        <?php if ($h['percobaan'] !== null) : ?>
                                            <p class="mt-1 text-xs text-gray-600" data-percobaan>Percobaan: <?= esc(implode(' / ', array_map(static fn (string $p): string => $p === '' ? '–' : $p, $h['percobaan_teks']))) ?></p>
                                        <?php endif ?>
                                        <?php if ($salah) : ?><p id="<?= $id ?>-galat" class="mt-1 text-sm font-medium text-red-700"><?= esc($galat[$h['id']]) ?></p><?php endif ?>
                                    </td>
                                    <?php endif ?>
                                    <td class="px-3 py-3">
                                        <label class="sr-only" for="<?= $id ?>-keterangan">Keterangan <?= esc($h['nama']) ?></label>
                                        <select class="<?= $kelasKotak(false) ?>" id="<?= $id ?>-keterangan" name="hasil[<?= $h['id'] ?>][keterangan]">
                                            <option value="">–</option>
                                            <?php foreach (Peringkat::KETERANGAN as $kode => $arti) : ?>
                                                <option value="<?= $kode ?>" title="<?= esc($arti, 'attr') ?>"<?= $nilai($h, 'keterangan', (string) $h['keterangan']) === $kode ? ' selected' : '' ?>><?= $kode ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </td>
                                    <?php if ($final) : ?>
                                        <td class="px-3 py-3">
                                            <label class="sr-only" for="<?= $id ?>-peringkat">Koreksi peringkat <?= esc($h['nama']) ?></label>
                                            <input type="text" inputmode="numeric" class="kotak-peringkat <?= $kelasKotak(false) ?>" id="<?= $id ?>-peringkat" name="hasil[<?= $h['id'] ?>][peringkat]"
                                                value="<?= esc($nilai($h, 'peringkat', $h['manual'] ? (string) $h['peringkat'] : ''), 'attr') ?>">
                                        </td>
                                    <?php endif ?>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= komponen('tombol', ['label' => 'Simpan hasil', 'tipe' => 'submit', 'ikon' => 'save']) ?>
                    <?= komponen('tombol', ['label' => 'Kembali ke ' . $nomor['nama'], 'href' => site_url('admin/hasil/nomor/' . $nomor['id']), 'varian' => 'hantu', 'ikon' => 'arrow-left']) ?>
                </div>
            </form>
        <?php endif ?>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="peserta" aria-labelledby="judul-peserta">
        <h2 class="text-lg font-bold text-gray-900" id="judul-peserta">Peserta babak</h2>
        <p class="mt-1 text-sm text-gray-600">Centang <?= $beregu ? 'regu' : 'atlet' ?> yang ikut babak ini. Calon peserta: <?= $beregu ? 'regu' : 'atlet' ?> dari pendaftaran Lunas di nomor ini. Peserta yang dikeluarkan kehilangan prestasinya di babak ini.</p>
        <?php if ($pilihan === []) : ?>
            <p class="mt-3 text-gray-600" id="calon-kosong">Belum ada <?= $beregu ? 'regu' : 'atlet' ?> dari pendaftaran Lunas di nomor ini.</p>
        <?php else : ?>
            <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/peserta') ?>" method="post" class="mt-4 space-y-4"<?= $hasil === [] ? '' : ' data-konfirmasi="Peserta yang tidak dicentang dikeluarkan dari babak ini, dan prestasinya di babak ini ikut terhapus." data-konfirmasi-judul="Simpan peserta?" data-konfirmasi-tombol="Simpan peserta" data-konfirmasi-jenis="utama"' ?>>
                <?= csrf_field() ?>
                <div class="grid gap-x-4 sm:grid-cols-2">
                    <?php foreach ($pilihan as $kunci => $p) : ?>
                        <?php $idKotak = 'peserta-' . substr(md5($kunci), 0, 10); ?>
                        <label class="inline-flex min-h-11 min-w-0 items-center gap-2 text-sm text-gray-900" for="<?= $idKotak ?>">
                            <input class="size-4 shrink-0 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" name="peserta[]" id="<?= $idKotak ?>" value="<?= esc($kunci, 'attr') ?>"<?= $p['ikut'] ? ' checked' : '' ?>>
                            <span class="min-w-0 break-words">
                                <?php if ($p['nomor_dada'] !== null) : ?><span class="font-semibold"><?= esc((string) $p['nomor_dada']) ?></span><?php endif ?>
                                <?= esc($p['nama']) ?><?= $beregu ? '' : ' <span class="text-gray-600">' . esc($p['sekolah']) . '</span>' ?>
                            </span>
                        </label>
                    <?php endforeach ?>
                </div>
                <?= komponen('tombol', ['label' => 'Simpan peserta', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'users']) ?>
            </form>
        <?php endif ?>
    </section>

    <?php
    // Jadwal babak (F9-07): sama dengan baris babak ini di Jadwal pertandingan.
    $jadwal      ??= ['tanggal' => null, 'jam' => null, 'lokasi' => ''];
    $galatJadwal ??= [];
    $isianJadwal ??= [];
    $nilaiJadwal = static fn (string $kotak, string $asal): string => is_string($isianJadwal[$kotak] ?? null) ? $isianJadwal[$kotak] : $asal;
    $tandaJadwal = static fn (string $kotak, string $id): string => isset($galatJadwal[$kotak]) ? ' aria-invalid="true" aria-describedby="' . $id . '-galat"' : '';
    $pesanJadwal = static fn (string $kotak, string $id): string => isset($galatJadwal[$kotak]) ? '<p id="' . $id . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galatJadwal[$kotak]) . '</p>' : '';
    ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="jadwal-babak" aria-labelledby="judul-jadwal-babak">
        <h2 class="text-lg font-bold text-gray-900" id="judul-jadwal-babak">Jadwal babak</h2>
        <p class="mt-1 text-sm text-gray-600">
            Tanggal, jam mulai (WIB), dan lokasi babak ini di halaman Jadwal. Semua babak bisa diisi sekaligus di
            <a href="<?= site_url('admin/jadwal-pertandingan') ?>" class="font-semibold text-primary-700 underline">Jadwal pertandingan</a>.
        </p>
        <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/jadwal') ?>" method="post" class="mt-4 space-y-4" novalidate>
            <?= csrf_field() ?>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12">
                <div class="sm:col-span-2 lg:col-span-5">
                    <label for="jadwal-tanggal" class="mb-1.5 block text-sm font-semibold text-gray-900">Tanggal</label>
                    <input type="date" class="<?= $kelasIsian(isset($galatJadwal['tanggal'])) ?>" id="jadwal-tanggal" name="tanggal" value="<?= esc($nilaiJadwal('tanggal', (string) $jadwal['tanggal']), 'attr') ?>"<?= JadwalPertandingan::rentangIsian() ?><?= $tandaJadwal('tanggal', 'jadwal-tanggal') ?>>
                    <?= $pesanJadwal('tanggal', 'jadwal-tanggal') ?>
                </div>
                <div class="lg:col-span-3">
                    <label for="jadwal-jam" class="mb-1.5 block text-sm font-semibold text-gray-900">Jam mulai</label>
                    <input type="time" class="<?= $kelasIsian(isset($galatJadwal['jam_mulai'])) ?>" id="jadwal-jam" name="jam_mulai" value="<?= esc($nilaiJadwal('jam_mulai', (string) $jadwal['jam']), 'attr') ?>"<?= $tandaJadwal('jam_mulai', 'jadwal-jam') ?>>
                    <?= $pesanJadwal('jam_mulai', 'jadwal-jam') ?>
                </div>
                <div class="lg:col-span-4">
                    <label for="jadwal-lokasi" class="mb-1.5 block text-sm font-semibold text-gray-900">Lokasi</label>
                    <input type="text" class="<?= $kelasIsian(isset($galatJadwal['lokasi'])) ?>" id="jadwal-lokasi" name="lokasi" maxlength="<?= JadwalPertandingan::LOKASI_MAKSIMAL ?>" placeholder="Boleh kosong, misalnya Lintasan utama" value="<?= esc($nilaiJadwal('lokasi', $jadwal['lokasi']), 'attr') ?>"<?= $tandaJadwal('lokasi', 'jadwal-lokasi') ?>>
                    <?= $pesanJadwal('lokasi', 'jadwal-lokasi') ?>
                </div>
            </div>
            <?= komponen('tombol', ['label' => 'Simpan jadwal babak', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'calendar-check']) ?>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="ubah-babak" aria-labelledby="judul-ubah-babak">
        <h2 class="text-lg font-bold text-gray-900" id="judul-ubah-babak">Ubah babak</h2>
        <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/ubah') ?>" method="post" class="mt-4 space-y-4" novalidate>
            <?= csrf_field() ?>
            <div class="grid items-end gap-4 sm:grid-cols-2">
                <div>
                    <label for="nama" class="mb-1.5 block text-sm font-semibold text-gray-900">Nama babak</label>
                    <input type="text" class="<?= $kelasIsian(isset($galatBabak['nama'])) ?>" id="nama" name="nama" maxlength="<?= HasilLomba::NAMA_BABAK_MAKSIMAL ?>" value="<?= esc($babak['nama'], 'attr') ?>"<?= isset($galatBabak['nama']) ? ' aria-invalid="true" aria-describedby="nama-galat"' : '' ?>>
                    <?php if (isset($galatBabak['nama'])) : ?><p id="nama-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galatBabak['nama']) ?></p><?php endif ?>
                </div>
                <div>
                    <label for="final" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                        <input type="checkbox" role="switch" id="final" name="final" value="1" class="peer sr-only"<?= $final ? ' checked' : '' ?><?= isset($galatBabak['final']) ? ' aria-invalid="true" aria-describedby="final-galat"' : '' ?>>
                        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                        <span class="text-sm font-semibold text-gray-900">Babak Final</span>
                    </label>
                    <?php if (isset($galatBabak['final'])) : ?><p id="final-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galatBabak['final']) ?></p><?php endif ?>
                </div>
            </div>
            <?= komponen('tombol', ['label' => 'Simpan babak', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'save']) ?>
        </form>
        <form action="<?= site_url('admin/hasil/babak/' . $babak['id'] . '/hapus') ?>" method="post" class="mt-6 border-t border-gray-100 pt-4"
            data-konfirmasi="<?= esc('Babak ' . $babak['nama'] . ' beserta semua prestasi pesertanya akan dihapus. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
            data-konfirmasi-judul="Hapus babak?" data-konfirmasi-tombol="Hapus babak">
            <?= csrf_field() ?>
            <?= komponen('tombol', ['label' => 'Hapus babak', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'trash-2']) ?>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<?= $this->endSection() ?>
