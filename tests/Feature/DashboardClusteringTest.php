<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\KegiatanPemberdayaan;
use App\Models\Kelompok;
use App\Models\Nte;
use App\Models\User;
use Database\Seeders\BksdaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardClusteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_real_clustering_results_for_valid_kawasan_data(): void
    {
        $this->seed(BksdaSeeder::class);

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $response = $this->actingAs($admin)
            ->get('/dashboard');

        $response->assertOk()
            ->assertSee('Data Mining: K-Means Clustering Kawasan')
            ->assertSee('Validasi silhouette')
            ->assertSee('NTE Rendah')
            ->assertSee('NTE Tinggi')
            ->assertSee('chart-cluster')
            ->assertDontSee('Target dan Kegiatan Pemberdayaan 2026')
            ->assertDontSee('Penyusunana SK PK');
        $response->assertViewHas('clusterAnalysis', fn (array $analysis): bool => $analysis['status'] === 'ok'
            && $analysis['sample_count'] === 15
            && $analysis['excluded_count'] === 3
            && count($analysis['clusters']) === 3
            && array_sum(array_column($analysis['clusters'], 'count')) === 15
            && $analysis['silhouette'] !== null);
        $response->assertDontSee('Catatan rekonsiliasi sumber NTE');
    }

    public function test_landing_preview_uses_the_same_metrics_and_charts_as_the_dashboard(): void
    {
        $this->seed(BksdaSeeder::class);

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $dashboardResponse = $this->actingAs($admin)->get('/dashboard');
        $landingResponse = $this->get('/');

        $dashboardResponse->assertOk();
        $landingResponse->assertOk()
            ->assertSee('Ringkasan Kegiatan')
            ->assertSee('Total Bantuan')
            ->assertDontSee('Bantuan per Jenis Usaha')
            ->assertDontSee('Komposisi NTE');

        $preview = $landingResponse->viewData('dashboardPreview');
        $dashboardCharts = json_decode(
            json_encode($dashboardResponse->viewData('chartData'), JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $landingCharts = json_decode(
            json_encode($preview['charts'], JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(1_529_209_000.0, $dashboardResponse->viewData('totalNte'));
        $this->assertSame($dashboardResponse->viewData('totalNte'), $preview['totalNte']);
        $this->assertSame($dashboardResponse->viewData('jumlahKelompok'), $preview['jumlahKelompok']);
        $this->assertSame($dashboardCharts, $landingCharts);
    }

    public function test_dashboard_displays_kecamatan_and_kabupaten_from_the_related_desa(): void
    {
        $this->seed(BksdaSeeder::class);
        $kawasan = Kawasan::where('nama_kawasan', 'SM Bakiriang')->firstOrFail();
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Uji Lokasi',
            'kecamatan' => 'Kecamatan Uji Lokasi',
            'kabupaten' => 'Kabupaten Uji Lokasi',
        ]);
        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Uji Lokasi',
        ]);

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $response = $this->actingAs($admin)
            ->get('/dashboard');

        $response->assertOk()
            ->assertSee('Kecamatan Uji Lokasi')
            ->assertSee('Kabupaten Uji Lokasi');
    }

    public function test_bksda_seeder_imports_all_group_rows_and_preserves_source_values(): void
    {
        $this->seed(BksdaSeeder::class);

        $this->assertDatabaseCount('kelompoks', 79);
        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Tani Konservasi Tambayoli',
            'no_hp' => '0822 6262 8325',
            'jumlah_anggota' => 12,
        ]);

        $kelompok = Kelompok::query()->where('nama_kelompok', 'Tani Konservasi Tambayoli')->firstOrFail();

        $this->assertSame('12 (P)', $kelompok->data_sumber['L']);
        $this->assertSame('CA. Morowali', $kelompok->kawasan->nama_kawasan);
        $this->assertSame('7212072003', $kelompok->desa->kode_desa);

        $this->assertSame(145, Desa::query()->whereNotNull('data_sumber')->count());
        $this->assertDatabaseHas('desas', [
            'kode_desa' => '7203312008',
            'nama_desa' => 'MANIMBAYA',
            'kecamatan' => 'BALAESANG TANJUNG',
            'kabupaten' => 'DONGGALA',
            'jumlah_penduduk' => 1089,
            'jumlah_penutupan_kk' => 277,
        ]);

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tambayoli')
            ->assertSee('12 (P)');
    }

    public function test_bksda_seeder_imports_pdf_nte_and_activities_idempotently(): void
    {
        $tanjungApi = Kawasan::create([
            'nama_kawasan' => 'CA Tanjung Api',
            'fungsi_kawasan' => 'CA',
        ]);
        Nte::create([
            'kawasan_id' => $tanjungApi->id,
            'tahun' => 2024,
            'nte_langsung' => 100,
            'nte_tidak_langsung' => 200,
            'total_nte' => 300,
        ]);
        $staleKawasan = Kawasan::create([
            'nama_kawasan' => 'CA Saluki',
            'fungsi_kawasan' => 'CA',
        ]);
        Nte::create([
            'kawasan_id' => $staleKawasan->id,
            'tahun' => 2025,
            'nte_langsung' => 100,
            'nte_tidak_langsung' => 200,
            'total_nte' => 300,
        ]);

        $this->seed(BksdaSeeder::class);
        $this->seed(BksdaSeeder::class);

        $this->assertDatabaseCount('ntes', 19);
        $this->assertSame(18, Nte::query()->where('tahun', 2025)->count());
        $this->assertSame(1_529_209_000, (int) Nte::query()->where('tahun', 2025)->sum('total_nte'));
        $this->assertDatabaseHas('ntes', [
            'kawasan_id' => $tanjungApi->id,
            'tahun' => 2024,
            'total_nte' => 300,
        ]);
        $this->assertDatabaseMissing('ntes', [
            'kawasan_id' => $staleKawasan->id,
            'tahun' => 2025,
        ]);

        $dakoNte = Nte::query()
            ->whereHas('kawasan', fn ($query) => $query->where('nama_kawasan', 'CA Gunung Dako'))
            ->where('tahun', 2025)
            ->firstOrFail();
        $this->assertSame([12, 13], $dakoNte->data_sumber['pdf_pages']);
        $this->assertStringContainsString('reports 9 facilitated villages', $dakoNte->data_sumber['source_conflict']);

        $bancea = Kawasan::query()->where('nama_kawasan', 'TWA Bancea')->firstOrFail();
        $this->assertSame(3, $bancea->jumlah_desa_sekitar);
        $this->assertSame(8, KegiatanPemberdayaan::query()->count());
        $this->assertDatabaseHas('kegiatan_pemberdayaans', [
            'tahun' => 2026,
            'jenis' => 'target',
            'target' => 'Rp. 1.575 Miliar',
        ]);
    }
}
