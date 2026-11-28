<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_profile()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user/profile');

        $response->assertStatus(200);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create([
            'email' => 'lama@gmail.com'
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Andini',
            'email' => 'baru@gmail.com',
            'phone' => '08123456789',
            'alamat' => 'Bandung'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Profile berhasil diperbarui'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Andini',
            'email' => 'baru@gmail.com',
            'phone' => '08123456789',
            'alamat' => 'Bandung'
        ]);
    }

    public function test_profile_update_requires_unique_email()
    {
        $user1 = User::factory()->create([
            'email' => 'user1@gmail.com'
        ]);

        User::factory()->create([
            'email' => 'user2@gmail.com'
        ]);

        Sanctum::actingAs($user1);

        $response = $this->putJson('/api/user/profile', [
            'name' => 'User 1',
            'email' => 'user2@gmail.com'
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_upload_profile_photo()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('foto.jpg');

        // Menggunakan post multipart biasa dengan header JSON untuk upload file
        $response = $this->patch('/api/user/profile/foto', [
            'foto' => $file
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Foto profil berhasil diunggah'
                 ]);

        $user->refresh();
        $this->assertNotNull($user->foto);
        Storage::disk('public')->assertExists($user->foto);
    }

    public function test_upload_photo_requires_image()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create(
            'dokumen.pdf',
            100,
            'application/pdf'
        );

        $response = $this->patch('/api/user/profile/foto', [
            'foto' => $file
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordlama')
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/user/profile/change-password', [
            'current_password' => 'passwordlama',
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'passwordbaru123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Password berhasil diubah'
                 ]);

        $this->assertTrue(Hash::check('passwordbaru123', $user->fresh()->password));
    }

    public function test_user_can_update_profile_preferences()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'tanggal_lahir' => '2000-05-10',
            'jenis_kelamin' => 'L',
            'experience_level' => 'beginner',
            'preferensi' => [
                'lokasi_favorit' => 'Jawa Timur',
                'budget_sewa' => 150000,
                'alat_dimiliki' => ['Tenda', 'Carrier'],
            ],
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Profile berhasil diperbarui',
                 ]);

        $user->refresh();
        $this->assertSame('beginner', $user->experience_level);
        $this->assertSame('L', $user->jenis_kelamin);
        $this->assertSame('2000-05-10', $user->tanggal_lahir?->format('Y-m-d'));
        $this->assertSame('Jawa Timur', $user->preferensi['lokasi_favorit'] ?? null);
    }

    public function test_profile_preferences_validation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'jenis_kelamin' => 'X',
            'experience_level' => 'expert',
            'preferensi' => [
                'budget_sewa' => -1,
            ],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['jenis_kelamin', 'experience_level', 'preferensi.budget_sewa']);
    }
}