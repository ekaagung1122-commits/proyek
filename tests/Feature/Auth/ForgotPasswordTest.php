<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_reset_password_link()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user@gmail.com'
        ]);

        $response = $this->postJson('/api/password/forgot', [
            'email' => 'user@gmail.com'
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_fails_if_email_not_found()
    {
        $response = $this->postJson('/api/password/forgot', [
            'email' => 'tidakada@gmail.com'
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reset_password()
    {
        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => Hash::make('passwordlama')
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/password/reset', [
            'email' => 'user@gmail.com',
            'token' => $token,
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123'
        ]);

        $response->assertStatus(200);

        $this->assertTrue(
            Hash::check('passwordbaru123', $user->fresh()->password)
        );
    }
}