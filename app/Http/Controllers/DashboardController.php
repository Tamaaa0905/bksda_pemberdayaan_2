<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\KegiatanPemberdayaan;
use App\Models\Kelompok;
use App\Models\User;
use App\Services\DashboardDataService;
use App\Services\KawasanClusteringService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public static function buildKawasanClusterData(Collection $kawasans, float $totalNte): Collection
    {
        $ranked = $kawasans
            ->map(function ($kawasan) {
                return [
                    'nama_kawasan' => $kawasan->nama_kawasan,
                    'total_nte' => (float) ($kawasan->nte?->total_nte ?? 0),
                ];
            })
            ->sortByDesc('total_nte')
            ->values();

        $count = max(1, $ranked->count());
        $tinggiBatas = (int) ceil($count / 3);
        $sedangBatas = (int) ceil(($count * 2) / 3);

        return $kawasans->map(function ($kawasan) use ($ranked, $tinggiBatas, $sedangBatas, $totalNte) {
            $nilai = (float) ($kawasan->nte?->total_nte ?? 0);
            $rankIndex = $ranked->search(function ($row) use ($kawasan, $nilai) {
                return $row['nama_kawasan'] === $kawasan->nama_kawasan && abs((float) $row['total_nte'] - $nilai) < 0.01;
            });

            $rankIndex = $rankIndex === false ? $ranked->count() : $rankIndex;

            if ($rankIndex < $tinggiBatas) {
                $cluster = 'Tinggi';
            } elseif ($rankIndex < $sedangBatas) {
                $cluster = 'Sedang';
            } else {
                $cluster = 'Rendah';
            }

            return [
                'label' => $kawasan->nama_kawasan,
                'x' => round($nilai / 1e6, 1),
                'y' => (int) $kawasan->jumlah_desa_sekitar,
                'cluster' => $cluster,
                'clusterColor' => match ($cluster) {
                    'Tinggi' => '#16a34a',
                    'Sedang' => '#f59e0b',
                    default => '#ef4444',
                },
                'persentase' => $totalNte > 0 ? ($nilai / $totalNte) * 100 : 0,
            ];
        })->values();
    }

    public function index(
        Request $request,
        KawasanClusteringService $clusteringService,
        DashboardDataService $dashboardDataService
    ): View {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            $dashboardData = $dashboardDataService->forDashboard();
            $kelompokQuery = Kelompok::query()
                ->with(['kawasan', 'desa'])
                ->orderBy('nama_kelompok');

            if ($user) {
                $kelompokQuery->where('petugas_id', $user->id);
            }

            $kelompoks = $kelompokQuery->get();
            $totalNte = $dashboardData['totalNte'];
            $jumlahKawasan = $dashboardData['jumlahKawasan'];
            $jumlahKelompok = $dashboardData['jumlahKelompok'];
            $totalBantuan = $dashboardData['totalBantuan'];
            $capaian = $dashboardData['capaian'];

            return view('dashboard-petugas', compact(
                'kelompoks',
                'totalNte',
                'jumlahKawasan',
                'jumlahKelompok',
                'totalBantuan',
                'capaian'
            ));
        }

        $dashboardData = $dashboardDataService->forDashboard();
        $kawasans = $dashboardData['kawasans'];
        $kelompoks = $dashboardData['kelompoks'];
        $totalNte = $dashboardData['totalNte'];
        $jumlahKawasan = $dashboardData['jumlahKawasan'];
        $jumlahKelompok = $dashboardData['jumlahKelompok'];
        $totalBantuan = $dashboardData['totalBantuan'];
        $chartData = $dashboardData['chartData'];

        $clusterData = self::buildKawasanClusterData($kawasans, (float) $totalNte);

        $analisisNte = $kawasans->map(function ($kawasan) use ($totalNte) {
            $total = (float) ($kawasan->nte?->total_nte ?? 0);

            return [
                'nama_kawasan' => $kawasan->nama_kawasan,
                'total_nte' => $total,
                'persentase' => $total > 0 && $totalNte > 0 ? ($total / $totalNte) * 100 : 0,
            ];
        })->sortByDesc('total_nte')->values();

        $topKawasan = $analisisNte;

        $clusterAnalysis = $clusteringService->cluster($kawasans->map(fn (Kawasan $kawasan) => [
            'nama_kawasan' => $kawasan->nama_kawasan,
            'total_nte' => $kawasan->nte?->total_nte,
            'jumlah_desa_sekitar' => $kawasan->jumlah_desa_sekitar,
            'jumlah_desa_difasilitasi' => $kawasan->jumlah_desa_difasilitasi,
        ])->all());

        $clusterSummary = collect($clusterData)->groupBy('cluster')->map->count();

        $targetPemberdayaan = KegiatanPemberdayaan::query()
            ->where('tahun', 2026)
            ->where('jenis', 'target')
            ->orderBy('urutan')
            ->get();
        $agendaPemberdayaan = KegiatanPemberdayaan::query()
            ->where('tahun', 2026)
            ->where('jenis', 'agenda')
            ->orderBy('urutan')
            ->get();

        return view('dashboard', compact(
            'totalNte',
            'jumlahKawasan',
            'jumlahKelompok',
            'totalBantuan',
            'kawasans',
            'kelompoks',
            'analisisNte',
            'clusterAnalysis',
            'topKawasan',
            'clusterSummary',
            'targetPemberdayaan',
            'agendaPemberdayaan',
            'dashboardData',
            'chartData'
        ));
    }

    public function createKelompok(Request $request): View
    {
        ['kawasans' => $kawasans, 'desas' => $desas] = $this->groupFormOptions($request->user());

        return view('tambah_kelompok', compact('kawasans', 'desas'));
    }

    public function map(Request $request): View
    {
        $user = $request->user();
        $kelompokQuery = Kelompok::query()
            ->with([
                'kawasan:id,nama_kawasan',
                'desa:id,nama_desa,kecamatan,kabupaten,latitude,longitude',
            ]);

        if (! $user->isAdmin()) {
            $kelompokQuery->where('petugas_id', $user->id);
        }

        $kelompoks = $kelompokQuery
            ->orderBy('nama_kelompok')
            ->get([
                'id',
                'kawasan_id',
                'desa_id',
                'nama_kelompok',
                'nama_ketua',
                'sk_kelompok',
                'jenis_usaha',
                'produk_unggulan',
                'jumlah_anggota',
                'jumlah_bantuan',
                'status_keaktifan',
                'kendala',
                'butuh_bantuan_lanjutan',
                'butuh_pendampingan',
                'catatan_kebutuhan',
                'tahun_pembinaan',
                'kecamatan',
                'kabupaten',
                'latitude',
                'longitude',
            ]);

        $mapGroups = $kelompoks
            ->map(function (Kelompok $kelompok): ?array {
                $coordinates = $kelompok->latitude !== null && $kelompok->longitude !== null
                    ? $kelompok
                    : $kelompok->desa;

                if ($coordinates?->latitude === null || $coordinates?->longitude === null) {
                    return null;
                }

                return [
                    'nama_kelompok' => $kelompok->nama_kelompok,
                    'nama_ketua' => $kelompok->nama_ketua,
                    'nama_desa' => $kelompok->desa?->nama_desa,
                    'nama_kawasan' => $kelompok->kawasan?->nama_kawasan,
                    'kecamatan' => $kelompok->kecamatan ?? $kelompok->desa?->kecamatan,
                    'kabupaten' => $kelompok->kabupaten ?? $kelompok->desa?->kabupaten,
                    'jenis_usaha' => $kelompok->jenis_usaha,
                    'sk_kelompok' => $kelompok->sk_kelompok,
                    'produk_unggulan' => $kelompok->produk_unggulan,
                    'jumlah_anggota' => $kelompok->jumlah_anggota,
                    'jumlah_bantuan' => (float) $kelompok->jumlah_bantuan,
                    'status_keaktifan' => $kelompok->status_keaktifan,
                    'kendala' => $kelompok->kendala,
                    'butuh_bantuan_lanjutan' => $kelompok->butuh_bantuan_lanjutan,
                    'butuh_pendampingan' => $kelompok->butuh_pendampingan,
                    'catatan_kebutuhan' => $kelompok->catatan_kebutuhan,
                    'tahun_pembinaan' => $kelompok->tahun_pembinaan,
                    'latitude' => (float) $coordinates->latitude,
                    'longitude' => (float) $coordinates->longitude,
                ];
            })
            ->filter()
            ->values();

        return view('peta-kelompok', compact('kelompoks', 'mapGroups'));
    }

    public function updateDesaCoordinates(Request $request, Desa $desa): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $desa->update($validated);

        return redirect()
            ->route('kelompok.map')
            ->with('success', "Koordinat desa {$desa->nama_desa} berhasil disimpan.");
    }

    public function updateGroupCoordinates(Request $request, Kelompok $kelompok): RedirectResponse
    {
        $this->ensureCanManageGroup($request->user(), $kelompok);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $kelompok->update($validated);

        return redirect()
            ->route('kelompok.map')
            ->with('success', "Koordinat kelompok {$kelompok->nama_kelompok} berhasil disimpan.");
    }

    public function editKelompok(Request $request, Kelompok $kelompok): View
    {
        $this->ensureCanManageGroup($request->user(), $kelompok);
        ['kawasans' => $kawasans, 'desas' => $desas] = $this->groupFormOptions(
            $request->user(),
            onlyOfficialKawasans: false
        );

        return view('tambah_kelompok', compact('kelompok', 'kawasans', 'desas'));
    }

    public function editGroupStatus(Request $request, Kelompok $kelompok): View
    {
        $this->ensureCanManageGroup($request->user(), $kelompok);

        return view('status_kelompok', compact('kelompok'));
    }

    public function storeKelompok(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->groupRules($request, onlyOfficialKawasans: true));
        $validated = $this->clearIrrelevantProgressFields($validated);
        $validated['tahun_pembinaan'] ??= 2026;
        $validated['jumlah_anggota'] ??= 0;

        if (! $request->user()->isAdmin()) {
            $validated['petugas_id'] = $request->user()->id;
        }

        Kelompok::create($validated);

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil ditambahkan!');
    }

    public function updateKelompok(Request $request, Kelompok $kelompok): RedirectResponse
    {
        $this->ensureCanManageGroup($request->user(), $kelompok);
        $validated = $request->validate($this->groupRules($request));
        $validated = $this->clearIrrelevantProgressFields($validated);

        $kelompok->update($validated);

        return redirect()->route('dashboard')->with('success', 'Data kelompok binaan berhasil diperbarui!');
    }

    public function updateGroupStatus(Request $request, Kelompok $kelompok): RedirectResponse
    {
        $this->ensureCanManageGroup($request->user(), $kelompok);
        $validated = $request->validate($this->groupProgressRules());
        $validated = $this->clearIrrelevantProgressFields($validated);

        $kelompok->update($validated);

        return redirect()
            ->route('dashboard')
            ->with('success', "Status kelompok {$kelompok->nama_kelompok} berhasil diperbarui.");
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    private function groupRules(Request $request, bool $onlyOfficialKawasans = false): array
    {
        $desaRules = [
            'required',
            Rule::exists('desas', 'id')->where('kawasan_id', $request->input('kawasan_id')),
        ];

        if (! $request->user()->isAdmin()) {
            $desaRules[] = Rule::exists('desa_user', 'desa_id')->where('user_id', $request->user()->id);
        }

        $kawasanRules = $onlyOfficialKawasans
            ? ['required', Rule::in($this->officialKawasanIds())]
            : ['required', 'exists:kawasans,id'];

        return array_merge([
            'nama_kelompok' => ['required', 'string', 'max:255'],
            'kawasan_id' => $kawasanRules,
            'desa_id' => $desaRules,
            'tahun_pembinaan' => ['nullable', 'integer', 'between:1900,2100'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'kabupaten' => ['nullable', 'string', 'max:255'],
            'nama_ketua' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'jenis_usaha' => ['required', 'string', 'max:255'],
            'sk_kelompok' => ['nullable', 'string', 'max:255'],
            'jumlah_anggota' => ['nullable', 'integer', 'min:0'],
            'jumlah_bantuan' => ['required', 'numeric', 'min:0'],
            'hasil_usaha' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ], $this->groupProgressRules());
    }

    /**
     * @return array<int, int>
     */
    private function officialKawasanIds(): array
    {
        return Kawasan::query()
            ->whereIn('nama_kawasan', DashboardDataService::OFFICIAL_KAWASAN_NAMES)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array{kawasans: \Illuminate\Database\Eloquent\Collection<int, Kawasan>, desas: \Illuminate\Database\Eloquent\Collection<int, Desa>}
     */
    private function groupFormOptions(User $user, bool $onlyOfficialKawasans = true): array
    {
        $desaQuery = Desa::query()
            ->with('kawasan:id,nama_kawasan')
            ->when(! $user->isAdmin(), function (EloquentBuilder $query) use ($user): void {
                $query->whereIn('id', $user->desas()->select('desas.id'));
            })
            ->when($onlyOfficialKawasans, function (EloquentBuilder $query): void {
                $query->whereHas('kawasan', function (EloquentBuilder $query): void {
                    $query->whereIn('nama_kawasan', DashboardDataService::OFFICIAL_KAWASAN_NAMES);
                });
            })
            ->orderBy('nama_desa');
        $desas = $desaQuery->get(['id', 'kawasan_id', 'nama_desa']);

        $kawasanQuery = Kawasan::query()
            ->whereIn('id', $desas->pluck('kawasan_id')->filter()->unique())
            ->orderBy('nama_kawasan');

        if ($onlyOfficialKawasans) {
            $kawasanQuery->whereIn('nama_kawasan', DashboardDataService::OFFICIAL_KAWASAN_NAMES);
        }

        return [
            'kawasans' => $kawasanQuery->get(),
            'desas' => $desas,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function groupProgressRules(): array
    {
        return [
            'status_keaktifan' => 'required|in:aktif,tidak_aktif',
            'kendala' => 'nullable|required_if:status_keaktifan,tidak_aktif|string|max:2000',
            'butuh_bantuan_lanjutan' => 'nullable|required_if:status_keaktifan,aktif|boolean',
            'butuh_pendampingan' => 'nullable|required_if:status_keaktifan,aktif|boolean',
            'catatan_kebutuhan' => 'nullable|string|max:2000',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function clearIrrelevantProgressFields(array $validated): array
    {
        if ($validated['status_keaktifan'] === 'tidak_aktif') {
            $validated['butuh_bantuan_lanjutan'] = null;
            $validated['butuh_pendampingan'] = null;
        } else {
            $validated['kendala'] = null;
        }

        return $validated;
    }

    private function ensureCanManageGroup(User $user, Kelompok $kelompok): void
    {
        abort_unless($user->isAdmin() || $kelompok->petugas_id === $user->id, 403);
    }
}
