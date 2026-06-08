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

        // Pastikan menyertakan field email agar tidak terkena constraint NOT NULL
        AdminRequest::factory()->count(3)->create([
            'request_by' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email, // <--- Menghindari NOT NULL constraint
            'basecamp_id' => $basecamp->id,
            'request_type' => 'admin_gunung'
        ]);

        // Jika Anda menerima 405, pastikan method di route-nya adalah GET. 
        $response = $this->getJson('/api/user/requests');

        // Jika route Anda sebenarnya menggunakan POST atau penamaan lain, sesuaikan method di atas.
        $response->assertStatus(200);
    }

    public function test_user_can_request_admin_gunung()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file1 = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->image('foto.png');

        // Sertakan data 'email' ke dalam payload request jika Controller membutuhkannya dari request input
        $response = $this->post('/api/user/requests', [
            'request_type' => 'admin_gunung',
            'email' => $user->email, // <--- Menyediakan input email untuk Controller
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
            'status' => 'pending'
        ]);

        $this->assertDatabaseCount('admin_request_documents', 2);
    }

    public function test_request_requires_documents()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Kirimkan email juga di sini agar kegagalan murni karena 'documents' kosong (Validation 422), bukan eror database 500
        $response = $this->postJson('/api/user/requests', [
            'request_type' => 'admin_gunung',
            'email' => $user->email
        ]);

        $response->assertStatus(422);
    }

    public function test_request_rejects_invalid_document_type()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $response = $this->post('/api/user/requests', [
            'request_type' => 'admin_gunung',
            'email' => $user->email,
            'documents' => [$file]
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }
}