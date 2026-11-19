<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">{{ isset($kelompok) ? 'Edit Kelompok Binaan' : 'Tambah Kelompok Binaan' }}</h1>
        <p class="page-sub">{{ isset($kelompok) ? 'Perbarui data kelompok masyarakat di sekitar kawasan konservasi.' : 'Daftarkan kelompok masyarakat baru di sekitar kawasan konservasi.' }}</p>
    </x-slot>

    @if ($desas->isEmpty())
        <x-glass-card class="max-w-3xl sm:p-8">
            <p class="text-sm text-ink-600">
                @if (Auth::user()->isAdmin())
                    Belum ada data desa yang dapat dipilih untuk kelompok ini.
                @else
                    Anda belum memiliki desa binaan. Atur wilayah kerja sebelum mendaftarkan kelompok.
                @endif
            </p>
            @unless (Auth::user()->isAdmin())
                <a href="{{ route('profile.edit') }}" class="btn-brand mt-4 inline-flex">Atur Wilayah Binaan</a>
            @endunless
        </x-glass-card>
    @else
        <x-glass-card class="max-w-3xl sm:p-8">
        <form action="{{ isset($kelompok) ? route('kelompok.update', $kelompok->id) : route('kelompok.store') }}" method="POST" class="grid gap-5 sm:grid-cols-2"
              x-data="{ statusKeaktifan: @js(old('status_keaktifan', $kelompok->status_keaktifan ?? '')) }">
            @csrf
            @if (isset($kelompok))
                @method('PUT')
            @endif

            <div class="sm:col-span-2">
                <x-input-label for="nama_kelompok" value="Nama Kelompok" />
                <x-text-input id="nama_kelompok" name="nama_kelompok" type="text" class="mt-1.5 block w-full" :value="old('nama_kelompok', $kelompok->nama_kelompok ?? '')" required placeholder="Contoh: Kelompok Tani Hutan Lestari" />
                <x-input-error :messages="$errors->get('nama_kelompok')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="tahun_pembinaan" value="Tahun Pembinaan" />
                <x-text-input id="tahun_pembinaan" name="tahun_pembinaan" type="number" min="1900" max="2100"
                              class="mt-1.5 block w-full" :value="old('tahun_pembinaan', $kelompok->tahun_pembinaan ?? now()->year)" />
                <x-input-error :messages="$errors->get('tahun_pembinaan')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="kawasan_id" value="Kawasan Konservasi" />
                <x-select-input id="kawasan_id" name="kawasan_id" class="mt-1.5 block w-full" required>
                    <option value="">Pilih kawasan</option>
                    @foreach ($kawasans as $k)
                        <option value="{{ $k->id }}" @selected(old('kawasan_id', $kelompok->kawasan_id ?? null) == $k->id)>{{ $k->nama_kawasan }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('kawasan_id')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="desa_id" value="Desa Penyangga" />
                <x-select-input id="desa_id" name="desa_id" class="mt-1.5 block w-full" required>
                    <option value="">Pilih desa</option>
                    @foreach ($desas as $desa)
                        <option value="{{ $desa->id }}" @selected(old('desa_id', $kelompok->desa_id ?? null) == $desa->id)>
                            {{ $desa->nama_desa }} — {{ $desa->kawasan?->nama_kawasan ?? 'Kawasan tidak tercatat' }}
                        </option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('desa_id')" class="mt-1.5" />
                <p class="mt-1.5 text-xs text-ink-500">
                    @if (Auth::user()->isAdmin())
                        Pilih desa yang sesuai dengan kawasan konservasi kelompok.
                    @else
                        Pilih desa yang telah ditetapkan pada wilayah binaan Anda.
                    @endif
                </p>
            </div>

            <div>
                <x-input-label for="nama_ketua" value="Nama Ketua Kelompok" />
                <x-text-input id="nama_ketua" name="nama_ketua" type="text" class="mt-1.5 block w-full" :value="old('nama_ketua', $kelompok->nama_ketua ?? '')" required placeholder="Nama ketua" />
                <x-input-error :messages="$errors->get('nama_ketua')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="no_hp" value="Nomor HP / Kontak" />
                <x-text-input id="no_hp" name="no_hp" type="text" class="mt-1.5 block w-full" :value="old('no_hp', $kelompok->no_hp ?? '')" placeholder="08xxxxxxxxxx" />
                <x-input-error :messages="$errors->get('no_hp')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="kecamatan" value="Kecamatan" />
                <x-text-input id="kecamatan" name="kecamatan" type="text" class="mt-1.5 block w-full" :value="old('kecamatan', $kelompok->kecamatan ?? '')" placeholder="Contoh: Bualemo" />
                <x-input-error :messages="$errors->get('kecamatan')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="kabupaten" value="Kabupaten" />
                <x-text-input id="kabupaten" name="kabupaten" type="text" class="mt-1.5 block w-full" :value="old('kabupaten', $kelompok->kabupaten ?? '')" placeholder="Contoh: Banggai" />
                <x-input-error :messages="$errors->get('kabupaten')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="latitude" value="Latitude" />
                <x-text-input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" class="mt-1.5 block w-full" :value="old('latitude', $kelompok->latitude ?? '')" placeholder="Contoh: -0.9012" />
                <x-input-error :messages="$errors->get('latitude')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="longitude" value="Longitude" />
                <x-text-input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" class="mt-1.5 block w-full" :value="old('longitude', $kelompok->longitude ?? '')" placeholder="Contoh: 119.8701" />
                <x-input-error :messages="$errors->get('longitude')" class="mt-1.5" />
            </div>
            <p class="text-xs text-ink-500 sm:col-span-2">Koordinat bersifat opsional, tetapi latitude dan longitude harus diisi berpasangan. Salin koordinat desimal dari Google Maps untuk menentukan titik.</p>

            <div>
                <x-input-label for="jenis_usaha" value="Jenis Usaha" />
                <x-text-input id="jenis_usaha" name="jenis_usaha" type="text" class="mt-1.5 block w-full" :value="old('jenis_usaha', $kelompok->jenis_usaha ?? '')" required placeholder="Contoh: Budidaya ikan" />
                <x-input-error :messages="$errors->get('jenis_usaha')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="sk_kelompok" value="Nomor SK Kelompok" />
                <x-text-input id="sk_kelompok" name="sk_kelompok" type="text" class="mt-1.5 block w-full"
                              :value="old('sk_kelompok', $kelompok->sk_kelompok ?? '')" placeholder="Nomor SK kelompok" />
                <x-input-error :messages="$errors->get('sk_kelompok')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="jumlah_anggota" value="Jumlah Anggota Kelompok" />
                <x-text-input id="jumlah_anggota" name="jumlah_anggota" type="number" min="0"
                              class="mt-1.5 block w-full" :value="old('jumlah_anggota', $kelompok->jumlah_anggota ?? '')" placeholder="Contoh: 20" />
                <x-input-error :messages="$errors->get('jumlah_anggota')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="status_keaktifan" value="Status Keaktifan Kelompok" />
                <x-select-input id="status_keaktifan" name="status_keaktifan" class="mt-1.5 block w-full" x-model="statusKeaktifan" required>
                    <option value="">Pilih status</option>
                    <option value="aktif" @selected(old('status_keaktifan', $kelompok->status_keaktifan ?? '') === 'aktif')>Aktif</option>
                    <option value="tidak_aktif" @selected(old('status_keaktifan', $kelompok->status_keaktifan ?? '') === 'tidak_aktif')>Tidak aktif</option>
                </x-select-input>
                <x-input-error :messages="$errors->get('status_keaktifan')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2" x-show="statusKeaktifan === 'tidak_aktif'" x-cloak>
                <x-input-label for="kendala" value="Kendala Kelompok Tidak Aktif" />
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

            <div>
                <x-input-label for="jumlah_bantuan" value="Jumlah Bantuan (Rp)" />
                <x-text-input id="jumlah_bantuan" name="jumlah_bantuan" type="number" class="mt-1.5 block w-full" :value="old('jumlah_bantuan', $kelompok->jumlah_bantuan ?? '')" required placeholder="Contoh: 25000000" />
                <x-input-error :messages="$errors->get('jumlah_bantuan')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="hasil_usaha" value="Hasil / Keuntungan Usaha (Rp)" />
                <x-text-input id="hasil_usaha" name="hasil_usaha" type="number" min="0" step="0.01"
                              class="mt-1.5 block w-full" :value="old('hasil_usaha', $kelompok->hasil_usaha ?? '')"
                              placeholder="Opsional" />
                <x-input-error :messages="$errors->get('hasil_usaha')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between gap-3 sm:col-span-2">
                <a href="{{ route('dashboard') }}" class="btn-ghost">Kembali</a>
                <x-primary-button>{{ isset($kelompok) ? 'Perbarui Data' : 'Simpan Data' }}</x-primary-button>
            </div>
        </form>
        </x-glass-card>
    @endif
</x-app-layout>
