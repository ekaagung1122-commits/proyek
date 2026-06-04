<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Basecamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_successfully()
    {
        $role = Role::create([
            'name' => 'user'
        ]);

        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => Hash::make('password123')
        ]);

        $user->roles()->attach($role->id);

        $response = $this->postJson('/api/login', [
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
                         'basecamp_id'
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

        $response = $this->postJson('/api/login', [
            'email' => 'user@gmail.com',
            'password' => 'salahpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthorized'
                 ]);
    }

    public function test_admin_basecamp_must_have_basecamp()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $user->roles()->attach($role->id);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'User belum punya basecamp'
                 ]);
    }

    public function test_admin_basecamp_can_login_if_has_basecamp()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $user->roles()->attach($role->id);

        $basecamp = Basecamp::create([
            'name' => 'Basecamp Semeru',
            'admin_basecamp_id' => $user->id
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'user' => [
                         'role' => 'admin_basecamp',
                         'basecamp_id' => $basecamp->id
                     ]
                 ]);
    }
}