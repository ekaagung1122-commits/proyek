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
        $basecamp = Basecamp::factory()->create();

        // Diubah menggunakan request_by dan menyuplai data email tiruan agar tidak memicu NOT NULL constraint user_id
        AdminRequest::factory()->count(3)->create([
            'request_by' => $this->admin->id,
            'email' => 'pendaki@example.com',
            'basecamp_id' => $basecamp->id,
            'request_type' => 'admin_basecamp'
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
            'basecamp_id' => $basecamp->id,
            'email' => $targetUser->email
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Request admin basecamp berhasil dibuat'
                 ]);

        // Dipastikan memeriksa berdasarkan kolom email & basecamp_id sesuai logika baru Controller kamu
        $this->assertDatabaseHas('admin_requests', [
            'email' => $targetUser->email,
            'basecamp_id' => $basecamp->id,
            'request_type' => 'admin_basecamp',
            'status' => 'pending'
        ]);
    }
}