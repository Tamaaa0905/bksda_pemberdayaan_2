<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Perbarui Status Kelompok</h1>
        <p class="page-sub">{{ $kelompok->nama_kelompok }} — {{ $kelompok->desa?->nama_desa ?? 'Desa belum tercatat' }}</p>
    </x-slot>

    <x-glass-card class="max-w-3xl sm:p-8">
        <form action="{{ route('kelompok.status.update', $kelompok) }}" method="POST" class="grid gap-5 sm:grid-cols-2"
              x-data="{ statusKeaktifan: @js(old('status_keaktifan', $kelompok->status_keaktifan ?? '')) }">
            @csrf
            @method('PUT')

            <div class="sm:col-span-2">
                <x-input-label for="status_keaktifan" value="Status Keaktifan Kelompok" />
                <x-select-input id="status_keaktifan" name="status_keaktifan" class="mt-1.5 block w-full" x-model="statusKeaktifan" required>
                    <option value="">Pilih status</option>
                    <option value="aktif" @selected(old('status_keaktifan', $kelompok->status_keaktifan ?? '') === 'aktif')>Aktif</option>
                    <option value="tidak_aktif" @selected(old('status_keaktifan', $kelompok->status_keaktifan ?? '') === 'tidak_aktif')>Tidak aktif</option>
                </x-select-input>
                <x-input-error :messages="$errors->get('status_keaktifan')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2" x-show="statusKeaktifan === 'tidak_aktif'" x-cloak>
                <x-input-label for="kendala" value="Kendala jika kelompok tidak aktif" />
                <textarea id="kendala" name="kendala" rows="3" class="field mt-1.5 block w-full"
                          x-bind:disabled="statusKeaktifan !== 'tidak_aktif'"
                          placeholder="Wajib diisi jika kelompok tidak aktif">{{ old('kendala', $kelompok->kendala ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('kendala')" class="mt-1.5" />
            </div>

            <div class="grid gap-5 sm:col-span-2 sm:grid-cols-2" x-show="statusKeaktifan === 'aktif'" x-cloak>
                <div>
                    <x-input-label for="butuh_bantuan_lanjutan" value="Butuh Bantuan Lanjutan?" />
                    <x-select-input id="butuh_bantuan_lanjutan" name="butuh_bantuan_lanjutan" class="mt-1.5 block w-full"
                                    x-bind:disabled="statusKeaktifan !== 'aktif'">
                        <option value="">Pilih jawaban jika kelompok aktif</option>
                        <option value="1" @selected(old('butuh_bantuan_lanjutan', $kelompok->butuh_bantuan_lanjutan ?? '') == '1')>Ya</option>
                        <option value="0" @selected(old('butuh_bantuan_lanjutan', $kelompok->butuh_bantuan_lanjutan ?? '') == '0')>Tidak</option>
                    </x-select-input>
                    <x-input-error :messages="$errors->get('butuh_bantuan_lanjutan')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="butuh_pendampingan" value="Butuh Pendampingan?" />
                    <x-select-input id="butuh_pendampingan" name="butuh_pendampingan" class="mt-1.5 block w-full"
                                    x-bind:disabled="statusKeaktifan !== 'aktif'">
                        <option value="">Pilih jawaban jika kelompok aktif</option>
                        <option value="1" @selected(old('butuh_pendampingan', $kelompok->butuh_pendampingan ?? '') == '1')>Ya</option>
                        <option value="0" @selected(old('butuh_pendampingan', $kelompok->butuh_pendampingan ?? '') == '0')>Tidak</option>
                    </x-select-input>
                    <x-input-error :messages="$errors->get('butuh_pendampingan')" class="mt-1.5" />
                </div>
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="catatan_kebutuhan" value="Catatan Kebutuhan atau Tindak Lanjut" />
                <textarea id="catatan_kebutuhan" name="catatan_kebutuhan" rows="3" class="field mt-1.5 block w-full"
                          placeholder="Jelaskan bantuan atau pendampingan yang diperlukan">{{ old('catatan_kebutuhan', $kelompok->catatan_kebutuhan ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('catatan_kebutuhan')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between gap-3 sm:col-span-2">
                <a href="{{ route('dashboard') }}" class="btn-ghost">Kembali</a>
                <x-primary-button>Simpan Status</x-primary-button>
            </div>
        </form>
    </x-glass-card>
</x-app-layout>
