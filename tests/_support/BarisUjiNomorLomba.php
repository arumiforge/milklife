<?php

namespace Tests\Support;

/**
 * Pembuat baris uji untuk nomor lomba (F2-04): kelompok umur, nomor lomba,
 * dan pemakaian nomor oleh atlet atau tim. Dipakai kelas turunan
 * `UjiBasisData`. Nama sekolah, nama atlet, dan NISN karangan.
 */
trait BarisUjiNomorLomba
{
    /**
     * @param array<string, mixed> $isi
     */
    protected function buatKelompokUmur(string $nama, array $isi = []): int
    {
        $this->db->table('kelompok_umur')->insert($isi + ['nama' => $nama]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $isi
     */
    protected function buatNomorLomba(int $kelompokUmurId, array $isi = []): int
    {
        $this->db->table('nomor_lomba')->insert($isi + [
            'kelompok_umur_id' => $kelompokUmurId,
            'nama'             => 'Lari 60 m',
            'jenis'            => 'individu',
            'kategori'         => 'putra',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Daftarkan seorang atlet karangan pada nomor itu.
     */
    protected function pakaiOlehAtlet(int $nomorLombaId, int $kelompokUmurId, string $nisn = '9900000001'): void
    {
        $this->db->table('atlet')->insert([
            'pendaftaran_id'   => $this->buatPendaftaranKarangan(),
            'nama'             => 'ATLET KARANGAN ' . $nisn,
            'nisn'             => $nisn,
            'tanggal_lahir'    => '2016-05-17',
            'jenis_kelamin'    => 'L',
            'kelompok_umur_id' => $kelompokUmurId,
        ]);
        $this->db->table('atlet_nomor')->insert([
            'atlet_id'       => (int) $this->db->insertID(),
            'nomor_lomba_id' => $nomorLombaId,
            'biaya'          => 75000,
        ]);
    }

    /**
     * Daftarkan satu tim karangan pada nomor beregu itu.
     */
    protected function pakaiOlehTim(int $nomorLombaId): void
    {
        $this->db->table('pendaftaran_beregu')->insert([
            'pendaftaran_id' => $this->buatPendaftaranKarangan(),
            'nomor_lomba_id' => $nomorLombaId,
            'biaya_tim'      => 100000,
        ]);
    }

    private function buatPendaftaranKarangan(): int
    {
        $this->db->table('pendaftaran')->insert([
            'jenjang'           => 'A',
            'npsn'              => '99999901',
            'nama_sekolah'      => 'SD KARANGAN SATU',
            'kabupaten'         => 'Kudus',
            'subtotal_individu' => 75000,
            'subtotal_beregu'   => 100000,
            'total'             => 175000,
        ]);

        return (int) $this->db->insertID();
    }
}
