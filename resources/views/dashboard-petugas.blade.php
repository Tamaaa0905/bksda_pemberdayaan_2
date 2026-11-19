@php
    $totalKelompok = $jumlahKelompok;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Informasi kelompok binaan di wilayah Sulawesi Tengah.</p>
    </x-slot>

    <x-slot name="actions">
        @guest
            <a href="{{ route('login') }}" class="btn-ghost">Login Petugas / Admin</a>
        @endguest
    </x-slot>

    <div class="flex flex-col gap-4">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-ink-900 sm:text-2xl">
                Data Kelompok Binaan
                <span aria-hidden="true">👋</span>
            </h2>
            <p class="mt-1 text-sm text-ink-500">Ringkasan kelompok masyarakat yang terdata di sekitar kawasan konservasi.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card icon="wallet" tone="teal" label="Total Nilai Ekonomi (NTE)" :chip="number_format($capaian, 1, ',', '.').'%'" note="dari target Rp 1,575 M">
                <x-rupiah :value="$totalNte" />
            </x-stat-card>
            <x-stat-card icon="mountain-sun" tone="green" label="Kawasan Konservasi" note="CA, SM, TB & TWA">
                {{ number_format($jumlahKawasan, 0, ',', '.') }} Wilayah
            </x-stat-card>
            <x-stat-card icon="users" tone="purple" label="Kelompok Binaan" note="Aktif terverifikasi">
                {{ number_format($jumlahKelompok, 0, ',', '.') }} Kelompok
            </x-stat-card>
            <x-stat-card icon="hand-holding-dollar" tone="blue" label="Total Penyaluran Bantuan" note="Fasilitas & pemberdayaan">
                <x-rupiah :value="$totalBantuan" />
            </x-stat-card>
        </div>

        <div class="grid items-start gap-4 xl:grid-cols-[minmax(16rem,0.75fr)_minmax(0,1.8fr)]">
            <x-glass-card title="Akses Data">
                <div class="flex flex-col gap-4">
                    <x-icon-badge icon="eye" tone="teal" class="size-11 text-base" />
                    <p class="text-sm leading-6 text-ink-600">
                        Dashboard dapat dilihat tanpa login. Petugas login untuk mengedit kelompok yang ditugaskan kepadanya; penambahan data tetap dibatasi.
                    </p>
                    @guest
                        <a href="{{ route('login') }}" class="btn-brand w-full">Login Petugas / Admin</a>
                    @endguest
                </div>
            </x-glass-card>

            <x-glass-card id="kelompok" title="Daftar Kelompok Binaan" class="scroll-mt-6">
                <x-slot name="action">
                    <span class="card-pill">{{ number_format($totalKelompok, 0, ',', '.') }} kelompok</span>
                </x-slot>

                @if ($kelompoks->isEmpty())
                    <div class="empty-state">
                        <i class="fa-solid fa-people-group text-2xl text-ink-400" aria-hidden="true"></i>
                        Belum ada data kelompok binaan yang terdaftar.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="glass-table min-w-[96rem]">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun</th>
                                    <th>Kawasan</th>
                                    <th>Desa</th>
                                    <th>Kecamatan</th>
                                    <th>Kabupaten</th>
                                    <th>Nama Kelompok</th>
                                    <th>Nama Ketua</th>
                                    <th>No HP</th>
                                    <th>Jenis Usaha</th>
                                    <th>SK Kelompok</th>
                                    <th>Anggota Kelompok</th>
                                    <th>Jumlah Bantuan</th>
                                    <th>Hasil / Keuntungan Usaha</th>
                                    <th>Status &amp; Kebutuhan</th>
                                    @if (auth()->check() && ! auth()->user()->isAdmin())
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kelompoks as $kelompok)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $kelompok->tahun_pembinaan ?? '-' }}</td>
                                        <td>{{ $kelompok->kawasan?->nama_kawasan ?? '-' }}</td>
                                        <td>{{ $kelompok->data_sumber['D'] ?? $kelompok->desa?->nama_desa ?? '-' }}</td>
                                        <td>{{ $kelompok->kecamatan ?? $kelompok->desa?->kecamatan ?? '-' }}</td>
                                        <td>{{ $kelompok->kabupaten ?? $kelompok->desa?->kabupaten ?? '-' }}</td>
                                        <td>
                                            <p class="font-semibold text-ink-900">{{ $kelompok->nama_kelompok }}</p>
                                            <p class="mt-1 text-xs text-ink-500">ID: KLP-00{{ $kelompok->id }}</p>
                                        </td>
                                        <td>{{ $kelompok->nama_ketua ?: '-' }}</td>
                                        <td class="whitespace-nowrap">{{ $kelompok->no_hp ?: '-' }}</td>
                                        <td>{{ $kelompok->jenis_usaha ?: '-' }}</td>
                                        <td>{{ $kelompok->sk_kelompok ?: '-' }}</td>
                                        <td>{{ $kelompok->data_sumber['L'] ?? $kelompok->jumlah_anggota ?? 0 }}</td>
                                        <td class="whitespace-nowrap font-semibold text-brand-700">
                                            <x-rupiah :value="$kelompok->jumlah_bantuan" full />
                                        </td>
                                        <td class="whitespace-nowrap">
                                            @if ($kelompok->hasil_usaha !== null)
                                                <x-rupiah :value="$kelompok->hasil_usaha" full />
                                            @else
                                                <span class="text-ink-500">Belum didata</span>
                                            @endif
                                        </td>
                                        <td class="min-w-[220px]">
                                            @if ($kelompok->status_keaktifan === 'aktif')
                                                <span class="tag">Aktif</span>
                                                <p class="mt-1 text-xs">Bantuan lanjutan: {{ $kelompok->butuh_bantuan_lanjutan === null ? 'Belum didata' : ($kelompok->butuh_bantuan_lanjutan ? 'Ya' : 'Tidak') }}</p>
                                                <p class="text-xs">Pendampingan: {{ $kelompok->butuh_pendampingan === null ? 'Belum didata' : ($kelompok->butuh_pendampingan ? 'Ya' : 'Tidak') }}</p>
                                                @if ($kelompok->catatan_kebutuhan)
                                                    <p class="mt-1 whitespace-normal text-xs text-ink-500">{{ $kelompok->catatan_kebutuhan }}</p>
                                                @endif
                                            @elseif ($kelompok->status_keaktifan === 'tidak_aktif')
                                                <span class="tag">Tidak aktif</span>
                                                <p class="mt-1 whitespace-normal text-xs">Kendala: {{ $kelompok->kendala ?: 'Belum dicatat' }}</p>
                                            @else
                                                <span class="tag">Belum didata</span>
                                            @endif
                                        </td>
                                        @if (auth()->check() && ! auth()->user()->isAdmin())
                                            <td>
                                                <a href="{{ route('kelompok.edit', $kelompok) }}" class="btn-icon"
                                                   title="Edit data"
                                                   aria-label="Edit data {{ $kelompok->nama_kelompok }}">
                                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-glass-card>
        </div>
    </div>
</x-app-layout>
