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

    public function test_cannot_create_duplicate_pending_request()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $targetUser = User::factory()->create();

        $gunung = Gunung::create([
            'nama' => 'Gunung Rinjani',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Sembalun',
            'gunung_id' => $gunung->id
        ]);

        AdminRequest::create([
            'user_id' => $targetUser->id,
            'request_by' => $admin->id,
            'request_type' => 'admin_basecamp',
            'basecamp_id' => $basecamp->id,
            'status' => 'pending'
        ]);

        $response = $this->postJson('/api/admin-gunung/requests', [
            'user_id' => $targetUser->id,
            'basecamp_id' => $basecamp->id
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Request masih pending'
                 ]);
    }

    public function test_request_requires_valid_user_and_basecamp()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin-gunung/requests', [
            'user_id' => 999,
            'basecamp_id' => 999
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_cannot_request_basecamp_from_other_mountain()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $targetUser = User::factory()->create();

        $otherAdmin = User::factory()->create();

        $gunung = Gunung::create([
            'nama' => 'Gunung Slamet',
            'created_by' => $otherAdmin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Bambangan',
            'gunung_id' => $gunung->id
        ]);

        $response = $this->postJson('/api/admin-gunung/requests', [
            'user_id' => $targetUser->id,
            'basecamp_id' => $basecamp->id
        ]);

        $response->assertStatus(404);
    }
}