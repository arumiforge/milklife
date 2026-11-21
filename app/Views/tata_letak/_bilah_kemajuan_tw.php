<?php
/**
 * Bilah muat tipis di atas halaman bertampilan baru, untuk `aset/js/kemajuan.js`
 * (K89). Dipasang tata letak `admin_tw`, `publik_tw`, dan halaman yang
 * memuat `kemajuan.js` (wizard dan Cek Pendaftaran, F9-05). Lebar diatur
 * skrip lewat `style.width`; kelas `selesai` memudarkannya.
 */
?>
<div id="bilah-kemajuan" class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-[3px]" hidden aria-hidden="true">
    <div class="bilah-kemajuan-isi h-full w-0 bg-oranye-500 shadow-[0_0_6px_rgb(232_95_49/0.6)] transition-[width,opacity] duration-300 ease-out [.selesai>&]:opacity-0"></div>
</div>
