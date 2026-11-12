<?php
/**
 * Langkah 2: Penanggung Jawab (rancangan 6.3; K50 foto wajib). Kartu tiap
 * orang dibuat `pendaftaran.js` dari `#templat-penanggung-jawab`.
 *
 * @var array<string, mixed> $wizard
 */

$minimal  = (int) $wizard['aturan']['penanggung_jawab_minimal'];
$maksimal = (int) $wizard['aturan']['penanggung_jawab_maksimal'];
?>
<section class="langkah" id="langkah-2" data-langkah="2" aria-labelledby="judul-langkah-2" hidden>
    <h2 id="judul-langkah-2" tabindex="-1">2. Penanggung Jawab</h2>
    <p class="langkah-keterangan">
        Isi <?= $minimal === $maksimal ? $minimal : $minimal . ' sampai ' . $maksimal ?> orang yang bisa dihubungi panitia.
        Foto wajib diisi dan hanya dilihat panitia.
    </p>

    <div class="ringkasan-galat alert alert-danger" role="alert" hidden></div>

    <div id="daftar-penanggung-jawab" data-jalur="penanggung_jawab"></div>

    <button type="button" class="btn btn-outline-primary mb-4" id="tambah-penanggung-jawab">
        <i class="bi bi-person-plus" aria-hidden="true"></i> Tambah penanggung jawab
    </button>

    <template id="templat-penanggung-jawab">
        <div class="kartu-isian kartu-orang">
            <div class="kartu-orang-kepala">
                <h3 class="h6 mb-0" data-judul></h3>
                <button type="button" class="btn btn-sm btn-outline-danger" data-hapus>
                    <i class="bi bi-trash" aria-hidden="true"></i> Hapus
                </button>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" data-untuk="nama">Nama lengkap</label>
                    <input type="text" class="form-control" data-isian="nama" maxlength="100" autocomplete="name">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" data-untuk="nomor_wa">Nomor WA</label>
                    <input type="tel" class="form-control" data-isian="nomor_wa" maxlength="20" autocomplete="tel" placeholder="0812-3456-7890">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-12">
                    <span class="form-label d-block" data-label-foto>Foto</span>
                    <div class="foto-kotak" data-isian-foto>
                        <img class="foto-pratinjau" alt="" hidden>
                        <span class="foto-kosong"><i class="bi bi-person-bounding-box" aria-hidden="true"></i></span>
                        <div class="foto-tombol">
                            <label class="btn btn-outline-primary btn-sm mb-0">
                                <i class="bi bi-camera" aria-hidden="true"></i> <span data-teks-foto>Ambil atau pilih foto</span>
                                <input type="file" accept="image/*" class="visually-hidden" data-isian="foto">
                            </label>
                            <div class="form-text">Foto wajah yang jelas. Diperkecil otomatis sebelum dikirim.</div>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block" data-galat-foto></div>
                </div>
            </div>
        </div>
    </template>

    <div class="langkah-tombol">
        <button type="button" class="btn btn-outline-secondary" data-kembali><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</button>
        <button type="button" class="btn btn-primary btn-lg" data-lanjut>Lanjut <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>
</section>
