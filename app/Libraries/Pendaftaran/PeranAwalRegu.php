<?php

namespace App\Libraries\Pendaftaran;

use CodeIgniter\Database\BaseConnection;

/**
 * Isi regu dan peran anggota beregu yang belum punya (K105): data dari
 * sebelum ada regu (migrasi `TambahReguBeregu`) atau paket pindah server lama
 * (`PaketPendaftaran`).
 *
 * Anggota tanpa regu masuk Regu A. Anggota tanpa peran diberi peran menurut
 * urutan daftar (pendaftaran, lalu atlet) di regunya: inti selama inti regu
 * itu belum sejumlah inti nomor, sisanya cadangan. Satu regu = satu NPSN di
 * satu nomor dan label, atau satu pendaftaran bila tanpa NPSN. Anggota yang
 * sudah berperan tidak diubah, tetapi ikut dihitung.
 *
 *     $jumlah = PeranAwalRegu::isi(db_connect());   // banyak baris yang diisi
 */
final class PeranAwalRegu
{
    public static function isi(BaseConnection $db): int
    {
        $db->table('atlet_nomor')
            ->where('regu IS NULL', null, false)
            ->whereIn('nomor_lomba_id', static fn ($q) => $q->select('id')->from('nomor_lomba')->where('jenis', 'beregu'))
            ->update(['regu' => 'A']);

        $baris = $db->table('atlet_nomor an')
            ->select('an.id, an.nomor_lomba_id, an.regu, an.peran, p.id AS pendaftaran_id, p.npsn, nl.jumlah_inti')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('nl.jenis', 'beregu')
            ->orderBy('p.id')->orderBy('a.id')->orderBy('an.id')
            ->get()->getResultArray();

        $inti   = [];
        $kosong = [];

        foreach ($baris as $b) {
            $regu = ($b['npsn'] !== null && $b['npsn'] !== '' ? 'n' . $b['npsn'] : 'p' . $b['pendaftaran_id']) . '-' . $b['nomor_lomba_id'] . '-' . $b['regu'];

            if ($b['peran'] === Regu::INTI) {
                $inti[$regu] = ($inti[$regu] ?? 0) + 1;
            } elseif ($b['peran'] === null || $b['peran'] === '') {
                $kosong[] = [$regu, (int) $b['id'], (int) $b['jumlah_inti']];
            }
        }

        $peran = [Regu::INTI => [], Regu::CADANGAN => []];

        foreach ($kosong as [$regu, $id, $jumlahInti]) {
            if (($inti[$regu] ?? 0) < $jumlahInti) {
                $inti[$regu]           = ($inti[$regu] ?? 0) + 1;
                $peran[Regu::INTI][]   = $id;
            } else {
                $peran[Regu::CADANGAN][] = $id;
            }
        }

        foreach ($peran as $nilai => $id) {
            foreach (array_chunk($id, 500) as $potong) {
                $db->table('atlet_nomor')->whereIn('id', $potong)->update(['peran' => $nilai]);
            }
        }

        return count($kosong);
    }
}
