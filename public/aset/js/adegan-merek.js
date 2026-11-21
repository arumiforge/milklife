/**
 * Adegan 3D lembut bernuansa lomba atletik (Tahap 9, K140): bola, cincin,
 * kerucut, dan kapsul berwarna MilkLife yang melayang pelan. Dipakai sebagai
 * hiasan di belakang isi; tidak memuat informasi.
 *
 * Pemakaian di view (modul ES, CSP aman karena dari berkas):
 *
 *     <div class="relative" data-adegan-merek>
 *         <div data-adegan-cadangan class="…">gambar diam/gradien</div>
 *     </div>
 *     <script type="module" src="<?= aset('js/adegan-merek.js') ?>"></script>
 *
 * Adegan tidak dijalankan (cadangan tetap tampil) bila pengguna memilih
 * kurangi gerak (`prefers-reduced-motion`) atau WebGL tidak tersedia.
 * Gambar berhenti saat tidak terlihat di layar atau tab disembunyikan.
 */
const wadahWadah = document.querySelectorAll('[data-adegan-merek]');
const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function adaWebgl() {
    try {
        const kanvas = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (kanvas.getContext('webgl2') || kanvas.getContext('webgl')));
    } catch {
        return false;
    }
}

if (wadahWadah.length && !kurangiGerak && adaWebgl()) {
    const THREE = await import('../vendor/three/three.module.min.js');

    const WARNA = [0x234987, 0xe85f31, 0xf0005a, 0x9bccec, 0x021f7b, 0xffffff];

    wadahWadah.forEach((wadah) => {
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'low-power' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.domElement.setAttribute('aria-hidden', 'true');
        // Di belakang isi (elemen pertama), lebih samar di layar sempit supaya teks tetap terbaca.
        renderer.domElement.classList.add('absolute', 'inset-0', 'size-full', 'pointer-events-none', 'opacity-35', 'md:opacity-100');
        wadah.prepend(renderer.domElement);

        const adegan = new THREE.Scene();
        const kamera = new THREE.PerspectiveCamera(40, 1, 0.1, 100);
        kamera.position.set(0, 0, 14);

        adegan.add(new THREE.HemisphereLight(0xffffff, 0xc6d0e0, 2.2));
        const matahari = new THREE.DirectionalLight(0xffffff, 1.6);
        matahari.position.set(-4, 6, 8);
        adegan.add(matahari);

        const bentuk = [
            new THREE.SphereGeometry(0.9, 48, 32),
            new THREE.TorusGeometry(0.8, 0.28, 24, 64),
            new THREE.ConeGeometry(0.7, 1.4, 40),
            new THREE.CapsuleGeometry(0.35, 1.1, 12, 24),
        ];

        const benda = [];
        for (let i = 0; i < 14; i++) {
            const bahan = new THREE.MeshStandardMaterial({ color: WARNA[i % WARNA.length], roughness: 0.55, metalness: 0.05 });
            const mesh = new THREE.Mesh(bentuk[i % bentuk.length], bahan);
            const skala = 0.6 + ((i * 37) % 10) / 14;
            mesh.scale.setScalar(skala);
            mesh.position.set(((i * 53) % 13) - 3, ((i * 29) % 9) - 4.5, -((i * 7) % 6) - 1);
            mesh.rotation.set(i, i * 0.7, i * 0.3);
            mesh.userData = { dasarY: mesh.position.y, fase: i * 0.9, laju: 0.15 + (i % 5) * 0.04 };
            adegan.add(mesh);
            benda.push(mesh);
        }

        function ukur() {
            const { clientWidth: l, clientHeight: t } = wadah;
            if (!l || !t) return;
            renderer.setSize(l, t, false);
            kamera.aspect = l / t;
            kamera.updateProjectionMatrix();
        }
        new ResizeObserver(ukur).observe(wadah);
        ukur();

        let terlihat = true;
        new IntersectionObserver(([e]) => { terlihat = e.isIntersecting; }).observe(wadah);

        const jam = new THREE.Clock();
        renderer.setAnimationLoop(() => {
            if (!terlihat || document.hidden) return;
            const w = jam.getElapsedTime();
            for (const m of benda) {
                m.position.y = m.userData.dasarY + Math.sin(w * m.userData.laju + m.userData.fase) * 0.35;
                m.rotation.x += 0.002;
                m.rotation.y += 0.003;
            }
            renderer.render(adegan, kamera);
        });

        wadah.querySelector('[data-adegan-cadangan]')?.classList.add('opacity-0');
        wadah.dataset.adeganJalan = '1';
    });
}
