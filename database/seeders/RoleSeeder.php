<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        foreach ([1 => 'client', 2 => 'freelancer', 3 => 'admin'] as $id => $name) {
            Role::updateOrCreate(['id' => $id], ['name' => $name]);
        }
    }
}
