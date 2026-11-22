<?php

namespace App\Libraries\Bantuan;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Tanya-jawab bawaan halaman Bantuan (F9-04), dipasang `php spark data:awal`
 * (pemasangan baru) dan `php spark faq:awal` (pemasangan lama).
 *
 * Disusun dari rancangan final, `agent/ATURAN.md`, dan petunjuk teknis Seri 2
 * – 2026. Angka dan tanggal yang bisa berubah ditulis sebagai isian otomatis
 * (`{tutup_pendaftaran}`, `{daftar_kelompok_umur}`, …, lihat `JawabanFaq::ISIAN`)
 * supaya mengikuti Pengaturan. Hal yang memang tertulis di juknis (aturan
 * lapangan, uang protes) ditulis langsung.
 *
 * Bawaan hanya dipasang bila tabel `faq` masih kosong, jadi isian admin tidak
 * pernah tertimpa. Setelah terpasang, semuanya bisa diubah, diurutkan,
 * disembunyikan, atau dihapus di Pengaturan > Bantuan.
 */
final class FaqBawaan
{
    /**
     * [kategori, pertanyaan, jawaban Markdown sederhana], berurutan per kategori.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const DAFTAR = [
        // ---------- Sebelum mendaftar ----------
        ['sebelum', 'Siapa yang boleh ikut lomba ini?', <<<'MD'
            Siswa SD/MI, SMP/MTs, dan SMA/MA/SMK negeri maupun swasta dari Kabupaten Blora, Rembang, Pati, Kudus, dan Jepara.

            Atlet didaftarkan oleh sekolahnya, biasanya oleh guru olahraga. Sekolah Luar Biasa (SLB) belum termasuk peserta lomba ini.
            MD],
        ['sebelum', 'Apakah harus punya akun atau kata sandi untuk mendaftar?', <<<'MD'
            Tidak perlu. Tidak ada akun, kata sandi, atau kode OTP.

            Cukup buka halaman depan, tekan tombol **Daftar**, lalu isi formulirnya sampai selesai. Untuk melihat pendaftaran yang sudah dikirim, pakai menu **Cek Pendaftaran** dengan NPSN sekolah atau ID pendaftaran.
            MD],
        ['sebelum', 'Kapan pendaftaran dibuka dan ditutup?', <<<'MD'
            Pendaftaran dibuka **{buka_pendaftaran}** dan ditutup otomatis **{tutup_pendaftaran}** WIB.

            Setelah ditutup, formulir pendaftaran tidak bisa dipakai lagi. Kirim pendaftaran beserta bukti transfer sebelum batas itu.
            MD],
        ['sebelum', 'Kapan dan di mana lombanya?', <<<'MD'
            {nama_acara} berlangsung **{tanggal_lomba}** di **{tempat}**.

            Jadwal per nomor lomba diumumkan saat pertemuan teknik (technical meeting). Lihat juga bagian **Agenda** di halaman depan.
            MD],
        ['sebelum', 'Kelompok umur apa saja yang dilombakan?', <<<'MD'
            Kelompok umur ditentukan dari **jenjang sekolah** lalu **tahun lahir** atlet:

            {daftar_kelompok_umur}

            Yang dihitung hanya tahun lahir, bukan tanggal atau bulan lahir.
            MD],
        ['sebelum', 'Atlet saya lahir 2016, ikut KU 10 atau KU 12?', <<<'MD'
            Lihat tahun lahir di daftar ini:

            {daftar_kelompok_umur}

            Anda tidak perlu menghitung sendiri. Setelah tanggal lahir diisi di formulir atlet, kelompok umurnya dipilih otomatis. Bila tahun lahirnya masuk dua kelompok umur, muncul kotak **Pilih kelompok umur**. Tekan salah satu. Satu atlet hanya ikut satu kelompok umur.
            MD],
        ['sebelum', 'Siswa SMP yang lahirnya masuk rentang KU 12, boleh ikut KU 12?', <<<'MD'
            Tidak. Kelompok umur dibatasi jenjang sekolah. Siswa SMP/MTs hanya bisa ikut kelompok umur untuk SMP/MTs. Aturan yang sama berlaku untuk SD/MI dan SMA/MA/SMK.

            Bila tahun lahir atlet tidak cocok dengan kelompok umur jenjangnya, formulir menampilkan pesan yang menyebut kelompok umur yang bisa diikuti.
            MD],
        ['sebelum', 'Berapa nomor lomba yang boleh diikuti satu atlet?', <<<'MD'
            Paling banyak **{nomor_individu_maksimal} nomor individu** per atlet. Nomor beregu di kelompok umurnya boleh diikuti tanpa batas. Setiap atlet wajib ikut paling sedikit satu nomor, individu atau beregu.
            MD],
        ['sebelum', 'Apa yang perlu disiapkan sebelum mengisi formulir?', <<<'MD'
            Siapkan dulu supaya pengisian cepat:

            1. **NPSN** sekolah (8 angka).
            2. Nama, nomor WA, dan **foto** setiap ofisial (pendamping), {ofisial_minimal} sampai {ofisial_maksimal} orang.
            3. Data setiap atlet: nama lengkap, **NISN** (10 angka), tanggal lahir, jenis kelamin, dan nomor lomba yang dipilih.
            4. Uang untuk transfer dan ponsel untuk memotret bukti transfer.

            Templat daftar atlet dan surat keterangan sekolah ada di halaman **Unduhan**.
            MD],
        ['sebelum', 'Di mana saya bisa membaca petunjuk teknis (juknis) lomba?', <<<'MD'
            Buka halaman **Unduhan** dari menu halaman depan, lalu unduh **Petunjuk teknis lomba**. Di sana ada aturan setiap nomor lomba, berat alat, dan tautan grup WA peserta.
            MD],

        // ---------- Mengisi pendaftaran ----------
        ['mengisi', 'Bagaimana langkah mendaftar?', <<<'MD'
            Tekan tombol **Daftar** di halaman depan, lalu ikuti lima langkah:

            1. **Data sekolah**: pilih jenjang dan kabupaten, ketik NPSN, tekan **Cari**.
            2. **Ofisial**: isi nama, nomor WA, dan foto pendamping.
            3. **Atlet**: isi data atlet satu per satu, pilih nomor lombanya, tekan **Tambahkan ke daftar**.
            4. **Biaya**: periksa rincian biaya.
            5. **Pembayaran**: transfer, pilih foto bukti transfer, lalu tekan **Kirim pendaftaran**.

            Setelah terkirim, Anda mendapat ID pendaftaran dan bukti pendaftaran yang bisa dicetak.
            MD],
        ['mengisi', 'Bisakah mendaftar lewat HP?', <<<'MD'
            Bisa. Formulir dibuat untuk ponsel. Pakai peramban seperti Chrome dan pastikan internet stabil. Foto ofisial dan bukti transfer bisa langsung diambil dari galeri atau kamera HP.
            MD],
        ['mengisi', 'NPSN sekolah saya tidak ditemukan, bagaimana?', <<<'MD'
            Periksa lagi 8 angka NPSN, jenjang, dan kabupaten yang dipilih, lalu tekan **Cari** lagi.

            Bila tetap tidak ditemukan, ketik nama sekolah di kotak yang muncul. Sekolah tanpa NPSN tetap bisa mendaftar. Pendaftarannya nanti dicek memakai ID pendaftaran.
            MD],
        ['mengisi', 'Nama sekolah yang muncul kurang lengkap atau sama dengan sekolah lain', <<<'MD'
            Tekan tautan **Nama sekolah kurang lengkap?** di langkah Data sekolah, lalu tulis nama lengkapnya, misalnya dengan nama desa. Pendaftaran Anda langsung memakai nama itu sampai panitia menyetujuinya.
            MD],
        ['mengisi', 'Foto ofisial dari HP boleh?', <<<'MD'
            Boleh. Tekan kotak foto di kartu ofisial, lalu pilih foto dari galeri atau ambil langsung dengan kamera. Swafoto dengan wajah yang jelas sudah cukup.

            Foto diperkecil otomatis, jadi tidak perlu diatur ukurannya. Foto **wajib** diisi dan hanya dilihat panitia.
            MD],
        ['mengisi', 'Berapa ofisial yang boleh didaftarkan?', <<<'MD'
            Paling sedikit {ofisial_minimal} dan paling banyak {ofisial_maksimal} ofisial. Tekan **Tambah ofisial** untuk menambah kartu ofisial berikutnya.
            MD],
        ['mengisi', 'Isian saya hilang kalau HP terkunci atau halaman tertutup?', <<<'MD'
            Tidak. Isian tersimpan otomatis di HP atau komputer yang Anda pakai. Buka lagi halaman pendaftaran di **perangkat dan peramban yang sama**. Isian Anda akan muncul kembali.

            Isian tidak pindah ke perangkat lain. Bila ingin mulai dari awal, tekan **Kosongkan** di sebelah kanan tombol **Bantuan**.
            MD],
        ['mengisi', 'Salah ketik nama atlet sebelum mengirim, bagaimana?', <<<'MD'
            Selama belum menekan **Kirim pendaftaran**, Anda masih bisa memperbaikinya. Di langkah **Atlet**, tekan **Ubah** di baris atlet itu pada **Daftar sementara**, perbaiki, lalu tekan **Simpan perubahan**. Untuk membuang atlet dari daftar, tekan **Hapus**.
            MD],
        ['mengisi', 'Muncul pesan "NISN sudah terdaftar", artinya apa?', <<<'MD'
            Atlet dengan NISN itu sudah didaftarkan sebelumnya, oleh sekolah Anda atau sekolah lain. Satu NISN hanya boleh terdaftar sekali.

            Periksa dulu lewat **Cek Pendaftaran**. Bila NISN memang salah ketik, perbaiki angkanya. Bila Anda yakin ada kekeliruan, hubungi panitia lewat WA.
            MD],
        ['mengisi', 'Apa itu Inti dan Cadangan di nomor beregu?', <<<'MD'
            Setiap regu harus punya atlet **Inti** tepat sejumlah yang diminta nomor itu (misalnya 4 orang untuk estafet 4 × 100 m). Atlet **Cadangan** boleh ditambahkan sampai batas yang tertulis di kartu nomor lomba.

            Saat memilih nomor beregu untuk seorang atlet, tekan **Inti** atau **Cadangan**. Sebelum lanjut, formulir memeriksa apakah setiap regu sudah lengkap.
            MD],
        ['mengisi', 'Bolehkah satu sekolah mendaftarkan lebih dari satu regu di nomor yang sama?', <<<'MD'
            Boleh. Setelah regu pertama terisi, tekan tautan **Daftarkan regu lain** di kartu nomor beregu itu. Biaya beregu dihitung per regu, jadi dua regu berarti dua kali biaya.

            Untuk nomor mixed (campuran), jumlah putra dan putri di atlet inti harus sesuai syarat yang tertulis di kartu nomornya.
            MD],
        ['mengisi', 'Kotak "Pemeriksaan keamanan" tidak mau centang', <<<'MD'
            Tunggu beberapa detik sampai kotak itu bertanda centang hijau, baru tekan **Kirim pendaftaran**. Bila lama tidak berubah, periksa internet, muat ulang halaman (isian tidak hilang), lalu coba lagi.
            MD],
        ['mengisi', 'Sudah mendaftar, ternyata ada atlet yang belum ikut. Bagaimana menambahnya?', <<<'MD'
            Daftar lagi lewat tombol **Daftar** dengan NPSN sekolah yang sama, lalu isi hanya atlet tambahan itu. Atlet yang sudah terdaftar tidak perlu diisi lagi (NISN-nya akan ditolak).

            Bila atlet tambahan masuk ke regu yang sudah terdaftar, formulir meminta Anda mencentang konfirmasi, dan tidak ada biaya regu lagi. Pendaftaran tambahan dibayar terpisah sesuai rinciannya.
            MD],

        // ---------- Pembayaran dan bukti ----------
        ['pembayaran', 'Berapa biaya pendaftarannya?', <<<'MD'
            {daftar_biaya}

            Biaya individu dihitung per atlet per nomor: atlet yang ikut dua nomor individu dibayar dua kali. Total yang harus dibayar dihitung otomatis di langkah **Biaya**.
            MD],
        ['pembayaran', 'Ke rekening mana saya transfer?', <<<'MD'
            Transfer ke rekening {rekening}.

            Nomor rekening dan total yang harus dibayar juga tampil di langkah **Pembayaran**. Tekan **Salin** untuk menyalin nomor rekening.
            MD],
        ['pembayaran', 'Bagaimana cara mengirim bukti transfer?', <<<'MD'
            1. Transfer sebesar total yang tertera di langkah **Pembayaran**.
            2. Potret struk atau ambil tangkapan layar bukti transfer dari aplikasi bank.
            3. Tekan kotak **Bukti transfer**, pilih gambar itu.
            4. Tekan **Kirim pendaftaran**, lalu setujui jendela konfirmasi.

            Gambar boleh JPG, PNG, atau WEBP, paling besar 10 MB.
            MD],
        ['pembayaran', 'Apakah pendaftaran sudah tersimpan sebelum saya transfer?', <<<'MD'
            Belum. Pendaftaran baru masuk ke panitia saat Anda menekan **Kirim pendaftaran** bersama bukti transfernya.

            Bila belum sempat transfer, tidak apa-apa: isian tetap tersimpan di perangkat Anda. Buka lagi halaman pendaftaran nanti di perangkat yang sama, lalu tinggal unggah bukti transfernya.
            MD],
        ['pembayaran', 'Bukti transfer hilang, bagaimana?', <<<'MD'
            Bila **belum** dikirim: buka aplikasi bank atau m-banking, cari transfer itu di riwayat transaksi, lalu ambil tangkapan layarnya. Bisa juga meminta bukti transfer ke bank.

            Bila **sudah** dikirim bersama pendaftaran: tidak perlu dikirim lagi, bukti itu sudah tersimpan di panitia.
            MD],
        ['pembayaran', 'Saya salah memilih gambar bukti transfer atau ingin menambah bukti', <<<'MD'
            Buka **Cek Pendaftaran**, ketik NPSN atau ID pendaftaran, tekan **Cari**. Di kartu pendaftaran yang belum Lunas ada kotak unggah bukti transfer: pilih gambar yang benar, lalu tekan **Unggah bukti bayar**.

            Bukti cukup diunggah di web, tidak perlu dikirim lewat WA.
            MD],
        ['pembayaran', 'Apakah perlu konfirmasi pembayaran lewat WA?', <<<'MD'
            Tidak wajib. Panitia memeriksa bukti transfer yang Anda unggah di web.

            Setelah pendaftaran terkirim, ada tombol konfirmasi WA bila Anda ingin mengabari panitia. Pesannya sudah terisi nama sekolah, ID pendaftaran, dan total.
            MD],
        ['pembayaran', 'Kapan status pendaftaran berubah menjadi Lunas?', <<<'MD'
            Status awal **Menunggu Konfirmasi Pembayaran**. Panitia mengubahnya menjadi **Lunas** setelah memeriksa transfer Anda. Pantau statusnya lewat **Cek Pendaftaran**.

            Bila sudah lama belum berubah, hubungi panitia lewat WA dengan menyebut ID pendaftaran.
            MD],
        ['pembayaran', 'Gambar bukti transfer tidak bisa diunggah', <<<'MD'
            Pastikan gambarnya JPG, PNG, atau WEBP dan tidak lebih dari 10 MB. Bila gambar dari kamera terlalu besar, ambil tangkapan layar dari gambar itu lalu unggah tangkapan layarnya. Berkas PDF belum bisa diunggah.
            MD],

        // ---------- Cek pendaftaran dan bukti PDF ----------
        ['cek', 'Bagaimana cara melihat pendaftaran yang sudah dikirim?', <<<'MD'
            Buka menu **Cek Pendaftaran**, ketik NPSN sekolah atau ID pendaftaran, lalu tekan **Cari**.

            Dengan NPSN, semua pendaftaran sekolah itu tampil sekaligus. Dengan ID, hanya pendaftaran itu yang tampil.
            MD],
        ['cek', 'Bukti pendaftaran (PDF) hilang atau belum dicetak', <<<'MD'
            Buka **Cek Pendaftaran**, cari dengan NPSN atau ID pendaftaran, lalu tekan **Cetak bukti** di kartu pendaftaran. Bukti bisa diunduh lagi kapan saja, tidak terbatas.
            MD],
        ['cek', 'Saya lupa ID pendaftaran', <<<'MD'
            Pakai NPSN sekolah di **Cek Pendaftaran**. ID pendaftaran tertulis di setiap kartu pendaftaran dan di bukti PDF. Sekolah tanpa NPSN perlu menghubungi panitia lewat WA.
            MD],
        ['cek', 'Salah ketik nama atlet setelah pendaftaran terkirim, bagaimana?', <<<'MD'
            Pendaftaran yang sudah terkirim tidak bisa Anda ubah sendiri. Jangan mendaftarkan ulang atlet yang sama, karena NISN-nya akan ditolak.

            Hubungi panitia lewat WA. Sebutkan **ID pendaftaran**, nama atlet, dan perbaikannya. Panitia akan memperbaiki datanya, dan ID pendaftaran tetap sama.
            MD],
        ['cek', 'Mengapa nomor atlet (BIB) belum muncul?', <<<'MD'
            Nomor atlet baru dibuat setelah pendaftaran ditandai **Lunas** oleh panitia. Setelah Lunas, nomor atlet tampil di **Cek Pendaftaran** dan di bukti PDF.
            MD],
        ['cek', 'Ada tulisan "Perlu didaftarkan ulang" atau "atlet ditolak panitia"', <<<'MD'
            Artinya panitia mengeluarkan atlet itu dari pendaftaran, biasanya karena kelompok umur atau nomor lombanya tidak sesuai. Alasan dan kelompok umur yang disarankan tertulis di kartunya.

            Tekan **Daftarkan ulang** untuk mendaftarkan atlet itu lagi dengan data yang benar. Bila uang sudah terlanjur ditransfer, hubungi panitia lewat WA.
            MD],
        ['cek', 'Pendaftaran saya yang Lunas berubah menjadi Menunggu lagi', <<<'MD'
            Ini terjadi bila panitia mengubah pendaftaran Anda (misalnya menambah atau mengurangi nomor) dan totalnya berubah. Panitia akan menghubungi Anda soal selisihnya, lalu menandai Lunas lagi. Nomor atlet yang sudah ada tetap berlaku.
            MD],
        ['cek', 'Sekolah saya mendaftar lebih dari sekali, apakah tidak apa-apa?', <<<'MD'
            Tidak apa-apa. Semua pendaftaran dengan NPSN yang sama dianggap satu sekolah. Setelah Lunas, panitia menggabungkannya menjadi satu pendaftaran. ID lama tetap bisa dicari di **Cek Pendaftaran**, dan kode QR di bukti lama tetap berlaku.
            MD],

        // ---------- Hari lomba ----------
        ['hari_lomba', 'Apa itu pertemuan teknik (technical meeting), wajib hadir?', <<<'MD'
            Pertemuan teknik membahas pelaksanaan lomba bersama ofisial sekolah, sekaligus pembagian nomor atlet (BIB). **Setiap sekolah yang mendaftarkan siswanya wajib hadir.** Sekolah yang tidak hadir wajib menerima keputusan pertemuan itu.

            Waktu dan tempatnya ada di bagian **Agenda** halaman depan.
            MD],
        ['hari_lomba', 'Apa yang dibawa saat pertemuan teknik?', <<<'MD'
            1. **Bukti pendaftaran** berstatus Lunas, dicetak atau di HP.
            2. **Surat keterangan dari sekolah** berpas foto dan berstempel untuk setiap atlet.

            Panitia memeriksa keabsahan peserta saat pertemuan teknik. Templat surat keterangan ada di halaman **Unduhan**.
            MD],
        ['hari_lomba', 'Kapan nomor atlet (BIB) dibagikan dan bagaimana memakainya?', <<<'MD'
            Nomor atlet dibagikan saat pertemuan teknik. Setiap atlet wajib memasang nomor atletnya di **dada** saat berlomba.
            MD],
        ['hari_lomba', 'Jam berapa lomba dimulai?', <<<'MD'
            Lomba berlangsung pagi pukul 06.30–10.00 dan sore pukul 14.30–17.00 WIB, **{tanggal_lomba}**. Jadwal per nomor lomba diumumkan saat pertemuan teknik.
            MD],
        ['hari_lomba', 'Bagaimana pemanggilan atlet sebelum lomba?', <<<'MD'
            Atlet dipanggil dua kali di tempat **Roll Call** (dekat tempat pemanasan): panggilan pertama 30 menit dan panggilan terakhir 20 menit sebelum lomba dimulai. Pemanasan dilakukan di tempat Roll Call, tidak di tempat lomba.
            MD],
        ['hari_lomba', 'Pakaian dan sepatu apa yang dipakai?', <<<'MD'
            Pakai pakaian olahraga, sebaiknya bertuliskan nama sekolah. Saat berlomba, atlet boleh tidak bersepatu asal nyaman.

            Saat upacara penghormatan pemenang, atlet memakai seragam olahraga sekolah lengkap, bersepatu, dan sopan.
            MD],
        ['hari_lomba', 'Bolehkah mengganti atau menambah atlet setelah terdaftar di nomor lomba?', <<<'MD'
            Menurut petunjuk teknis, atlet yang sudah terdaftar di nomor lomba **tidak dapat ditambah atau digantikan**, kecuali dicoret. Pastikan data atlet dan nomor lombanya benar sebelum pendaftaran ditutup.
            MD],
        ['hari_lomba', 'Apakah panitia menanggung transportasi dan penginapan?', <<<'MD'
            Tidak. Biaya transportasi dan akomodasi ditanggung masing-masing peserta.
            MD],
        ['hari_lomba', 'Bagaimana jika ingin mengajukan protes?', <<<'MD'
            Protes dibuat **tertulis**, ditandatangani pelatih atau ofisial, disertai uang protes **Rp 300.000**. Bila protes diterima, uangnya dikembalikan. Keputusan wasit bersifat mutlak.
            MD],

        // ---------- Hasil dan juara ----------
        ['hasil', 'Di mana melihat hasil lomba?', <<<'MD'
            Buka halaman **Hasil** dari menu halaman depan. Hasil setiap nomor tampil setelah panitia menerbitkannya, dan bisa diunduh sebagai PDF atau Excel.
            MD],
        ['hasil', 'Hasil nomor lomba anak saya belum muncul', <<<'MD'
            Panitia memeriksa hasil setiap nomor lalu menerbitkannya satu per satu. Tunggu sebentar, lalu buka lagi halaman **Hasil**.
            MD],
        ['hasil', 'Bagaimana juara ditentukan?', <<<'MD'
            Juara I, II, dan III diambil dari babak **Final**. Atlet juara I, II, dan III mendapat piagam dan piala. Untuk nomor beregu, satu regu juara mendapat satu piala untuk sekolahnya.

            Nomor Tri Lomba (lari 60 m, lompat jauh, tolak peluru) dinilai dari poin gabungan ketiga nomor.
            MD],
        ['hasil', 'Bagaimana juara umum dihitung?', <<<'MD'
            {cara_juara_umum} Satu sekolah (satu NPSN) dihitung satu kontingen.

            Kontingen juara umum I, II, dan III mendapat piala dan uang pembinaan. Klasemennya ada di halaman **Hasil**, bagian klasemen sekolah.
            MD],
        ['hasil', 'Kapan piagam dan piala diberikan?', <<<'MD'
            Piagam dan piala diberikan panitia di lokasi lomba melalui upacara penghormatan pemenang. Piagam tidak diunduh dari web ini.
            MD],
    ];

    /**
     * Pasang tanya-jawab bawaan bila tabel `faq` masih kosong. Semua masuk
     * dalam satu `insertBatch`, jadi tersimpan semua atau tidak sama sekali.
     *
     * @return int Jumlah yang dipasang; 0 bila tabel sudah berisi (tidak ada yang diubah).
     *
     * @throws RuntimeException Bila penyimpanan gagal.
     */
    public static function pasang(BaseConnection $db): int
    {
        if ($db->table('faq')->countAllResults() > 0) {
            return 0;
        }

        $baris = self::baris(Time::now()->format('Y-m-d H:i:s'));

        if ($db->table('faq')->insertBatch($baris) !== count($baris)) {
            throw new RuntimeException('Tanya-jawab bawaan gagal disimpan.');
        }

        return count($baris);
    }

    /**
     * Baris siap simpan: jawaban tanpa jorok heredoc, urutan 1, 2, 3, … per kategori.
     *
     * @return list<array<string, mixed>>
     */
    public static function baris(string $waktu): array
    {
        $urutan = [];
        $hasil  = [];

        foreach (self::DAFTAR as [$kategori, $pertanyaan, $jawaban]) {
            $urutan[$kategori] = ($urutan[$kategori] ?? 0) + 1;

            $hasil[] = [
                'kategori'    => $kategori,
                'pertanyaan'  => $pertanyaan,
                'jawaban'     => trim($jawaban),
                'urutan'      => $urutan[$kategori],
                'aktif'       => 1,
                'dibuat_pada' => $waktu,
                'diubah_pada' => $waktu,
            ];
        }

        return $hasil;
    }
}
