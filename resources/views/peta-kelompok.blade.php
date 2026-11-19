<x-app-layout>
    <div class="sr-only">
        <h1>Peta Sebaran Kelompok Binaan</h1>
    </div>

    <div class="gis-map-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="gis-map-outer" style="height: 62vh; min-height: 440px; border: 3px solid #2c8a6a; border-radius: 26px; background: #dfece8; box-shadow: 0 14px 35px rgba(19, 58, 48, 0.08); overflow: hidden;">
            <div class="gis-map-inner" style="height: 100%; overflow: hidden; border-radius: 20px;">
                <div id="group-map"
                     class="gis-map"
                     role="region"
                     aria-label="Peta sebaran titik kelompok binaan"
                     style="width: 100%; height: 100%; min-height: 440px; overflow: hidden; display: block;"></div>
            </div>
        </div>

        <p class="text-xs text-slate-500">Peta OpenStreetMap menampilkan titik kelompok binaan. Klik pin untuk melihat informasi kelompok.</p>

        @if (auth()->user()->isAdmin())
            <div class="gis-form-panel">
                <div class="gis-panel-header">
                    <h2>Perbarui Titik Koordinat Kelompok</h2>
                    <p>Masukkan koordinat dari Google Maps untuk memperbarui posisi kelompok pada peta.</p>
                </div>

                @if ($kelompoks->isEmpty())
                    <p class="text-sm text-ink-500">
                        Belum ada kelompok untuk diperbarui.
                        <a href="{{ route('kelompok.create') }}" class="font-semibold text-brand-700 underline">Tambah kelompok</a>
                    </p>
                @else
                    <form id="group-coordinate-form"
                          class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"
                          method="POST"
                          data-url-template="{{ route('kelompok.coordinates.update', ['kelompok' => '__GROUP_ID__']) }}">
                        @csrf
                        @method('PUT')

                        <div class="md:col-span-2">
                            <x-input-label for="group-map-select" value="Kelompok binaan" />
                            <select id="group-map-select" name="kelompok_id" class="field mt-1.5 block w-full" required>
                                <option value="">Pilih kelompok</option>
                                @foreach ($kelompoks as $kelompok)
                                    <option value="{{ $kelompok->id }}" @selected(old('kelompok_id') == $kelompok->id)
                                            data-latitude="{{ $kelompok->latitude }}"
                                            data-longitude="{{ $kelompok->longitude }}">
                                        {{ $kelompok->nama_kelompok }} — {{ $kelompok->desa?->nama_desa ?? 'Desa belum tercatat' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('kelompok')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="latitude" value="Latitude" />
                            <x-text-input id="latitude" name="latitude" type="number" step="any" min="-90" max="90"
                                          class="mt-1.5 block w-full" :value="old('latitude')" required />
                            <x-input-error :messages="$errors->get('latitude')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="longitude" value="Longitude" />
                            <x-text-input id="longitude" name="longitude" type="number" step="any" min="-180" max="180"
                                          class="mt-1.5 block w-full" :value="old('longitude')" required />
                            <x-input-error :messages="$errors->get('longitude')" class="mt-1.5" />
                        </div>

                        <div class="flex items-end">
                            <button class="btn-brand w-full" type="submit">Simpan Koordinat Kelompok</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <script type="application/json" id="group-map-data">@json($mapGroups, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush
</x-app-layout>
