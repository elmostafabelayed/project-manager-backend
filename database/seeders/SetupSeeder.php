<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, SkillSeeder::class]);
    }
}
