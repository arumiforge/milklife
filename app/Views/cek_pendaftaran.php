<?php
/**
 * Halaman cek pendaftaran (`/cek-pendaftaran`, rancangan 8). Pengendali:
 * `App\Controllers\CekPendaftaranController`. Tampilan Tailwind (F9-05,
 * tata letak `publik_tw`).
 *
 * Satu kartu per pendaftaran (`cek_pendaftaran/_kartu`), berurutan (K49).
 * Data sekolah tampil sekali di atas; total keseluruhan di bawah bila lebih
 * dari satu pendaftaran. Foto penanggung jawab tidak pernah ditampilkan.
 * Formulir unggah bukti bayar di kartu memakai jendela konfirmasi
 * (`konfirmasi.js`, modal Flowbite) dan bilah muat (`kemajuan.js`).
 *
 * @var CodeIgniter\View\View    $this
 * @var string                   $namaAcara
 * @var bool                     $siap  pengaturan lengkap
 * @var string                   $kode  isian yang sudah dirapikan
 * @var string|null              $galat pesan untuk official
 * @var string|null              $info  keterangan ID lama yang sudah digabung (K109)
 * @var string|null              $waTanya alamat WA tanya admin, untuk atlet yang ditolak (K116)
 * @var list<array<string, mixed>> $hasil lihat App\Libraries\DataPendaftaran
 * @var array{kunci_situs: string, aksi: string, skrip: string}|null $turnstile pemeriksaan keamanan (K122); null bila mati atau sudah lolos
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$turnstile ??= null;
$sekolah = $hasil[0] ?? null;
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Cek Pendaftaran – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Lihat data pendaftaran, biaya, dan status pembayaran sekolah.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_bilah_kemajuan_tw', [], ['saveData' => false]) ?>
<?= view('tata_letak/_judul_halaman_tw', ['judul' => 'Cek Pendaftaran', 'ikon' => 'search', 'gambar' => 'judul/cek-pendaftaran.webp', 'teks' => 'Lihat data pendaftaran, biaya, dan status pembayaran sekolah Anda.'], ['saveData' => false]) ?>

<div class="halaman-cek mx-auto max-w-5xl px-4 pb-12">
    <?php if (! $siap) : ?>
        <div class="cek-belum-siap mt-8" role="status">
            <?= komponen('kosong', [
                'ikon'  => 'hourglass',
                'judul' => 'Cek pendaftaran belum bisa dipakai',
                'teks'  => 'Panitia masih menyiapkan pengaturan pendaftaran. Silakan kembali lagi nanti.',
                'aksi'  => komponen('tombol', ['label' => 'Kembali ke beranda', 'href' => site_url('/'), 'varian' => 'kedua', 'ikon' => 'arrow-left']),
            ]) ?>
        </div>
    <?php else : ?>
        <form class="cek-formulir relative -mt-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-angkat sm:p-6" method="get" action="<?= site_url('cek-pendaftaran') ?>" role="search">
            <label class="<?= K::LABEL ?>" for="kode">NPSN atau ID pendaftaran</label>
            <div class="flex flex-wrap gap-2">
                <input class="<?= K::ISIAN ?><?= $galat !== null ? ' is-invalid' : '' ?> !w-auto min-w-0 flex-1 text-lg" type="text" id="kode" name="kode" value="<?= esc($kode) ?>" maxlength="30" autocomplete="off" required aria-describedby="kode-keterangan<?= $galat !== null ? ' kode-galat' : '' ?>">
                <button class="<?= K::UTAMA_BESAR ?>" type="submit"><?= ikon('search', 'size-5') ?> Cari</button>
            </div>
            <div class="<?= K::BANTU ?>" id="kode-keterangan">NPSN menampilkan semua pendaftaran sekolah itu. ID pendaftaran (contoh MAC2A0000126) menampilkan satu pendaftaran.</div>
            <?php if ($turnstile !== null) : ?>
                <div class="cf-turnstile mt-3" data-sitekey="<?= esc($turnstile['kunci_situs'], 'attr') ?>" data-action="<?= esc($turnstile['aksi'], 'attr') ?>" data-language="id"></div>
            <?php endif ?>
            <?php if ($galat !== null) : ?>
                <div class="mt-3 flex gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" id="kode-galat" role="alert"><?= ikon('triangle-alert', 'mt-0.5') ?><div><?= esc($galat) ?></div></div>
            <?php endif ?>
            <?php if (($info ?? null) !== null) : ?>
                <div class="mt-3 flex gap-3 rounded-xl border border-primary-200 bg-primary-100 p-4 text-sm text-primary-900" id="kode-info" role="status"><?= ikon('info', 'mt-0.5') ?><div><?= esc($info) ?></div></div>
            <?php endif ?>
        </form>

        <?php if ($sekolah !== null) : ?>
            <section class="cek-hasil mt-8 space-y-6" aria-labelledby="cek-sekolah">
                <div class="cek-sekolah flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-6">
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-primary-700" aria-hidden="true"><?= ikon('school', 'size-6') ?></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-oranye-700">Sekolah</p>
                        <h2 class="text-2xl font-extrabold break-words text-primary-900" id="cek-sekolah"><?= esc($sekolah['nama_sekolah']) ?></h2>
                        <dl class="cek-sekolah-data mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4 [&_dd]:font-semibold [&_dd]:text-gray-900 [&_dt]:text-gray-500">
                            <div><dt>NPSN</dt><dd><?= esc($sekolah['npsn'] ?? 'Tanpa NPSN') ?></dd></div>
                            <div><dt>Jenjang</dt><dd><?= esc($sekolah['jenjang_label']) ?></dd></div>
                            <div><dt>Kabupaten / Kota</dt><dd><?= esc($sekolah['kabupaten']) ?></dd></div>
                            <div><dt>Jumlah pendaftaran</dt><dd><?= count($hasil) ?></dd></div>
                        </dl>
                    </div>
                </div>

                <?php // Ringkasan atlet yang perlu didaftarkan ulang (K116, K117), dengan tautan ke kartunya. ?>
                <?php $perluUlang = array_filter(array_map(static fn (array $h): int => count($h['atlet_ditolak'] ?? []), $hasil)); ?>
                <?php if ($perluUlang !== []) : ?>
                    <div class="cek-ringkasan-ditolak flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" id="ringkasan-atlet-ditolak">
                        <?= ikon('octagon-alert', 'mt-0.5') ?>
                        <div>
                            <strong><?= array_sum($perluUlang) ?> atlet ditolak panitia dan perlu didaftarkan ulang.</strong>
                            Lihat alasannya dan kelompok umur yang disarankan di
                            <?php $tautan = []; ?>
                            <?php foreach (array_keys($perluUlang) as $i) : ?>
                                <?php $tautan[] = '<a class="font-semibold underline" href="#atlet-ditolak-' . ($i + 1) . '">' . esc($hasil[$i]['id_pendaftaran']) . '</a>'; ?>
                            <?php endforeach ?>
                            pendaftaran <?= implode(', ', $tautan) ?>.
                        </div>
                    </div>
                <?php endif ?>

                <?php foreach ($hasil as $nomor => $pendaftaran) : ?>
                    <?= view('cek_pendaftaran/_kartu', ['p' => $pendaftaran, 'urutan' => $nomor + 1, 'banyak' => count($hasil) > 1, 'kode' => $kode, 'waTanya' => $waTanya ?? null], ['saveData' => false]) ?>
                <?php endforeach ?>

                <?php if (count($hasil) > 1) : ?>
                    <div class="cek-total-semua flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-primary-900 px-5 py-4 text-white shadow-angkat">
                        <span class="flex items-center gap-2 font-semibold"><?= ikon('wallet', 'size-5') ?> Total semua pendaftaran</span>
                        <strong class="text-2xl font-extrabold"><?= rupiah(array_sum(array_column($hasil, 'total'))) ?></strong>
                    </div>
                <?php endif ?>
            </section>
        <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<?php if ($turnstile !== null) : ?>
    <script src="<?= esc($turnstile['skrip']) ?>" async defer></script>
<?php endif ?>
<?php // Bilah muat (K89) dan jendela konfirmasi unggah bukti bayar (K83), seperti di tata letak lama. ?>
<script src="<?= aset('js/kemajuan.js') ?>"></script>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
