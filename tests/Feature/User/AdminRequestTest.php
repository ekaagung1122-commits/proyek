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
            'email' => $user->email,
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

    public function test_user_request_stores_notes_from_gunung_fields(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/user/requests', [
            'request_type' => 'admin_gunung',
            'nama_gunung' => 'Gunung Argopuro',
            'lokasi' => 'Jawa Timur',
            'deskripsi' => 'Gunung dengan jalur panjang',
            'documents' => [UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $request = AdminRequest::where('request_by', $user->id)->firstOrFail();
        $this->assertStringContainsString('Nama Gunung: Gunung Argopuro', (string) $request->notes);
        $this->assertStringContainsString('Lokasi: Jawa Timur', (string) $request->notes);
        $this->assertStringContainsString('Deskripsi: Gunung dengan jalur panjang', (string) $request->notes);
    }

    public function test_user_request_requires_documents(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/user/requests', [
            'request_type' => 'admin_gunung',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_user_cannot_create_duplicate_pending_admin_gunung_request(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        AdminRequest::factory()->create([
            'user_id' => $user->id,
            'request_by' => $user->id,
            'request_type' => 'admin_gunung',
            'status' => 'pending',
        ]);

        $response = $this->post('/api/user/requests', [
            'request_type' => 'admin_gunung',
            'documents' => [UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Request masih pending']);
    }
}