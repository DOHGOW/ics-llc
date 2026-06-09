<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Root seeder. Order matters: permissions, then roles, then the mapping.
 * (Business-data seeders are added by their module sprints.)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
