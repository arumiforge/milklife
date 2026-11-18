<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\Panel\TimTanpaPembayar;
use App\Libraries\Panel\UbahPendaftaran;
use App\Libraries\Pendaftaran\Regu;
use CodeIgniter\Database\BaseConnection;

/**
 * Biaya Ubah pendaftaran (bagian dari `UbahPendaftaran`, F7-30; asumsi 4
 * rencana Tahap 6, K68):
 *
 * - baris atlet-nomor (atlet tersimpan, nomor yang sama) mempertahankan biaya
 *   tersimpan; baris baru memakai tarif saat ini;
 * - regu (nomor dan label regu yang sama) mempertahankan biaya tersimpan;
 *   regu baru memakai tarif saat ini (regu susulan tetap Rp 0);
 * - regu tanpa pembayar yang dicentang "Bebankan biaya regu" diberi tarif
 *   saat ini, atau ditolak bila regu itu sudah punya pembayar lain.
 */
final class BiayaUbah
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * Terapkan biaya pada data hasil `PemeriksaPendaftaran::periksa()`.
     *
     * @param array<string, mixed>     $lama     Data tersimpan (`DataTersimpan::baca()`).
     * @param array<string, mixed>     $data     `data` hasil pemeriksaan.
     * @param array<int, int|null>     $idAtlet  ID atlet tersimpan per urutan atlet kiriman.
     * @param list<string>             $bebankan Regu ("nomor:regu") yang dicentang "Bebankan biaya regu".
     *
     * @return array{galat: list<array{jalur: string, pesan: string}>, data: array<string, mixed>, individu: list<int>, beregu: list<int>, tim_dibebankan: list<string>, tim_ditinggalkan: array<mixed>}
     *         `tim_ditinggalkan` hanya dihitung bila tanpa galat.
     */
    public function terapkan(array $lama, array $data, array $idAtlet, array $bebankan, int $pendaftaranId): array
    {
        $galat         = [];
        $timDibebankan = [];

        // Biaya individu: baris tersimpan dipertahankan, baris baru tarif saat ini.
        $biayaIndividu = [];

        foreach ($data['atlet'] as $i => $a) {
            $simpan = $idAtlet[$i] !== null ? ($lama['nomor'][$idAtlet[$i]] ?? []) : [];

            foreach ($a['nomor'] as $j => $nomor) {
                if (array_key_exists($nomor['nomor_lomba_id'], $simpan)) {
                    $nomor['biaya'] = $simpan[$nomor['nomor_lomba_id']];
                }

                $data['atlet'][$i]['nomor'][$j] = $nomor;

                if ($nomor['biaya'] !== null) {
                    $biayaIndividu[] = (int) $nomor['biaya'];
                }
            }

            $data['atlet'][$i]['id'] = $idAtlet[$i];
        }

        // Biaya regu: regu tersimpan dipertahankan, regu baru tarif saat ini (susulan Rp 0);
        // regu tanpa pembayar yang dicentang mendapat tarif saat ini (K68).
        $biayaBeregu = [];
        $timTanpa    = new TimTanpaPembayar($this->db);
        $npsnBaru    = $data['sekolah']['npsn'] ?? null;

        foreach ($data['beregu'] as $j => $tim) {
            $nomorId = (int) $tim['nomor_lomba_id'];
            $kunci   = Regu::kunci($nomorId, (string) $tim['regu']);

            if (array_key_exists($kunci, $lama['beregu'])) {
                $tim['biaya_tim'] = $lama['beregu'][$kunci];
            }

            if (in_array($kunci, $bebankan, true) && (int) $tim['biaya_tim'] === 0) {
                $tarif = $timTanpa->tarifBebankan($pendaftaranId, $npsnBaru, $nomorId, (string) $tim['regu']);

                if ($tarif === null) {
                    $galat[] = ['jalur' => UbahPendaftaran::KUNCI_BEBANKAN . '.' . $kunci, 'pesan' => $timTanpa->namaNomor($nomorId, (string) $tim['regu'])
                        . ': regu ini sudah punya pembayar biaya regu atau tarifnya Rp 0, jadi tidak perlu dibebankan. Hapus centang "Bebankan biaya regu", lalu simpan lagi.'];
                } else {
                    $tim['biaya_tim'] = $tarif;
                    $timDibebankan[]  = $kunci;
                }
            }

            $data['beregu'][$j] = $tim;
            $biayaBeregu[]      = (int) $tim['biaya_tim'];
        }

        $timDitinggalkan = $galat !== [] ? [] : $timTanpa->ditinggalkan($pendaftaranId, $npsnBaru, array_map(
            static fn (array $t): string => Regu::kunci((int) $t['nomor_lomba_id'], (string) $t['regu']),
            $data['beregu'],
        ));

        return [
            'galat'            => $galat,
            'data'             => $data,
            'individu'         => $biayaIndividu,
            'beregu'           => $biayaBeregu,
            'tim_dibebankan'   => $timDibebankan,
            'tim_ditinggalkan' => $timDitinggalkan,
        ];
    }
}
