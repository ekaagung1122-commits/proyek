<?php

namespace Tests\Feature\AdminGunung;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gunung;
use App\Models\Basecamp;
use App\Models\AdminRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AdminRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gunung_can_view_requests()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        AdminRequest::factory()->count(3)->create([
            'request_by' => $admin->id
        ]);

        $response = $this->getJson('/api/admin-gunung/requests');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Request Admin Gunung'
                 ]);
    }

    public function test_admin_gunung_can_create_admin_basecamp_request()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $targetUser = User::factory()->create();

        $gunung = Gunung::create([
            'nama' => 'Gunung Semeru',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Ranu Pane',
            'gunung_id' => $gunung->id
        ]);

        $response = $this->postJson('/api/admin-gunung/requests', [
            'user_id' => $targetUser->id,
            'basecamp_id' => $basecamp->id
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Request admin basecamp berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('admin_requests', [
            'user_id' => $targetUser->id,
            'request_type' => 'admin_basecamp',
            'status' => 'pending'
        ]);
    }
}