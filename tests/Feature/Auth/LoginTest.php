<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_successfully()
    {
        $role = Role::create(['name' => 'user']);

        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => Hash::make('password123')
        ]);

        $user->roles()->attach($role->id);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/login', [
            'email' => 'user@gmail.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role',
                     ],
                     'token'
                 ]);
    }

    public function test_login_fails_if_password_wrong()
    {
        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/login', [
            'email' => 'user@gmail.com',
            'password' => 'salahpassword'
        ]);

        $response->assertStatus(401);
    }
}