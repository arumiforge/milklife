<?php

namespace App\Libraries\Panel;

use App\Models\SekolahModel;
use App\Models\UsulanNamaSekolahModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Keputusan admin atas usulan nama sekolah dari pendaftar (K99).
 *
 *     $usulan = new UsulanNamaSekolah();
 *     $hasil  = $usulan->setujui(3, 'MIS MUHAMMADIYAH JATI');
 *     // ['galat' => null, 'nama' => 'MIS MUHAMMADIYAH JATI', 'pendaftaran' => 2, 'usulan_lain' => 0]
 *     $hasil  = $usulan->tolak(4, 'Nama desa salah.');
 *     // ['galat' => null, 'nama' => 'MIS MUHAMMADIYAH', 'pendaftaran' => 1]
 *
 * - Setujui: nama di data sekolah diganti (ditandai disunting, jadi tidak
 *   ditimpa `sekolah:impor`), semua pendaftaran ber-NPSN itu memakai nama baru,
 *   dan usulan lain yang masih menunggu untuk NPSN yang sama ikut selesai
 *   (ditolak dengan alasan nama lain sudah disetujui).
 * - Tolak: pendaftaran pengusul kembali memakai nama dari data sekolah,
 *   kecuali namanya sudah diubah lagi oleh admin.
 *
 * `galat` berisi pesan untuk admin bila usulan tidak bisa diputuskan
 * (sudah diputuskan, sekolah hilang, nama kosong atau dipakai sekolah lain).
 */
final class UsulanNamaSekolah
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Catat usulan baru. Dipanggil `SimpanPendaftaran` di dalam transaksinya.
     */
    public function catat(string $npsn, int $pendaftaranId, string $namaLama, string $namaUsulan): void
    {
        $waktu = date('Y-m-d H:i:s');
        $hasil = $this->db->table('usulan_nama_sekolah')->insert([
            'npsn'           => $npsn,
            'pendaftaran_id' => $pendaftaranId,
            'nama_lama'      => $namaLama,
            'nama_usulan'    => $namaUsulan,
            'status'         => UsulanNamaSekolahModel::MENUNGGU,
            'dibuat_pada'    => $waktu,
            'diubah_pada'    => $waktu,
        ]);

        if ($hasil === false) {
            throw new RuntimeException('Usulan nama sekolah gagal disimpan.');
        }
    }

    /**
     * @return array{galat: string|null, nama: string, pendaftaran: int, usulan_lain: int}
     */
    public function setujui(int $id, string $nama): array
    {
        $nama  = trim((string) preg_replace('/\s+/u', ' ', $nama));
        $hasil = ['galat' => null, 'nama' => $nama, 'pendaftaran' => 0, 'usulan_lain' => 0];

        [$usulan, $sekolah, $galat] = $this->bacaMenunggu($id);

        if ($galat !== null) {
            return ['galat' => $galat] + $hasil;
        }

        if ($nama === '') {
            return ['galat' => 'Nama sekolah belum diisi. Tulis nama yang akan dipakai, lalu tekan Setujui lagi.'] + $hasil;
        }

        if (mb_strlen($nama) > SekolahModel::PANJANG['nama']) {
            return ['galat' => 'Nama sekolah terlalu panjang. Persingkat menjadi paling banyak ' . SekolahModel::PANJANG['nama'] . ' huruf.'] + $hasil;
        }

        $kembar = $this->db->table('sekolah')->where('nama', $nama)->where('id !=', $sekolah['id'])->get()->getRowArray();

        if (is_array($kembar)) {
            return ['galat' => 'Nama "' . $nama . '" sudah dipakai ' . $kembar['nama'] . ' (NPSN ' . $kembar['npsn'] . ', '
                . SekolahModel::lokasi($kembar) . '). Tambahkan pembeda, misalnya nama desa, lalu tekan Setujui lagi.'] + $hasil;
        }

        $waktu = date('Y-m-d H:i:s');

        $this->db->transStart();
        $this->db->table('sekolah')->where('id', $sekolah['id'])->update(['nama' => $nama, 'disunting_pada' => $waktu, 'diubah_pada' => $waktu]);
        $this->db->table('pendaftaran')->where('npsn', $usulan['npsn'])->where('nama_sekolah !=', $nama)
            ->update(['nama_sekolah' => $nama, 'diubah_pada' => $waktu]);
        $hasil['pendaftaran'] = $this->db->affectedRows();
        $this->db->table('usulan_nama_sekolah')->where('id', $id)->update([
            'status'          => UsulanNamaSekolahModel::DISETUJUI,
            'nama_disetujui'  => $nama,
            'diputuskan_pada' => $waktu,
            'diubah_pada'     => $waktu,
        ]);
        $this->db->table('usulan_nama_sekolah')
            ->where('npsn', $usulan['npsn'])->where('status', UsulanNamaSekolahModel::MENUNGGU)->where('id !=', $id)
            ->update([
                'status'          => UsulanNamaSekolahModel::DITOLAK,
                'alasan'          => mb_substr('Nama lain sudah disetujui: ' . $nama, 0, UsulanNamaSekolahModel::ALASAN_MAKSIMAL),
                'diputuskan_pada' => $waktu,
                'diubah_pada'     => $waktu,
            ]);
        $hasil['usulan_lain'] = $this->db->affectedRows();
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['galat' => 'Keputusan belum tersimpan karena basis data sedang bermasalah. Tunggu sebentar, lalu coba lagi.', 'pendaftaran' => 0, 'usulan_lain' => 0] + $hasil;
        }

        return $hasil;
    }

    /**
     * @return array{galat: string|null, nama: string, pendaftaran: int}
     */
    public function tolak(int $id, string $alasan): array
    {
        $alasan = trim((string) preg_replace('/\s+/u', ' ', $alasan));
        [$usulan, $sekolah, $galat] = $this->bacaMenunggu($id);
        $hasil  = ['galat' => $galat, 'nama' => (string) ($sekolah['nama'] ?? ''), 'pendaftaran' => 0];

        if ($galat !== null) {
            return $hasil;
        }

        if (mb_strlen($alasan) > UsulanNamaSekolahModel::ALASAN_MAKSIMAL) {
            return ['galat' => 'Alasan terlalu panjang. Persingkat menjadi paling banyak ' . UsulanNamaSekolahModel::ALASAN_MAKSIMAL . ' huruf.'] + $hasil;
        }

        $waktu = date('Y-m-d H:i:s');

        $this->db->transStart();

        // Hanya pendaftaran yang masih memakai nama usulan yang dikembalikan.
        if ($usulan['pendaftaran_id'] !== null) {
            $this->db->table('pendaftaran')
                ->where('id', (int) $usulan['pendaftaran_id'])->where('nama_sekolah', $usulan['nama_usulan'])
                ->update(['nama_sekolah' => $sekolah['nama'], 'diubah_pada' => $waktu]);
            $hasil['pendaftaran'] = $this->db->affectedRows();
        }

        $this->db->table('usulan_nama_sekolah')->where('id', $id)->update([
            'status'          => UsulanNamaSekolahModel::DITOLAK,
            'alasan'          => $alasan === '' ? null : $alasan,
            'diputuskan_pada' => $waktu,
            'diubah_pada'     => $waktu,
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['galat' => 'Keputusan belum tersimpan karena basis data sedang bermasalah. Tunggu sebentar, lalu coba lagi.', 'pendaftaran' => 0] + $hasil;
        }

        return $hasil;
    }

    /**
     * Usulan yang masih menunggu beserta sekolahnya, atau pesan galat.
     *
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: string|null}
     */
    private function bacaMenunggu(int $id): array
    {
        $usulan = $this->db->table('usulan_nama_sekolah')->where('id', $id)->get()->getRowArray();

        if (! is_array($usulan)) {
            return [null, null, 'Usulan itu tidak ditemukan. Pilih usulan dari daftar di bawah.'];
        }

        if ($usulan['status'] !== UsulanNamaSekolahModel::MENUNGGU) {
            return [$usulan, null, 'Usulan ini sudah diputuskan sebelumnya, jadi tidak diubah lagi.'];
        }

        $sekolah = $this->db->table('sekolah')->where('npsn', $usulan['npsn'])->get()->getRowArray();

        if (! is_array($sekolah)) {
            return [$usulan, null, 'Sekolah dengan NPSN ' . $usulan['npsn'] . ' tidak ada lagi di data sekolah, jadi usulan ini tidak bisa diputuskan.'];
        }

        return [$usulan, $sekolah, null];
    }
}
