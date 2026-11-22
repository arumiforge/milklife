<?php
/**
 * Langkah 2: Penanggung Jawab (rancangan 6.3; K50 foto wajib). Kartu tiap
 * orang dibuat `aset/js/pendaftaran/ofisial.js` dari `#templat-penanggung-jawab`.
 *
 * @var array<string, mixed> $wizard
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$minimal  = (int) $wizard['aturan']['penanggung_jawab_minimal'];
$maksimal = (int) $wizard['aturan']['penanggung_jawab_maksimal'];
?>
<section class="langkah" id="langkah-2" data-langkah="2" aria-labelledby="judul-langkah-2" hidden>
    <?= view('pendaftaran/_kepala_langkah', ['langkah' => 2, 'judul' => 'Ofisial'], ['saveData' => false]) ?>
    <p class="langkah-keterangan mb-5 text-gray-600">
        Isi <?= $minimal === $maksimal ? $minimal : $minimal . ' sampai ' . $maksimal ?> orang yang bisa dihubungi panitia.
        Foto wajib diisi dan hanya dilihat panitia.
    </p>

    <div class="<?= K::RINGKASAN ?>" role="alert" hidden></div>

    <div id="daftar-penanggung-jawab" data-jalur="penanggung_jawab"></div>

    <button type="button" class="<?= K::GARIS ?> mb-4" id="tambah-penanggung-jawab">
        <?= ikon('user-plus', 'size-5') ?> Tambah ofisial
    </button>

    <template id="templat-penanggung-jawab">
        <div class="<?= K::KARTU ?> kartu-orang">
            <div class="kartu-orang-kepala mb-4 flex items-center justify-between gap-2">
                <h3 class="font-bold text-primary-900" data-judul></h3>
                <button type="button" class="<?= K::HAPUS_KECIL ?>" data-hapus>
                    <?= ikon('trash-2', 'size-4') ?> Hapus
                </button>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="<?= K::LABEL ?>" data-untuk="nama">Nama lengkap</label>
                    <input type="text" class="<?= K::ISIAN ?>" data-isian="nama" maxlength="100" autocomplete="name">
                    <div class="<?= K::UMPAN ?>"></div>
                </div>
                <div>
                    <label class="<?= K::LABEL ?>" data-untuk="nomor_wa">Nomor WA</label>
                    <input type="tel" class="<?= K::ISIAN ?>" data-isian="nomor_wa" maxlength="20" autocomplete="tel" placeholder="0812-3456-7890">
                    <div class="<?= K::UMPAN ?>"></div>
                </div>
                <div class="md:col-span-2">
                    <span class="<?= K::LABEL ?>" data-label-foto>Foto</span>
                    <div class="foto-kotak flex flex-wrap items-center gap-4 rounded-lg p-1 [&.is-invalid]:ring-2 [&.is-invalid]:ring-red-500" data-isian-foto>
                        <img class="foto-pratinjau size-22 flex-none rounded-lg object-cover" alt="" hidden>
                        <span class="foto-kosong inline-flex size-22 flex-none items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white text-gray-400"><?= ikon('scan-face', 'size-9') ?></span>
                        <div class="foto-tombol min-w-0 flex-[1_1_12rem]">
                            <label class="<?= K::GARIS_KECIL ?> cursor-pointer has-focus-visible:ring-4">
                                <?= ikon('camera', 'size-4') ?> <span data-teks-foto>Ambil atau pilih foto</span>
                                <input type="file" accept="image/*" class="sr-only" data-isian="foto">
                            </label>
                            <div class="<?= K::BANTU ?>">Foto wajah yang jelas. Diperkecil otomatis sebelum dikirim.</div>
                        </div>
                    </div>
                    <div class="<?= K::UMPAN ?>" data-galat-foto></div>
                </div>
            </div>
        </div>
    </template>

    <div class="langkah-tombol mt-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" class="<?= K::KEDUA ?>" data-kembali><?= ikon('arrow-left', 'size-5') ?> Kembali</button>
        <button type="button" class="<?= K::UTAMA_BESAR ?>" data-lanjut>Lanjut <?= ikon('arrow-right', 'size-5') ?></button>
    </div>
</section>
