<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminRequest;
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

        // Menyelaraskan nama relasi pembuat request ke 'user_id'
        AdminRequest::factory()->count(3)->create([
            'user_id' => $user->id
        ]);

        $response = $this->getJson('/api/user/request');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Request Admin Gunung'
                 ]);
    }

    public function test_user_can_request_admin_gunung()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file1 = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->image('foto.png');

        // Menggunakan POST biasa dengan rincian berkas agar multipart form data ter-parsing sempurna
        $response = $this->post('/api/user/request', [
            'request_type' => 'admin_gunung',
            'documents' => [
                $file1,
                $file2
            ]
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Request admin Gunung berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('admin_requests', [
            'user_id' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending'
        ]);

        $this->assertDatabaseCount('admin_request_documents', 2);
    }

    public function test_request_requires_documents()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/request', [
            'request_type' => 'admin_gunung'
        ]);

        $response->assertStatus(422);
    }

    public function test_request_rejects_invalid_document_type()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $response = $this->post('/api/user/request', [
            'request_type' => 'admin_gunung',
            'documents' => [$file]
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }
}