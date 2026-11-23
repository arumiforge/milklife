<?php
/**
 * Lembar juri satu babak (F9-09, K140 butir 7d), tata letak Tailwind
 * `admin_tw`, ponsel lebih dulu. Data dari `Admin\JuriController::babak()`.
 *
 * - Pilih seri (F9-08), lalu satu kartu per peserta: lintasan atau urutan,
 *   BIB besar, nama, sekolah.
 * - Isian mengikuti jenis hasil nomor: waktu (boleh seperseribu detik),
 *   jarak per percobaan dengan tombol X (gagal), poin, atau prestasi tiap
 *   komponen poin multi-lomba (`komponen[kode]`, sama dengan halaman hasil).
 * - Setiap kartu formulir sendiri: `aset/js/lembar-juri.js` menyimpannya
 *   otomatis begitu diisi dan menulis keadaannya (`data-keadaan` =
 *   kosong | belum | menyimpan | tersimpan | galat | bentrok); tanpa skrip,
 *   tombol Simpan di tiap kartu tetap bekerja.
 *
 * @var CodeIgniter\View\View                  $this
 * @var array<string, mixed>                   $babak   `SeriLintasan::babak()`
 * @var array<int, list<array<string, mixed>>> $perSeri seri => peserta (`HasilLomba::hasilBabak()`); 0 = belum dibagi
 * @var int                                    $seri    seri yang dibuka
 */

use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\LembarJuri;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\UnduhanSeri;
use App\Libraries\SesiAdmin;

// ikon-sprite: trophy list-ordered users check
// Akun juri tidak bisa membuka Hasil, Seri, dan Pengaturan (F11-16), jadi tanpa tautan ke sana.
$juri     = SesiAdmin::juri();
$nomor    = $babak['nomor'];
$jenis    = $nomor['jenis_hasil'];
$lintasan = $babak['aturan']['lintasan'];
$beregu   = $nomor['jenis'] === 'beregu';
$jadwal   = UnduhanSeri::jadwal($babak);
$peserta  = $perSeri[$seri] ?? [];
$namaSeri = static fn (int $s): string => $s === 0 ? 'Belum dibagi' : ($lintasan ? 'Seri ' . $s : 'Kelompok ' . $s);
$komponen = $jenis === JenisHasil::MULTI ? $nomor['komponen'] : [];

$petunjuk = [
    JenisHasil::WAKTU => 'Tulis waktu seperti 12,34, atau 2:05,10 untuk lari jauh. Waktu dari foto finis boleh sampai seperseribu detik, misalnya 12,345.',
    JenisHasil::JARAK => 'Tulis jarak tiap percobaan dalam meter, misalnya 3,45. Tekan X bila percobaan gagal. Kosongkan percobaan yang belum dilakukan. Prestasinya adalah jarak sah terjauh.',
    JenisHasil::POIN  => 'Tulis poin akhir berupa angka bulat, misalnya 1250.',
    JenisHasil::MULTI => 'Tulis prestasi tiap komponen: waktu lari seperti 7,00 dan jarak dalam meter seperti 4,25. Poinnya dihitung dari tabel skor saat tersimpan.',
][$jenis] ?? '';

// Waktu tangan (F11-04).
if (! empty($nomor['waktu_tangan'])) {
    $petunjuk = 'Waktu tangan: tulis waktu stopwatch seperti 12,34 atau 12,4. Waktunya dibulatkan ke atas ke 0,1 detik saat tersimpan, jadi 12,34 menjadi 12,4.';
}

$kelasKotak = 'block h-14 w-full min-w-0 rounded-lg border border-gray-300 bg-white px-2 text-center text-2xl font-bold text-gray-900 tabular-nums placeholder:font-normal placeholder:text-gray-500 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 aria-[invalid=true]:border-red-500 aria-[invalid=true]:bg-red-50';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Juri <?= esc($babak['nama']) ?> <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Lembar juri', 'alamat' => 'admin/juri'], ['label' => $nomor['nama'] . ' ' . $nomor['kelompok_umur']], ['label' => $babak['nama']]]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4" data-lembar-juri>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-babak-juri">
        <h2 id="judul-babak-juri" class="text-lg font-bold break-words text-gray-900"><?= esc(JadwalPertandingan::awalanAcara($babak['nomor_acara'])) ?><?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?> · <?= esc($babak['nama']) ?></h2>
        <p class="text-sm text-gray-600"><?= $jadwal === '' ? 'Jadwal menyusul' : esc($jadwal) ?> · <?= $babak['peserta'] ?> <?= $beregu ? 'regu' : 'peserta' ?></p>
        <p class="mt-2 text-sm text-gray-700"><?= esc($petunjuk) ?> Pilih DNS, DNF, atau DQ untuk peserta yang tidak start, tidak finis, atau didiskualifikasi. Untuk DQ, tulis pasalnya bila ada, misalnya TR16.8.<?php if ($jenis === JenisHasil::JARAK) : ?> Bila semua percobaan X, keterangan NM (tanpa lompatan atau lemparan sah) terisi sendiri. Pilih r bila peserta mundur: prestasi sebelum mundur tetap dinilai.<?php endif ?></p>
        <?php if ($juri) : ?>
            <p class="mt-2 text-sm text-gray-700">Peringkat dan penerbitan hasil diurus admin panitia.</p>
        <?php else : ?>
            <p class="mt-2 text-sm text-gray-700">Peringkat dan tombol Terbitkan ada di <a href="<?= site_url('admin/hasil/babak/' . $babak['id']) ?>" class="font-semibold text-primary-700 underline">halaman hasil babak ini</a>.</p>
        <?php endif ?>
        <?php if ($jenis === JenisHasil::MULTI && $komponen === []) : ?>
            <div class="mt-3"><?= komponen('peringatan', ['jenis' => 'peringatan', 'judul' => 'Tabel skor belum dipilih', 'isi' => 'Nomor ini belum punya tabel skor. Prestasinya baru bisa diisi setelah tabel skor dipilih. ' . ($juri ? 'Minta admin panitia memilih tabel skornya.' : 'Pilih tabel skor dan komponennya di Pengaturan &gt; Nomor lomba.')]) ?></div>
        <?php endif ?>
    </section>

    <?php if (count($perSeri) > 1) : ?>
        <nav aria-label="Pilih seri" class="flex flex-wrap gap-2" data-pilih-seri>
            <?php foreach ($perSeri as $s => $daftar) : ?>
                <a href="<?= site_url('admin/juri/babak/' . $babak['id']) . '?seri=' . $s ?>" class="inline-flex min-h-11 items-center rounded-lg border px-4 text-sm font-semibold <?= $s === $seri ? 'border-primary-700 bg-primary-700 text-white' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50' ?>"<?= $s === $seri ? ' aria-current="page"' : '' ?>><?= esc($namaSeri($s)) ?> <span class="ms-1 font-normal">(<?= count($daftar) ?>)</span></a>
            <?php endforeach ?>
        </nav>
    <?php endif ?>

    <?php if ($peserta === []) : ?>
        <?php if ($juri) : ?>
            <?= komponen('kosong', ['padat' => true, 'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'], 'ikon' => 'users', 'judul' => 'Belum ada peserta', 'teks' => 'Babak ini belum punya peserta. Minta admin panitia mengatur pesertanya, lalu muat ulang halaman ini.']) ?>
        <?php else : ?>
            <?= komponen('kosong', ['padat' => true, 'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'], 'ikon' => 'users', 'judul' => 'Belum ada peserta', 'teks' => 'Babak ini belum punya peserta. Atur pesertanya di halaman hasil babak atau di Seri dan lintasan.', 'aksi' => komponen('tombol', ['label' => 'Buka seri dan lintasan', 'href' => site_url('admin/seri/babak/' . $babak['id']), 'ikon' => 'list-ordered'])]) ?>
        <?php endif ?>
    <?php else : ?>
        <h3 class="text-base font-bold tracking-wide text-oranye-700 uppercase"><?= esc($namaSeri($seri)) ?></h3>
        <ol class="space-y-3" aria-label="Peserta <?= esc($namaSeri($seri), 'attr') ?>">
            <?php foreach ($peserta as $p) : ?>
                <?php
                $awal     = 'juri-' . $p['id'];
                $terisi   = $p['prestasi'] !== null || $p['keterangan'] !== null || $p['percobaan'] !== null;
                $ringkas  = LembarJuri::ringkasan($p);
                $bisaIsi  = $jenis !== JenisHasil::MULTI || $komponen !== [];
                ?>
                <li>
                    <form id="baris-<?= $p['id'] ?>" action="<?= site_url('admin/juri/babak/' . $babak['id'] . '/' . $p['id']) ?>" method="post" novalidate
                        class="group scroll-mt-20 rounded-2xl border-2 border-gray-200 bg-white p-4 shadow-lembut data-[keadaan=bentrok]:border-red-500 data-[keadaan=galat]:border-red-500 data-[keadaan=menyimpan]:border-primary-300 data-[keadaan=belum]:border-oranye-300 sm:p-5"
                        data-baris-juri="<?= $p['id'] ?>" data-keadaan="<?= $terisi ? 'tersimpan' : 'kosong' ?>" aria-labelledby="<?= $awal ?>-nama">
                        <?= csrf_field() ?>
                        <input type="hidden" name="versi" value="<?= $p['versi'] ?>" data-versi>
                        <input type="hidden" name="seri" value="<?= $seri ?>">

                        <div class="flex items-start gap-3">
                            <?php if ($p['lintasan'] !== null) : ?>
                                <div class="flex w-14 shrink-0 flex-col items-center rounded-xl bg-primary-50 py-1 text-primary-900">
                                    <span class="text-xs font-semibold"><?= $lintasan ? 'Lint.' : 'Urut' ?></span>
                                    <span class="text-2xl font-extrabold leading-tight" data-lintasan><?= $p['lintasan'] ?></span>
                                </div>
                            <?php endif ?>
                            <div class="min-w-0 flex-1">
                                <?php if (! $beregu && ($p['nomor_dada'] ?? '') !== '') : ?>
                                    <p class="text-3xl font-extrabold leading-none text-gray-900 tabular-nums" data-bib><?= esc((string) $p['nomor_dada']) ?></p>
                                <?php endif ?>
                                <p id="<?= $awal ?>-nama" class="mt-1 text-base font-bold break-words text-gray-900"><?= esc($p['nama']) ?></p>
                                <?php if (! $beregu) : ?><p class="text-sm break-words text-gray-600"><?= esc($p['sekolah']) ?></p><?php endif ?>
                            </div>
                        </div>

                        <?php if ($bisaIsi) : ?>
                            <div class="mt-3 space-y-3">
                                <?php if ($jenis === JenisHasil::JARAK) : ?>
                                    <fieldset>
                                        <legend class="mb-1 text-sm font-semibold text-gray-700">Percobaan (meter)</legend>
                                        <div class="grid grid-cols-3 gap-2">
                                            <?php foreach ($p['percobaan_teks'] as $i => $teks) : ?>
                                                <div class="min-w-0">
                                                    <label for="<?= $awal ?>-p<?= $i ?>" class="block text-center text-xs font-semibold text-gray-600"><?= $i + 1 ?></label>
                                                    <input type="text" id="<?= $awal ?>-p<?= $i ?>" name="percobaan[]" value="<?= esc($teks, 'attr') ?>" inputmode="decimal" autocomplete="off" enterkeyhint="next" maxlength="6" placeholder="3,45" class="<?= $kelasKotak ?>" data-isian-juri data-percobaan="<?= $i ?>">
                                                    <button type="button" hidden class="mt-1 min-h-11 w-full rounded-lg border border-gray-300 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:ring-4 focus:ring-gray-100 aria-pressed:border-red-600 aria-pressed:bg-red-600 aria-pressed:text-white" data-tombol-gagal="<?= $awal ?>-p<?= $i ?>" aria-pressed="<?= $teks === 'X' ? 'true' : 'false' ?>" aria-label="Percobaan <?= $i + 1 ?> gagal">X</button>
                                                </div>
                                            <?php endforeach ?>
                                        </div>
                                    </fieldset>
                                <?php elseif ($jenis === JenisHasil::MULTI) : ?>
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <?php foreach ($komponen as $i => $k) : ?>
                                            <?php $r = $p['rincian'][$i] ?? null; ?>
                                            <div class="min-w-0">
                                                <label for="<?= $awal ?>-<?= esc($k['kode'], 'attr') ?>" class="block text-sm font-semibold text-gray-700"><?= esc($k['nama']) ?></label>
                                                <input type="text" id="<?= $awal ?>-<?= esc($k['kode'], 'attr') ?>" name="komponen[<?= esc($k['kode'], 'attr') ?>]" value="<?= esc($p['rincian_isian'][$k['kode']] ?? '', 'attr') ?>" inputmode="decimal" autocomplete="off" enterkeyhint="next" maxlength="8" placeholder="<?= $k['jenis'] === JenisHasil::WAKTU ? '7,00' : '4,25' ?>" class="<?= $kelasKotak ?>" data-isian-juri>
                                                <p class="mt-1 text-sm text-gray-600" data-poin-komponen="<?= $i ?>"><?= $r === null || $r['prestasi'] === '' ? '' : esc($r['poin'] . ' poin' . ($r['luar'] !== null ? ', di luar tabel' : '')) ?></p>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                <?php else : ?>
                                    <div>
                                        <label for="<?= $awal ?>-prestasi" class="block text-sm font-semibold text-gray-700"><?= $jenis === JenisHasil::WAKTU ? 'Waktu (detik)' : 'Poin' ?></label>
                                        <input type="text" id="<?= $awal ?>-prestasi" name="prestasi" value="<?= esc($p['prestasi_isian'], 'attr') ?>" inputmode="<?= $jenis === JenisHasil::POIN ? 'numeric' : 'decimal' ?>" autocomplete="off" enterkeyhint="next" maxlength="10" placeholder="<?= $jenis === JenisHasil::WAKTU ? '12,34' : '1250' ?>" class="<?= $kelasKotak ?>" data-isian-juri>
                                    </div>
                                <?php endif ?>

                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="min-w-0 flex-1 basis-40">
                                        <label for="<?= $awal ?>-keterangan" class="block text-sm font-semibold text-gray-700">Keterangan</label>
                                        <select id="<?= $awal ?>-keterangan" name="keterangan" class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100" data-isian-juri>
                                            <option value="">Ada prestasi</option>
                                            <?php foreach (Peringkat::pilihan($jenis) as $kode => $arti) : ?>
                                                <option value="<?= $kode ?>"<?= $p['keterangan'] === $kode ? ' selected' : '' ?>><?= $kode ?> – <?= esc($arti) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                    <?php // Pasal diskualifikasi (F11-05), disimpan bila keterangan DQ. ?>
                                    <div class="min-w-0 flex-1 basis-40">
                                        <label for="<?= $awal ?>-pasal" class="block text-sm font-semibold text-gray-700">Pasal DQ</label>
                                        <input type="text" id="<?= $awal ?>-pasal" name="pasal" value="<?= esc((string) $p['pasal'], 'attr') ?>" maxlength="<?= Peringkat::PANJANG_PASAL ?>" autocomplete="off" placeholder="TR16.8" class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100" data-isian-juri data-pasal>
                                    </div>
                                    <?= komponen('tombol', ['label' => 'Simpan', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check', 'kelas' => 'min-h-12', 'atribut' => ['data-tombol-simpan' => '1']]) ?>
                                </div>
                            </div>
                        <?php endif ?>

                        <p class="mt-3 min-h-6 text-sm font-semibold text-gray-600 group-data-[keadaan=bentrok]:text-red-700 group-data-[keadaan=belum]:text-oranye-800 group-data-[keadaan=galat]:text-red-700 group-data-[keadaan=tersimpan]:text-green-800" role="status" aria-live="polite" data-status-baris><?= $terisi ? esc('Tersimpan' . ($ringkas === '' ? '' : ': ' . $ringkas)) : 'Belum diisi' ?></p>
                    </form>
                </li>
            <?php endforeach ?>
        </ol>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/lembar-juri.js') ?>"></script>
<?= $this->endSection() ?>
