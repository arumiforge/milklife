<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\NomorWa;
use App\Libraries\Panel\TimTanpaPembayar;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use CodeIgniter\Database\BaseConnection;

/**
 * Isian formulir Ubah pendaftaran (bagian dari `UbahPendaftaran::isian()`,
 * F7-30): dari data tersimpan, atau dari kiriman yang ditampilkan ulang.
 */
final class IsianUbah
{
    public function __construct(
        private readonly BaseConnection $db,
        private readonly DataTersimpan $tersimpan,
    ) {
    }

    /**
     * Bentuk hasilnya: lihat `UbahPendaftaran::isian()`.
     *
     * @param array<string, mixed>      $lama    Data tersimpan (`DataTersimpan::baca()`).
     * @param array<string, mixed>|null $kiriman
     *
     * @return array<string, mixed>
     */
    public function susun(int $id, array $lama, ?array $kiriman): array
    {
        $p     = $lama['pendaftaran'];
        $hasil = [
            'pendaftaran' => [
                'id'                => (int) $p['id'],
                'id_pendaftaran'    => (string) $p['id_pendaftaran'],
                'status'            => (string) $p['status'],
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
            ],
            'tim_tanpa_pembayar' => [],
        ];

        $bebankan = MasukanUbah::reguBebankan($kiriman ?? []);

        foreach ((new TimTanpaPembayar($this->db))->untukPendaftaran($id) as $kunci => $tim) {
            $hasil['tim_tanpa_pembayar'][$kunci] = $tim + ['dibebankan' => in_array($kunci, $bebankan, true)];
        }

        if ($kiriman === null) {
            $hasil['sekolah'] = [
                'jenjang'   => (string) $p['jenjang'],
                'kabupaten' => (string) $p['kabupaten'],
                'npsn'      => (string) ($p['npsn'] ?? ''),
                'nama'      => (string) $p['nama_sekolah'],
            ];

            $hasil['penanggung_jawab'] = array_map(fn (array $o): array => [
                'id'       => (int) $o['id'],
                'nama'     => (string) $o['nama'],
                'nomor_wa' => NomorWa::tampilan((string) $o['nomor_wa']),
                'foto'     => '',
                'ada_foto' => $this->tersimpan->adaFoto($o['foto']),
            ], array_values($lama['penanggung_jawab']));

            $hasil['atlet'] = array_map(static fn (array $a): array => [
                'id'               => (int) $a['id'],
                'nama'             => (string) $a['nama'],
                'nisn'             => (string) $a['nisn'],
                'tanggal_lahir'    => (string) $a['tanggal_lahir'],
                'jenis_kelamin'    => (string) $a['jenis_kelamin'],
                'kelompok_umur_id' => (int) $a['kelompok_umur_id'],
                'nomor'            => array_keys($lama['nomor'][(int) $a['id']] ?? []),
                'regu'             => $lama['regu'][(int) $a['id']] ?? [],
                'nomor_dada'       => $a['nomor_dada'] === null || $a['nomor_dada'] === '' ? null : (string) $a['nomor_dada'],
                'ku_lama'          => (int) $a['kelompok_umur_id'],
            ], array_values($lama['atlet']));

            return $hasil;
        }

        $sekolah          = is_array($kiriman['sekolah'] ?? null) ? $kiriman['sekolah'] : [];
        $hasil['sekolah'] = [
            'jenjang'   => MasukanUbah::teks($sekolah['jenjang'] ?? null),
            'kabupaten' => MasukanUbah::teks($sekolah['kabupaten'] ?? null),
            'npsn'      => MasukanUbah::teks($sekolah['npsn'] ?? null),
            'nama'      => MasukanUbah::teks($sekolah['nama'] ?? null),
        ];

        $hasil['penanggung_jawab'] = [];

        foreach (MasukanUbah::daftar($kiriman['penanggung_jawab'] ?? null) as $o) {
            $o    = is_array($o) ? $o : [];
            $idPj = MasukanUbah::id($o['id'] ?? null);
            $baru = MasukanUbah::teks($o['foto'] ?? null);

            $hasil['penanggung_jawab'][] = [
                'id'       => isset($lama['penanggung_jawab'][$idPj]) ? $idPj : null,
                'nama'     => MasukanUbah::teks($o['nama'] ?? null),
                'nomor_wa' => MasukanUbah::teks($o['nomor_wa'] ?? null),
                'foto'     => FotoPenanggungJawab::baca($baru) !== null ? $baru : '',
                'ada_foto' => isset($lama['penanggung_jawab'][$idPj]) && $this->tersimpan->adaFoto($lama['penanggung_jawab'][$idPj]['foto']),
            ];
        }

        $hasil['atlet'] = [];

        foreach (MasukanUbah::daftar($kiriman['atlet'] ?? null) as $a) {
            $a       = is_array($a) ? $a : [];
            $idAtlet = MasukanUbah::id($a['id'] ?? null);
            $simpan  = $lama['atlet'][$idAtlet] ?? null;

            $hasil['atlet'][] = [
                'id'               => $simpan !== null ? $idAtlet : null,
                'nama'             => MasukanUbah::teks($a['nama'] ?? null),
                'nisn'             => MasukanUbah::teks($a['nisn'] ?? null),
                'tanggal_lahir'    => MasukanUbah::teks($a['tanggal_lahir'] ?? null),
                'jenis_kelamin'    => MasukanUbah::teks($a['jenis_kelamin'] ?? null),
                'kelompok_umur_id' => MasukanUbah::id($a['kelompok_umur_id'] ?? null),
                'nomor'            => array_values(array_filter(array_map(MasukanUbah::id(...), MasukanUbah::daftar($a['nomor'] ?? null)))),
                'regu'             => MasukanUbah::reguIsian($a['regu'] ?? null),
                'nomor_dada'       => $simpan !== null && $simpan['nomor_dada'] !== null && $simpan['nomor_dada'] !== '' ? (string) $simpan['nomor_dada'] : null,
                'ku_lama'          => $simpan !== null ? (int) $simpan['kelompok_umur_id'] : null,
            ];
        }

        return $hasil;
    }
}
