<?php

namespace Tests\Support;

/**
 * Data uji karangan untuk halaman cek dan bukti (Tahap 5), diisi langsung ke
 * tabel tanpa model Tahap 4. Nama, NISN, NPSN, dan nomor WA karangan.
 *
 * Isi `isiDataCek()`:
 *
 * - KU Contoh (tidak aktif) dengan tarif sekarang Rp175.000, padahal pendaftaran menyimpan
 *   Rp150.000 (membuktikan biaya tidak dihitung ulang).
 * - `MAC2A0000126` (NPSN 20300001, Menunggu): dua penanggung jawab (satu
 *   berfoto), BUDI (Lari 60 m + Estafet), CANDRA (Lompat Jauh + Lari 60 m +
 *   Estafet), DIMAS dan EKO (Estafet saja). Total tersimpan Rp650.000.
 * - `MAC2A0000226` (NPSN sama, Lunas, susulan): FAJAR menambah anggota
 *   Estafet tanpa baris `pendaftaran_beregu`; bernomor dada 1001.
 * - `MAC2B0000326` (tanpa NPSN): nama sekolah berisi tanda HTML.
 */
trait CekDataPendaftaran
{
    /**
     * Data awal ditambah semua isian wajib (data karangan), sehingga
     * pengaturan lengkap. Kelas uji perlu `UnggahanSementara`, karena
     * `data:awal` menyalin gambar bawaan ke folder unggahan.
     */
    protected function lengkapiPengaturanCek(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));

        $this->db->query('UPDATE kelompok_umur SET biaya_individu = 50000');
        $this->db->query("UPDATE nomor_lomba SET batas_anggota = jumlah_inti + 2, biaya_beregu = 100000 WHERE jenis = 'beregu'");
        $this->db->query("UPDATE nomor_lomba SET jumlah_putra = jumlah_inti DIV 2, jumlah_putri = jumlah_inti DIV 2 WHERE kategori = 'mix'");

        foreach ($this->db->table('kelompok_umur')->select('id')->get()->getResultArray() as $ku) {
            $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku['id'], 'jenjang' => 'A']);
        }

        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '1234567890');
        setting('Pembayaran.pemilik_rekening', 'Panitia Karangan');
        setting('Pembayaran.nomor_wa', '6281234567890');
    }

    /**
     * @return array{ku: int, lari: int, lompat: int, estafet: int}
     */
    protected function isiDataCek(): array
    {
        $t = fn (string $tabel, array $isi): int => $this->db->table($tabel)->insert($isi) ? (int) $this->db->insertID() : 0;

        // Tidak aktif, supaya tidak mengganggu kelengkapan dari `data:awal`; tetap terbaca di pendaftaran.
        $ku      = $t('kelompok_umur', ['nama' => 'KU Contoh', 'biaya_individu' => 175000, 'urutan' => 99, 'aktif' => 0]);
        $lari    = $t('nomor_lomba', ['kelompok_umur_id' => $ku, 'nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putra', 'urutan' => 1, 'aktif' => 0]);
        $lompat  = $t('nomor_lomba', ['kelompok_umur_id' => $ku, 'nama' => 'Lompat Jauh', 'jenis' => 'individu', 'kategori' => 'putra', 'urutan' => 2, 'aktif' => 0]);
        $estafet = $t('nomor_lomba', ['kelompok_umur_id' => $ku, 'nama' => 'Estafet 4 × 50 m', 'jenis' => 'beregu', 'kategori' => 'putra', 'urutan' => 3, 'jumlah_inti' => 4, 'batas_anggota' => 6, 'biaya_beregu' => 250000, 'aktif' => 0]);

        $p1 = $t('pendaftaran', [
            'id_pendaftaran' => 'MAC2A0000126', 'jenjang' => 'A', 'npsn' => '20300001',
            'nama_sekolah'   => 'SD NEGERI CONTOH 1', 'kabupaten' => 'Kudus',
            'subtotal_individu' => 450000, 'subtotal_beregu' => 200000, 'total' => 650000,
            'status' => 'menunggu', 'dibuat_pada' => '2026-11-12 09:15:00',
        ]);
        $t('penanggung_jawab', ['pendaftaran_id' => $p1, 'nama' => 'Ibu Karangan', 'nomor_wa' => '081200000001', 'foto' => 'rahasia/foto-penanggung-jawab.jpg']);
        $t('penanggung_jawab', ['pendaftaran_id' => $p1, 'nama' => 'Pak Rekaan', 'nomor_wa' => '081200000002']);
        $t('pendaftaran_beregu', ['pendaftaran_id' => $p1, 'nomor_lomba_id' => $estafet, 'biaya_tim' => 200000]);

        $atlet = function (int $p, string $nama, string $nisn, array $nomor, ?string $dada = null) use ($t, $ku): void {
            $a = $t('atlet', ['pendaftaran_id' => $p, 'nama' => $nama, 'nisn' => $nisn, 'tanggal_lahir' => '2016-05-01', 'jenis_kelamin' => 'L', 'kelompok_umur_id' => $ku, 'nomor_dada' => $dada]);

            foreach ($nomor as $n => $biaya) {
                $t('atlet_nomor', ['atlet_id' => $a, 'nomor_lomba_id' => $n, 'biaya' => $biaya]);
            }
        };

        $atlet($p1, 'BUDI KARANGAN', '9900000001', [$lari => 150000, $estafet => null]);
        $atlet($p1, 'CANDRA REKAAN', '9900000002', [$lompat => 150000, $lari => 150000, $estafet => null]);
        $atlet($p1, 'DIMAS CONTOH', '9900000003', [$estafet => null]);
        $atlet($p1, 'EKO KARANGAN', '9900000004', [$estafet => null]);

        $p2 = $t('pendaftaran', [
            'id_pendaftaran' => 'MAC2A0000226', 'jenjang' => 'A', 'npsn' => '20300001',
            'nama_sekolah'   => 'SD NEGERI CONTOH 1', 'kabupaten' => 'Kudus',
            'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0,
            'status' => 'lunas', 'waktu_lunas' => '2026-11-13 10:00:00', 'dibuat_pada' => '2026-11-13 08:00:00',
        ]);
        $t('penanggung_jawab', ['pendaftaran_id' => $p2, 'nama' => 'Ibu Karangan', 'nomor_wa' => '081200000001']);
        $atlet($p2, 'FAJAR REKAAN', '9900000005', [$estafet => null], '1001');

        $p3 = $t('pendaftaran', [
            'id_pendaftaran' => 'MAC2B0000326', 'jenjang' => 'B', 'npsn' => null,
            'nama_sekolah'   => 'SMP <b>Contoh</b> & Mandiri', 'kabupaten' => 'Pati',
            'subtotal_individu' => 150000, 'subtotal_beregu' => 0, 'total' => 150000,
            'status' => 'menunggu', 'dibuat_pada' => '2026-11-14 13:00:00',
        ]);
        $t('penanggung_jawab', ['pendaftaran_id' => $p3, 'nama' => 'Pak <i>Uji</i>', 'nomor_wa' => '081200000003']);
        $atlet($p3, 'GILANG <SCRIPT>', '9900000006', [$lari => 150000]);

        return ['ku' => $ku, 'lari' => $lari, 'lompat' => $lompat, 'estafet' => $estafet];
    }
}
