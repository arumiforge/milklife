/*
 * Pemilih tanggal Flowbite berbahasa Indonesia untuk halaman bertampilan baru
 * (F9-07). Dimuat sebagai modul: `<script type="module" src="…/pemilih-tanggal-tw.js">`,
 * setelah `flowbite.min.js` (tata letak `admin_tw` dan `publik_tw`).
 *
 * Isian `<input type="text" data-pemilih-tanggal>` mendapat kalender Flowbite
 * (`window.Datepicker`) bertanggal Indonesia: nama hari dan bulan dari
 * `vendor/flowbite/datepicker-id.js` (salinan `flowbite-datepicker`, `npm run vendor`),
 * minggu mulai Senin, format `hh/bb/tttt` yang juga dibaca server
 * (`JadwalPertandingan::periksa()`). Isian tetap bisa diketik; tanpa
 * JavaScript isian tetap berupa kotak teks biasa.
 *
 * Atribut `data-pemilih-tanggal` (bukan `datepicker`) dipakai supaya Flowbite
 * tidak memasangnya sendiri dengan bahasa Inggris lebih dulu.
 */
const isian = document.querySelectorAll('input[data-pemilih-tanggal]');

if (isian.length > 0 && typeof window.Datepicker === 'function') {
    const bahasa = (await import('../vendor/flowbite/datepicker-id.js')).default;

    isian.forEach((kotak) => {
        const pemilih = new window.Datepicker(kotak, {
            autohide: true,
            format: 'dd/mm/yyyy',
            orientation: 'bottom left',
            todayBtn: true,
            clearBtn: true,
        });
        const inti = pemilih.getDatepickerInstance();

        // Kamus bahasa disimpan di kelas flowbite-datepicker (bersama semua isian).
        Object.assign(inti.constructor.locales, bahasa);
        inti.setOptions({ language: 'id', weekStart: 1, todayBtnMode: 1 });

        // Pemilih mengubah nilai tanpa peristiwa `input`; skrip lain (misalnya
        // "Isi berurutan") dan pembaca formulir tetap diberi tahu.
        kotak.addEventListener('changeDate', () => kotak.dispatchEvent(new Event('input', { bubbles: true })));
    });
}
