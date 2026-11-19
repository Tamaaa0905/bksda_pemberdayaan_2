const mapElement = document.getElementById('group-map');

if (mapElement) {
    if (!window.L) {
        mapElement.textContent = 'Peta tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.';
    } else {
        const map = window.L.map(mapElement, {
            zoomControl: true,
        }).setView([-1.4300, 121.4456], 6);

        const standardLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });
        const satelliteLayer = window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri',
        });
        const hybridLayer = window.L.layerGroup([
            window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri',
            }),
            window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Labels &copy; Esri',
            }),
        ]);
        const terrainLayer = window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri',
        });

        const baseLayers = {
            'Peta Normal': standardLayer,
            'Satelit': satelliteLayer,
            'Hybrid': hybridLayer,
            'Medan / Topografi': terrainLayer,
        };

        const groupLayer = window.L.layerGroup();

        baseLayers['Peta Normal'].addTo(map);
        groupLayer.addTo(map);

        const layerControl = window.L.control.layers(baseLayers, {
            'Kelompok Binaan': groupLayer,
        }, {
            position: 'topright',
            collapsed: true,
        }).addTo(map);
        const layerToggle = layerControl.getContainer().querySelector('.leaflet-control-layers-toggle');
        layerToggle?.setAttribute('aria-label', 'Pilih lapisan peta');
        layerToggle?.setAttribute('title', 'Pilih lapisan peta');

        requestAnimationFrame(() => {
            map.invalidateSize();
        });
        setTimeout(() => map.invalidateSize(), 200);

        const groupDataElement = document.getElementById('group-map-data');
        const coordinateForm = document.getElementById('group-coordinate-form');
        const groupSelect = document.getElementById('group-map-select');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');

        groupSelect?.addEventListener('change', () => {
            const option = groupSelect.selectedOptions[0];
            latitudeInput.value = option?.dataset.latitude ?? '';
            longitudeInput.value = option?.dataset.longitude ?? '';
        });

        coordinateForm?.addEventListener('submit', (event) => {
            const selectedGroupId = groupSelect?.value;

            if (!selectedGroupId) {
                event.preventDefault();
                groupSelect?.focus();
                return;
            }

            coordinateForm.action = coordinateForm.dataset.urlTemplate.replace(
                '__GROUP_ID__',
                encodeURIComponent(selectedGroupId),
            );
        });

        const createPopupContent = (group) => {
            const container = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = group.nama_kelompok || 'Kelompok binaan';
            container.appendChild(title);

            const formatYesNo = (value) => value === true ? 'Ya' : value === false ? 'Tidak' : 'Belum didata';
            const progressRows = [
                ['Status kelompok', group.status_keaktifan === 'aktif' ? 'Aktif' : group.status_keaktifan === 'tidak_aktif' ? 'Tidak aktif' : 'Belum didata'],
                ...(group.status_keaktifan === 'tidak_aktif' ? [['Kendala', group.kendala]] : []),
                ...(group.status_keaktifan === 'aktif' ? [
                    ['Butuh bantuan lanjutan', formatYesNo(group.butuh_bantuan_lanjutan)],
                    ['Butuh pendampingan', formatYesNo(group.butuh_pendampingan)],
                    ['Catatan kebutuhan', group.catatan_kebutuhan],
                ] : []),
            ];

            [
                ['Ketua', group.nama_ketua],
                ['Desa', group.nama_desa],
                ['Kawasan', group.nama_kawasan],
                ['Kecamatan', group.kecamatan],
                ['Kabupaten', group.kabupaten],
                ['Jenis usaha', group.jenis_usaha],
                ['Nomor SK kelompok', group.sk_kelompok],
                ['Produk unggulan', group.produk_unggulan],
                ['Jumlah anggota', group.jumlah_anggota],
                ['Jumlah bantuan', group.jumlah_bantuan === null ? null : `Rp ${new Intl.NumberFormat('id-ID').format(group.jumlah_bantuan)}`],
                ['Tahun pembinaan', group.tahun_pembinaan],
                ...progressRows,
            ].forEach(([label, value]) => {
                const row = document.createElement('div');
                row.textContent = `${label}: ${value ?? '-'}`;
                container.appendChild(row);
            });

            return container;
        };

        const createMarkerLabel = (group) => {
            const container = document.createElement('div');
            const title = document.createElement('strong');
            const subtitle = document.createElement('span');

            title.textContent = group.nama_kelompok || 'Kelompok binaan';
            subtitle.textContent = group.jenis_usaha || group.nama_kawasan || 'Kelompok binaan';
            container.append(title, subtitle);

            return container;
        };

        const loadGroupMarkers = () => {
            const groups = JSON.parse(groupDataElement?.textContent ?? '[]');
            const groupCoordinates = [];

            groups.forEach((group) => {
                const longitude = Number(group.longitude);
                const latitude = Number(group.latitude);

                if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                    return;
                }

                groupCoordinates.push([latitude, longitude]);
                const marker = window.L.marker([latitude, longitude], {
                    icon: window.L.divIcon({
                        className: 'group-map-marker-icon',
                        html: '<span class="group-map-pin" aria-hidden="true"></span>',
                        iconSize: [44, 52],
                        iconAnchor: [22, 52],
                        popupAnchor: [0, -48],
                    }),
                })
                    .bindTooltip(createMarkerLabel(group), {
                        permanent: true,
                        direction: 'top',
                        offset: [0, -44],
                        className: 'group-map-tooltip',
                        opacity: 1,
                    })
                    .bindPopup(createPopupContent(group));

                groupLayer.addLayer(marker);
            });

            if (groupCoordinates.length === 1) {
                map.setView(groupCoordinates[0], 13);
            } else if (groupCoordinates.length > 1) {
                map.fitBounds(window.L.latLngBounds(groupCoordinates).pad(0.15));
            }
        };

        loadGroupMarkers();
    }
}
