import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Grafik hanya dimuat (code-splitting) pada halaman yang menyediakan datanya.
if (document.getElementById('dashboard-data')) {
    import('./dashboard');
}

// Interaksi landing page (scroll reveal, spotlight kartu, parallax hero, scrollspy).
if (document.getElementById('landing')) {
    import('./landing');
}

if (document.getElementById('group-map')) {
    const loadMapModule = () => {
        if (window.L) {
            import('./map');
            return true;
        }

        return false;
    };

    if (!loadMapModule()) {
        window.addEventListener('load', () => {
            loadMapModule();
        }, { once: true });
    }
}
