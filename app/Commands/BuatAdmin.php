<?php

namespace App\Commands;

use App\Models\AdminModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark admin:buat` — membuat akun admin, atau mengganti kata sandi
 * admin yang nama penggunanya sudah ada.
 *
 * Kata sandi tidak pernah diterima lewat argumen baris perintah (akan
 * tersimpan di riwayat terminal). Dari terminal, kata sandi dibaca tanpa
 * ditampilkan bila `stty` tersedia. Bila masukan standar bukan terminal
 * (dipakai dari skrip), setiap jawaban dibaca satu baris dari masukan standar.
 */
class BuatAdmin extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'admin:buat';
    protected $description = 'Membuat akun admin, atau mengganti kata sandi admin yang sudah ada.';
    protected $usage       = 'admin:buat [--nama "Nama Lengkap"] [--pengguna nama_pengguna]';
    protected $options     = [
        '--nama'     => 'Nama admin yang tampil di panel admin. Bila tidak diisi, akan ditanyakan.',
        '--pengguna' => 'Nama pengguna untuk masuk (huruf, angka, titik, garis bawah; 3–30 karakter). Bila tidak diisi, akan ditanyakan.',
    ];

    /**
     * Berapa kali sebuah pertanyaan diulang bila jawabannya belum benar.
     */
    private const BATAS_ULANG = 3;

    public function run(array $params): int
    {
        $model = model(AdminModel::class);

        $namaPengguna = $this->tanyaSampaiBenar(
            'Nama pengguna',
            $this->opsi($params, 'pengguna'),
            static fn (string $isi): array => $model->periksaIsian(['nama_pengguna' => $isi], true),
        );
        if ($namaPengguna === null) {
            return EXIT_ERROR;
        }
        $namaPengguna = AdminModel::normalkanNamaPengguna($namaPengguna);

        $admin = $model->cariNamaPengguna($namaPengguna);
        $nama  = '';

        if ($admin !== null) {
            CLI::write('Nama pengguna "' . $namaPengguna . '" sudah dipakai oleh admin "' . $admin['nama'] . '".', 'yellow');

            if (! $this->konfirmasi('Ganti kata sandinya?')) {
                CLI::write('Tidak ada yang diubah.');

                return EXIT_SUCCESS;
            }
        } else {
            $nama = $this->tanyaSampaiBenar(
                'Nama',
                $this->opsi($params, 'nama'),
                static fn (string $isi): array => $model->periksaIsian(['nama' => $isi]),
            );
            if ($nama === null) {
                return EXIT_ERROR;
            }
        }

        $sandi = $this->tanyaSandi($model);
        if ($sandi === null) {
            return EXIT_ERROR;
        }

        if ($admin !== null) {
            $galat = $this->gantiSandi($model, (int) $admin['id'], $sandi);
            $pesan = 'Kata sandi admin "' . $namaPengguna . '" sudah diganti.';
        } else {
            $galat = $this->simpanAdmin($model, $nama, $namaPengguna, $sandi);
            $pesan = 'Admin "' . $namaPengguna . '" sudah dibuat. Silakan masuk di /admin/masuk.';
        }

        if ($galat !== []) {
            foreach ($galat as $baris) {
                CLI::error($baris);
            }

            return EXIT_ERROR;
        }

        CLI::write($pesan, 'green');

        return EXIT_SUCCESS;
    }

    /**
     * Simpan admin baru. Kata sandi diberikan sebagai teks asli; model yang
     * mengubahnya menjadi hash.
     *
     * @return list<string> pesan galat; kosong bila berhasil
     */
    public function simpanAdmin(AdminModel $model, string $nama, string $namaPengguna, string $sandi): array
    {
        $berhasil = $model->insert([
            'nama'          => $nama,
            'nama_pengguna' => $namaPengguna,
            'sandi'         => $sandi,
        ]);

        return $berhasil === false ? array_values($model->errors()) : [];
    }

    /**
     * @return list<string> pesan galat; kosong bila berhasil
     */
    public function gantiSandi(AdminModel $model, int $id, string $sandi): array
    {
        return $model->simpanSandi($id, $sandi) ? [] : array_values($model->errors());
    }

    /**
     * Tanyakan kata sandi dua kali sampai sah dan sama.
     */
    private function tanyaSandi(AdminModel $model): ?string
    {
        if (! $this->bisaSembunyikanKetikan() && $this->masukanDariTerminal()) {
            CLI::write('Perhatian: kata sandi akan terlihat saat diketik. Pastikan tidak ada yang melihat layar.', 'yellow');
        }

        for ($ke = 1; $ke <= self::BATAS_ULANG; $ke++) {
            $sandi = $this->bacaSandi('Kata sandi (minimal ' . AdminModel::SANDI_MINIMAL . ' karakter): ');
            $galat = $model->periksaIsian(['sandi' => $sandi]);

            if ($galat !== []) {
                CLI::error(implode(' ', $galat));

                continue;
            }

            if ($this->bacaSandi('Ulangi kata sandi: ') !== $sandi) {
                CLI::error('Kedua kata sandi tidak sama. Ketik ulang kata sandi yang sama dua kali.');

                continue;
            }

            return $sandi;
        }

        CLI::error('Kata sandi belum benar setelah ' . self::BATAS_ULANG . ' kali percobaan. Jalankan perintah ini lagi.');

        return null;
    }

    /**
     * Pakai nilai dari opsi bila ada; bila tidak, tanyakan. Jawaban diperiksa
     * dengan `$periksa` (mengembalikan pesan galat) dan diulang bila salah.
     *
     * @param callable(string): array<string, string> $periksa
     */
    private function tanyaSampaiBenar(string $label, ?string $dariOpsi, callable $periksa): ?string
    {
        if ($dariOpsi !== null) {
            $galat = $periksa($dariOpsi);
            if ($galat === []) {
                return $dariOpsi;
            }

            CLI::error(implode(' ', $galat));

            return null;
        }

        for ($ke = 1; $ke <= self::BATAS_ULANG; $ke++) {
            $isi   = $this->tanya($label . ': ');
            $galat = $periksa($isi);

            if ($galat === []) {
                return $isi;
            }

            CLI::error(implode(' ', $galat));
        }

        CLI::error($label . ' belum benar setelah ' . self::BATAS_ULANG . ' kali percobaan. Jalankan perintah ini lagi.');

        return null;
    }

    private function konfirmasi(string $pertanyaan): bool
    {
        for ($ke = 1; $ke <= self::BATAS_ULANG; $ke++) {
            $jawaban = strtolower($this->tanya($pertanyaan . ' Ketik ya atau tidak: '));

            if (in_array($jawaban, ['ya', 'y'], true)) {
                return true;
            }
            if (in_array($jawaban, ['tidak', 't', 'n'], true)) {
                return false;
            }

            CLI::error('Jawab dengan "ya" atau "tidak".');
        }

        return false;
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function opsi(array $params, string $nama): ?string
    {
        $nilai = $params[$nama] ?? CLI::getOption($nama);

        return is_string($nilai) && trim($nilai) !== '' ? trim($nilai) : null;
    }

    private function tanya(string $label): string
    {
        return trim(CLI::input($label));
    }

    /**
     * Baca kata sandi tanpa menampilkannya bila terminal mendukung.
     */
    private function bacaSandi(string $label): string
    {
        if (! $this->bisaSembunyikanKetikan()) {
            return rtrim(CLI::input($label), "\r\n");
        }

        CLI::print($label);

        $keadaanAwal = trim((string) shell_exec('stty -g'));
        $pulihkan    = static function () use ($keadaanAwal): void {
            shell_exec($keadaanAwal !== '' ? 'stty ' . escapeshellarg($keadaanAwal) : 'stty echo');
        };

        // Bila dihentikan dengan Ctrl+C, tampilan ketikan dipulihkan dulu.
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, static function () use ($pulihkan): void {
                $pulihkan();
                CLI::print(PHP_EOL);

                exit(130);
            });
        }

        shell_exec('stty -echo');

        try {
            $baris = fgets(STDIN);
        } finally {
            $pulihkan();
            CLI::print(PHP_EOL);

            if (function_exists('pcntl_signal')) {
                pcntl_signal(SIGINT, SIG_DFL);
            }
        }

        return $baris === false ? '' : rtrim($baris, "\r\n");
    }

    private function masukanDariTerminal(): bool
    {
        return ENVIRONMENT !== 'testing' && defined('STDIN') && stream_isatty(STDIN);
    }

    /**
     * Ketikan bisa disembunyikan bila masukan dari terminal dan `stty`
     * tersedia (Linux, macOS, Git Bash). Di Windows (cmd) tidak tersedia.
     */
    private function bisaSembunyikanKetikan(): bool
    {
        static $bisa = null;

        if ($bisa === null) {
            $bisa = $this->masukanDariTerminal()
                && DIRECTORY_SEPARATOR === '/'
                && function_exists('shell_exec')
                && trim((string) @shell_exec('stty -g 2>/dev/null')) !== '';
        }

        return $bisa;
    }
}
