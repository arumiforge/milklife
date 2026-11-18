<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\Panel\UbahPendaftaran;
use App\Libraries\Pendaftaran\BiayaPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Models\SekolahModel;

/**
 * Pemeriksaan kiriman Ubah pendaftaran dan rencana simpannya (bagian dari
 * `UbahPendaftaran`, F7-30):
 *
 * 1. `id` penanggung jawab dan atlet harus milik pendaftaran ini, sekali saja;
 *    foto baru harus terbaca.
 * 2. `PemeriksaPendaftaran` dengan data "sudah ada" tanpa pendaftaran ini
 *    (NISN pendaftaran lain, regu tersimpan, induk dengan data nonaktif K98).
 * 3. Biaya (`BiayaUbah`) dan status (`StatusUbah`, K32).
 */
final class RencanaUbah
{
    /**
     * Isi `foto` pengganti untuk pemeriksa bila fotonya tidak dikirim ulang.
     */
    private const FOTO_ADA = 'ada';

    public function __construct(
        private readonly DataTersimpan $tersimpan,
        private readonly BiayaUbah $biaya,
    ) {
    }

    /**
     * Periksa kiriman terhadap data tersimpan dan susun rencana simpan.
     *
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    public function hitung(array $lama, array $kiriman, string $hariIni): array
    {
        $galat   = [];
        $pj      = MasukanUbah::daftar($kiriman['penanggung_jawab'] ?? null);
        $atlet   = MasukanUbah::daftar($kiriman['atlet'] ?? null);
        $idPj    = [];
        $idAtlet = [];
        $fotoIsi = [];

        $bebankan = MasukanUbah::reguBebankan($kiriman);
        unset($kiriman[PemeriksaPendaftaran::KUNCI_KONFIRMASI], $kiriman[PemeriksaPendaftaran::KUNCI_TURUN], $kiriman[UbahPendaftaran::KUNCI_BEBANKAN]);

        foreach ($pj as $i => $orang) {
            $orang = is_array($orang) ? $orang : [];
            $id    = MasukanUbah::id($orang['id'] ?? null);
            $ke    = 'Ofisial ' . ($i + 1);

            if ($id !== null && (! isset($lama['penanggung_jawab'][$id]) || in_array($id, $idPj, true))) {
                $galat[] = ['jalur' => "penanggung_jawab.{$i}", 'pesan' => "{$ke}: data tersimpannya tidak dikenal. Muat ulang halaman ini, lalu ubah lagi."];
                $id      = null;
            }

            $idPj[$i]    = $id;
            $fotoIsi[$i] = null;
            $foto        = MasukanUbah::teks($orang['foto'] ?? null);

            if ($foto !== '') {
                $fotoIsi[$i] = FotoPenanggungJawab::baca($foto);

                if ($fotoIsi[$i] === null) {
                    $galat[] = ['jalur' => "penanggung_jawab.{$i}.foto", 'pesan' => "{$ke}: foto belum bisa dibaca. Pilih ulang fotonya."];
                }

                $orang['foto'] = self::FOTO_ADA;
            } else {
                $orang['foto'] = $id !== null && $this->tersimpan->adaFoto($lama['penanggung_jawab'][$id]['foto']) ? self::FOTO_ADA : '';
            }

            $pj[$i] = $orang;
        }

        foreach ($atlet as $i => $isian) {
            $isian = is_array($isian) ? $isian : [];
            $id    = MasukanUbah::id($isian['id'] ?? null);

            if ($id !== null && (! isset($lama['atlet'][$id]) || in_array($id, $idAtlet, true))) {
                $galat[] = ['jalur' => "atlet.{$i}", 'pesan' => 'Atlet ' . ($i + 1) . ': data tersimpannya tidak dikenal. Muat ulang halaman ini, lalu ubah lagi.'];
                $id      = null;
            }

            $idAtlet[$i] = $id;
        }

        $kiriman['penanggung_jawab'] = $pj;
        $kiriman['atlet']            = $atlet;

        $pendaftaranId = (int) $lama['pendaftaran']['id'];
        $npsn          = PemeriksaPendaftaran::npsnKiriman($kiriman);
        $ada           = [
            'referensi'      => $npsn !== null ? model(SekolahModel::class)->cariNpsn($npsn) : null,
            'nisn_terdaftar' => $this->tersimpan->nisnLain($pendaftaranId, PemeriksaPendaftaran::nisnKiriman($kiriman)),
            // Regu milik pendaftaran ini: boleh dipertahankan walau batas regu diturunkan,
            // dan boleh kurang inti (K105).
            'regu_tersimpan' => array_keys($lama['beregu']),
        ] + $this->tersimpan->dataSekolah($npsn, $pendaftaranId);

        // Nama tersimpan yang berbeda dari data sekolah (usulan pendaftar yang
        // belum diputuskan, K99, atau nama yang sengaja tidak diganti admin)
        // dipertahankan selama NPSN-nya tidak diubah.
        if ($npsn !== null && $npsn === ($lama['pendaftaran']['npsn'] ?? null) && is_array($ada['referensi'])) {
            $kiriman['sekolah']                = is_array($kiriman['sekolah'] ?? null) ? $kiriman['sekolah'] : [];
            $kiriman['sekolah']['nama_usulan'] = (string) $lama['pendaftaran']['nama_sekolah'];
        }

        // Kelompok umur dan nomor lomba yang sudah dimatikan tetap diterima
        // untuk atlet yang sudah memakainya (K98).
        [$induk, $simpanan] = $this->tersimpan->indukDenganSimpanan($lama);
        $periksa            = (new PemeriksaPendaftaran($induk, $ada, $hariIni, $simpanan, true))->periksa($kiriman, false);

        foreach ($periksa['galat'] as $g) {
            $galat[] = ['jalur' => $g['jalur'], 'pesan' => $g['pesan']];
        }

        $statusLama = (string) $lama['pendaftaran']['status'];
        $totalLama  = (int) $lama['pendaftaran']['total'];
        $rencana    = [
            'galat'             => $galat,
            'id_pendaftaran'    => (string) $lama['pendaftaran']['id_pendaftaran'],
            'perlu_setuju'      => false,
            'tersimpan'         => false,
            'status_lama'       => $statusLama,
            'status'            => $statusLama,
            'kembali_menunggu'  => false,
            'total_lama'        => $totalLama,
            'subtotal_individu' => (int) $lama['pendaftaran']['subtotal_individu'],
            'subtotal_beregu'   => (int) $lama['pendaftaran']['subtotal_beregu'],
            'total'             => $totalLama,
            'atlet_baru'        => count(array_filter($idAtlet, static fn (?int $id): bool => $id === null)),
            'nomor_dada'        => [],
            'tim_dibebankan'    => [],
            'tim_ditinggalkan'  => [],
            'gabung'            => null,
        ];

        if ($galat !== []) {
            return $rencana;
        }

        $biaya                     = $this->biaya->terapkan($lama, $periksa['data'], $idAtlet, $bebankan, $pendaftaranId);
        $data                      = $biaya['data'];
        $rencana['tim_dibebankan'] = $biaya['tim_dibebankan'];

        if ($biaya['galat'] !== []) {
            return ['galat' => $biaya['galat']] + $rencana;
        }

        $rencana['tim_ditinggalkan'] = $biaya['tim_ditinggalkan'];

        foreach ($data['penanggung_jawab'] as $i => $orang) {
            $data['penanggung_jawab'][$i] = [
                'id'        => $idPj[$i],
                'nama'      => $orang['nama'],
                'nomor_wa'  => $orang['nomor_wa'],
                'foto_baru' => $fotoIsi[$i],
            ];
        }

        $total   = BiayaPendaftaran::hitung($biaya['individu'], $biaya['beregu']);
        $kembali = StatusUbah::kembaliMenunggu($statusLama, $total['total'], $totalLama);

        return [
            'status'           => $kembali ? 'menunggu' : $statusLama,
            'kembali_menunggu' => $kembali,
            'sekolah'          => $data['sekolah'],
            'penanggung_jawab' => $data['penanggung_jawab'],
            'atlet'            => $data['atlet'],
            'beregu'           => $data['beregu'],
        ] + $total + $rencana;
    }
}
