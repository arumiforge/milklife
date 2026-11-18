<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Pendaftaran\PemeriksaPendaftaran;

/**
 * Langkah 3: daftar atlet (bagian dari `PemeriksaPendaftaran`, F7-30). Per
 * atlet, berurutan: nama, NISN (`PeriksaNisn`), jenis kelamin, tanggal lahir
 * dan kelompok umur (`PeriksaKelompokUmur`), nomor lomba (`PeriksaNomor`),
 * lalu regu dan peran di setiap nomor beregu (`PeriksaRegu`).
 */
final class PeriksaAtlet
{
    /**
     * @param array<string, mixed>                                       $ada
     * @param array<int, array{kelompok_umur_id: int, nomor: list<int>}> $simpanan Atlet tersimpan per id (admin mengubah pendaftaran).
     */
    public function __construct(
        private readonly IndukPeriksa $induk,
        private readonly array $ada,
        private readonly string $hariIni,
        private readonly array $simpanan,
        private readonly PeriksaRegu $regu,
        private readonly DaftarGalat $galat,
    ) {
    }

    /**
     * @param list<mixed> $daftar
     *
     * @return array{0: list<array<string, mixed>>, 1: array<int, int>, 2: array<int, array<string, list<array{jenis_kelamin: string|null, peran: string}>>>}
     *                                                                                                atlet bersih, jumlah atlet per nomor individu, anggota per nomor beregu per regu
     */
    public function periksa(array $daftar, ?string $jenjang): array
    {
        if ($daftar === []) {
            $this->galat->tambah('atlet', 'Belum ada atlet. Tambahkan minimal satu atlet.');

            return [[], [], []];
        }

        $maksimalIndividu = (int) ($this->induk->aturan['nomor_individu_maksimal'] ?? 2);
        $periksaNisn      = new PeriksaNisn($this->ada['nisn_terdaftar'] ?? [], $this->galat);
        $periksaKu        = new PeriksaKelompokUmur($this->induk, $this->hariIni, $this->galat);
        $periksaNomor     = new PeriksaNomor($this->induk, $this->galat);
        $panjang          = PemeriksaPendaftaran::NAMA_MAKSIMAL;
        $hasil            = [];
        $individu         = [];
        $regu             = [];

        foreach ($daftar as $i => $isian) {
            $isian = is_array($isian) ? $isian : [];
            $jalur  = "atlet.{$i}";
            $idLama = $isian['id'] ?? null;
            $simpan = (is_int($idLama) || (is_string($idLama) && ctype_digit($idLama))) ? ($this->simpanan[(int) $idLama] ?? null) : null;
            $nama   = mb_strtoupper(MasukanPeriksa::rapikanSpasi(MasukanPeriksa::teks($isian['nama'] ?? null)));
            $ke     = $nama !== '' ? $nama : 'Atlet ' . ($i + 1);
            $nisn   = trim(MasukanPeriksa::teks($isian['nisn'] ?? null));
            $jk     = strtoupper(trim(MasukanPeriksa::teks($isian['jenis_kelamin'] ?? null)));
            $lahir  = MasukanPeriksa::tanggal(MasukanPeriksa::teks($isian['tanggal_lahir'] ?? null));

            if ($nama === '') {
                $this->galat->tambah("{$jalur}.nama", "{$ke}: nama belum diisi.");
            } elseif (mb_strlen($nama) > $panjang) {
                $this->galat->tambah("{$jalur}.nama", "{$ke}: nama terlalu panjang. Tulis paling banyak " . $panjang . ' huruf.');
            }

            $periksaNisn->periksa($nisn, $jalur, $ke);

            if (! in_array($jk, ['L', 'P'], true)) {
                $this->galat->tambah("{$jalur}.jenis_kelamin", "{$ke}: jenis kelamin belum dipilih.");
                $jk = null;
            }

            $ku          = $periksaKu->periksa($lahir, $jenjang, $simpan, $jalur, $ke);
            $nomor       = $periksaNomor->periksa($isian['nomor'] ?? null, $jalur, $ke, $ku, $jk, $maksimalIndividu, $simpan['nomor'] ?? []);
            $pilihanRegu = is_array($isian['regu'] ?? null) ? $isian['regu'] : [];

            foreach ($nomor as $j => $baris) {
                $id = (int) $baris['id'];

                if ($baris['jenis'] === 'individu') {
                    $individu[$id] = ($individu[$id] ?? 0) + 1;

                    continue;
                }

                $pilih = $this->regu->periksa($pilihanRegu[$id] ?? null, $baris, $jalur, $ke);

                if ($pilih === null) {
                    continue;
                }

                $nomor[$j] += $pilih;
                $regu[$id][$pilih['regu']][] = ['jenis_kelamin' => $jk, 'peran' => $pilih['peran']];
            }

            $hasil[] = [
                'nama'          => $nama,
                'nisn'          => $nisn,
                'tanggal_lahir' => $lahir?->format('Y-m-d'),
                'jenis_kelamin' => $jk,
                'kelompok_umur' => $ku,
                'nomor'         => $nomor,
            ];
        }

        return [$hasil, $individu, $regu];
    }
}
