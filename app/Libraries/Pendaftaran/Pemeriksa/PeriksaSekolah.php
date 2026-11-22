<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;

/**
 * Langkah 1: data sekolah (bagian dari `PemeriksaPendaftaran`, F7-30).
 * NPSN 8 angka atau kosong; sekolah di data referensi menentukan nama,
 * kabupaten, dan jenjang (asumsi 1 rencana Tahap 4); SLB ditolak (K28);
 * pendaftar boleh mengusulkan nama yang lebih lengkap (K99).
 */
final class PeriksaSekolah
{
    /**
     * @param mixed $referensi `ada['referensi']`: sekolah dengan NPSN kiriman (array), atau null.
     */
    public function __construct(
        private readonly mixed $referensi,
        private readonly DaftarGalat $galat,
    ) {
    }

    /**
     * Apakah sekolah di data referensi berbentuk SLB (K28).
     *
     * @param array<string, mixed> $sekolah
     */
    public static function slb(array $sekolah): bool
    {
        return strtoupper(trim((string) ($sekolah['bentuk'] ?? ''))) === 'SLB';
    }

    /**
     * Pesan penolakan SLB, dipakai juga pencarian NPSN.
     */
    public static function pesanSlb(string $nama = ''): string
    {
        return ($nama !== '' ? $nama . ' adalah ' : '') . 'Sekolah Luar Biasa (SLB). SLB tidak termasuk peserta lomba ini. '
            . 'Bila NPSN salah ketik, periksa lagi NPSN-nya.';
    }

    /**
     * @param array<string, mixed> $isian
     *
     * @return array{jenjang: string|null, kabupaten: string|null, npsn: string|null, nama_sekolah: string, nama_usulan: string|null, nama_resmi: string|null}
     */
    public function periksa(array $isian): array
    {
        $jenjang    = strtoupper(trim(MasukanPeriksa::teks($isian['jenjang'] ?? null)));
        $jenjang    = Jenjang::sah($jenjang) ? $jenjang : null;
        $kabupaten  = Kabupaten::baku($isian['kabupaten'] ?? null);
        $npsnTeks   = preg_replace('/\s+/', '', MasukanPeriksa::teks($isian['npsn'] ?? null)) ?? '';
        $nama       = MasukanPeriksa::rapikanSpasi(MasukanPeriksa::teks($isian['nama'] ?? null));
        $npsn       = null;
        $namaUsulan = null;
        $maksimal   = PemeriksaPendaftaran::NAMA_SEKOLAH_MAKSIMAL;

        if ($npsnTeks !== '') {
            if (preg_match('/^\d{8}$/D', $npsnTeks) !== 1) {
                $this->galat->tambah('sekolah.npsn', 'NPSN harus 8 angka. Periksa lagi NPSN sekolah, atau kosongkan bila sekolah belum punya NPSN.');
            } else {
                $npsn = $npsnTeks;
            }
        }

        $referensi = $npsn !== null ? $this->referensi : null;

        if (is_array($referensi) && ($referensi['npsn'] ?? null) === $npsn) {
            if (self::slb($referensi)) {
                $this->galat->tambah('sekolah.npsn', self::pesanSlb(MasukanPeriksa::rapikanSpasi((string) $referensi['nama'])));
            }

            // Data referensi menentukan nama, kabupaten, dan jenjang (asumsi 1 rencana Tahap 4).
            $nama      = MasukanPeriksa::rapikanSpasi((string) $referensi['nama']);
            $kabupaten = Kabupaten::baku($referensi['kabupaten'] ?? null) ?? $kabupaten;
            $jenjang   = PemeriksaPendaftaran::JENJANG_REFERENSI[strtoupper((string) ($referensi['jenjang'] ?? ''))] ?? $jenjang;

            // Pendaftar boleh mengusulkan nama yang lebih lengkap (K99); pendaftaran
            // ini memakainya sementara sampai panitia memutuskan.
            $usulan = MasukanPeriksa::rapikanSpasi(MasukanPeriksa::teks($isian['nama_usulan'] ?? null));

            if ($usulan !== '' && mb_strtolower($usulan) !== mb_strtolower($nama)) {
                if (mb_strlen($usulan) > $maksimal) {
                    $this->galat->tambah('sekolah.nama_usulan', 'Usulan nama sekolah terlalu panjang. Tulis paling banyak ' . $maksimal . ' huruf.');
                } elseif (preg_match('/\bSLB\b|sekolah\s+luar\s+biasa/iu', $usulan) === 1) {
                    $this->galat->tambah('sekolah.nama_usulan', self::pesanSlb());
                } else {
                    $namaUsulan = $usulan;
                }
            }
        } else {
            if ($nama === '') {
                $this->galat->tambah('sekolah.nama', $npsn === null
                    ? 'Nama sekolah belum diisi. Tulis nama sekolah, atau isi NPSN supaya nama sekolah muncul otomatis.'
                    : 'NPSN ini belum ada di data sekolah. Tulis nama sekolah secara lengkap.');
            } elseif (mb_strlen($nama) > $maksimal) {
                $this->galat->tambah('sekolah.nama', 'Nama sekolah terlalu panjang. Tulis paling banyak ' . $maksimal . ' huruf.');
            } elseif (preg_match('/\bSLB\b|sekolah\s+luar\s+biasa/iu', $nama) === 1) {
                $this->galat->tambah('sekolah.nama', self::pesanSlb());
            }
        }

        if ($jenjang === null) {
            $this->galat->tambah('sekolah.jenjang', 'Jenjang sekolah belum dipilih. Pilih SD/MI, SMP/MTs, atau SMA/SMK/MA.');
        }

        if ($kabupaten === null) {
            $this->galat->tambah('sekolah.kabupaten', 'Kabupaten belum dipilih. Pilih salah satu: ' . implode(', ', Kabupaten::semua()) . '.');
        }

        return [
            'jenjang'      => $jenjang,
            'kabupaten'    => $kabupaten,
            'npsn'         => $npsn,
            'nama_sekolah' => $namaUsulan ?? $nama,
            // Hanya terisi bila pendaftar mengusulkan nama lain untuk sekolah di data referensi (K99).
            'nama_usulan'  => $namaUsulan,
            'nama_resmi'   => $namaUsulan !== null ? $nama : null,
        ];
    }
}
