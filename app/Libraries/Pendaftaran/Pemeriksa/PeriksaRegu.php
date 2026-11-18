<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Pendaftaran\Regu;
use App\Models\NomorLombaModel;

/**
 * Regu dan peran satu atlet di satu nomor beregu (bagian dari
 * `PemeriksaPendaftaran`, F7-30; K105–K107), serta regu yang sudah dimiliki
 * sekolah ini. Mode Ketentuan: nama regu dari daftar admin (huruf A, B, C bila
 * kosong) sebanyak batas regu, ditambah regu lama. Mode Bebas: nama regu
 * diketik official; banyaknya regu diperiksa `PeriksaTim`.
 */
final class PeriksaRegu
{
    /**
     * Regu sekolah ini dari pendaftaran lain: nomor => regu => anggota.
     *
     * @var array<int, array<string, list<array<string, mixed>>>>
     */
    public readonly array $timAda;

    /**
     * @param mixed       $tim           `ada['tim']`; `[]` bila kiriman tanpa NPSN.
     * @param list<mixed> $reguTersimpan `ada['regu_tersimpan']`: regu ("nomor:regu") pendaftaran yang diubah admin.
     */
    public function __construct(
        private readonly IndukPeriksa $induk,
        mixed $tim,
        private readonly array $reguTersimpan,
        private readonly DaftarGalat $galat,
    ) {
        $this->timAda = self::rapikanTim($tim);
    }

    /**
     * @param array<string, mixed> $nomor
     *
     * @return array{regu: string, peran: string}|null
     */
    public function periksa(mixed $pilihan, array $nomor, string $jalur, string $ke): ?array
    {
        $pilihan   = is_array($pilihan) ? $pilihan : [];
        $label     = Regu::rapikan($pilihan['regu'] ?? null);
        $peran     = strtolower(trim(MasukanPeriksa::teks($pilihan['peran'] ?? null)));
        $namaNomor = NomorLombaModel::namaLengkap($nomor);
        $sah       = true;

        if ($this->bebas()) {
            // Nama regu diketik official (K106); banyaknya regu diperiksa di PeriksaTim.
            if ($label === '') {
                $this->galat->tambah("{$jalur}.regu", "{$ke}: {$namaNomor} belum diberi nama regu. Tulis nama regunya, misalnya A atau Merah.");
                $sah = false;
            } elseif (! Regu::sah($label)) {
                $this->galat->tambah("{$jalur}.regu", "{$ke}: nama regu {$namaNomor} hanya boleh berisi huruf, angka, spasi, titik, garis miring, atau tanda hubung, paling banyak "
                    . Regu::PANJANG_MAKSIMAL . ' huruf. Ganti nama regunya.');
                $sah = false;
            }
        } else {
            $boleh = $this->dibolehkan($nomor);

            if ($label === '') {
                $this->galat->tambah("{$jalur}.regu", "{$ke}: {$namaNomor} belum memilih regu. Pilih " . Regu::awalan() . ' ' . self::teksLabel($boleh) . '.');
                $sah = false;
            } elseif (! in_array($label, $boleh, true)) {
                $this->galat->tambah("{$jalur}.regu", "{$ke}: {$namaNomor} tidak punya " . Regu::label($label) . '; satu sekolah paling banyak '
                    . count($boleh) . ' regu di nomor ini. Pilih ' . Regu::awalan() . ' ' . self::teksLabel($boleh) . '.');
                $sah = false;
            }
        }

        if (! Regu::peranSah($peran)) {
            $this->galat->tambah("{$jalur}.regu", "{$ke}: {$namaNomor} belum ditandai Inti atau Cadangan. Pilih salah satu.");
            $sah = false;
        }

        return $sah ? ['regu' => $label, 'peran' => $peran] : null;
    }

    /**
     * Mode nama regu Bebas: official mengetik nama regu sendiri (K106).
     */
    public function bebas(): bool
    {
        return ($this->induk->aturan['regu_mode'] ?? Regu::MODE_KETENTUAN) === Regu::MODE_BEBAS;
    }

    /**
     * Regu yang sudah dimiliki sekolah ini (pendaftaran lain) atau pendaftaran
     * yang sedang diubah di satu nomor: tetap boleh dipakai walau batas regu,
     * mode, atau daftar nama regu diubah.
     *
     * @return list<string>
     */
    public function lamaNomor(int $nomorId): array
    {
        $lama = array_map('strval', array_keys($this->timAda[$nomorId] ?? []));

        foreach ($this->reguTersimpan as $kunci) {
            $pecah = Regu::pecah($kunci);

            if ($pecah !== null && $pecah[0] === $nomorId) {
                $lama[] = $pecah[1];
            }
        }

        return array_values(array_unique($lama));
    }

    /**
     * Nama regu yang boleh dipakai di nomor ini pada mode Ketentuan admin:
     * nama pertama dari daftar admin (huruf A, B, C, … bila daftarnya tidak
     * ada) sebanyak batas regu nomor itu, ditambah regu lama (`lamaNomor()`).
     *
     * @param array<string, mixed> $nomor
     *
     * @return list<string>
     */
    public function dibolehkan(array $nomor): array
    {
        $daftar = $this->induk->aturan['regu_label'] ?? null;
        $boleh  = Regu::dibolehkan(isset($nomor['regu_maksimal']) ? (int) $nomor['regu_maksimal'] : null, is_array($daftar) && $daftar !== [] ? $daftar : null);

        foreach ($this->lamaNomor((int) $nomor['id']) as $label) {
            if (! in_array($label, $boleh, true)) {
                $boleh[] = $label;
            }
        }

        return $boleh;
    }

    /**
     * "A, B, atau C".
     *
     * @param list<string> $label
     */
    public static function teksLabel(array $label): string
    {
        if (count($label) <= 1) {
            return implode('', $label);
        }

        // Huruf berurutan yang panjang ditulis "A sampai Z".
        if (count($label) > 5 && array_filter($label, static fn (string $l): bool => strlen($l) !== 1) === []) {
            return $label[0] . ' sampai ' . $label[count($label) - 1];
        }

        $akhir = array_pop($label);

        return implode(', ', $label) . (count($label) > 1 ? ', atau ' : ' atau ') . $akhir;
    }

    /**
     * Regu sekolah dari `ada['tim']`, dirapikan: nomor => regu => anggota.
     *
     * @return array<int, array<string, list<array<string, mixed>>>>
     */
    private static function rapikanTim(mixed $tim): array
    {
        $hasil = [];

        foreach (is_array($tim) ? $tim : [] as $nomorId => $regu) {
            foreach (is_array($regu) ? $regu : [] as $label => $anggota) {
                if (Regu::sah((string) $label)) {
                    $hasil[(int) $nomorId][(string) $label] = array_values(is_array($anggota) ? $anggota : []);
                }
            }
        }

        return $hasil;
    }
}
