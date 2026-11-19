<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="page-title">
                {{ $user->isAdmin() ? 'Profil Administrator' : 'Profil Petugas' }}
            </h2>
            <p class="page-sub">Kelola informasi akun dan keamanan Anda.</p>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="profile-page">
            <section class="profile-hero">
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
                    <span class="profile-hero-icon" aria-hidden="true">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="profile-section-kicker">Pengaturan Akun</p>
                        <h1 class="mt-1 break-words text-2xl font-semibold tracking-[-0.03em] text-ink-900 sm:text-3xl">
                            {{ $user->name }}
                        </h1>
                        <p class="mt-1 break-all text-sm text-ink-500">{{ $user->email }}</p>
                    </div>
                </div>
            </section>

            <section class="profile-card">
                <div class="{{ $user->isAdmin() ? 'max-w-3xl' : 'max-w-2xl' }}">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </section>

            @unless ($user->isAdmin())
                <section class="profile-card">
                    <div class="max-w-2xl">
                        @include('profile.partials.update-staff-information-form')
                    </div>
                </section>
            @endunless

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="profile-card">
                    @include('profile.partials.update-password-form')
                </section>

                <section class="profile-card self-start border-rose-200/80">
                    @include('profile.partials.delete-user-form')
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
