<?php

namespace Tests\Feature\AdminGunung;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Gunung;
use App\Models\Basecamp;
use App\Models\AdminRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AdminRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin_gunung']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);

        Sanctum::actingAs($this->admin);
    }

    public function test_admin_gunung_can_view_requests()
    {
        // Pastikan kolom pengait factory disesuaikan dengan skema database Anda (user_id atau request_by)
        AdminRequest::factory()->count(3)->create([
            'user_id' => $this->admin->id 
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/admin-gunung/requests');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Request Admin Gunung'
                 ]);
    }

    public function test_admin_gunung_can_create_admin_basecamp_request()
    {
        $targetUser = User::factory()->create();
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create(['gunung_id' => $gunung->id]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/admin-gunung/requests', [
            'user_id' => $targetUser->id,
            'basecamp_id' => $basecamp->id
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Request admin basecamp berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('admin_requests', [
            'user_id' => $targetUser->id,
            'request_type' => 'admin_basecamp',
            'status' => 'pending'
        ]);
    }
}