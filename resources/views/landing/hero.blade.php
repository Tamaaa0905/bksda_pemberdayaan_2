@php
    $kataPutar = ['kelompok binaan', 'bantuan usaha', 'nilai ekonomi', 'desa penyangga'];
    $kataAkhir = ['di', 'sekitar', 'kawasan', 'konservasi'];
    $jenisKawasan = ['Cagar Alam', 'Suaka Margasatwa', 'Taman Buru', 'Taman Wisata Alam'];

    // Partikel daun/cahaya yang naik perlahan (posisi dibuat deterministik)
    $partikel = collect(range(0, 15))->map(fn ($i) => [
        'left' => ($i * 53) % 100,
        'size' => 4 + ($i * 7) % 9,
        'delay' => -($i * 1.7),
        'dur' => 16 + ($i * 5) % 12,
        'dx' => ($i % 2 ? 1 : -1) * (20 + ($i * 11) % 50),
        'rise' => 520 + ($i * 37) % 380,
    ]);
@endphp

<section id="hero" data-pause-offscreen class="relative flex min-h-screen scroll-mt-24 items-center overflow-x-clip pb-24 pt-32 sm:pt-36">
    <!-- Latar aurora beranimasi -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <span class="blob blob-teal"></span>
        <span class="blob blob-sky"></span>
        <span class="blob blob-green"></span>
        <div class="absolute inset-0 grid place-items-center"><div class="aurora-conic"></div></div>

        <svg class="absolute left-1/2 top-1/2 size-[60rem] -translate-x-1/2 -translate-y-1/2 text-brand-500/15" viewBox="0 0 400 400" fill="none" stroke="currentColor" stroke-width="1">
            <ellipse cx="200" cy="200" rx="190" ry="150" /><ellipse cx="200" cy="200" rx="160" ry="120" />
            <ellipse cx="200" cy="200" rx="130" ry="92" /><ellipse cx="200" cy="200" rx="100" ry="66" />
        </svg>

        <div class="absolute inset-0 overflow-hidden">
            @foreach ($partikel as $p)
                <span class="particle" style="left: {{ $p['left'] }}%; width: {{ $p['size'] }}px; height: {{ $p['size'] }}px; animation-delay: {{ $p['delay'] }}s; animation-duration: {{ $p['dur'] }}s; --dx: {{ $p['dx'] }}px; --rise: {{ $p['rise'] }}px"></span>
            @endforeach
        </div>

        <i class="fa-solid fa-leaf absolute left-[6%] top-[30%] animate-float text-3xl text-brand-400/40"></i>
        <i class="fa-solid fa-seedling absolute right-[8%] top-[20%] animate-float-sm text-2xl text-emerald-400/40" style="animation-delay: -3s"></i>
        <div class="grain"></div>
    </div>

    <div class="relative mx-auto grid w-full max-w-6xl items-center gap-14 px-4 lg:grid-cols-[1.2fr_1fr]">
        <!-- Teks -->
        <div class="min-w-0">
            <h1 class="hero-title" aria-label="Dampingi dan pantau kelompok binaan di sekitar kawasan konservasi">
                <span aria-hidden="true">
                    <span class="block">
                        @foreach (['Dampingi', 'dan', 'pantau'] as $i => $kata)
                            <span class="word" style="--i: {{ $i }}">{{ $kata }}</span>
                        @endforeach
                    </span>
                    <span class="word block" style="--i: 3">
                        <span class="rotator">
                            @foreach ($kataPutar as $i => $kata)
                                <span style="--i: {{ $i }}">{{ $kata }}</span>
                            @endforeach
                        </span>
                    </span>
                    <span class="block">
                        @foreach ($kataAkhir as $i => $kata)
                            <span class="word" style="--i: {{ $i + 4 }}">{{ $kata }}</span>
                        @endforeach
                    </span>
                </span>
            </h1>

            <p class="mt-6 max-w-xl animate-rise text-base leading-relaxed text-ink-500 sm:text-lg" style="animation-delay: 900ms">
                Aplikasi ini membantu penyuluh pemberdayaan BKSDA Sulawesi Tengah mencatat kelompok binaan, bantuan, dan nilai ekonomi kawasan, lalu menampilkannya sebagai grafik yang mudah dibaca.
            </p>

            <div class="mt-8 flex animate-rise flex-wrap items-center gap-3" style="animation-delay: 1050ms">
                @auth
                    <a href="{{ route('dashboard') }}" data-magnetic class="btn-brand btn-glow btn-lg">Buka Dashboard</a>
                @else
                    <a href="{{ route('dashboard') }}" data-magnetic class="btn-brand btn-glow btn-lg">Lihat Dashboard</a>
                    <button type="button" data-magnetic @click="modal = 'login'" class="btn-ghost btn-glow btn-lg">Login Petugas / Admin</button>
                @endauth
                <a href="#fitur" class="group inline-flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-ink-700 transition-colors hover:text-brand-700">
                    Lihat fitur
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-hover:translate-y-1" aria-hidden="true"></i>
                </a>
            </div>

            <ul class="mt-10 flex animate-rise flex-wrap gap-2" style="animation-delay: 1200ms" aria-label="Jenis kawasan yang dipantau">
                @foreach ($jenisKawasan as $jenis)
                    <li class="tag transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700">
                        <span class="size-1.5 rounded-full bg-brand-500"></span>{{ $jenis }}
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Ilustrasi interaktif -->
        <div class="relative mx-auto w-full min-w-0 max-w-lg animate-rise py-8" style="animation-delay: 300ms">
            <div class="relative rounded-4xl border border-emerald-100 bg-white/95 p-5 shadow-2xl shadow-emerald-950/10 sm:p-7" role="region" aria-label="Ringkasan kegiatan kelompok binaan">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-serif text-lg font-bold tracking-tight text-ink-900 sm:text-xl">Ringkasan Kegiatan</h2>
                    <span class="shrink-0 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">
                        {{ $dashboardPreview['tahunNte'] ?? 'Terbaru' }}
                    </span>
                </div>

                <div class="mt-5 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-ink-500">Total Nilai Ekonomi (NTE)</p>
                        <p class="mt-1 text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">
                            <x-rupiah :value="$dashboardPreview['totalNte']" />
                        </p>
                    </div>
                    <p class="text-right text-xs text-ink-500">Akumulasi<br>per kawasan</p>
                </div>

                <div class="mt-3 h-44 min-w-0 sm:h-52">
                    <canvas id="chart-nte" role="img" aria-label="Grafik akumulasi NTE per kawasan dari data dashboard"></canvas>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
                    <div class="rounded-2xl bg-brand-50/80 p-4 sm:p-5">
                        <p class="text-xs font-medium text-ink-500">Kelompok Binaan</p>
                        <p class="mt-1 text-2xl font-extrabold tabular-nums tracking-tight text-ink-900 sm:text-3xl">
                            {{ number_format($dashboardPreview['jumlahKelompok'], 0, ',', '.') }}
                        </p>
                        <p class="mt-1 text-xs text-brand-700">kelompok tercatat</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50/70 p-4 sm:p-5">
                        <p class="text-xs font-medium text-ink-500">Total Bantuan</p>
                        <p class="mt-1 text-2xl font-extrabold tracking-tight text-brand-700 sm:text-3xl">
                            <x-rupiah :value="$dashboardPreview['totalBantuan']" />
                        </p>
                        <p class="mt-1 text-xs text-ink-500">fasilitas &amp; pemberdayaan</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <a href="#informasi" class="absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-1 text-xs font-medium text-ink-500 transition-colors hover:text-brand-700 lg:flex" aria-label="Gulir ke bawah">
        Gulir
        <i class="fa-solid fa-chevron-down animate-bounce" aria-hidden="true"></i>
    </a>
</section>
