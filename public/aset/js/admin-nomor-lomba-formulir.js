/*
 * Dipindah dari skrip sebaris admin/pengaturan/nomor_lomba/formulir.php supaya lolos CSP (F7-32, K127).
 */
// Tampilkan isian tim hanya untuk nomor beregu, dan isian mix hanya untuk
// nomor beregu mix. Pilihan mix dimatikan untuk nomor individu.
(function () {
    var formulir = document.getElementById('formulir-nomor-lomba');
    if (!formulir) {
        return;
    }
    var bagianTim = document.getElementById('bagian-tim');
    var bagianMix = document.getElementById('bagian-mix');
    var pilihanMix = document.getElementById('kategori-mix');
    var terkunci = formulir.getAttribute('data-terkunci') === '1';

    function nilai(nama) {
        var dipilih = formulir.querySelector('input[name="' + nama + '"]:checked')
            || formulir.querySelector('input[type="hidden"][name="' + nama + '"]');
        return dipilih ? dipilih.value : '';
    }

    function perbarui() {
        var beregu = nilai('jenis') === 'beregu';
        if (!terkunci) {
            pilihanMix.disabled = !beregu;
            if (!beregu && pilihanMix.checked) {
                pilihanMix.checked = false;
                document.getElementById('kategori-putra').checked = true;
            }
        }
        bagianTim.hidden = !beregu;
        bagianMix.hidden = !(beregu && nilai('kategori') === 'mix');
    }

    formulir.addEventListener('change', function (peristiwa) {
        if (peristiwa.target.name === 'jenis' || peristiwa.target.name === 'kategori') {
            perbarui();
        }
    });
    perbarui();
})();

// Poin multi-lomba (F9-10): bagian tabel skor hanya untuk jenis hasil multi,
// dan hanya komponen tabel yang dipilih yang tampil.
(function () {
    var formulir = document.getElementById('formulir-nomor-lomba');
    var bagian = document.getElementById('bagian-multi');
    if (!formulir || !bagian) {
        return;
    }
    var pilihTabel = document.getElementById('tabel_skor_id');

    function perbarui() {
        var jenis = formulir.querySelector('input[name="jenis_hasil"]:checked');
        bagian.hidden = !(jenis && jenis.value === 'multi');
        var tabel = pilihTabel ? pilihTabel.value : '';
        bagian.querySelectorAll('.komponen-skor').forEach(function (kotak) {
            kotak.hidden = kotak.getAttribute('data-tabel') !== tabel;
        });
    }

    formulir.addEventListener('change', function (peristiwa) {
        if (peristiwa.target.name === 'jenis_hasil' || peristiwa.target.name === 'tabel_skor_id') {
            perbarui();
        }
    });
    perbarui();
})();
