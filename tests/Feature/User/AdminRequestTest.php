<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminRequest;
use App\Models\AdminRequestDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class AdminRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_admin_requests()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        AdminRequest::factory()->count(3)->create([
            'request_by' => $user->id
        ]);

        $response = $this->getJson('/api/user/request');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Request Admin Gunung'
                 ]);
    }

    public function test_user_can_request_admin_gunung()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $file1 = UploadedFile::fake()->create(
            'ktp.pdf',
            100,
            'application/pdf'
        );

        $file2 = UploadedFile::fake()->image('foto.png');

        $response = $this->postJson(
            '/api/user/request',
            [
                'request_type' => 'admin_gunung',
                'documents' => [
                    $file1,
                    $file2
                ]
            ]
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Request admin Gunung berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('admin_requests', [
            'user_id' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending'
        ]);

        $this->assertDatabaseCount(
            'admin_request_documents',
            2
        );
    }

    public function test_user_cannot_create_duplicate_pending_request()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        AdminRequest::create([
            'user_id' => $user->id,
            'request_by' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending'
        ]);

        $file = UploadedFile::fake()->create(
            'ktp.pdf',
            100,
            'application/pdf'
        );

        $response = $this->postJson(
            '/api/user/request',
            [
                'request_type' => 'admin_gunung',
                'documents' => [$file]
            ]
        );

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Request masih pending'
                 ]);
    }

    public function test_request_requires_valid_request_type()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create(
            'ktp.pdf',
            100,
            'application/pdf'
        );

        $response = $this->postJson(
            '/api/user/request',
            [
                'request_type' => 'admin_basecamp',
                'documents' => [$file]
            ]
        );

        $response->assertStatus(422);
    }

    public function test_request_requires_documents()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/user/request',
            [
                'request_type' => 'admin_gunung'
            ]
        );

        $response->assertStatus(422);
    }

    public function test_request_rejects_invalid_document_type()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create(
            'virus.exe',
            100,
            'application/octet-stream'
        );

        $response = $this->postJson(
            '/api/user/request',
            [
                'request_type' => 'admin_gunung',
                'documents' => [$file]
            ]
        );

        $response->assertStatus(422);
    }
}