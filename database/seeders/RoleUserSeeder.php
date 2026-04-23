<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Roles
        $roles = [
            'super_admin',
            'admin_gunung',
            'admin_basecamp',
            'user'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName
            ]);
        }

        // Users
        $super = User::firstOrCreate(
            ['email' => 'super@mail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('123456')
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@mail.com'],
            [
                'name' => 'User Biasa',
                'password' => bcrypt('123456')
            ]
        );

        $gunung = User::firstOrCreate(
            ['email' => 'gunung@mail.com'],
            [
                'name' => 'Admin Gunung',
                'password' => bcrypt('123456')
            ]
        );

        // Assign role
        $superRole = Role::where('name', 'super_admin')->first();
        $userRole = Role::where('name', 'user')->first();
        $gunungRole = Role::where('name', 'admin_gunung')->first();

        $super->roles()->syncWithoutDetaching([$superRole->id]);
        $user->roles()->syncWithoutDetaching([$userRole->id]);
        $gunung->roles()->syncWithoutDetaching([$gunungRole->id]);
    }
}
