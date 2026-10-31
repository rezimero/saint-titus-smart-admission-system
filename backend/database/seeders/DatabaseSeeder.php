<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Safe for repeated execution. Admin creation is ALWAYS an explicit operation.
        $this->call(RolePermissionSeeder::class);
    }
}
