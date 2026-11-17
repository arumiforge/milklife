<?php

namespace App\Libraries\Panel;

use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\KelompokUmurAtlet;
use App\Models\KelompokUmurModel;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Tolak atlet dari sebuah pendaftaran (K116).
 *
 * Contoh kasusnya: kelompok umur sempat salah diatur, sehingga atlet lahir
 * 2018 masuk KU 8 padahal seharusnya KU 10. Atletnya tidak dipindah oleh
 * admin; panitia menolaknya dan sekolah mendaftarkannya ulang.
 *
 * ## Aturan
 *
 * - Hanya untuk pendaftaran **Menunggu**. Pendaftaran Lunas dikembalikan ke
 *   Menunggu dulu (atlet Lunas sudah punya nomor atlet).
 * - Admin memilih satu atau beberapa atlet dan menulis alasan
 *   (`PANJANG_ALASAN_MIN`–`PANJANG_ALASAN_MAKS` huruf).
 * - Atlet yang ditolak dihapus dari pendaftaran (nomor lombanya ikut), jadi
 *   NISN-nya bisa didaftarkan lagi. Salinannya disimpan di `atlet_ditolak`
 *   untuk Cek Pendaftaran: kelompok umur dan nomor lomba lama, biaya
 *   individunya, alasan, kelompok umur yang sesuai menurut pengaturan saat
 *   ditolak, serta total pendaftaran sebelum dan sesudahnya.
 * - Bila masih ada atlet yang tersisa, penyimpanan lewat `UbahPendaftaran`
 *   (aturan yang sama dengan Ubah admin): biaya dan regu dihitung ulang,
 *   regu yang kosong biayanya hilang, dan atlet yang tersisa diperiksa ulang.
 *   Bila ada atlet tersisa yang juga tidak cocok, penolakan dibatalkan dengan
 *   pesannya, supaya admin ikut menolak atau membetulkannya.
 * - Bila semua atlet ditolak, pendaftaran tetap ada (ofisial dan bukti bayar
 *   tidak hilang, untuk urusan pengembalian uang), tanpa atlet dan regu, dengan
 *   total Rp 0.
 * - Catatan penolakan dan penghapusan atlet ditulis dalam satu transaksi.
 *
 *     $tolak = new TolakAtlet();
 *     $form  = $tolak->formulir($id);    // null bila pendaftaran tidak ada
 *     $hasil = $tolak->tolak($id, [11, 12], 'Tahun lahir tidak sesuai …', 'Admin');
 *     // ['galat' => [], 'tersimpan' => true, 'jumlah' => 2, 'semua' => false,
 *     //  'total_lama' => 125000, 'total' => 75000, 'tim_ditinggalkan' => [ … ]]
 */
final class TolakAtlet
{
    public const PANJANG_ALASAN_MIN = 10;

    public const PANJANG_ALASAN_MAKS = 1000;

    /**
     * Lama menunggu kunci per NPSN, dalam detik (sama dengan `UbahPendaftaran`).
     */
    private const TUNGGU_KUNCI = 10;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null, private readonly ?UbahPendaftaran $ubah = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Pendaftaran dan atletnya, dengan kecocokan kelompok umur menurut
     * pengaturan sekarang. `null` bila pendaftaran tidak ada.
     *
     *     [
     *         'pendaftaran' => ['id', 'id_pendaftaran', 'nama_sekolah', 'npsn', 'jenjang', 'jenjang_label', 'status', 'total', 'diubah_pada'],
     *         'atlet' => [[
     *             'id' => 11, 'nama' => '…', 'nisn' => '…', 'tanggal_lahir' => '2018-05-01', 'jenis_kelamin' => 'L',
     *             'kelompok_umur_id' => 1, 'kelompok_umur' => 'KU 8',
     *             'nomor_lomba' => [['nama' => 'Lari 40 M putra', 'jenis' => 'individu', 'biaya' => 25000, 'regu' => null, 'peran' => null]],
     *             'biaya_individu' => 25000,
     *             'cocok' => false,                       // kelompok umur tersimpan masih cocok dengan pengaturan sekarang
     *             'sesuai_id' => 2, 'sesuai' => 'KU 10 (lahir 2016–2018)',
     *         ]],
     *     ]
     *
     * @return array<string, mixed>|null
     */
    public function formulir(int $id): ?array
    {
        $p = $this->db->table('pendaftaran')
            ->select('id, id_pendaftaran, nama_sekolah, npsn, jenjang, status, total, diubah_pada')
            ->where('id', $id)->get()->getRowArray();

        if (! is_array($p)) {
            return null;
        }

        $atlet = $this->db->table('atlet a')
            ->select('a.id, a.nama, a.nisn, a.tanggal_lahir, a.jenis_kelamin, a.kelompok_umur_id, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('a.pendaftaran_id', $id)
            ->orderBy('a.id')
            ->get()->getResultArray();

        $nomor = [];

        foreach ($this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.biaya, an.regu, an.peran, nl.nama, nl.kategori, nl.jenis')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('a.pendaftaran_id', $id)
            ->orderBy('an.atlet_id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray() as $n) {
            $nomor[(int) $n['atlet_id']][] = [
                'nama'  => NomorLombaModel::namaLengkap($n),
                'jenis' => (string) $n['jenis'],
                'biaya' => $n['biaya'] === null ? null : (int) $n['biaya'],
                'regu'  => $n['regu'] ?? null,
                'peran' => $n['peran'] ?? null,
            ];
        }

        $induk        = new DataIndukPendaftaran();
        $kelompokUmur = $induk->induk()['kelompok_umur'];
        // Kelompok umur yang sudah dimatikan tetap sah bagi atlet yang memakainya (K98).
        $nonaktif     = array_column($induk->nonaktif(array_map('intval', array_column($atlet, 'kelompok_umur_id')), [])['kelompok_umur'], null, 'id');
        $jenjang      = (string) $p['jenjang'];
        $hasil        = [];

        foreach ($atlet as $a) {
            $idAtlet = (int) $a['id'];
            $ikut    = $nomor[$idAtlet] ?? [];
            $lama    = $nonaktif[(int) $a['kelompok_umur_id']] ?? null;
            $tetap   = $lama === null ? null : self::kecocokan($jenjang, (string) $a['tanggal_lahir'], (int) $a['kelompok_umur_id'], [$lama]);
            $cocok   = $tetap !== null && $tetap['cocok']
                ? $tetap
                : self::kecocokan($jenjang, (string) $a['tanggal_lahir'], (int) $a['kelompok_umur_id'], $kelompokUmur);

            $hasil[] = [
                'id'               => $idAtlet,
                'nama'             => (string) $a['nama'],
                'nisn'             => (string) $a['nisn'],
                'tanggal_lahir'    => (string) $a['tanggal_lahir'],
                'jenis_kelamin'    => (string) $a['jenis_kelamin'],
                'kelompok_umur_id' => (int) $a['kelompok_umur_id'],
                'kelompok_umur'    => (string) $a['kelompok_umur'],
                'nomor_lomba'      => $ikut,
                'biaya_individu'   => array_sum(array_map(
                    static fn (array $n): int => $n['jenis'] === 'individu' ? (int) $n['biaya'] : 0,
                    $ikut,
                )),
            ] + $cocok;
        }

        return [
            'pendaftaran' => [
                'id'             => (int) $p['id'],
                'id_pendaftaran' => (string) $p['id_pendaftaran'],
                'nama_sekolah'   => (string) $p['nama_sekolah'],
                'npsn'           => $p['npsn'] === null || $p['npsn'] === '' ? null : (string) $p['npsn'],
                'jenjang'        => $jenjang,
                'jenjang_label'  => Jenjang::label($jenjang) ?? $jenjang,
                'status'         => (string) $p['status'],
                'total'          => (int) $p['total'],
                'diubah_pada'    => $p['diubah_pada'] === null ? null : (string) $p['diubah_pada'],
            ],
            'atlet' => $hasil,
        ];
    }

    /**
     * Apakah kelompok umur tersimpan masih cocok dengan pengaturan sekarang
     * (jenjang dan tahun lahir, K95), dan kelompok umur mana yang sesuai.
     * Aturan murni.
     *
     *     TolakAtlet::kecocokan('A', '2018-05-01', 1, $kelompokUmurAktif);
     *     // ['cocok' => false, 'sesuai_id' => 2, 'sesuai' => 'KU 10 (lahir 2016–2018)']
     *
     * @param list<array<string, mixed>> $kelompokUmur kelompok umur aktif (`DataIndukPendaftaran::induk()`)
     *
     * @return array{cocok: bool, sesuai_id: int|null, sesuai: string}
     */
    public static function kecocokan(string $jenjang, string $tanggalLahir, int $kelompokUmurId, array $kelompokUmur): array
    {
        $tentu = KelompokUmurAtlet::tentukan((int) substr($tanggalLahir, 0, 4), $jenjang, $kelompokUmur);
        $ku    = $tentu['kelompok_umur'];

        if ($ku === null) {
            return ['cocok' => false, 'sesuai_id' => null, 'sesuai' => (string) $tentu['galat']];
        }

        $rentang = KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'] ?? null, $ku['tahun_lahir_akhir'] ?? null);

        return [
            'cocok'     => (int) $ku['id'] === $kelompokUmurId,
            'sesuai_id' => (int) $ku['id'],
            'sesuai'    => $ku['nama'] . ($rentang === '' ? '' : ' (' . lcfirst($rentang) . ')'),
        ];
    }

    /**
     * Tolak atlet terpilih. `null` bila pendaftaran tidak ada.
     *
     * @param list<int|string> $atletId
     *
     * @return array{galat: list<string>, tersimpan: bool, jumlah: int, semua: bool, total_lama: int, total: int, tim_ditinggalkan: list<array<string, mixed>>}|null
     */
    public function tolak(int $id, array $atletId, string $alasan, string $admin, ?string $hariIni = null): ?array
    {
        $form = $this->formulir($id);

        if ($form === null) {
            return null;
        }

        $p     = $form['pendaftaran'];
        $hasil = [
            'galat'            => [],
            'tersimpan'        => false,
            'jumlah'           => 0,
            'semua'            => false,
            'total_lama'       => $p['total'],
            'total'            => $p['total'],
            'tim_ditinggalkan' => [],
        ];

        $alasan  = trim((string) preg_replace('/[ \t]+/u', ' ', str_replace("\r\n", "\n", $alasan)));
        $panjang = mb_strlen($alasan);
        $pilih   = array_values(array_unique(array_filter(array_map(
            static fn (mixed $v): int => is_int($v) || (is_string($v) && ctype_digit($v)) ? (int) $v : 0,
            $atletId,
        ))));
        $ada     = array_column($form['atlet'], null, 'id');

        if ($p['status'] !== 'menunggu') {
            $hasil['galat'][] = 'Pendaftaran ini sudah Lunas. Kembalikan ke Menunggu dulu bila atletnya memang perlu ditolak.';
        }

        if ($pilih === []) {
            $hasil['galat'][] = 'Belum ada atlet yang dipilih. Centang atlet yang ditolak.';
        } elseif (array_diff($pilih, array_keys($ada)) !== []) {
            $hasil['galat'][] = 'Ada atlet yang sudah tidak ada di pendaftaran ini. Muat ulang halaman ini, lalu pilih lagi.';
        }

        if ($panjang < self::PANJANG_ALASAN_MIN) {
            $hasil['galat'][] = 'Alasan penolakan belum diisi atau terlalu pendek. Tulis alasannya, paling sedikit ' . self::PANJANG_ALASAN_MIN . ' huruf; alasan ini dibaca sekolah di Cek Pendaftaran.';
        } elseif ($panjang > self::PANJANG_ALASAN_MAKS) {
            $hasil['galat'][] = 'Alasan penolakan terlalu panjang. Persingkat menjadi paling banyak ' . self::PANJANG_ALASAN_MAKS . ' huruf.';
        }

        if ($hasil['galat'] !== []) {
            return $hasil;
        }

        $ditolak = array_map(static fn (int $i): array => $ada[$i], $pilih);
        $sisa    = array_values(array_filter($form['atlet'], static fn (array $a): bool => ! in_array($a['id'], $pilih, true)));
        $admin   = mb_substr(trim($admin) === '' ? 'Admin' : trim($admin), 0, 100);

        $hasil['jumlah'] = count($ditolak);
        $hasil['semua']  = $sisa === [];

        return $sisa === []
            ? $this->tolakSemua($form, $ditolak, $alasan, $admin, $hasil)
            : $this->tolakSebagian($form, $pilih, $ditolak, $alasan, $admin, $hariIni, $hasil);
    }

    /**
     * Masih ada atlet tersisa: simpan lewat Ubah pendaftaran tanpa atlet yang ditolak.
     *
     * @param array<string, mixed>       $form
     * @param list<int>                  $pilih
     * @param list<array<string, mixed>> $ditolak
     * @param array<string, mixed>       $hasil
     *
     * @return array<string, mixed>
     */
    private function tolakSebagian(array $form, array $pilih, array $ditolak, string $alasan, string $admin, ?string $hariIni, array $hasil): array
    {
        $ubah  = $this->ubah ?? new UbahPendaftaran($this->db);
        $id    = (int) $form['pendaftaran']['id'];
        $isian = $ubah->isian($id);

        if ($isian === null) {
            $hasil['galat'][] = 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.';

            return $hasil;
        }

        $kiriman = [
            'sekolah'          => $isian['sekolah'],
            'penanggung_jawab' => array_map(static fn (array $o): array => [
                'id' => $o['id'], 'nama' => $o['nama'], 'nomor_wa' => $o['nomor_wa'], 'foto' => '',
            ], $isian['penanggung_jawab']),
            'atlet' => array_values(array_map(static fn (array $a): array => [
                'id'            => $a['id'],
                'nama'          => $a['nama'],
                'nisn'          => $a['nisn'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'jenis_kelamin' => $a['jenis_kelamin'],
                'nomor'         => $a['nomor'],
                'regu'          => $a['regu'],
            ], array_filter($isian['atlet'], static fn (array $a): bool => ! in_array((int) $a['id'], $pilih, true)))),
        ];

        $catat = function (BaseConnection $db, array $rencana) use ($form, $ditolak, $alasan, $admin): void {
            // Bisa saja baru ditandai Lunas admin lain setelah formulir dibaca.
            if (($rencana['status_lama'] ?? '') !== 'menunggu') {
                throw new DomainException('Pendaftaran ini baru saja ditandai Lunas. Kembalikan ke Menunggu dulu bila atletnya memang perlu ditolak.');
            }

            $this->catat($db, $form, $ditolak, $alasan, $admin, (int) $rencana['total']);
        };

        try {
            $simpan = $ubah->simpan($id, $kiriman, false, $hariIni, $catat);
        } catch (DomainException $e) {
            $hasil['galat'][] = $e->getMessage();

            return $hasil;
        }

        if ($simpan === null) {
            $hasil['galat'][] = 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.';

            return $hasil;
        }

        if (! ($simpan['tersimpan'] ?? false)) {
            $hasil['galat'][] = 'Atlet belum ditolak, karena data atlet lain di pendaftaran ini perlu dibetulkan lebih dulu:';

            foreach ($simpan['galat'] as $g) {
                $hasil['galat'][] = (string) $g['pesan'];
            }

            $hasil['galat'][] = 'Ikut centang atlet yang juga tidak sesuai, atau betulkan datanya lewat Ubah, lalu tolak lagi.';

            return $hasil;
        }

        $hasil['tersimpan']        = true;
        $hasil['total']            = (int) $simpan['total'];
        $hasil['tim_ditinggalkan'] = $simpan['tim_ditinggalkan'] ?? [];

        return $hasil;
    }

    /**
     * Semua atlet ditolak: pendaftaran tetap ada tanpa atlet dan regu, total Rp 0.
     *
     * @param array<string, mixed>       $form
     * @param list<array<string, mixed>> $ditolak
     * @param array<string, mixed>       $hasil
     *
     * @return array<string, mixed>
     */
    private function tolakSemua(array $form, array $ditolak, string $alasan, string $admin, array $hasil): array
    {
        $p     = $form['pendaftaran'];
        $id    = (int) $p['id'];
        $kunci = $p['npsn'] === null ? null : 'milklife-pendaftaran-' . $p['npsn'];

        if ($kunci !== null) {
            $dapat = $this->db->query('SELECT GET_LOCK(?, ?) AS dapat', [$kunci, self::TUNGGU_KUNCI])->getRowArray();

            if ((int) ($dapat['dapat'] ?? 0) !== 1) {
                $hasil['galat'][] = 'Pendaftaran lain dari sekolah ini sedang disimpan. Tunggu beberapa detik, lalu tolak lagi.';

                return $hasil;
            }
        }

        try {
            $tinggal = (new TimTanpaPembayar($this->db))->ditinggalkan($id);

            $this->db->resetTransStatus();
            $this->db->transBegin();

            $kini = $this->db->query('SELECT status, total, diubah_pada FROM pendaftaran WHERE id = ? FOR UPDATE', [$id]);
            $kini = is_object($kini) ? $kini->getRowArray() : null;

            if (! is_array($kini) || $kini['status'] !== 'menunggu' || (int) $kini['total'] !== $p['total']
                || (string) $kini['diubah_pada'] !== (string) $p['diubah_pada']) {
                $this->db->transRollback();
                $hasil['galat'][] = 'Pendaftaran ini baru saja diubah di tempat lain. Muat ulang halaman ini, lalu tolak lagi.';

                return $hasil;
            }

            $this->catat($this->db, $form, $ditolak, $alasan, $admin, 0);

            $this->pastikan($this->db->table('atlet')->where('pendaftaran_id', $id)->delete());
            $this->pastikan($this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $id)->delete());
            $this->pastikan($this->db->table('pendaftaran')->where('id', $id)->update([
                'subtotal_individu' => 0,
                'subtotal_beregu'   => 0,
                'total'             => 0,
                'diubah_pada'       => Time::now()->toDateTimeString(),
            ]));

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi tolak atlet gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            while ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            throw $e;
        } finally {
            if ($kunci !== null) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
            }
        }

        $hasil['tersimpan']        = true;
        $hasil['total']            = 0;
        $hasil['tim_ditinggalkan'] = $tinggal;

        return $hasil;
    }

    /**
     * Tulis salinan atlet yang ditolak. Dipanggil di dalam transaksi.
     *
     * @param array<string, mixed>       $form
     * @param list<array<string, mixed>> $ditolak
     */
    private function catat(BaseConnection $db, array $form, array $ditolak, string $alasan, string $admin, int $totalSesudah): void
    {
        $sekarang = Time::now()->toDateTimeString();

        foreach ($ditolak as $a) {
            $this->pastikan($db->table('atlet_ditolak')->insert([
                'pendaftaran_id'          => (int) $form['pendaftaran']['id'],
                'nama'                    => $a['nama'],
                'nisn'                    => $a['nisn'],
                'tanggal_lahir'           => $a['tanggal_lahir'],
                'jenis_kelamin'           => $a['jenis_kelamin'],
                'kelompok_umur'           => $a['kelompok_umur'],
                'nomor_lomba'             => json_encode($a['nomor_lomba'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'biaya_individu'          => (int) $a['biaya_individu'],
                'kelompok_umur_sesuai_id' => $a['sesuai_id'],
                'kelompok_umur_sesuai'    => mb_substr((string) $a['sesuai'], 0, 255),
                'alasan'                  => $alasan,
                'total_sebelum'           => (int) $form['pendaftaran']['total'],
                'total_sesudah'           => $totalSesudah,
                'ditolak_oleh'            => $admin,
                'dibuat_pada'             => $sekarang,
            ]));
        }

        // F8-01: riwayat tindakan admin, di transaksi yang sama.
        $totalLama = (int) $form['pendaftaran']['total'];
        (new RiwayatAdmin($db))->catat((int) $form['pendaftaran']['id'], (string) $form['pendaftaran']['id_pendaftaran'], RiwayatAdmin::TOLAK_ATLET,
            count($ditolak) . ' atlet ditolak: ' . implode(', ', array_map(RiwayatAdmin::namaAtlet(...), $ditolak)) . '. Alasan: ' . rtrim($alasan, '. ') . '. '
            . ($totalLama === $totalSesudah ? 'Total tetap ' . rupiah($totalSesudah) . '.' : 'Total ' . rupiah($totalLama) . ' → ' . rupiah($totalSesudah) . '.'));
    }

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Tolak atlet gagal: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }
    }
}
