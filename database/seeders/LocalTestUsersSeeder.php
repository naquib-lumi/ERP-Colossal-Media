<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One login per role for local testing:
 *   php artisan db:seed --class=LocalTestUsersSeeder
 *
 * Emails look like boss@test.local; every account uses the same password.
 * Never runs in production.
 */
class LocalTestUsersSeeder extends Seeder
{
    public const PASSWORD = 'Test@1234';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('LocalTestUsersSeeder is for local testing only — skipped in production.');
            return;
        }

        foreach (Role::cases() as $role) {
            $this->createUser($role, $role->value, 'Test ' . $role->label());
        }

        // Second salesperson and artist, for testing assignment between people
        // (LeadSeeder also needs at least two salespeople).
        $this->createUser(Role::Salesperson, 'salesperson2', 'Test Salesperson 2');
        $this->createUser(Role::Artist, 'artist2', 'Test Artist 2');
    }

    private function createUser(Role $role, string $emailName, string $name): void
    {
        $user = User::updateOrCreate(
            ['email' => $emailName . '@test.local'],
            [
                'name'     => $name,
                'password' => Hash::make(self::PASSWORD),
                'role'     => $role->value,
                'status'   => 'active',
            ]
        );

        // Not in $fillable; the /dashboard route requires a verified email.
        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
