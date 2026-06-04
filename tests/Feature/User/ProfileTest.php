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

        $response = $this->get('api/user/profile');

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
                 ->assertJson([
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

        $user2 = User::factory()->create([
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

        $response = $this->post('/api/user/profile/foto', [
            'foto' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Foto profil berhasil diunggah'
                 ]);

        $user->refresh();

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

        $response = $this->post('/api/user/profile/foto', [
            'foto' => $file
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordlama')
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/profile/change-password', [
            'current_password' => 'passwordlama',
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'passwordbaru123'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Password berhasil diubah'
                 ]);

        $this->assertTrue(
            Hash::check(
                'passwordbaru123',
                $user->fresh()->password
            )
        );
    }

    public function test_change_password_fails_if_current_password_wrong()
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordlama')
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/profile/change-password', [
            'current_password' => 'salahpassword',
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'passwordbaru123'
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Password salah'
                 ]);
    }

    public function test_change_password_requires_confirmation()
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordlama')
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/profile/change-password', [
            'current_password' => 'passwordlama',
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'beda'
        ]);

        $response->assertStatus(422);
    }
}