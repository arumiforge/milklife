<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

/**
 * Galat satu pemeriksaan kiriman wizard (bagian dari `PemeriksaPendaftaran`,
 * F7-30), berurutan seperti ditemukan. Langkah wizard ditentukan dari awal
 * jalurnya.
 */
final class DaftarGalat
{
    private const LANGKAH = ['sekolah' => 1, 'penanggung_jawab' => 2, 'atlet' => 3, 'beregu' => 3, 'individu' => 3, 'konfirmasi' => 3, 'turun' => 3];

    /**
     * @var list<array{langkah: int, jalur: string, pesan: string}>
     */
    private array $galat = [];

    public function tambah(string $jalur, string $pesan): void
    {
        $akar = explode('.', $jalur, 2)[0];

        $this->galat[] = ['langkah' => self::LANGKAH[$akar] ?? 3, 'jalur' => $jalur, 'pesan' => $pesan];
    }

    /**
     * @return list<array{langkah: int, jalur: string, pesan: string}>
     */
    public function semua(): array
    {
        return $this->galat;
    }

    public function kosong(): bool
    {
        return $this->galat === [];
    }
}
