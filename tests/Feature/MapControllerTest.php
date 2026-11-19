<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_displays_group_markers_for_groups_with_coordinates(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Penyangga Peta',
            'kecamatan' => 'Bualemo',
            'kabupaten' => 'Banggai',
            'jumlah_penutupan_kk' => 80,
            'jumlah_penduduk' => 320,
            'latitude' => -0.902,
            'longitude' => 119.871,
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Peta Lestari',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '1',
                'butuh_pendampingan' => '0',
                'kecamatan' => 'Bualemo',
                'kabupaten' => 'Banggai',
                'latitude' => '-0.9012000',
                'longitude' => '119.8701000',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Peta Lestari',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);

        $mapResponse = $this->get(route('kelompok.map'));

        $mapResponse->assertOk()
            ->assertSee('Peta Sebaran Kelompok Binaan')
            ->assertSee('Peta OpenStreetMap menampilkan titik kelompok binaan.')
            ->assertSee('leaflet@1.9.4/dist/leaflet.js', false)
            ->assertSee('group-map')
            ->assertDontSee('kawasan-konservasi-sidak.geojson')
            ->assertDontSee('Poligon Kawasan')
            ->assertSee('group-map-data')
            ->assertSee('"nama_kelompok":"Kelompok Peta Lestari"', false)
            ->assertSee('"status_keaktifan":"aktif"', false)
            ->assertSee('"butuh_bantuan_lanjutan":true', false)
            ->assertSee('"latitude":-0.9012', false)
            ->assertSee('"longitude":119.8701', false)
            ->assertSee('Desa Penyangga Peta')
            ->assertSee('group-coordinate-form')
            ->assertDontSee('village-map-data');

        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Peta Lestari',
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_map_uses_related_village_coordinates_for_groups_without_group_coordinates(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Penyangga Koordinat',
            'latitude' => -0.902,
            'longitude' => 119.871,
        ]);
        $user = $this->createAdmin();
        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'petugas_id' => $user->id,
            'nama_kelompok' => 'Kelompok Mengikuti Lokasi Desa',
            'jenis_usaha' => 'Budidaya ikan',
        ]);

        $response = $this->actingAs($user)
            ->get(route('kelompok.map'));

        $response->assertOk()
            ->assertSee('"nama_kelompok":"Kelompok Mengikuti Lokasi Desa"', false)
            ->assertSee('"nama_desa":"Desa Penyangga Koordinat"', false)
            ->assertSee('"latitude":-0.902', false)
            ->assertSee('"longitude":119.871', false);
    }

    public function test_admin_map_shows_all_groups_and_updates_group_coordinates(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $admin = $this->createAdmin();
        $kelompokPetugas = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => User::factory()->create()->id,
            'nama_kelompok' => 'Kelompok Petugas',
            'nama_ketua' => 'Ketua Petugas',
            'jenis_usaha' => 'Kerajinan',
            'latitude' => -0.9,
            'longitude' => 119.8,
        ]);
        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => User::factory()->create()->id,
            'nama_kelompok' => 'Kelompok Petugas Lain',
            'nama_ketua' => 'Ketua Lain',
            'jenis_usaha' => 'Ternak',
            'latitude' => -0.8,
            'longitude' => 119.7,
        ]);

        $this->actingAs($admin)
            ->get(route('kelompok.map'))
            ->assertOk()
            ->assertSee('"nama_kelompok":"Kelompok Petugas"', false)
            ->assertSee('"nama_kelompok":"Kelompok Petugas Lain"', false);

        $response = $this->put(route('kelompok.coordinates.update', $kelompokPetugas), [
            'latitude' => '-0.9012',
            'longitude' => '119.8701',
        ]);

        $response->assertRedirect(route('kelompok.map'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompokPetugas->id,
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_petugas_cannot_update_another_petugas_group_coordinates(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => User::factory()->create()->id,
            'nama_kelompok' => 'Kelompok Petugas Lain',
            'nama_ketua' => 'Ketua Lain',
            'jenis_usaha' => 'Ternak',
            'latitude' => -0.8,
            'longitude' => 119.7,
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('kelompok.coordinates.update', $kelompok), [
                'latitude' => '-0.9012',
                'longitude' => '119.8701',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'latitude' => -0.8,
            'longitude' => 119.7,
        ]);
    }

    public function test_admin_map_allows_group_coordinate_updates(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => User::factory()->create()->id,
            'nama_kelompok' => 'Kelompok Peta Admin',
            'nama_ketua' => 'Ketua Peta',
            'jenis_usaha' => 'Kerajinan',
            'latitude' => -0.9,
            'longitude' => 119.8,
        ]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->get(route('kelompok.map'))
            ->assertOk()
            ->assertSee('"nama_kelompok":"Kelompok Peta Admin"', false)
            ->assertSee('group-coordinate-form');

        $this->put(route('kelompok.coordinates.update', $kelompok), [
            'latitude' => '-0.9012',
            'longitude' => '119.8701',
        ])->assertRedirect(route('kelompok.map'));

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_desa_coordinates_can_be_saved_for_the_map(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Koordinat Peta',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->put(route('desa.coordinates.update', $desa), [
                'latitude' => '-0.9012',
                'longitude' => '119.8701',
            ]);

        $response->assertRedirect(route('kelompok.map'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('desas', [
            'id' => $desa->id,
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_desa_coordinates_must_be_within_valid_geographic_ranges(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Koordinat Tidak Valid',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->from(route('kelompok.map'))
            ->put(route('desa.coordinates.update', $desa), [
                'latitude' => '-91',
                'longitude' => '119.8701',
            ]);

        $response->assertRedirect(route('kelompok.map'))
            ->assertSessionHasErrors('latitude');
        $this->assertDatabaseHas('desas', [
            'id' => $desa->id,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_group_coordinates_must_be_provided_together(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Koordinat Berpasangan',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Tanpa Koordinat Lengkap',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '0',
                'butuh_pendampingan' => '0',
                'latitude' => '-0.9012',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('longitude');
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Tanpa Koordinat Lengkap',
        ]);
    }

    public function test_group_coordinates_must_be_within_valid_geographic_ranges(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Koordinat Tidak Valid',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Koordinat Tidak Valid',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '0',
                'butuh_pendampingan' => '0',
                'latitude' => '-91',
                'longitude' => '119.8701',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('latitude');
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Koordinat Tidak Valid',
        ]);
    }

    public function test_group_coordinates_can_be_updated_from_the_edit_form(): void
    {
        $petugas = $this->createAdmin();
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Peta Edit',
        ]);
        $petugas->desas()->attach($desa);

        $this->actingAs($petugas)
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Peta Edit',
                'nama_ketua' => 'Ketua Peta',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 25000000,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '0',
                'butuh_pendampingan' => '0',
            ]);
        $kelompokId = (int) Kelompok::query()
            ->where('nama_kelompok', 'Kelompok Peta Edit')
            ->value('id');

        $response = $this->put(route('kelompok.update', $kelompokId), [
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Peta Edit',
            'nama_ketua' => 'Ketua Peta',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 25000000,
            'status_keaktifan' => 'aktif',
            'butuh_bantuan_lanjutan' => '0',
            'butuh_pendampingan' => '0',
            'latitude' => '-0.9012',
            'longitude' => '119.8701',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompokId,
            'latitude' => -0.9012,
            'longitude' => 119.8701,
        ]);
    }

    public function test_map_redirects_guests_to_login(): void
    {
        $response = $this->get(route('kelompok.map'));

        $response->assertRedirect(route('login'));
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }
}
