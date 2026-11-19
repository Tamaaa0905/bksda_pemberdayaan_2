<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\BksdaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_form_offers_village_and_progress_fields(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Pilihan Form',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->get(route('kelompok.create'));

        $response->assertOk()
            ->assertSee('Desa Pilihan Form')
            ->assertSee('Tahun Pembinaan')
            ->assertSee('Kecamatan')
            ->assertSee('Kabupaten')
            ->assertSee('Nomor SK Kelompok')
            ->assertSee('Jumlah Anggota Kelompok')
            ->assertSee('Hasil / Keuntungan Usaha (Rp)')
            ->assertSee('Status Keaktifan Kelompok')
            ->assertSee('Kendala Kelompok Tidak Aktif')
            ->assertSee('Butuh Bantuan Lanjutan?')
            ->assertSee('Butuh Pendampingan?')
            ->assertSee('x-show="statusKeaktifan === \'tidak_aktif\'"', false)
            ->assertSee('x-show="statusKeaktifan === \'aktif\'"', false);
    }

    public function test_group_creation_only_offers_the_18_official_conservation_areas(): void
    {
        $this->seed(BksdaSeeder::class);
        $petugas = $this->createAdmin();
        $desas = Kawasan::query()
            ->get()
            ->map(fn (Kawasan $kawasan): Desa => Desa::create([
                'kawasan_id' => $kawasan->id,
                'nama_desa' => 'Desa '.$kawasan->id,
            ]));
        $petugas->desas()->attach($desas->modelKeys());

        $response = $this->actingAs($petugas)
            ->get(route('kelompok.create'));

        $response->assertOk()
            ->assertViewHas('kawasans', fn ($kawasans): bool => $kawasans->count() === 18)
            ->assertSee('CA Morowali')
            ->assertDontSee('CA Marisa')
            ->assertDontSee('CA. Morowali');
    }

    public function test_group_creation_rejects_a_non_official_conservation_area(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'CA Marisa']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Marisa',
        ]);
        $petugas = $this->createAdmin();
        $petugas->desas()->attach($desa);

        $response = $this->actingAs($petugas)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Kawasan Tidak Resmi',
                'nama_ketua' => 'Ketua Uji',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 0,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '0',
                'butuh_pendampingan' => '0',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('kawasan_id');
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Kawasan Tidak Resmi',
        ]);
    }

    public function test_user_can_open_progress_form_for_an_existing_group(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Pemantauan',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'desa_id' => $desa->id,
            'petugas_id' => $user->id,
            'nama_kelompok' => 'Kelompok Pemantauan',
            'nama_ketua' => 'Ketua Pemantauan',
            'jenis_usaha' => 'Budidaya ikan',
        ]);

        $this->actingAs($user)
            ->get(route('kelompok.status.edit', $kelompok))
            ->assertOk()
            ->assertSee('Perbarui Status Kelompok')
            ->assertSee('Kelompok Pemantauan')
            ->assertSee('Kendala jika kelompok tidak aktif')
            ->assertSee('x-show="statusKeaktifan === \'tidak_aktif\'"', false)
            ->assertSee('x-show="statusKeaktifan === \'aktif\'"', false);
    }

    public function test_user_can_update_progress_without_editing_group_identity(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $user = $this->createAdmin();
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => $user->id,
            'nama_kelompok' => 'Kelompok Tetap',
            'nama_ketua' => 'Ketua Tetap',
            'jenis_usaha' => 'Budidaya ikan',
            'status_keaktifan' => 'aktif',
            'kendala' => 'Kendala lama',
            'butuh_bantuan_lanjutan' => true,
            'butuh_pendampingan' => true,
        ]);

        $response = $this->actingAs($user)
            ->put(route('kelompok.status.update', $kelompok), [
                'status_keaktifan' => 'tidak_aktif',
                'kendala' => 'Akses jalan rusak',
                'butuh_bantuan_lanjutan' => '1',
                'butuh_pendampingan' => '1',
                'nama_kelompok' => 'Nama Tidak Boleh Diubah',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Tetap',
            'status_keaktifan' => 'tidak_aktif',
            'kendala' => 'Akses jalan rusak',
            'butuh_bantuan_lanjutan' => null,
            'butuh_pendampingan' => null,
        ]);

        $response = $this->put(route('kelompok.status.update', $kelompok), [
            'status_keaktifan' => 'aktif',
            'butuh_bantuan_lanjutan' => '0',
            'butuh_pendampingan' => '1',
            'kendala' => 'Kendala lama',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'status_keaktifan' => 'aktif',
            'kendala' => null,
            'butuh_bantuan_lanjutan' => false,
            'butuh_pendampingan' => true,
        ]);
    }

    public function test_user_cannot_update_progress_for_another_petugas_group(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $kelompok = Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => User::factory()->create()->id,
            'nama_kelompok' => 'Kelompok Petugas Lain',
            'nama_ketua' => 'Ketua Lain',
            'jenis_usaha' => 'Budidaya ikan',
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('kelompok.status.update', $kelompok), [
                'status_keaktifan' => 'tidak_aktif',
                'kendala' => 'Akses jalan rusak',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'status_keaktifan' => null,
        ]);
    }

    public function test_user_can_record_group_activity_and_follow_up_needs(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Lestari',
            'kecamatan' => 'Bualemo',
            'kabupaten' => 'Banggai',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'tahun_pembinaan' => 2025,
                'kecamatan' => 'Bualemo',
                'kabupaten' => 'Banggai',
                'nama_kelompok' => 'Kelompok Lestari',
                'nama_ketua' => 'Ketua Lestari',
                'no_hp' => '081234567890',
                'jenis_usaha' => 'Budidaya ikan',
                'sk_kelompok' => 'SK-2025/001',
                'jumlah_anggota' => 18,
                'jumlah_bantuan' => 1500000,
                'hasil_usaha' => 2850000,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '1',
                'butuh_pendampingan' => '0',
                'kendala' => 'Kendala lama yang tidak berlaku',
                'catatan_kebutuhan' => 'Perlu alat pengering',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'desa_id' => $desa->id,
            'petugas_id' => null,
            'tahun_pembinaan' => 2025,
            'kecamatan' => 'Bualemo',
            'kabupaten' => 'Banggai',
            'nama_kelompok' => 'Kelompok Lestari',
            'nama_ketua' => 'Ketua Lestari',
            'no_hp' => '081234567890',
            'jenis_usaha' => 'Budidaya ikan',
            'sk_kelompok' => 'SK-2025/001',
            'jumlah_anggota' => 18,
            'jumlah_bantuan' => 1500000,
            'hasil_usaha' => 2850000,
            'status_keaktifan' => 'aktif',
            'kendala' => null,
            'butuh_bantuan_lanjutan' => true,
            'butuh_pendampingan' => false,
            'catatan_kebutuhan' => 'Perlu alat pengering',
        ]);
    }

    public function test_inactive_group_requires_an_explanation(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Tidak Aktif',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Tidak Aktif',
                'nama_ketua' => 'Ketua Tidak Aktif',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 0,
                'status_keaktifan' => 'tidak_aktif',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('kendala');
        $this->assertDatabaseMissing('kelompoks', ['nama_kelompok' => 'Kelompok Tidak Aktif']);
    }

    public function test_inactive_group_creation_discards_active_only_answers(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Tidak Aktif',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Berhenti',
                'nama_ketua' => 'Ketua Kelompok',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 0,
                'status_keaktifan' => 'tidak_aktif',
                'kendala' => 'Akses bahan baku terhenti',
                'butuh_bantuan_lanjutan' => '1',
                'butuh_pendampingan' => '0',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('kelompoks', [
            'nama_kelompok' => 'Kelompok Berhenti',
            'status_keaktifan' => 'tidak_aktif',
            'kendala' => 'Akses bahan baku terhenti',
            'butuh_bantuan_lanjutan' => null,
            'butuh_pendampingan' => null,
        ]);
    }

    public function test_group_creation_rejects_negative_business_income(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Usaha',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Usaha Minus',
                'nama_ketua' => 'Ketua Usaha',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 0,
                'hasil_usaha' => -1,
                'status_keaktifan' => 'aktif',
                'butuh_bantuan_lanjutan' => '0',
                'butuh_pendampingan' => '0',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors('hasil_usaha');
        $this->assertDatabaseMissing('kelompoks', ['nama_kelompok' => 'Kelompok Usaha Minus']);
    }

    public function test_user_cannot_register_a_group_outside_their_assigned_villages(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desaBinaan = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Tugas Petugas',
        ]);
        $desaLain = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Di Luar Tugas',
        ]);
        $user = User::factory()->create();
        $user->desas()->attach($desaBinaan);

        $response = $this->actingAs($user)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desaLain->id,
                'nama_kelompok' => 'Kelompok Bukan Tugas',
                'nama_ketua' => 'Ketua Uji',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 0,
                'status_keaktifan' => 'tidak_aktif',
                'kendala' => 'Belum ada kegiatan',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('kelompoks', ['nama_kelompok' => 'Kelompok Bukan Tugas']);
    }

    public function test_active_group_requires_assistance_and_mentoring_answers(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Kelompok Aktif',
        ]);
        $user = $this->createAdmin();
        $user->desas()->attach($desa);

        $response = $this->actingAs($user)
            ->from(route('kelompok.create'))
            ->post(route('kelompok.store'), [
                'kawasan_id' => $kawasan->id,
                'desa_id' => $desa->id,
                'nama_kelompok' => 'Kelompok Aktif',
                'nama_ketua' => 'Ketua Aktif',
                'jenis_usaha' => 'Budidaya ikan',
                'jumlah_bantuan' => 0,
                'status_keaktifan' => 'aktif',
            ]);

        $response->assertRedirect(route('kelompok.create'))
            ->assertSessionHasErrors(['butuh_bantuan_lanjutan', 'butuh_pendampingan']);
        $this->assertDatabaseMissing('kelompoks', ['nama_kelompok' => 'Kelompok Aktif']);
    }

    public function test_dashboard_hides_village_aid_comparison_and_displays_group_follow_up_details(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $user = $this->createAdmin();

        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => $user->id,
            'nama_kelompok' => 'Kelompok Aktif Terbantu',
            'nama_ketua' => 'Ketua Aktif',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 2000000,
            'hasil_usaha' => 5000000,
            'status_keaktifan' => 'aktif',
            'butuh_bantuan_lanjutan' => true,
            'butuh_pendampingan' => false,
            'catatan_kebutuhan' => 'Perlu alat pengering',
        ]);
        Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'petugas_id' => $user->id,
            'nama_kelompok' => 'Kelompok Tidak Aktif',
            'nama_ketua' => 'Ketua Tidak Aktif',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 0,
            'status_keaktifan' => 'tidak_aktif',
            'kendala' => 'Akses pemasaran terbatas',
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('Perbandingan Bantuan per Desa')
            ->assertSee('Bantuan lanjutan: Ya')
            ->assertSee('Pendampingan: Tidak')
            ->assertSee('Perlu alat pengering')
            ->assertSee('Hasil / Keuntungan Usaha')
            ->assertSee('Rp 5.000.000')
            ->assertSee('Kendala: Akses pemasaran terbatas');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }
}
