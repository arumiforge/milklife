/**
 * Grafik dasbor admin (K102). Data dari `#data-grafik-dasbor` (JSON, dibuat
 * `RingkasanDasbor::grafik()`), digambar dengan ApexCharts
 * (`aset/vendor/apexcharts`). Gaya grafik mengikuti grafik dasbor Flowbite di
 * `template-flowbites/` (garis halus, isian gradasi, tanpa garis kisi, tooltip
 * bersama), dengan warna merek MilkLife.
 *
 * Bila ApexCharts gagal dimuat, kotak grafik menampilkan keterangan, dan
 * angka di kartu tetap bisa dibaca.
 */
(function () {
    'use strict';

    var sumber = document.getElementById('data-grafik-dasbor');

    if (!sumber) {
        return;
    }

    var data = JSON.parse(sumber.textContent || '{}');

    var WARNA = {
        biru: '#234987',
        biruTua: '#021F7B',
        oranye: '#E85F31',
        biruMuda: '#9BCCEC',
        hijau: '#0E9F6E',
        toska: '#12ADBD',
        ungu: '#7E3AF2',
        merahMuda: '#E74694',
        garis: '#EEF2F7',
        label: '#6B7280'
    };

    var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function angka(n) {
        return Number(n || 0).toLocaleString('id-ID');
    }

    function rupiah(n) {
        return 'Rp ' + angka(Math.round(n || 0));
    }

    function rupiahRingkas(n) {
        n = Number(n || 0);
        if (n >= 1e9) { return 'Rp ' + (n / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M'; }
        if (n >= 1e6) { return 'Rp ' + (n / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt'; }
        if (n >= 1e3) { return 'Rp ' + (n / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb'; }
        return 'Rp ' + angka(n);
    }

    function tanggalPendek(t) {
        var b = String(t).split('-');
        return b.length === 3 ? parseInt(b[2], 10) + ' ' + BULAN[parseInt(b[1], 10) - 1] : t;
    }

    function jumlah(daftar) {
        return (daftar || []).reduce(function (a, b) { return a + Number(b || 0); }, 0);
    }

    // Pengaturan bersama, setara `getMainChartOptions()` template Flowbite.
    var dasar = {
        chart: {
            fontFamily: 'inherit',
            foreColor: WARNA.label,
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, speed: 500 },
            defaultLocale: 'id',
            locales: [{
                name: 'id',
                options: {
                    months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    shortMonths: BULAN,
                    days: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                    shortDays: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    toolbar: { exportToSVG: 'Unduh SVG', exportToPNG: 'Unduh PNG', exportToCSV: 'Unduh CSV', menu: 'Menu' }
                }
            }]
        },
        dataLabels: { enabled: false },
        grid: { show: true, borderColor: WARNA.garis, strokeDashArray: 4, padding: { left: 8, right: 8, top: 0, bottom: 0 } },
        legend: { fontSize: '13px', fontWeight: 500, position: 'top', horizontalAlign: 'left', markers: { size: 6, shape: 'circle' }, itemMargin: { horizontal: 10 } },
        tooltip: { style: { fontSize: '13px' } },
        noData: { text: 'Belum ada data', style: { color: WARNA.label, fontSize: '14px' } },
        states: { hover: { filter: { type: 'darken', value: 0.9 } } }
    };

    function gabung(a, b) {
        var hasil = Array.isArray(a) ? a.slice() : Object.assign({}, a);
        Object.keys(b).forEach(function (k) {
            var nilai = b[k];
            if (nilai && typeof nilai === 'object' && !Array.isArray(nilai) && hasil[k] && typeof hasil[k] === 'object' && !Array.isArray(hasil[k])) {
                hasil[k] = gabung(hasil[k], nilai);
            } else {
                hasil[k] = nilai;
            }
        });
        return hasil;
    }

    var grafik = {};

    function gambar(id, opsi) {
        var el = document.getElementById(id);
        if (!el) { return null; }
        if (typeof window.ApexCharts !== 'function') {
            el.innerHTML = '<p class="text-body-secondary small py-5 text-center mb-0">Grafik tidak bisa dimuat. Muat ulang halaman.</p>';
            return null;
        }
        var g = new window.ApexCharts(el, gabung(dasar, opsi));
        g.render();
        grafik[id] = g;
        return g;
    }

    // ---------- Tren pendaftaran ----------
    var tren = data.tren || { tanggal: [], pendaftaran: [], atlet: [], tagihan: [] };
    var kategoriTren = tren.tanggal.map(tanggalPendek);

    function opsiTren(jenis) {
        var uang = jenis === 'tagihan';
        return {
            chart: { type: 'area', height: 320 },
            series: uang
                ? [{ name: 'Tagihan', data: tren.tagihan, color: WARNA.oranye }]
                : [
                    { name: 'Pendaftaran', data: tren.pendaftaran, color: WARNA.biru },
                    { name: 'Atlet', data: tren.atlet, color: WARNA.oranye }
                ],
            fill: { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0, stops: [0, 95, 100] } },
            stroke: { curve: 'smooth', width: 3 },
            markers: { size: 0, strokeColors: '#fff', hover: { sizeOffset: 4 } },
            xaxis: {
                categories: kategoriTren,
                tickAmount: 10,
                labels: { rotate: 0, hideOverlappingLabels: true, style: { fontSize: '12px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
                crosshairs: { show: true, position: 'back', stroke: { color: WARNA.garis, width: 1, dashArray: 6 } },
                tooltip: { enabled: false }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: { formatter: uang ? rupiahRingkas : function (v) { return angka(Math.round(v)); } }
            },
            tooltip: { shared: true, intersect: false, y: { formatter: uang ? rupiah : angka } }
        };
    }

    var grafikTren = gambar('grafik-tren', opsiTren('jumlah'));

    document.querySelectorAll('[data-tren]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            document.querySelectorAll('[data-tren]').forEach(function (t) {
                var aktif = t === tombol;
                t.classList.toggle('active', aktif);
                t.setAttribute('aria-pressed', aktif ? 'true' : 'false');
            });
            if (grafikTren) {
                var opsi = opsiTren(tombol.getAttribute('data-tren'));
                grafikTren.updateOptions({ yaxis: opsi.yaxis, tooltip: opsi.tooltip }, false, false);
                grafikTren.updateSeries(opsi.series, true);
            }
        });
    });

    // ---------- Status pembayaran (donat) ----------
    var status = data.status || { label: [], jumlah: [], rupiah: [] };
    var totalStatus = jumlah(status.jumlah);

    gambar('grafik-status', {
        chart: { type: 'donut', height: 300 },
        series: totalStatus > 0 ? status.jumlah : [],
        labels: status.label,
        colors: [WARNA.hijau, WARNA.oranye],
        stroke: { colors: ['#fff'], width: 3 },
        legend: { position: 'bottom', horizontalAlign: 'center' },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: { show: true, fontSize: '13px', offsetY: 18 },
                        value: { show: true, fontSize: '28px', fontWeight: 700, color: WARNA.biruTua, offsetY: -14, formatter: function (v) { return angka(v); } },
                        total: { show: true, showAlways: true, label: 'Pendaftaran', fontSize: '13px', color: WARNA.label, formatter: function () { return angka(totalStatus); } }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (v, o) {
                    return angka(v) + ' pendaftaran · ' + rupiah(status.rupiah[o.seriesIndex]);
                }
            }
        }
    });

    // ---------- Batang putra/putri ----------
    function opsiPutraPutri(isi, mendatar) {
        var ada = jumlah(isi.putra) + jumlah(isi.putri) > 0;
        return {
            chart: { type: 'bar', height: mendatar ? 300 : 300, stacked: true },
            series: ada ? [
                { name: 'Putra', data: isi.putra, color: WARNA.biru },
                { name: 'Putri', data: isi.putri, color: WARNA.merahMuda }
            ] : [],
            plotOptions: { bar: { horizontal: !!mendatar, columnWidth: '55%', barHeight: '60%', borderRadius: 6, borderRadiusApplication: 'end', borderRadiusWhenStacked: 'last' } },
            xaxis: { categories: isi.label, axisBorder: { show: false }, axisTicks: { show: false }, labels: { formatter: mendatar ? function (v) { return angka(v); } : undefined } },
            yaxis: { labels: { maxWidth: 160 } },
            tooltip: { shared: true, intersect: false, y: { formatter: function (v) { return angka(v) + ' atlet'; } } }
        };
    }

    gambar('grafik-kabupaten', opsiPutraPutri(data.kabupaten || { label: [], putra: [], putri: [] }, false));
    gambar('grafik-kelompok-umur', opsiPutraPutri(data.kelompok_umur || { label: [], putra: [], putri: [] }, false));

    // ---------- Jenjang (donat semi) ----------
    var jenjang = data.jenjang || { label: [], atlet: [], pendaftaran: [] };
    var totalJenjang = jumlah(jenjang.atlet);

    gambar('grafik-jenjang', {
        chart: { type: 'donut', height: 300 },
        series: totalJenjang > 0 ? jenjang.atlet : [],
        labels: jenjang.label,
        colors: [WARNA.toska, WARNA.biru, WARNA.ungu],
        stroke: { colors: ['#fff'], width: 3 },
        legend: { position: 'bottom', horizontalAlign: 'center' },
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 90,
                offsetY: 10,
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: { offsetY: -30, fontSize: '13px' },
                        value: { offsetY: -10, fontSize: '26px', fontWeight: 700, color: WARNA.biruTua, formatter: function (v) { return angka(v); } },
                        total: { show: true, showAlways: true, label: 'Atlet', color: WARNA.label, formatter: function () { return angka(totalJenjang); } }
                    }
                }
            }
        },
        grid: { padding: { bottom: -80 } },
        tooltip: {
            y: {
                formatter: function (v, o) {
                    return angka(v) + ' atlet dari ' + angka(jenjang.pendaftaran[o.seriesIndex]) + ' pendaftaran';
                }
            }
        }
    });

    // ---------- Nomor lomba terpopuler ----------
    var nomor = data.nomor_lomba || { label: [], peserta: [] };

    gambar('grafik-nomor-lomba', {
        chart: { type: 'bar', height: Math.max(220, 34 * nomor.label.length + 60) },
        series: nomor.peserta.length ? [{ name: 'Peserta', data: nomor.peserta }] : [],
        colors: [WARNA.biru],
        fill: { type: 'gradient', gradient: { shade: 'light', type: 'horizontal', gradientToColors: [WARNA.biruMuda], stops: [0, 100] } },
        plotOptions: { bar: { horizontal: true, barHeight: '62%', borderRadius: 6, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: true, style: { fontSize: '12px', colors: ['#fff'] }, formatter: function (v) { return angka(v); } },
        xaxis: { categories: nomor.label, axisBorder: { show: false }, axisTicks: { show: false }, labels: { formatter: function (v) { return angka(v); } } },
        yaxis: { labels: { maxWidth: 260, style: { fontSize: '12px' } } },
        grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        tooltip: { y: { formatter: function (v) { return angka(v) + ' peserta'; } } }
    });

    // ---------- Pengunjung ----------
    var pengunjung = data.pengunjung || { tanggal: [], kunjungan: [], unik: [] };
    var adaPengunjung = jumlah(pengunjung.kunjungan) > 0;

    gambar('grafik-pengunjung', {
        chart: { type: 'line', height: 300 },
        series: adaPengunjung ? [
            { name: 'Pengunjung', type: 'column', data: pengunjung.unik, color: WARNA.toska },
            { name: 'Halaman dibuka', type: 'area', data: pengunjung.kunjungan, color: WARNA.ungu }
        ] : [],
        stroke: { width: [0, 3], curve: 'smooth' },
        fill: { type: ['solid', 'gradient'], gradient: { opacityFrom: 0.35, opacityTo: 0 } },
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
        xaxis: {
            categories: pengunjung.tanggal.map(tanggalPendek),
            tickAmount: 10,
            labels: { rotate: 0, hideOverlappingLabels: true, style: { fontSize: '12px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: function (v) { return angka(Math.round(v)); } } },
        tooltip: { shared: true, intersect: false, y: { formatter: angka } }
    });

    // ---------- Jam mendaftar ----------
    var jam = data.jam_daftar || { label: [], pendaftaran: [] };

    gambar('grafik-jam', {
        chart: { type: 'bar', height: 300 },
        series: jumlah(jam.pendaftaran) > 0 ? [{ name: 'Pendaftaran', data: jam.pendaftaran }] : [],
        colors: [WARNA.oranye],
        plotOptions: { bar: { columnWidth: '70%', borderRadius: 3, borderRadiusApplication: 'end' } },
        xaxis: {
            categories: jam.label,
            tickAmount: 8,
            labels: { rotate: 0, hideOverlappingLabels: true, style: { fontSize: '11px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: function (v) { return angka(Math.round(v)); } } },
        tooltip: { y: { formatter: function (v) { return angka(v) + ' pendaftaran'; } } }
    });
}());
