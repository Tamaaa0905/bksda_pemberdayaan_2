<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_all_group_data_columns_without_edit_actions(): void
    {
        $kelompok = $this->createKelompok();
        $desa = Desa::create([
            'kawasan_id' => $kelompok->kawasan_id,
            'nama_desa' => 'Desa Binaan Uji',
            'kecamatan' => 'Bualemo',
            'kabupaten' => 'Banggai',
        ]);
        $kelompok->update([
            'desa_id' => $desa->id,
            'tahun_pembinaan' => 2025,
            'nama_ketua' => 'Ketua Privat',
            'no_hp' => '081234567890',
            'sk_kelompok' => 'SK-2025/002',
            'jumlah_anggota' => 16,
        ]);
        $kelompokLain = $this->createKelompok();
        $kelompokLain->update(['nama_kelompok' => 'Kelompok Wilayah Lain']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Total Nilai Ekonomi (NTE)')
            ->assertSee('Kawasan Konservasi')
            ->assertSee('Kelompok Binaan')
            ->assertSee('Total Penyaluran Bantuan')
            ->assertSee('Data Kelompok Binaan')
            ->assertSee('No')
            ->assertSee('Tahun')
            ->assertSee('Kawasan')
            ->assertSee('Desa')
            ->assertSee('Kecamatan')
            ->assertSee('2025')
            ->assertSee('Banggai')
            ->assertSee('Desa Binaan Uji')
            ->assertSee('Nama Kelompok')
            ->assertSee('Nama Ketua')
            ->assertSee('No HP')
            ->assertSee('Kelompok Uji Role')
            ->assertSee('Kelompok Wilayah Lain')
            ->assertSee('Ketua Privat')
            ->assertSee('081234567890')
            ->assertSee('SK-2025/002')
            ->assertSee('Budidaya ikan')
            ->assertSee('16')
            ->assertSee('Jumlah Bantuan')
            ->assertSee('Hasil / Keuntungan Usaha')
            ->assertSee('Status & Kebutuhan')
            ->assertDontSee('Edit data')
            ->assertDontSee('Tambah Kelompok');
    }

    public function test_petugas_can_edit_only_assigned_groups_and_cannot_create(): void
    {
        $petugas = User::factory()->create();
        $kelompok = $this->createKelompok();
        $desa = Desa::create([
            'kawasan_id' => $kelompok->kawasan_id,
            'nama_desa' => 'Desa Tugas Petugas',
        ]);
        $petugas->desas()->attach($desa);
        $kelompok->update([
            'desa_id' => $desa->id,
            'petugas_id' => $petugas->id,
        ]);
        $kelompokLain = $this->createKelompok();
        $kelompokLain->update(['nama_kelompok' => 'Kelompok Bukan Petugas']);

        $this->actingAs($petugas)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($kelompok->nama_kelompok)
            ->assertSee('aria-label="Edit data '.$kelompok->nama_kelompok.'"', false)
            ->assertDontSee($kelompokLain->nama_kelompok);

        $this->get(route('kelompok.edit', $kelompok))
            ->assertOk()
            ->assertSee('Edit Kelompok Binaan');

        $this->get(route('kelompok.edit', $kelompokLain))
            ->assertForbidden();

        $this->get(route('kelompok.create'))->assertForbidden();

        $this->post(route('kelompok.store'), [
            'nama_kelompok' => 'Kelompok Tidak Diizinkan',
        ])->assertForbidden();

        $this->put(route('kelompok.update', $kelompok), [
            'kawasan_id' => $kelompok->kawasan_id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Diperbarui Petugas',
            'nama_ketua' => 'Ketua Baru',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 1500000,
            'status_keaktifan' => 'aktif',
            'butuh_bantuan_lanjutan' => '0',
            'butuh_pendampingan' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'petugas_id' => $petugas->id,
            'nama_kelompok' => 'Kelompok Diperbarui Petugas',
            'nama_ketua' => 'Ketua Baru',
        ]);

        $this->put(route('kelompok.update', $kelompokLain), [
            'kawasan_id' => $kelompokLain->kawasan_id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Nama Diubah Tanpa Izin',
        ])->assertForbidden();

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompokLain->id,
            'nama_kelompok' => 'Kelompok Uji Role',
        ]);
        $this->assertDatabaseMissing('kelompoks', [
            'nama_kelompok' => 'Kelompok Tidak Diizinkan',
        ]);
    }

    public function test_guest_cannot_update_group_data(): void
    {
        $kelompok = $this->createKelompok();

        $this->put(route('kelompok.update', $kelompok), [
            'nama_kelompok' => 'Nama Diubah Tanpa Login',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Uji Role',
        ]);
    }

    public function test_admin_can_edit_any_group_but_cannot_delete_it(): void
    {
        $kelompok = $this->createKelompok();
        $desa = Desa::create([
            'kawasan_id' => $kelompok->kawasan_id,
            'nama_desa' => 'Desa Admin',
        ]);
        $kelompok->update(['desa_id' => $desa->id]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($kelompok->nama_kelompok)
            ->assertSee('aria-label="Edit data '.$kelompok->nama_kelompok.'"', false)
            ->assertDontSee('Hapus data')
            ->assertDontSee('fa-trash-can');

        $this->put(route('kelompok.update', $kelompok), [
            'kawasan_id' => $kelompok->kawasan_id,
            'desa_id' => $desa->id,
            'nama_kelompok' => 'Kelompok Diperbarui Admin',
            'nama_ketua' => 'Ketua Admin',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 2750000,
            'status_keaktifan' => 'aktif',
            'butuh_bantuan_lanjutan' => '1',
            'butuh_pendampingan' => '0',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('kelompoks', [
            'id' => $kelompok->id,
            'nama_kelompok' => 'Kelompok Diperbarui Admin',
            'nama_ketua' => 'Ketua Admin',
            'jumlah_bantuan' => 2750000,
        ]);

        $this->delete('/kelompok/'.$kelompok->id)->assertStatus(405);
        $this->assertDatabaseHas('kelompoks', ['id' => $kelompok->id]);
    }

    private function createKelompok(): Kelompok
    {
        $kawasan = Kawasan::create(['nama_kawasan' => 'Kawasan Uji Role']);

        return Kelompok::create([
            'kawasan_id' => $kawasan->id,
            'nama_kelompok' => 'Kelompok Uji Role',
            'nama_ketua' => 'Ketua Uji',
            'jenis_usaha' => 'Budidaya ikan',
            'jumlah_bantuan' => 1000000,
        ]);
    }
}
