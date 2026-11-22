<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Pendaftaran\KelengkapanTim;
use App\Libraries\Pendaftaran\Regu;
use App\Models\NomorLombaModel;

/**
 * Regu beregu satu kiriman (bagian dari `PemeriksaPendaftaran`, F7-30):
 * kelengkapan setiap regu (`KelengkapanTim`), banyaknya regu pada mode Bebas
 * (K106), regu susulan dan konfirmasinya (K57), regu tunggal (K108), dan inti
 * lama yang diturunkan menjadi cadangan (K105).
 */
final class PeriksaTim
{
    /**
     * @param bool $longgar Admin mengubah pendaftaran: regu yang sudah terdaftar boleh kurang inti.
     */
    public function __construct(
        private readonly IndukPeriksa $induk,
        private readonly PeriksaRegu $regu,
        private readonly bool $longgar,
        private readonly DaftarGalat $galat,
    ) {
    }

    /**
     * @param array<int, array<string, list<array{jenis_kelamin: string|null, peran: string}>>> $regu
     * @param list<string>|null $konfirmasi    "nomor:regu" yang regu susulannya dikonfirmasi; null = tidak diperiksa (K57)
     * @param list<mixed>       $reguTersimpan `ada['regu_tersimpan']`
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array{atlet_id: int, nomor_lomba_id: int}>}
     */
    public function periksa(array $regu, ?array $konfirmasi, mixed $turunKiriman, array $reguTersimpan): array
    {
        $turun = self::turunKiriman($turunKiriman);
        $hasil = [];
        $data  = [];

        if ($turun === null) {
            $this->galat->tambah('turun', 'Pilihan inti lama yang menjadi cadangan tidak dikenal. Muat ulang halaman, lalu pilih lagi.');
            $turun = [];
        }

        // Setiap inti lama yang diturunkan harus anggota inti regu sekolah ini
        // yang juga mendapat atlet dari kiriman ini.
        foreach ($turun as $nomorId => $daftarAtlet) {
            foreach (array_keys($daftarAtlet) as $atletId) {
                $ketemu = null;

                foreach ($this->regu->timAda[$nomorId] ?? [] as $label => $anggota) {
                    foreach ($anggota as $a) {
                        if ((int) ($a['id'] ?? 0) === $atletId && ($a['peran'] ?? '') === Regu::INTI) {
                            $ketemu = (string) $label;
                        }
                    }
                }

                if ($ketemu === null || ! isset($regu[$nomorId][$ketemu])) {
                    $this->galat->tambah("turun.{$nomorId}", 'Pilihan inti lama yang menjadi cadangan tidak dikenal atau bukan di regu yang ditambah. Muat ulang halaman, lalu pilih lagi.');

                    continue;
                }

                $data[] = ['atlet_id' => $atletId, 'nomor_lomba_id' => $nomorId];
            }
        }

        $tersimpan = array_flip(array_map('strval', $reguTersimpan));

        foreach ($regu as $nomorId => $perRegu) {
            $nomor = $this->induk->nomorLomba[$nomorId];
            ksort($perRegu);

            // Mode Bebas (K106): banyaknya regu sekolah di nomor ini, termasuk regu lama,
            // tidak boleh melewati batas regu nomor itu. Regu lama tetap boleh.
            $batasRegu = isset($nomor['regu_maksimal']) && $nomor['regu_maksimal'] !== null ? (int) $nomor['regu_maksimal'] : null;

            if ($this->regu->bebas() && $batasRegu !== null && $batasRegu > 0) {
                $lama  = $this->regu->lamaNomor($nomorId);
                $semua = array_values(array_unique(array_merge($lama, array_map('strval', array_keys($perRegu)))));

                if (count($semua) > $batasRegu) {
                    foreach (array_diff(array_map('strval', array_keys($perRegu)), $lama) as $baru) {
                        $this->galat->tambah('beregu.' . Regu::kunci($nomorId, $baru), Regu::nama(NomorLombaModel::namaLengkap($nomor), $baru)
                            . ": satu sekolah paling banyak {$batasRegu} regu di nomor ini, sekarang " . count($semua) . ' regu'
                            . ($lama !== [] ? ' (termasuk ' . Regu::awalan() . ' ' . PeriksaRegu::teksLabel($lama) . ' yang sudah terdaftar)' : '')
                            . '. Masukkan atletnya ke regu yang sudah ada.');
                    }
                }
            }

            // Regu tunggal (K108): sekolah hanya punya satu regu di nomor ini (lama
            // dan kiriman), jadi pesan untuk official tidak menulis nama regunya.
            // Admin tetap melihat nama regu.
            $tunggal = ! $this->longgar
                && count(array_unique(array_merge($this->regu->lamaNomor($nomorId), array_map('strval', array_keys($perRegu))))) <= 1;

            foreach ($perRegu as $label => $baru) {
                $label       = (string) $label;
                $kunci       = Regu::kunci($nomorId, $label);
                $anggota     = $this->regu->timAda[$nomorId][$label] ?? null;
                $lama        = null;
                $jumlahTurun = 0;

                if ($anggota !== null) {
                    $lama = [];

                    foreach ($anggota as $a) {
                        $peran = (string) ($a['peran'] ?? '');

                        if ($peran === Regu::INTI && isset($turun[$nomorId][(int) ($a['id'] ?? 0)])) {
                            $peran = Regu::CADANGAN;
                            $jumlahTurun++;
                        }

                        $lama[] = ['jenis_kelamin' => $a['jenis_kelamin'] ?? null, 'peran' => $peran];
                    }
                }

                // Admin: regu yang sudah terdaftar (di pendaftaran ini atau lain) boleh kurang inti.
                $longgar = $this->longgar && ($lama !== null || isset($tersimpan[$kunci]));

                foreach (KelengkapanTim::periksa($nomor, $label, $baru, $lama, $longgar, $tunggal) as $pesan) {
                    $this->galat->tambah("beregu.{$kunci}", $pesan);
                }

                if ($lama !== null && $konfirmasi !== null && ! in_array($kunci, $konfirmasi, true)) {
                    $this->galat->tambah("konfirmasi.{$kunci}", Regu::nama(NomorLombaModel::namaLengkap($nomor), $label, $tunggal)
                        . ': sekolah ini sudah punya regu ini (' . count($lama) . ' atlet dari pendaftaran sebelumnya). '
                        . 'Atlet yang dipilih menjadi tambahan anggota regu itu tanpa biaya regu. '
                        . 'Centang kotak konfirmasi pada regu ini di daftar sementara, atau pilih regu lain, lalu lanjutkan lagi.');
                }

                $inti    = count(array_filter($baru, static fn (array $a): bool => $a['peran'] === Regu::INTI));
                $hasil[] = [
                    'nomor'        => $nomor,
                    'regu'         => $label,
                    'susulan'      => $lama !== null,
                    'anggota'      => count($baru),
                    'inti'         => $inti,
                    'cadangan'     => count($baru) - $inti,
                    'anggota_lama' => $lama !== null ? count($lama) : null,
                    'turun'        => $jumlahTurun,
                    'tunggal'      => $tunggal,
                ];
            }
        }

        return [$hasil, $data];
    }

    /**
     * Inti lama yang diturunkan menjadi cadangan: nomor => id atlet => true.
     * `null` bila ada isian yang bentuknya tidak sah.
     *
     * @return array<int, array<int, true>>|null
     */
    private static function turunKiriman(mixed $nilai): ?array
    {
        $hasil = [];

        foreach (MasukanPeriksa::daftar($nilai) as $isian) {
            if (! is_string($isian) || preg_match('/^([1-9]\d*):([1-9]\d*)$/D', $isian, $m) !== 1) {
                return null;
            }

            $hasil[(int) $m[1]][(int) $m[2]] = true;
        }

        return $hasil;
    }
}
