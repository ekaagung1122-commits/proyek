<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminRequest;
use App\Models\Basecamp;
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

        $basecamp = Basecamp::factory()->create();

        // Di sini email diset null karena ini adalah request dari user biasa (admin_gunung)
        AdminRequest::factory()->count(3)->create([
            'request_by' => $user->id,
            'user_id' => $user->id,
            'email' => null, // <--- User biasa tidak memiliki data email request
            'basecamp_id' => $basecamp->id,
            'request_type' => 'admin_gunung'
        ]);

        $response = $this->getJson('/api/user/requests');

        $response->assertStatus(200);
    }

    public function test_user_can_request_admin_gunung()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file1 = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->image('foto.png');

        // Sesuai logic: payload dari user murni TANPA email
        $response = $this->post('/api/user/requests', [
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
            'request_by' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending',
            'email' => null // Memastikan di DB tersimpan null untuk user biasa
        ]);

        $this->assertDatabaseCount('admin_request_documents', 2);
    }

    public function test_request_rejects_invalid_document_type()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $response = $this->post('/api/user/requests', [
            'request_type' => 'admin_gunung',
            'documents' => [$file]
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }
}