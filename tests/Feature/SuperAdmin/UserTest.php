<?php

namespace Tests\Feature\SuperAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_user_list()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/super-admin/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data'
                 ]);
    }

    public function test_super_admin_can_delete_user()
    {
        $role = Role::create([
            'name' => 'admin_gunung'
        ]);

        $user = User::factory()->create();

        $user->roles()->attach($role->id);

        $response = $this->deleteJson("/api/super-admin/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'User berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }

    public function test_can_remove_role_from_user()
    {
        $role = Role::create([
            'name' => 'admin_gunung'
        ]);

        $user = User::factory()->create();

        $user->roles()->attach($role->id);

        $response = $this->deleteJson("/api/super-admin/users/{$user->id}/roles/admin_gunung");

        $response->assertStatus(200);

        $this->assertFalse(
            $user->fresh()->roles()->where('name', 'admin_gunung')->exists()
        );
    }
}