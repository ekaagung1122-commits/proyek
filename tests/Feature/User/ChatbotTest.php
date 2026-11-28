<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatbotMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.chatbot.enabled' => true,
            'services.chatbot.api_key' => null,
        ]);
    }

    public function test_user_can_view_own_chatbot_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        ChatbotMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => 'Halo']);
        ChatbotMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => 'Hai juga']);
        ChatbotMessage::create(['user_id' => $other->id, 'role' => 'user', 'content' => 'Rahasia']);

        $response = $this->getJson('/api/user/chatbot/messages');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Riwayat chatbot')
                 ->assertJsonCount(2, 'data')
                 ->assertJsonMissing(['content' => 'Rahasia']);
    }

    public function test_user_can_send_chatbot_message_and_receive_fallback(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/chatbot/messages', [
            'message' => 'Apa rekomendasi gunung untuk pemula?',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Pesan berhasil diproses')
                 ->assertJsonStructure([
                     'data' => [
                         'user_message' => ['id', 'role', 'content', 'created_at'],
                         'assistant_message' => ['id', 'role', 'content', 'created_at'],
                     ],
                 ])
                 ->assertJsonPath('data.user_message.role', 'user')
                 ->assertJsonPath('data.assistant_message.role', 'assistant');

        $this->assertDatabaseHas('chatbot_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'Apa rekomendasi gunung untuk pemula?',
        ]);
        $this->assertDatabaseHas('chatbot_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
        ]);
    }

    public function test_chatbot_message_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/chatbot/messages', ['message' => ''], ['Accept' => 'application/json'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['message']);

        $this->postJson('/api/user/chatbot/messages', ['message' => str_repeat('a', 2001)], ['Accept' => 'application/json'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['message']);
    }

    public function test_user_can_clear_chatbot_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        ChatbotMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => 'A']);
        ChatbotMessage::create(['user_id' => $other->id, 'role' => 'user', 'content' => 'B']);

        $response = $this->deleteJson('/api/user/chatbot/messages');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Riwayat chatbot berhasil dihapus');

        $this->assertDatabaseMissing('chatbot_messages', ['user_id' => $user->id]);
        $this->assertDatabaseHas('chatbot_messages', ['user_id' => $other->id, 'content' => 'B']);
    }
}