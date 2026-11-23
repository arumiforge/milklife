/*
 * Pencarian menu di kepala panel admin (F11-10, K174 butir 1).
 *
 * Kotak `[data-cari-menu]` berisi isian combobox `[data-cari-menu-isian]`
 * dan daftar hasil `[data-cari-menu-hasil]` (role listbox). Butirnya diambil
 * dari tautan sidebar (`nav[data-menu-admin="samping"] a[data-menu-tautan]`),
 * jadi satu sumber dengan `MenuAdmin`. Saat mengetik:
 * - daftar hasil menampilkan butir yang cocok (semua kata harus ada di label
 *   atau nama kelompoknya, tanpa membedakan huruf besar dan tanda baca);
 * - butir sidebar yang tidak cocok disembunyikan dan submenu yang memuat
 *   butir cocok dibuka; isian dikosongkan, sidebar kembali seperti semula.
 *
 * Papan ketik: panah bawah/atas memilih hasil, Enter membuka hasil terpilih
 * (atau hasil pertama), Esc mengosongkan isian lalu menutup kotak di ponsel.
 * Di ponsel kotak tertutup dan dibuka tombol ikon `[data-cari-menu-buka]`;
 * kotak yang terbuka diberi atribut `data-terbuka`.
 *
 * Tanpa JavaScript kotak tetap tersembunyi (`hidden`), menu sidebar tetap
 * bisa dipakai.
 */
(function () {
    'use strict';

    var kotak = document.querySelector('[data-cari-menu]');
    var sidebar = document.querySelector('nav[data-menu-admin="samping"]');

    if (!kotak || !sidebar) {
        return;
    }

    var isian = kotak.querySelector('[data-cari-menu-isian]');
    var panel = kotak.querySelector('[data-cari-menu-panel]');
    var daftar = kotak.querySelector('[data-cari-menu-hasil]');
    var kosong = kotak.querySelector('[data-cari-menu-kosong]');
    var status = kotak.querySelector('[data-cari-menu-status]');
    var tombolBuka = kotak.querySelector('[data-cari-menu-buka]');
    var tombolTutup = kotak.querySelector('[data-cari-menu-tutup]');
    var pilihan = -1;
    var hasil = [];
    var simpanan = null;

    function rata(teks) {
        return (teks || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function teksTautan(a) {
        var salin = a.cloneNode(true);
        Array.prototype.forEach.call(salin.querySelectorAll('.sr-only, svg'), function (el) {
            el.remove();
        });
        return salin.textContent.replace(/\s+/g, ' ').trim();
    }

    // Indeks butir menu: label, keterangan (kelompok atau induk), alamat.
    var butir = [];
    Array.prototype.forEach.call(sidebar.querySelectorAll('a[data-menu-tautan]'), function (a) {
        if (a.getAttribute('target') === '_blank') {
            return;
        }

        var label = teksTautan(a);
        var keterangan = '';

        if (a.hasAttribute('data-menu-anak')) {
            var ul = a.closest('ul');
            var induk = ul && ul.id ? sidebar.querySelector('[aria-controls="' + ul.id + '"]') : null;
            var tautanInduk = induk ? induk.parentElement.querySelector('a[data-menu-tautan]') : null;
            keterangan = tautanInduk ? teksTautan(tautanInduk) : '';
        } else {
            var kelompok = a.closest('[data-menu-kelompok]');
            keterangan = kelompok ? kelompok.getAttribute('data-menu-kelompok') : '';
        }

        if (keterangan === label) {
            keterangan = '';
        }

        butir.push({ label: label, keterangan: keterangan, alamat: a.href, tautan: a, cari: rata(label + ' ' + keterangan) });
    });

    function cocok(satu, kata) {
        return kata.every(function (k) {
            return satu.cari.indexOf(k) !== -1;
        });
    }

    // ---------- Saring sidebar ----------

    function simpanKeadaan() {
        if (simpanan !== null) {
            return;
        }
        simpanan = [];
        Array.prototype.forEach.call(sidebar.querySelectorAll('li, ul, [data-menu-kelompok], [data-lipat]'), function (el) {
            simpanan.push([el, el.hidden, el.getAttribute('aria-expanded')]);
        });
    }

    function pulihkan() {
        if (simpanan === null) {
            return;
        }
        simpanan.forEach(function (s) {
            s[0].hidden = s[1];
            if (s[2] !== null) {
                s[0].setAttribute('aria-expanded', s[2]);
            }
        });
        simpanan = null;
    }

    function saringSidebar(cocokSet) {
        simpanKeadaan();

        Array.prototype.forEach.call(sidebar.querySelectorAll('li'), function (li) {
            var ada = Array.prototype.some.call(li.querySelectorAll('a[data-menu-tautan]'), function (a) {
                return cocokSet.indexOf(a) !== -1;
            });
            li.hidden = !ada;

            // Submenu yang memuat butir cocok dibuka.
            if (ada) {
                Array.prototype.forEach.call(li.querySelectorAll('ul'), function (ul) {
                    ul.hidden = false;
                });
                Array.prototype.forEach.call(li.querySelectorAll('[data-lipat]'), function (t) {
                    t.setAttribute('aria-expanded', 'true');
                });
            }
        });

        Array.prototype.forEach.call(sidebar.querySelectorAll('[data-menu-kelompok]'), function (k) {
            var ada = k.querySelector('li:not([hidden])') !== null;
            k.hidden = !ada;
            var daftarKelompok = k.querySelector(':scope > ul');
            var tombolKelompok = k.querySelector(':scope > [data-lipat]');
            if (ada && daftarKelompok) {
                daftarKelompok.hidden = false;
            }
            if (ada && tombolKelompok) {
                tombolKelompok.setAttribute('aria-expanded', 'true');
            }
        });

        var akhir = sidebar.querySelector('[data-menu-akhir]');
        if (akhir) {
            akhir.hidden = akhir.querySelector('li:not([hidden])') === null;
        }
    }

    // ---------- Daftar hasil ----------

    function tandai(indeks) {
        pilihan = indeks;
        Array.prototype.forEach.call(daftar.children, function (li, i) {
            var aktif = i === indeks;
            li.setAttribute('aria-selected', aktif ? 'true' : 'false');
            if (aktif) {
                li.scrollIntoView({ block: 'nearest' });
            }
        });
        if (indeks >= 0 && daftar.children[indeks]) {
            isian.setAttribute('aria-activedescendant', daftar.children[indeks].id);
        } else {
            isian.removeAttribute('aria-activedescendant');
        }
    }

    function tampilkan(terbuka) {
        daftar.hidden = !terbuka || hasil.length === 0;
        kosong.hidden = !terbuka || hasil.length > 0;
        isian.setAttribute('aria-expanded', terbuka && hasil.length > 0 ? 'true' : 'false');
    }

    function perbarui() {
        var kata = rata(isian.value).split(' ').filter(Boolean);

        daftar.textContent = '';
        pilihan = -1;
        isian.removeAttribute('aria-activedescendant');

        if (!kata.length) {
            hasil = [];
            tampilkan(false);
            pulihkan();
            status.textContent = '';
            return;
        }

        hasil = butir.filter(function (satu) {
            return cocok(satu, kata);
        });

        hasil.forEach(function (satu, i) {
            var li = document.createElement('li');
            li.id = 'cari-menu-hasil-' + i;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');
            li.className = 'flex min-h-11 cursor-pointer flex-col justify-center rounded-md px-3 py-1.5 text-sm text-gray-900 hover:bg-gray-100 aria-selected:bg-primary-50 aria-selected:text-primary-800';
            var label = document.createElement('span');
            label.className = 'font-semibold';
            label.textContent = satu.label;
            li.appendChild(label);
            if (satu.keterangan) {
                var ket = document.createElement('span');
                ket.className = 'text-xs text-gray-500';
                ket.textContent = satu.keterangan;
                li.appendChild(ket);
            }
            li.addEventListener('mousedown', function (e) {
                // Jangan biarkan isian kehilangan fokus sebelum klik selesai.
                e.preventDefault();
            });
            li.addEventListener('click', function () {
                buka(satu);
            });
            daftar.appendChild(li);
        });

        saringSidebar(hasil.map(function (satu) {
            return satu.tautan;
        }));
        tampilkan(true);
        status.textContent = hasil.length ? hasil.length + ' menu cocok.' : 'Tidak ada menu yang cocok.';
    }

    function buka(satu) {
        if (satu) {
            window.location.href = satu.alamat;
        }
    }

    function aturPanel(terbuka) {
        if (terbuka) {
            kotak.setAttribute('data-terbuka', '');
        } else {
            kotak.removeAttribute('data-terbuka');
        }
        if (tombolBuka) {
            tombolBuka.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
        }
    }

    function ponsel() {
        return tombolBuka && window.getComputedStyle(tombolBuka).display !== 'none';
    }

    isian.addEventListener('input', perbarui);

    isian.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' && hasil.length) {
            e.preventDefault();
            tampilkan(true);
            tandai((pilihan + 1) % hasil.length);
        } else if (e.key === 'ArrowUp' && hasil.length) {
            e.preventDefault();
            tampilkan(true);
            tandai(pilihan <= 0 ? hasil.length - 1 : pilihan - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            buka(hasil[pilihan >= 0 ? pilihan : 0]);
        } else if (e.key === 'Escape') {
            if (isian.value !== '') {
                e.preventDefault();
                isian.value = '';
                perbarui();
            } else if (ponsel()) {
                e.preventDefault();
                aturPanel(false);
                tombolBuka.focus();
            }
        }
    });

    isian.addEventListener('focus', function () {
        if (isian.value !== '') {
            tampilkan(true);
        }
    });

    isian.addEventListener('blur', function () {
        tampilkan(false);
    });

    if (tombolBuka) {
        tombolBuka.addEventListener('click', function () {
            aturPanel(true);
            isian.focus();
        });
    }

    if (tombolTutup) {
        tombolTutup.addEventListener('click', function () {
            isian.value = '';
            perbarui();
            aturPanel(false);
            tombolBuka.focus();
        });
    }

    // Shortcut "/" membuka pencarian menu bila fokus tidak di isian lain.
    document.addEventListener('keydown', function (e) {
        var sasaran = e.target;
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey || (sasaran && (sasaran.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(sasaran.tagName)))) {
            return;
        }
        e.preventDefault();
        aturPanel(true);
        isian.focus();
    });

    panel.hidden = false;
    if (tombolBuka) {
        tombolBuka.hidden = false;
    }
}());
