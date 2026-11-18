<?php

namespace App\Libraries\Panel;

use App\Libraries\SesiAdmin;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;

/**
 * Riwayat tindakan admin per pendaftaran (F8-01, K124): siapa melakukan apa
 * dan kapan.
 *
 * Dipanggil di dalam transaksi tindakan yang dicatat, jadi bila tindakannya
 * dibatalkan, catatannya ikut batal. Galat tulis selalu dilempar sebagai
 * `DatabaseException` supaya transaksi pemanggil ikut dibatalkan.
 *
 * Pelaku dibaca dari sesi admin (`admin_id`, `admin_nama`) tanpa memulai
 * sesi; di luar permintaan admin (perintah spark) pelakunya "Sistem".
 *
 *     $riwayat = new RiwayatAdmin($db);
 *     $riwayat->catatUntuk($id, RiwayatAdmin::LUNAS, 'Ditandai Lunas. 2 atlet mendapat nomor atlet (BIB) baru.');
 *     $riwayat->catat(null, 'MAC2A0000126', RiwayatAdmin::HAPUS, 'Pendaftaran dihapus beserta 3 atlet.');
 *     $riwayat->untukPendaftaran($id);   // terbaru di atas
 *     $riwayat->cari('MAC2A0000126');    // termasuk pendaftaran yang sudah dihapus
 *
 * Saat pendaftaran digabung (K109) barisnya pindah ke pendaftaran tujuan
 * (`pindahkan()`); `id_pendaftaran` tetap ID lamanya.
 */
final class RiwayatAdmin
{
    public const LUNAS        = 'lunas';
    public const MENUNGGU     = 'menunggu';
    public const UBAH         = 'ubah';
    public const TOLAK_ATLET  = 'tolak_atlet';
    public const HAPUS        = 'hapus';
    public const UNGGAH_BUKTI = 'unggah_bukti';
    public const HAPUS_BUKTI  = 'hapus_bukti';
    public const URUTKAN      = 'urutkan_nomor';
    public const GABUNG       = 'gabung';

    /**
     * Nama tindakan untuk tampilan.
     *
     * @var array<string, string>
     */
    public const LABEL = [
        self::LUNAS        => 'Tandai Lunas',
        self::MENUNGGU     => 'Kembali ke Menunggu',
        self::UBAH         => 'Ubah',
        self::TOLAK_ATLET  => 'Tolak atlet',
        self::HAPUS        => 'Hapus pendaftaran',
        self::UNGGAH_BUKTI => 'Unggah bukti bayar',
        self::HAPUS_BUKTI  => 'Hapus bukti bayar',
        self::URUTKAN      => 'Urutkan nomor atlet',
        self::GABUNG       => 'Gabung',
    ];

    /**
     * Nama pelaku bila tidak ada sesi admin (misalnya perintah spark).
     */
    public const SISTEM = 'Sistem';

    /**
     * Paling banyak baris hasil pencarian `admin/riwayat`.
     */
    public const BATAS_CARI = 200;

    private BaseConnection $db;

    /**
     * @var array{id: int|null, nama: string}
     */
    private array $pelaku;

    /**
     * @param array{id: int|null, nama: string}|null $pelaku Bawaan: admin di sesi (`pelakuSesi()`).
     */
    public function __construct(?BaseConnection $db = null, ?array $pelaku = null)
    {
        $this->db     = $db ?? db_connect();
        $this->pelaku = $pelaku ?? self::pelakuSesi();
    }

    /**
     * Admin yang sedang masuk, dibaca dari sesi tanpa memulai sesi baru.
     *
     * @return array{id: int|null, nama: string}
     */
    public static function pelakuSesi(): array
    {
        $id   = $_SESSION[SesiAdmin::KUNCI_ID] ?? null;
        $nama = trim((string) ($_SESSION[SesiAdmin::KUNCI_NAMA] ?? ''));

        if ($id === null && $nama === '') {
            return ['id' => null, 'nama' => self::SISTEM];
        }

        return [
            'id'   => is_numeric($id) && (int) $id > 0 ? (int) $id : null,
            'nama' => $nama === '' ? 'Admin' : $nama,
        ];
    }

    /**
     * Catat satu tindakan.
     *
     * @throws DatabaseException Bila gagal ditulis.
     */
    public function catat(?int $pendaftaranId, string $idPendaftaran, string $tindakan, string $ringkasan): void
    {
        $hasil = $this->db->table('riwayat_admin')->insert([
            'pendaftaran_id' => $pendaftaranId,
            'id_pendaftaran' => mb_substr($idPendaftaran, 0, 20),
            'admin_id'       => $this->pelaku['id'],
            'nama_admin'     => mb_substr($this->pelaku['nama'], 0, 100),
            'tindakan'       => mb_substr($tindakan, 0, 30),
            'ringkasan'      => trim($ringkasan),
            'dibuat_pada'    => self::sekarang(),
        ]);

        if ($hasil === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Riwayat tindakan admin gagal dicatat: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }
    }

    /**
     * Catat tindakan untuk pendaftaran yang masih ada; ID pendaftarannya
     * dibaca dari basis data. Pendaftaran yang tidak ada dilewati.
     */
    public function catatUntuk(int $pendaftaranId, string $tindakan, string $ringkasan): void
    {
        $hasil = $this->db->query('SELECT id_pendaftaran FROM pendaftaran WHERE id = ?', [$pendaftaranId]);
        $baris = is_object($hasil) ? $hasil->getRowArray() : null;

        if (! is_array($baris)) {
            return;
        }

        $this->catat($pendaftaranId, (string) $baris['id_pendaftaran'], $tindakan, $ringkasan);
    }

    /**
     * Pindahkan riwayat satu pendaftaran ke pendaftaran lain (penggabungan, K109).
     *
     * @throws DatabaseException Bila gagal ditulis.
     */
    public function pindahkan(int $dari, int $ke): void
    {
        if ($this->db->table('riwayat_admin')->where('pendaftaran_id', $dari)->update(['pendaftaran_id' => $ke]) === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Riwayat tindakan admin gagal dipindahkan: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }
    }

    /**
     * Riwayat satu pendaftaran, terbaru di atas.
     *
     * @return list<array{id: int, id_pendaftaran: string, nama_admin: string, tindakan: string, label: string, ringkasan: string, dibuat_pada: string|null}>
     */
    public function untukPendaftaran(int $pendaftaranId): array
    {
        return array_map(self::bentuk(...), $this->db->table('riwayat_admin')
            ->where('pendaftaran_id', $pendaftaranId)
            ->orderBy('dibuat_pada', 'DESC')->orderBy('id', 'DESC')
            ->get()->getResultArray());
    }

    /**
     * Cari riwayat menurut ID pendaftaran (sebagian boleh), termasuk
     * pendaftaran yang sudah dihapus atau digabung. Terbaru di atas, paling
     * banyak `BATAS_CARI` baris.
     *
     * @return list<array{id: int, pendaftaran_id: int|null, id_pendaftaran: string, nama_admin: string, tindakan: string, label: string, ringkasan: string, dibuat_pada: string|null}>
     */
    public function cari(string $kata): array
    {
        $kata = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $kata));

        if ($kata === '') {
            return [];
        }

        return array_map(self::bentuk(...), $this->db->table('riwayat_admin')
            ->like('id_pendaftaran', $kata)
            ->orderBy('dibuat_pada', 'DESC')->orderBy('id', 'DESC')
            ->limit(self::BATAS_CARI)
            ->get()->getResultArray());
    }

    /**
     * Ringkasan perubahan dari Ubah pendaftaran. Aturan murni.
     *
     * @param array<string, mixed> $lama    Data tersimpan (`UbahPendaftaran::bacaLama()`).
     * @param array<string, mixed> $rencana Rencana simpan (`UbahPendaftaran::hitung()`).
     */
    public static function ringkasanUbah(array $lama, array $rencana): string
    {
        $bagian = [];

        $p       = $lama['pendaftaran'] ?? [];
        $sekolah = $rencana['sekolah'] ?? [];
        $ubahSek = [];

        foreach (['nama_sekolah' => 'nama sekolah', 'npsn' => 'NPSN', 'jenjang' => 'jenjang', 'kabupaten' => 'kabupaten'] as $kolom => $nama) {
            $dulu = trim((string) ($p[$kolom] ?? ''));
            $kini = trim((string) ($sekolah[$kolom] ?? ''));

            if ($dulu !== $kini) {
                $ubahSek[] = $nama . ' ' . ($dulu === '' ? '(kosong)' : $dulu) . ' → ' . ($kini === '' ? '(kosong)' : $kini);
            }
        }

        if ($ubahSek !== []) {
            $bagian[] = 'Data sekolah diubah: ' . implode('; ', $ubahSek) . '.';
        }

        // Ofisial (penanggung jawab).
        $pjLama  = $lama['penanggung_jawab'] ?? [];
        $pjBaru  = $rencana['penanggung_jawab'] ?? [];
        $ubahPj  = count($pjLama) !== count($pjBaru);

        foreach ($pjBaru as $orang) {
            $id = $orang['id'] ?? null;

            if ($id === null || ! isset($pjLama[$id]) || ($orang['foto_baru'] ?? null) !== null
                || (string) $pjLama[$id]['nama'] !== (string) $orang['nama'] || (string) $pjLama[$id]['nomor_wa'] !== (string) $orang['nomor_wa']) {
                $ubahPj = true;
            }
        }

        if ($ubahPj) {
            $bagian[] = 'Data ofisial diubah.';
        }

        // Atlet.
        $atletLama = $lama['atlet'] ?? [];
        $ditambah  = [];
        $diubah    = [];
        $dipakai   = [];

        foreach ($rencana['atlet'] ?? [] as $a) {
            $id   = $a['id'] ?? null;
            $nama = self::namaAtlet($a);

            if ($id === null || ! isset($atletLama[$id])) {
                $ditambah[] = $nama;

                continue;
            }

            $dipakai[] = (int) $id;
            $dulu      = $atletLama[$id];
            $apa       = [];

            foreach (['nama', 'nisn', 'tanggal_lahir', 'jenis_kelamin'] as $kolom) {
                if (mb_strtoupper(trim((string) ($dulu[$kolom] ?? ''))) !== mb_strtoupper(trim((string) ($a[$kolom] ?? '')))) {
                    $apa[] = 'data diri';

                    break;
                }
            }

            if ((int) ($dulu['kelompok_umur_id'] ?? 0) !== (int) ($a['kelompok_umur_id'] ?? 0)) {
                $apa[] = 'kelompok umur';
            }

            $nomorDulu = array_map('intval', array_keys($lama['nomor'][$id] ?? []));
            $nomorKini = array_map(static fn (array $n): int => (int) $n['nomor_lomba_id'], $a['nomor'] ?? []);
            sort($nomorDulu);
            sort($nomorKini);

            if ($nomorDulu !== $nomorKini) {
                $apa[] = 'nomor lomba';
            } else {
                foreach ($a['nomor'] ?? [] as $n) {
                    $sebelum = $lama['regu'][$id][(int) $n['nomor_lomba_id']] ?? null;

                    if ($sebelum !== null && ($n['regu'] ?? null) !== null
                        && ['regu' => $n['regu'] ?? null, 'peran' => $n['peran'] ?? null] !== ['regu' => $sebelum['regu'], 'peran' => $sebelum['peran']]) {
                        $apa[] = 'regu';

                        break;
                    }
                }
            }

            if ($apa !== []) {
                $diubah[] = $nama . ' (' . implode(', ', $apa) . ')';
            }
        }

        $dihapus = [];

        foreach ($atletLama as $id => $a) {
            if (! in_array((int) $id, $dipakai, true)) {
                $dihapus[] = self::namaAtlet($a);
            }
        }

        if ($ditambah !== []) {
            $bagian[] = 'Atlet ditambah: ' . implode(', ', $ditambah) . '.';
        }

        if ($dihapus !== []) {
            $bagian[] = 'Atlet dihapus: ' . implode(', ', $dihapus) . '.';
        }

        if ($diubah !== []) {
            $bagian[] = 'Atlet diubah: ' . implode(', ', $diubah) . '.';
        }

        if (($rencana['tim_dibebankan'] ?? []) !== []) {
            $bagian[] = 'Biaya ' . count($rencana['tim_dibebankan']) . ' regu tanpa pembayar dibebankan ke pendaftaran ini.';
        }

        if ($bagian === []) {
            $bagian[] = 'Disimpan tanpa perubahan data.';
        }

        $totalLama = (int) ($rencana['total_lama'] ?? 0);
        $total     = (int) ($rencana['total'] ?? 0);
        $bagian[]  = $totalLama === $total
            ? 'Total tetap ' . rupiah($total) . '.'
            : 'Total ' . rupiah($totalLama) . ' → ' . rupiah($total) . '.';

        if ($rencana['kembali_menunggu'] ?? false) {
            $bagian[] = 'Status kembali dari Lunas ke Menunggu karena total berubah.';
        }

        return implode(' ', $bagian);
    }

    /**
     * Gabung daftar nama atlet untuk ringkasan, contoh "Budi Karangan (0012345678)".
     *
     * @param array<string, mixed> $atlet
     */
    public static function namaAtlet(array $atlet): string
    {
        $nisn = trim((string) ($atlet['nisn'] ?? ''));

        return trim((string) ($atlet['nama'] ?? '')) . ($nisn === '' ? '' : ' (' . $nisn . ')');
    }

    /**
     * @param array<string, mixed> $baris
     *
     * @return array{id: int, pendaftaran_id: int|null, id_pendaftaran: string, nama_admin: string, tindakan: string, label: string, ringkasan: string, dibuat_pada: string|null}
     */
    private static function bentuk(array $baris): array
    {
        $tindakan = (string) $baris['tindakan'];

        return [
            'id'             => (int) $baris['id'],
            'pendaftaran_id' => $baris['pendaftaran_id'] === null ? null : (int) $baris['pendaftaran_id'],
            'id_pendaftaran' => (string) $baris['id_pendaftaran'],
            'nama_admin'     => (string) $baris['nama_admin'],
            'tindakan'       => $tindakan,
            'label'          => self::LABEL[$tindakan] ?? $tindakan,
            'ringkasan'      => (string) $baris['ringkasan'],
            'dibuat_pada'    => $baris['dibuat_pada'] === null ? null : (string) $baris['dibuat_pada'],
        ];
    }

    private static function sekarang(): string
    {
        return Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');
    }
}
