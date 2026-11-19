<?php

namespace App\Services;

use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class DashboardDataService
{
    public const OFFICIAL_KAWASAN_NAMES = [
        'CA Morowali',
        'CA Pamona',
        'SM Bakiriang',
        'CA Tanjung Api',
        'CA Gunung Sojol',
        'TWA Tokobae',
        'CA Pangi Binangga',
        'TWA Bancea',
        'CA Gunung Dako',
        'SM Lombuyan',
        'SM Pinjan Tanjung Matop',
        'CA Gunung Tinombala',
        'SM Pulau Dolangan',
        'SM Tanjung Santigi',
        'TWA Wera',
        'SM Pulau Pasoso',
        'CA Pati-pati',
        'TB Londusa Tomata',
    ];

    private const TARGET_NTE = 1_575_000_000;

    private const CHART_COLORS = ['#0f8f84', '#35bfae', '#7fb4f0', '#a9cdf5', '#d3e6fb'];

    public function forDashboard(): array
    {
        $kawasans = $this->officialKawasans();
        $kelompoks = Kelompok::query()->with(['kawasan', 'desa'])->orderBy('id')->get();

        return $this->buildData($kawasans, $kelompoks);
    }

    public function forLanding(): array
    {
        $kawasans = $this->officialKawasans();
        $kelompoks = Kelompok::query()->orderBy('id')->get(['id', 'jenis_usaha', 'jumlah_bantuan']);
        $data = $this->buildData($kawasans, $kelompoks);

        return [
            'totalNte' => $data['totalNte'],
            'jumlahKawasan' => $data['jumlahKawasan'],
            'jumlahKelompok' => $data['jumlahKelompok'],
            'totalBantuan' => $data['totalBantuan'],
            'targetNte' => $data['targetNte'],
            'capaian' => $data['capaian'],
            'totalUsaha' => $data['totalUsaha'],
            'tahunNte' => $data['tahunNte'],
            'charts' => $data['chartData'],
        ];
    }

    private function officialKawasans(): Collection
    {
        return Kawasan::query()
            ->with('nte')
            ->whereIn('nama_kawasan', self::OFFICIAL_KAWASAN_NAMES)
            ->orderBy('nama_kawasan')
            ->get();
    }

    private function buildData(Collection $kawasans, Collection $kelompoks): array
    {
        $totalNte = (float) Nte::query()
            ->whereIn('kawasan_id', $kawasans->modelKeys())
            ->sum('total_nte');
        $jumlahKelompok = $kelompoks->count();
        $totalBantuan = (float) $kelompoks->sum('jumlah_bantuan');
        $capaian = $totalNte > 0 ? round($totalNte / self::TARGET_NTE * 100, 1) : 0;

        $nteKawasan = $kawasans->filter(fn (Kawasan $kawasan): bool => $kawasan->nte !== null);
        $tahunNte = $nteKawasan->first()?->nte?->tahun;
        $urutNte = $nteKawasan->sortByDesc(fn (Kawasan $kawasan): float => (float) $kawasan->nte->total_nte)->values();
        $kawasanTeratas = $urutNte->take(4);
        $topDelapan = $urutNte->take(8);
        $totalLangsung = (float) $nteKawasan->sum(fn (Kawasan $kawasan): float => (float) $kawasan->nte->nte_langsung);
        $porsiLangsung = $totalNte > 0 ? round($totalLangsung / $totalNte * 100, 1) : 0;

        $perUsaha = $kelompoks
            ->groupBy(fn (Kelompok $kelompok): string => $kelompok->jenis_usaha ?: 'Lainnya')
            ->map(fn (SupportCollection $kelompokUsaha): float => (float) $kelompokUsaha->sum('jumlah_bantuan'))
            ->sortDesc();
        $usahaTampil = $perUsaha->take(4);
        if (($sisa = (float) $perUsaha->slice(4)->sum()) > 0) {
            $usahaTampil->put('Lainnya', (float) ($usahaTampil['Lainnya'] ?? 0) + $sisa);
        }
        $totalUsaha = (float) $usahaTampil->sum();

        $kumulatif = 0;
        $chartData = [
            'nte' => [
                'labels' => $urutNte->pluck('nama_kawasan'),
                'each' => $urutNte->map(fn (Kawasan $kawasan): float => round($kawasan->nte->total_nte / 1e6, 1)),
                'values' => $urutNte->map(function (Kawasan $kawasan) use (&$kumulatif): float {
                    $kumulatif += $kawasan->nte->total_nte / 1e6;

                    return round($kumulatif, 1);
                }),
            ],
            'usaha' => [
                'labels' => $usahaTampil->keys(),
                'values' => $usahaTampil->values(),
                'colors' => array_slice(self::CHART_COLORS, 0, $usahaTampil->count()),
            ],
            'split' => [
                'labels' => $topDelapan->pluck('nama_kawasan'),
                'langsung' => $topDelapan->map(fn (Kawasan $kawasan): float => round($kawasan->nte->nte_langsung / 1e6, 1)),
                'tidakLangsung' => $topDelapan->map(fn (Kawasan $kawasan): float => round($kawasan->nte->nte_tidak_langsung / 1e6, 1)),
            ],
        ];

        return [
            'kawasans' => $kawasans,
            'kelompoks' => $kelompoks,
            'totalNte' => $totalNte,
            'jumlahKawasan' => count(self::OFFICIAL_KAWASAN_NAMES),
            'jumlahKelompok' => $jumlahKelompok,
            'totalBantuan' => $totalBantuan,
            'targetNte' => self::TARGET_NTE,
            'capaian' => $capaian,
            'nteKawasan' => $nteKawasan,
            'tahunNte' => $tahunNte,
            'urutNte' => $urutNte,
            'kawasanTeratas' => $kawasanTeratas,
            'topDelapan' => $topDelapan,
            'totalLangsung' => $totalLangsung,
            'porsiLangsung' => $porsiLangsung,
            'usahaTampil' => $usahaTampil,
            'totalUsaha' => $totalUsaha,
            'palet' => self::CHART_COLORS,
            'chartData' => $chartData,
        ];
    }
}
