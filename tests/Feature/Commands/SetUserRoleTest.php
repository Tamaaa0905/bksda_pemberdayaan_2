<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_assigns_admin_role_to_existing_user(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:set-user-role', [
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
        ])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_command_rejects_an_invalid_role_without_changing_user_role(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:set-user-role', [
            'email' => $user->email,
            'role' => 'superadmin',
        ])->assertExitCode(Command::FAILURE);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_command_reports_an_unknown_user(): void
    {
        $this->artisan('app:set-user-role', [
            'email' => 'missing@example.com',
            'role' => User::ROLE_ADMIN,
        ])->assertExitCode(Command::FAILURE);
    }
}
