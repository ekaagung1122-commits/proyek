<?php

namespace Tests\Feature\SuperAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Basecamp;
use App\Models\AdminRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestStatusMail;

class AdminRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_approve_admin_request()
    {
        Mail::fake();

        $role = Role::create([
            'name' => 'admin_gunung'
        ]);

        $user = User::factory()->create();

        $request = AdminRequest::create([
            'user_id' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/super-admin/request/{$request->id}/approve");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Request admin berhasil disetujui'
                 ]);

        $this->assertDatabaseHas('admin_requests', [
            'id' => $request->id,
            'status' => 'approved'
        ]);

        $this->assertTrue(
            $user->roles()->where('name', 'admin_gunung')->exists()
        );

        Mail::assertSent(RequestStatusMail::class);
    }

    public function test_super_admin_can_reject_request()
    {
        Mail::fake();

        $user = User::factory()->create();

        $request = AdminRequest::create([
            'user_id' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/super-admin/request/{$request->id}/reject", [
            'reason' => 'Dokumen tidak valid'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Request admin berhasil ditolak'
                 ]);

        $this->assertDatabaseHas('admin_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reason' => 'Dokumen tidak valid'
        ]);

        Mail::assertSent(RequestStatusMail::class);
    }

    public function test_reject_requires_reason()
    {
        $user = User::factory()->create();

        $request = AdminRequest::create([
            'user_id' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/super-admin/request/{$request->id}/reject", []);

        $response->assertStatus(422);
    }

    public function test_admin_basecamp_approval_updates_basecamp_admin()
    {
        Mail::fake();

        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Ranu Pane'
        ]);

        $request = AdminRequest::create([
            'user_id' => $user->id,
            'request_type' => 'admin_basecamp',
            'status' => 'pending',
            'basecamp_id' => $basecamp->id
        ]);

        $response = $this->postJson("/api/super-admin/request/{$request->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('basecamps', [
            'id' => $basecamp->id,
            'admin_basecamp_id' => $user->id
        ]);
    }
}