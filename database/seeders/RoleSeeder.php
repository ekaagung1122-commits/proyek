<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run():void
    {
        Role::create([
            'name' => 'user'
        ]);

        Role::create([
            'name' => 'admin_gunung'
        ]);

        Role::create([
            'name' => 'admin_basecamp'
        ]);

        Role::create([
            'name' => 'super_admin'
        ]);
    }
}
