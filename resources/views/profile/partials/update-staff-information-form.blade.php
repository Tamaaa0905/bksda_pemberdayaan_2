@php
    $selectedDesaIds = old('desa_binaan', $selectedDesaIds);
    $selectedDesaIds = array_values(array_map('strval', is_array($selectedDesaIds) ? $selectedDesaIds : []));

    if ($selectedDesaIds === []) {
        $selectedDesaIds = [''];
    }
@endphp

<section>
    <header class="profile-section-header">
        <span class="profile-section-icon" aria-hidden="true">
            <i class="fa-solid fa-map-location-dot"></i>
        </span>
        <div>
            <p class="profile-section-kicker">Wilayah Kerja</p>
            <h2 class="profile-section-title">Profil Kepegawaian dan Wilayah Binaan</h2>
            <p class="profile-section-description">Lengkapi identitas resmi dan desa yang menjadi wilayah tugas Anda.</p>
        </div>
    </header>

    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2"
          x-data='{ kecamatan: @json($selectedKecamatan), kecamatanKey: @json($selectedKecamatanKey), desaRows: @json($selectedDesaIds), desaOptions: @json($desaOptions), filteredDesas() { if (!this.kecamatanKey) return []; return this.desaOptions.filter((desa) => desa.kecamatan_key === this.kecamatanKey); } }'>
        @csrf
        @method('PATCH')
        <input type="hidden" name="update_work_area" value="1">

        <div>
            <x-input-label for="nip" value="NIP" />
            <x-text-input id="nip" name="nip" type="text" class="mt-1.5 block w-full"
                          :value="old('nip', $user->nip)" required />
            <x-input-error :messages="$errors->get('nip')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="pangkat_golongan" value="Pangkat/Golongan" />
            <x-text-input id="pangkat_golongan" name="pangkat_golongan" type="text" class="mt-1.5 block w-full"
                          :value="old('pangkat_golongan', $user->pangkat_golongan)" required
                          placeholder="Contoh: Penata / III-c" />
            <x-input-error :messages="$errors->get('pangkat_golongan')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="jabatan" value="Jabatan" />
            <x-text-input id="jabatan" name="jabatan" type="text" class="mt-1.5 block w-full"
                          :value="old('jabatan', $user->jabatan)" required />
            <x-input-error :messages="$errors->get('jabatan')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="unit_kerja" value="Unit Kerja" />
            <x-text-input id="unit_kerja" name="unit_kerja" type="text" class="mt-1.5 block w-full"
                          :value="old('unit_kerja', $user->unit_kerja)" required />
            <x-input-error :messages="$errors->get('unit_kerja')" class="mt-1.5" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="kecamatan_binaan" value="Kecamatan Binaan" />
            <select id="kecamatan_binaan" name="kecamatan_binaan" x-model="kecamatan"
                    @change="kecamatanKey = $event.target.selectedOptions[0].dataset.kecamatanKey || ''; desaRows = ['']"
                    class="field mt-1.5 block w-full" required>
                <option value="">Pilih kecamatan</option>
                @foreach ($kecamatanOptions as $kecamatanOption)
                    <option value="{{ $kecamatanOption['value'] }}" data-kecamatan-key="{{ $kecamatanOption['key'] }}"
                            @selected($selectedKecamatan === $kecamatanOption['value'])>
                        {{ $kecamatanOption['value'] }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('kecamatan_binaan')" class="mt-1.5" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label value="Desa/Kelurahan Binaan" />
            <div class="mt-2 grid gap-3">
                <template x-for="(desaId, index) in desaRows" :key="index">
                    <div class="flex items-start gap-2">
                        <select name="desa_binaan[]" x-model="desaRows[index]" class="field block w-full"
                                aria-label="Pilih desa binaan" required>
                            <option value="">Pilih desa/kelurahan</option>
                            <template x-for="desa in filteredDesas()" :key="desa.id">
                                <option :value="desa.id" x-text="desa.label"></option>
                            </template>
                        </select>
                        <button type="button" class="btn-ghost shrink-0"
                                @click="desaRows.splice(index, 1)" aria-label="Hapus desa binaan">
                            Hapus
                        </button>
                    </div>
                </template>
            </div>
            <button type="button" class="btn-ghost mt-3"
                    @click="desaRows.push('')">
                Tambah Desa/Kelurahan
            </button>
            <x-input-error :messages="$errors->get('desa_binaan')" class="mt-1.5" />
            @foreach ($errors->getMessages() as $field => $messages)
                @if (str_starts_with($field, 'desa_binaan.'))
                    <x-input-error :messages="$messages" class="mt-1.5" />
                @endif
            @endforeach
        </div>

        <div class="flex items-center gap-4 sm:col-span-2">
            <x-primary-button>Simpan Profil Petugas</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p class="text-sm text-emerald-700" role="status">Profil petugas berhasil disimpan.</p>
            @endif
        </div>
    </form>
</section>
