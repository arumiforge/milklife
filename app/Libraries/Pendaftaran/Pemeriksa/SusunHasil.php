<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\BiayaPendaftaran;
use App\Models\NomorLombaModel;

/**
 * Hasil kiriman yang sah (bagian dari `PemeriksaPendaftaran`, F7-30): `data`
 * untuk `SimpanPendaftaran` dan `rincian` untuk langkah 4, dengan biaya dari
 * kelompok umur dan nomor beregu (`BiayaPendaftaran`; regu susulan Rp 0).
 * Bentuknya: lihat komentar `PemeriksaPendaftaran`.
 */
final class SusunHasil
{
    public function __construct(private readonly IndukPeriksa $induk)
    {
    }

    /**
     * @param array<string, mixed>       $sekolah
     * @param list<array<string, mixed>> $penanggungJawab
     * @param list<array<string, mixed>> $atlet
     * @param list<array<string, mixed>> $beregu
     * @param list<array{atlet_id: int, nomor_lomba_id: int}> $turun
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, data: array<string, mixed>, rincian: array<string, mixed>}
     */
    public function susun(array $sekolah, array $penanggungJawab, array $atlet, array $beregu, array $turun): array
    {
        $dataAtlet     = [];
        $rincianIndiv  = [];
        $biayaIndividu = [];

        foreach ($atlet as $a) {
            $ku    = $a['kelompok_umur'];
            $biaya = (int) ($ku['biaya_individu'] ?? 0);
            $nomor = [];

            foreach ($a['nomor'] as $n) {
                if ($n['jenis'] === 'individu') {
                    $nomor[]         = ['nomor_lomba_id' => (int) $n['id'], 'biaya' => $biaya];
                    $biayaIndividu[] = $biaya;
                    $rincianIndiv[]  = [
                        'nama'          => $a['nama'],
                        'nisn'          => $a['nisn'],
                        'kelompok_umur' => (string) $ku['nama'],
                        'nomor'         => NomorLombaModel::namaLengkap($n),
                        'biaya'         => $biaya,
                    ];
                } else {
                    $nomor[] = ['nomor_lomba_id' => (int) $n['id'], 'biaya' => null, 'regu' => $n['regu'], 'peran' => $n['peran']];
                }
            }

            $dataAtlet[] = [
                'nama'             => $a['nama'],
                'nisn'             => $a['nisn'],
                'tanggal_lahir'    => $a['tanggal_lahir'],
                'jenis_kelamin'    => $a['jenis_kelamin'],
                'kelompok_umur_id' => (int) $ku['id'],
                'nomor'            => $nomor,
            ];
        }

        $dataBeregu    = [];
        $rincianBeregu = [];
        $biayaBeregu   = [];

        foreach ($beregu as $tim) {
            $biaya = BiayaPendaftaran::tim((int) ($tim['nomor']['biaya_beregu'] ?? 0), $tim['susulan']);

            $dataBeregu[]    = ['nomor_lomba_id' => (int) $tim['nomor']['id'], 'regu' => $tim['regu'], 'biaya_tim' => $biaya];
            $biayaBeregu[]   = $biaya;
            $rincianBeregu[] = [
                'nomor'          => NomorLombaModel::namaLengkap($tim['nomor']),
                'nomor_lomba_id' => (int) $tim['nomor']['id'],
                'regu'           => $tim['regu'],
                'regu_tunggal'   => $tim['tunggal'],
                'kelompok_umur'  => (string) ($this->induk->kelompokUmur[(int) $tim['nomor']['kelompok_umur_id']]['nama'] ?? ''),
                'biaya'          => $biaya,
                'susulan'        => $tim['susulan'],
                'anggota'        => $tim['anggota'],
                'inti'           => $tim['inti'],
                'cadangan'       => $tim['cadangan'],
                'anggota_lama'   => $tim['anggota_lama'],
                'turun'          => $tim['turun'],
            ];
        }

        $total = BiayaPendaftaran::hitung($biayaIndividu, $biayaBeregu);

        return [
            'galat' => [],
            'data'  => [
                'sekolah'          => $sekolah,
                'penanggung_jawab' => $penanggungJawab,
                'atlet'            => $dataAtlet,
                'beregu'           => $dataBeregu,
                'turun_cadangan'   => $turun,
            ] + $total,
            'rincian' => [
                'sekolah' => [
                    'nama'          => $sekolah['nama_sekolah'],
                    'nama_resmi'    => $sekolah['nama_resmi'],
                    'npsn'          => $sekolah['npsn'],
                    'kabupaten'     => $sekolah['kabupaten'],
                    'jenjang'       => $sekolah['jenjang'],
                    'jenjang_label' => (string) Jenjang::label($sekolah['jenjang']),
                ],
                'individu' => $rincianIndiv,
                'beregu'   => $rincianBeregu,
            ] + $total,
        ];
    }
}
