<?php

namespace Database\Seeders;

use App\Authorization\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seeds the 13 Spatie roles (D-021 / D-040 / USER_ROLE_MATRIX).
 * "Guest" is the unauthenticated state — it has NO role record.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Roles::ALL as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
