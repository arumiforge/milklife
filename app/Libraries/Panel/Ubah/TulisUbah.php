<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\Panel\GabungPendaftaran;
use App\Libraries\Panel\RiwayatAdmin;
use App\Libraries\Panel\StatusPembayaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\Regu;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * Penulisan rencana Ubah pendaftaran ke basis data (bagian dari
 * `UbahPendaftaran`, F7-30), dalam satu transaksi:
 *
 * 1. foto baru ditulis sebelum transaksi dan dihapus bila gagal;
 * 2. baris `pendaftaran` dikunci (`FOR UPDATE`) dan dibandingkan dengan yang
 *    dibaca saat pemeriksaan; bila sudah berubah, perubahan dibatalkan;
 * 3. pendaftaran, penanggung jawab, atlet dan nomornya, serta regu beregu;
 * 4. K59: pendaftaran Lunas langsung memberi nomor atlet (BIB) kepada atlet
 *    yang belum punya; awalan penuh membatalkan seluruh perubahan;
 * 5. F8-01: riwayat tindakan admin "Ubah", kecuali pemanggil dengan
 *    `$dalamTransaksi` (Tolak atlet) yang mencatat riwayatnya sendiri;
 * 6. K109: pendaftaran Lunas digabung dengan Lunas lain ber-NPSN sama;
 * 7. foto lama (diganti, dihapus, atau dari pendaftaran yang digabung)
 *    dihapus setelah transaksi berhasil.
 */
final class TulisUbah
{
    public function __construct(
        private readonly BaseConnection $db,
        private readonly FotoPenanggungJawab $foto,
    ) {
    }

    /**
     * Tulis rencana. Kegagalan basis data dilempar setelah transaksi
     * dibatalkan dan foto baru dihapus.
     *
     * @param array<string, mixed> $lama    Data tersimpan (`DataTersimpan::baca()`), dibaca setelah kunci NPSN didapat.
     * @param array<string, mixed> $rencana Rencana tanpa galat (`RencanaUbah::hitung()`).
     *
     * @return array{pesan: string|null, nomor_dada: array<int, string>, gabung: array<string, mixed>|null}
     *         `pesan` terisi bila perubahan dibatalkan tanpa galat basis data (tidak ada yang ditulis).
     */
    public function tulis(array $lama, array $rencana, ?callable $dalamTransaksi = null): array
    {
        $id        = (int) $lama['pendaftaran']['id'];
        $fotoBaru  = [];
        $fotoLama  = [];
        $nomorDada = [];
        $gabung    = null;

        try {
            foreach ($rencana['penanggung_jawab'] as $i => $orang) {
                if ($orang['foto_baru'] !== null) {
                    $fotoBaru[$i] = $this->foto->simpan($orang['foto_baru']);
                }
            }

            // Penanda gagal transaksi CodeIgniter melekat dari kegagalan sebelumnya.
            $this->db->resetTransStatus();
            $this->db->transBegin();

            $kini = $this->baca('SELECT status, total, diubah_pada FROM pendaftaran WHERE id = ? FOR UPDATE', [$id])[0] ?? null;

            if ($kini === null || $kini['status'] !== $lama['pendaftaran']['status'] || (int) $kini['total'] !== (int) $lama['pendaftaran']['total']
                || (string) $kini['diubah_pada'] !== (string) $lama['pendaftaran']['diubah_pada']) {
                $this->db->transRollback();
                $this->hapusFoto($fotoBaru);

                return self::batal('Pendaftaran ini baru saja diubah di tempat lain. Muat ulang halaman ini, lalu ubah lagi.');
            }

            $sekarang = Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');

            $this->tulisPendaftaran($lama, $rencana, $sekarang);
            $fotoLama = $this->tulisPenanggungJawab($lama, $rencana, $fotoBaru, $sekarang);
            $this->tulisAtlet($lama, $rencana, $sekarang);
            $this->tulisRegu($lama, $rencana, $sekarang);

            // K59: Lunas dan total tetap → atlet tanpa nomor langsung mendapat nomor.
            if ($rencana['status'] === 'lunas') {
                $beri = (new StatusPembayaran($this->db))->beriNomorDada($id);

                if ($beri['penuh'] !== []) {
                    $this->db->transRollback();
                    $this->hapusFoto($fotoBaru);

                    return self::batal('Perubahan belum disimpan. Pendaftaran ini sudah Lunas, jadi atlet baru langsung mendapat nomor atlet (BIB), '
                        . 'tetapi awalan nomor atlet (BIB) ' . implode(', ', $beri['penuh']) . ' sudah penuh atau belum ada. '
                        . 'Tambahkan awalan baru di Pengaturan, Kelompok Umur, lalu simpan lagi.');
                }

                $nomorDada = $beri['nomor'];
            }

            // F8-01: riwayat tindakan admin, sebelum digabung supaya ikut pindah ke tujuan.
            // Pemanggil dengan `$dalamTransaksi` (Tolak atlet) mencatat tindakannya sendiri.
            if ($dalamTransaksi === null) {
                $nomorBaru = array_values($nomorDada);
                sort($nomorBaru);
                (new RiwayatAdmin($this->db))->catat($id, (string) $lama['pendaftaran']['id_pendaftaran'], RiwayatAdmin::UBAH, RiwayatAdmin::ringkasanUbah($lama, $rencana)
                    . ($nomorBaru === [] ? '' : ' Nomor atlet (BIB) baru: ' . implode(', ', $nomorBaru) . '.'));
            }

            if ($rencana['status'] === 'lunas') {
                // K109: NPSN baru mungkin sudah punya pendaftaran Lunas lain; gabungkan.
                $gabung = (new GabungPendaftaran($this->db))->gabungNpsn($rencana['sekolah']['npsn']);
            }

            if ($dalamTransaksi !== null) {
                $dalamTransaksi($this->db, $rencana);
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi ubah pendaftaran gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            while ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            $this->hapusFoto($fotoBaru);

            throw $e;
        }

        $this->hapusFoto(array_merge($fotoLama, $gabung['foto'] ?? []));

        return ['pesan' => null, 'nomor_dada' => $nomorDada, 'gabung' => $gabung];
    }

    /**
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $rencana
     */
    private function tulisPendaftaran(array $lama, array $rencana, string $sekarang): void
    {
        $menunggu = $rencana['status'] === 'menunggu';

        $this->pastikan($this->db->table('pendaftaran')->where('id', (int) $lama['pendaftaran']['id'])->update([
            'jenjang'           => $rencana['sekolah']['jenjang'],
            'npsn'              => $rencana['sekolah']['npsn'],
            'nama_sekolah'      => $rencana['sekolah']['nama_sekolah'],
            'kabupaten'         => $rencana['sekolah']['kabupaten'],
            'subtotal_individu' => $rencana['subtotal_individu'],
            'subtotal_beregu'   => $rencana['subtotal_beregu'],
            'total'             => $rencana['total'],
            'status'            => $rencana['status'],
            'waktu_lunas'       => $menunggu ? null : $lama['pendaftaran']['waktu_lunas'],
            'diubah_pada'       => $sekarang,
        ]));
    }

    /**
     * @param array<string, mixed>      $lama
     * @param array<string, mixed>      $rencana
     * @param array<int|string, string> $fotoBaru Jalur foto baru per urutan penanggung jawab.
     *
     * @return list<string|null> Jalur foto lama yang dihapus setelah transaksi berhasil.
     */
    private function tulisPenanggungJawab(array $lama, array $rencana, array $fotoBaru, string $sekarang): array
    {
        $id       = (int) $lama['pendaftaran']['id'];
        $fotoLama = [];

        $dipakaiPj = array_filter(array_column($rencana['penanggung_jawab'], 'id'));

        foreach ($lama['penanggung_jawab'] as $idPj => $orang) {
            if (! in_array($idPj, $dipakaiPj, true)) {
                $this->pastikan($this->db->table('penanggung_jawab')->where('id', $idPj)->delete());
                $fotoLama[] = $orang['foto'];
            }
        }

        foreach ($rencana['penanggung_jawab'] as $i => $orang) {
            $baris = ['nama' => $orang['nama'], 'nomor_wa' => $orang['nomor_wa'], 'diubah_pada' => $sekarang];

            if (isset($fotoBaru[$i])) {
                $baris['foto'] = $fotoBaru[$i];
            }

            if ($orang['id'] !== null) {
                if (isset($fotoBaru[$i])) {
                    $fotoLama[] = $lama['penanggung_jawab'][$orang['id']]['foto'];
                }

                $this->pastikan($this->db->table('penanggung_jawab')->where('id', $orang['id'])->update($baris));
            } else {
                $this->pastikan($this->db->table('penanggung_jawab')->insert($baris + ['pendaftaran_id' => $id, 'dibuat_pada' => $sekarang]));
            }
        }

        return $fotoLama;
    }

    /**
     * Atlet dan nomornya. Atlet tersimpan mempertahankan nomor dadanya.
     *
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $rencana
     */
    private function tulisAtlet(array $lama, array $rencana, string $sekarang): void
    {
        $id = (int) $lama['pendaftaran']['id'];

        // Atlet yang dihapus (nomor atletnya ikut terhapus lewat kunci asing).
        $dipakaiAtlet = array_filter(array_column($rencana['atlet'], 'id'));

        foreach (array_keys($lama['atlet']) as $idAtlet) {
            if (! in_array($idAtlet, $dipakaiAtlet, true)) {
                $this->pastikan($this->db->table('atlet')->where('id', $idAtlet)->delete());
            }
        }

        // NISN yang bertukar antar-atlet pendaftaran ini: kosongkan dulu
        // dengan nilai sementara supaya kunci unik tidak menolak.
        foreach ($rencana['atlet'] as $atlet) {
            if ($atlet['id'] !== null && $lama['atlet'][$atlet['id']]['nisn'] !== $atlet['nisn']) {
                $this->pastikan($this->db->table('atlet')->where('id', $atlet['id'])->update(['nisn' => sprintf('X%09d', $atlet['id'])]));
            }
        }

        foreach ($rencana['atlet'] as $atlet) {
            $baris = [
                'nama'             => $atlet['nama'],
                'nisn'             => $atlet['nisn'],
                'tanggal_lahir'    => $atlet['tanggal_lahir'],
                'jenis_kelamin'    => $atlet['jenis_kelamin'],
                'kelompok_umur_id' => $atlet['kelompok_umur_id'],
                'diubah_pada'      => $sekarang,
            ];

            if ($atlet['id'] !== null) {
                // Nomor dada tidak disentuh.
                $idAtlet = $atlet['id'];
                $this->pastikan($this->db->table('atlet')->where('id', $idAtlet)->update($baris));

                $simpan   = $lama['nomor'][$idAtlet] ?? [];
                $hapus    = array_diff(array_keys($simpan), array_column($atlet['nomor'], 'nomor_lomba_id'));
                $reguLama = $lama['regu'][$idAtlet] ?? [];

                if ($hapus !== []) {
                    $this->pastikan($this->db->table('atlet_nomor')->where('atlet_id', $idAtlet)->whereIn('nomor_lomba_id', array_values($hapus))->delete());
                }
            } else {
                $this->pastikan($this->db->table('atlet')->insert($baris + ['pendaftaran_id' => $id, 'dibuat_pada' => $sekarang]));
                $idAtlet  = (int) $this->db->insertID();
                $simpan   = [];
                $reguLama = [];

                if ($idAtlet < 1) {
                    throw new RuntimeException('Atlet baru gagal ditulis.');
                }
            }

            foreach ($atlet['nomor'] as $nomor) {
                $regu = ['regu' => $nomor['regu'] ?? null, 'peran' => $nomor['peran'] ?? null];

                if (array_key_exists($nomor['nomor_lomba_id'], $simpan)) {
                    // Regu atau peran (inti/cadangan) yang berubah (K105).
                    $sebelum = $reguLama[$nomor['nomor_lomba_id']] ?? ['regu' => null, 'peran' => null];

                    if ($regu['regu'] !== null && $regu !== $sebelum) {
                        $this->pastikan($this->db->table('atlet_nomor')->where('atlet_id', $idAtlet)->where('nomor_lomba_id', $nomor['nomor_lomba_id'])
                            ->update($regu + ['diubah_pada' => $sekarang]));
                    }

                    continue;
                }

                $this->pastikan($this->db->table('atlet_nomor')->insert([
                    'atlet_id'       => $idAtlet,
                    'nomor_lomba_id' => $nomor['nomor_lomba_id'],
                    'biaya'          => $nomor['biaya'],
                    'regu'           => $regu['regu'],
                    'peran'          => $regu['peran'],
                    'dibuat_pada'    => $sekarang,
                    'diubah_pada'    => $sekarang,
                ]));
            }
        }
    }

    /**
     * Regu beregu (K105), satu baris per nomor per regu; biaya regu yang baru
     * dibebankan (K68) ditulis ulang.
     *
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $rencana
     */
    private function tulisRegu(array $lama, array $rencana, string $sekarang): void
    {
        $id      = (int) $lama['pendaftaran']['id'];
        $timBaru = [];

        foreach ($rencana['beregu'] as $tim) {
            $timBaru[Regu::kunci((int) $tim['nomor_lomba_id'], (string) $tim['regu'])] = (int) $tim['biaya_tim'];
        }

        foreach (array_diff(array_keys($lama['beregu']), array_keys($timBaru)) as $kunci) {
            [$nomorId, $regu] = Regu::pecah($kunci) ?? [0, ''];
            $this->pastikan($this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $id)->where('nomor_lomba_id', $nomorId)->where('regu', $regu)->delete());
        }

        foreach ($timBaru as $kunci => $biaya) {
            [$nomorId, $regu] = Regu::pecah($kunci) ?? [0, ''];

            if (! array_key_exists($kunci, $lama['beregu'])) {
                $this->pastikan($this->db->table('pendaftaran_beregu')->insert([
                    'pendaftaran_id' => $id,
                    'nomor_lomba_id' => $nomorId,
                    'regu'           => $regu,
                    'biaya_tim'      => $biaya,
                    'dibuat_pada'    => $sekarang,
                    'diubah_pada'    => $sekarang,
                ]));
            } elseif (in_array($kunci, $rencana['tim_dibebankan'], true)) {
                $this->pastikan($this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $id)->where('nomor_lomba_id', $nomorId)->where('regu', $regu)
                    ->update(['biaya_tim' => $biaya, 'diubah_pada' => $sekarang]));
            }
        }
    }

    /**
     * @return array{pesan: string, nomor_dada: array<int, string>, gabung: null}
     */
    private static function batal(string $pesan): array
    {
        return ['pesan' => $pesan, 'nomor_dada' => [], 'gabung' => null];
    }

    /**
     * @param array<int|string, string|null> $jalur
     */
    private function hapusFoto(array $jalur): void
    {
        foreach ($jalur as $satu) {
            $this->foto->hapus($satu);
        }
    }

    /**
     * @param list<int|string> $ikat
     *
     * @return list<array<string, mixed>>
     */
    private function baca(string $sql, array $ikat): array
    {
        $hasil = $this->db->query($sql, $ikat);

        if (! is_object($hasil)) {
            $galat = $this->db->error();

            throw new DatabaseException('Data pendaftaran gagal dibaca: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.');
        }

        return $hasil->getResultArray();
    }

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Data pendaftaran gagal ditulis: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }
    }
}
