<section>
    <header class="profile-section-header">
        <span class="profile-section-icon" aria-hidden="true">
            <i class="fa-solid fa-id-card"></i>
        </span>
        <div>
            <p class="profile-section-kicker">Informasi Akun</p>
            <h2 class="profile-section-title">
                {{ $user->isAdmin() ? 'Identitas Pegawai / Administrator' : 'Informasi Profil' }}
            </h2>

            <p class="profile-section-description">
                {{ $user->isAdmin() ? 'Lengkapi identitas pegawai, unit kerja, wilayah tanggung jawab, dan kontak resmi.' : 'Perbarui informasi profil dan alamat email akun Anda.' }}
            </p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}"
          class="{{ $user->isAdmin() ? 'mt-6 grid gap-5 sm:grid-cols-2' : 'mt-6 space-y-6' }}">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="$user->isAdmin() ? 'Nama Lengkap' : 'Nama'" />
            <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        @if ($user->isAdmin())
            <div>
                <x-input-label for="nip" value="NIP (Nomor Induk Pegawai)" />
                <x-text-input id="nip" name="nip" type="text" class="mt-1.5 block w-full"
                              :value="old('nip', $user->nip)" />
                <x-input-error :messages="$errors->get('nip')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="pangkat_golongan" value="Pangkat / Golongan" />
                <x-text-input id="pangkat_golongan" name="pangkat_golongan" type="text" class="mt-1.5 block w-full"
                              :value="old('pangkat_golongan', $user->pangkat_golongan)"
                              placeholder="Contoh: Pembina / IV-a" />
                <x-input-error :messages="$errors->get('pangkat_golongan')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="jabatan" value="Jabatan" />
                <x-text-input id="jabatan" name="jabatan" type="text" class="mt-1.5 block w-full"
                              :value="old('jabatan', $user->jabatan)"
                              placeholder="Contoh: Kepala Seksi Wilayah" />
                <x-input-error :messages="$errors->get('jabatan')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="unit_kerja" value="Unit Kerja / Instansi" />
                <x-text-input id="unit_kerja" name="unit_kerja" type="text" class="mt-1.5 block w-full"
                              :value="old('unit_kerja', $user->unit_kerja)"
                              placeholder="Contoh: Balai Besar KSDA / Kantor Utama" />
                <x-input-error :messages="$errors->get('unit_kerja')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="wilayah_tanggung_jawab" value="Wilayah Tanggung Jawab" />
                <x-text-input id="wilayah_tanggung_jawab" name="wilayah_tanggung_jawab" type="text"
                              class="mt-1.5 block w-full"
                              :value="old('wilayah_tanggung_jawab', $user->wilayah_tanggung_jawab)"
                              placeholder="Contoh: Seluruh Resort & Seksi" />
                <x-input-error :messages="$errors->get('wilayah_tanggung_jawab')" class="mt-1.5" />
            </div>

        @endif

        <div>
            <x-input-label for="email" :value="$user->isAdmin() ? 'Email Resmi' : 'Email'" />
            <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        Alamat email Anda belum diverifikasi.

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            Kirim ulang email verifikasi.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            Tautan verifikasi baru telah dikirim ke alamat email Anda.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($user->isAdmin())
            <div>
                <x-input-label for="no_telepon" value="Nomor Telepon / WhatsApp" />
                <x-text-input id="no_telepon" name="no_telepon" type="tel" class="mt-1.5 block w-full"
                              :value="old('no_telepon', $user->no_telepon)" placeholder="08xxxxxxxxxx"
                              autocomplete="tel" />
                <x-input-error :messages="$errors->get('no_telepon')" class="mt-1.5" />
            </div>
        @endif

        <div class="{{ $user->isAdmin() ? 'sm:col-span-2' : '' }} flex items-center gap-4">
            <x-primary-button>Simpan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ $user->isAdmin() ? 'Profil administrator berhasil disimpan.' : 'Profil berhasil disimpan.' }}</p>
            @endif
        </div>
    </form>
</section>
