<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:set-user-role {email : The existing user email} {role : admin or user}')]
#[Description('Assign the admin or user role to an existing account')]
class SetUserRole extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->argument('role');

        if (! is_string($role) || ! in_array($role, [User::ROLE_ADMIN, User::ROLE_USER], true)) {
            $this->error('Role harus admin atau user.');

            return self::FAILURE;
        }

        if (! is_string($email) || $email === '') {
            $this->error('Email pengguna wajib diisi.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("Pengguna dengan email {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();

        $this->info("Role pengguna {$email} berhasil diubah menjadi {$role}.");

        return self::SUCCESS;
    }
}
