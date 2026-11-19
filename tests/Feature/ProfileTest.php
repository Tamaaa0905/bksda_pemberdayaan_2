<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_profile_displays_employee_access_and_contact_fields(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profil Administrator')
            ->assertSee('Identitas Pegawai / Administrator')
            ->assertSee('Nama Lengkap')
            ->assertSee('NIP (Nomor Induk Pegawai)')
            ->assertSee('Pangkat / Golongan')
            ->assertSee('Jabatan')
            ->assertSee('Unit Kerja / Instansi')
            ->assertSee('Wilayah Tanggung Jawab')
            ->assertSee('Email Resmi')
            ->assertSee('Nomor Telepon / WhatsApp')
            ->assertDontSee('Hak Akses Sistem')
            ->assertDontSee('Administrator Utama')
            ->assertDontSee('name="role"', false);
    }

    public function test_admin_can_update_employee_and_contact_details_without_changing_role(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $response = $this->actingAs($admin)
            ->patch('/profile', [
                'name' => 'Administrator BKSDA',
                'email' => 'admin.bksda@example.com',
                'nip' => '197901211995121001',
                'pangkat_golongan' => 'Pembina / IV-a',
                'jabatan' => 'Kepala Seksi Wilayah',
                'unit_kerja' => 'Balai Besar KSDA',
                'wilayah_tanggung_jawab' => 'Seluruh Resort dan Seksi',
                'no_telepon' => '081234567890',
                'role' => User::ROLE_USER,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Administrator BKSDA',
            'email' => 'admin.bksda@example.com',
            'nip' => '197901211995121001',
            'pangkat_golongan' => 'Pembina / IV-a',
            'jabatan' => 'Kepala Seksi Wilayah',
            'unit_kerja' => 'Balai Besar KSDA',
            'wilayah_tanggung_jawab' => 'Seluruh Resort dan Seksi',
            'no_telepon' => '081234567890',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_petugas_cannot_change_role_or_admin_only_profile_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'role' => User::ROLE_ADMIN,
                'wilayah_tanggung_jawab' => 'Seluruh wilayah',
                'no_telepon' => '081234567890',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_USER,
            'wilayah_tanggung_jawab' => null,
            'no_telepon' => null,
        ]);
    }

    public function test_petugas_profile_displays_employee_and_work_area_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profil Kepegawaian dan Wilayah Binaan')
            ->assertSee('Pangkat/Golongan')
            ->assertSee('Desa/Kelurahan Binaan');
    }

    public function test_petugas_can_update_employee_profile_and_multiple_assigned_villages(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desaPertama = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Binaan Satu',
            'kecamatan' => 'Bualemo',
        ]);
        $desaKedua = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Binaan Dua',
            'kecamatan' => 'Bualemo',
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'update_work_area' => '1',
                'nip' => '198201012010011001',
                'pangkat_golongan' => 'Penata / III-c',
                'jabatan' => 'Penyuluh Kehutanan',
                'unit_kerja' => 'BKSDA Sulawesi Tengah',
                'kecamatan_binaan' => 'Bualemo',
                'desa_binaan' => [$desaPertama->id, $desaKedua->id],
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'nip' => '198201012010011001',
            'pangkat_golongan' => 'Penata / III-c',
            'jabatan' => 'Penyuluh Kehutanan',
            'unit_kerja' => 'BKSDA Sulawesi Tengah',
            'kecamatan_binaan' => 'Bualemo',
        ]);
        $this->assertDatabaseHas('desa_user', [
            'user_id' => $user->id,
            'desa_id' => $desaPertama->id,
        ]);
        $this->assertDatabaseHas('desa_user', [
            'user_id' => $user->id,
            'desa_id' => $desaKedua->id,
        ]);
    }

    public function test_petugas_cannot_assign_a_village_from_a_different_working_district(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desa = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Luwuk',
            'kecamatan' => 'Luwuk',
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'update_work_area' => '1',
                'nip' => '198201012010011002',
                'pangkat_golongan' => 'Penata / III-c',
                'jabatan' => 'Penyuluh Kehutanan',
                'unit_kerja' => 'BKSDA Sulawesi Tengah',
                'kecamatan_binaan' => 'Bualemo',
                'desa_binaan' => [$desa->id],
            ]);

        $response->assertRedirect('/profile')
            ->assertSessionHasErrors('desa_binaan.0');
        $this->assertDatabaseMissing('desa_user', [
            'user_id' => $user->id,
            'desa_id' => $desa->id,
        ]);
    }

    public function test_profile_normalizes_district_spacing_and_accepts_matching_village_data(): void
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'SM Bakiriang']);
        $desaDenganNamaKecamatanResmi = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Pangi',
            'kecamatan' => 'PARIGI UTARA',
        ]);
        $desaDenganVariasiKecamatan = Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Pangi Baru',
            'kecamatan' => 'Parigi Utara',
        ]);
        Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Tanpa Kecamatan',
        ]);
        Desa::create([
            'kawasan_id' => $kawasan->id,
            'nama_desa' => 'Desa Parigi Barat',
            'kecamatan' => 'PARIGI BARAT',
        ]);
        $user = User::factory()->create();
        $user->forceFill(['kecamatan_binaan' => 'PARIGIUTARA'])->save();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertViewHas('selectedKecamatan', 'PARIGI UTARA')
            ->assertViewHas('kecamatanOptions', fn ($options): bool => $options->count() === 2)
            ->assertViewHas('desaOptions', fn (array $options): bool => count($options) === 3
                && collect($options)->every(fn (array $desa): bool => $desa['kecamatan_key'] !== ''));

        $response = $this->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'update_work_area' => '1',
            'nip' => '198201012010011003',
            'pangkat_golongan' => 'Penata / III-c',
            'jabatan' => 'Penyuluh Kehutanan',
            'unit_kerja' => 'BKSDA Sulawesi Tengah',
            'kecamatan_binaan' => 'PARIGI UTARA',
            'desa_binaan' => [$desaDenganVariasiKecamatan->id],
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
        $this->assertDatabaseHas('desa_user', [
            'user_id' => $user->id,
            'desa_id' => $desaDenganVariasiKecamatan->id,
        ]);
        $this->assertDatabaseMissing('desa_user', [
            'user_id' => $user->id,
            'desa_id' => $desaDenganNamaKecamatanResmi->id,
        ]);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
