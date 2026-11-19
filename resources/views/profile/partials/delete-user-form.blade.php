<section class="space-y-6">
    <header class="profile-section-header border-rose-100">
        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-600" aria-hidden="true">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </span>
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-600">Zona Berisiko</p>
            <h2 class="profile-section-title">Hapus Akun</h2>
            <p class="profile-section-description">Penghapusan akun akan menghapus data akun secara permanen dan tidak dapat dibatalkan.</p>
        </div>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold tracking-tight text-gray-900">
                Apakah Anda yakin ingin menghapus akun?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Seluruh data akun akan dihapus secara permanen. Masukkan kata sandi untuk mengonfirmasi tindakan ini.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata Sandi" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Kata Sandi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus Akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
