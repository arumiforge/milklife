<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Videotron `/layar` (F9-13, K141, F11-09, K173), diatur di Pengaturan > Videotron.
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Videotron.<kunci>')` (lihat `Config\Acara`), atau lewat
 * `App\Libraries\Hasil\PapanVideotron::pengaturan()` yang sudah merapikannya.
 */
class Videotron extends BaseConfig
{
    /**
     * Tampilkan videotron. Nilai awal mati: `/layar`, semua tampilannya, dan
     * datanya menjawab 404 sampai admin menyalakannya setelah mencobanya di staging
     * (rencana Tahap 9, "Aman untuk produksi").
     */
    public bool $tampil = false;

    /**
     * Gambar latar unggahan admin (F11-09, K173), jalur relatif
     * `unggahan/videotron/….webp`; kosong = latar bawaan dengan animasi.
     */
    public string $latar = '';

    /**
     * Latar bawaan bila belum ada gambar unggahan (K192): `kuas` (gambar
     * sapuan kuas biru G9-06 tanpa gerakan), `polos` (gradien biru tua), atau
     * `animasi` (gambar G9-06 dengan bentuk 3D bergerak pelan, latar lama).
     * Lihat `PapanVideotron::LATAR_BAWAAN`.
     */
    public string $latar_bawaan = 'kuas';

    /**
     * Sebutan pembagian babak di layar dan daftar tampilan videotron (K192):
     * `Heat` (bawaan, istilah panitia dan vendor di Seri 1) atau `Seri`.
     * Lihat `PapanVideotron::SEBUTAN_SERI`.
     */
    public string $sebutan_seri = 'Heat';

    /**
     * Isi papan putar `/layar/putar`, dipisah koma, urut tampil. Kode dari
     * `PapanVideotron::ISI`: `hasil`, `juara_umum`, `jadwal`, `berlangsung`.
     */
    public string $isi = 'hasil,juara_umum,berlangsung,jadwal';

    /**
     * Lama tiap tampilan papan putar dalam detik (`PapanVideotron::LAMA_MIN` sampai `LAMA_MAKS`).
     */
    public int $lama = 12;
}
