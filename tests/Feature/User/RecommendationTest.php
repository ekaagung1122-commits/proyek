<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gunung;
use App\Models\Basecamp;
use App\Models\RecommendationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.chatbot.api_key' => null, 'services.chatbot.enabled' => true]);
    }

    public function test_guest_cannot_access_recommendations(): void
    {
        $response = $this->getJson('/api/user/recommendations', ['Accept' => 'application/json']);

        $response->assertStatus(401);
    }

    public function test_user_can_get_deterministic_recommendations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $gunung = Gunung::factory()->create([
            'nama' => 'Gunung Semeru',
            'status' => true,
            'ketinggian' => 3676,
            'tingkat_kesulitan' => 'sulit',
        ]);
        Basecamp::factory()->create(['gunung_id' => $gunung->id]);

        $response = $this->getJson('/api/user/recommendations');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Rekomendasi berhasil dibuat')
                 ->assertJsonPath('data.source', 'deterministic')
                 ->assertJsonStructure([
                     'data' => [
                         'profile',
                         'mountains',
                         'equipments',
                         'generated_at',
                         'source',
                     ],
                 ]);
    }

    public function test_recommendation_records_impression_event(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/recommendations')->assertStatus(200);

        $this->assertDatabaseHas('recommendation_events', [
            'user_id' => $user->id,
            'event_type' => 'impression',
            'target_type' => 'recommendation',
        ]);
    }

    public function test_user_can_store_recommendation_event(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $gunung = Gunung::factory()->create();

        $response = $this->postJson('/api/user/recommendations/events', [
            'event_type' => 'click',
            'target_type' => 'mountain',
            'target_id' => $gunung->id,
            'metadata' => ['source' => 'test'],
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Event rekomendasi dicatat');

        $this->assertDatabaseHas('recommendation_events', [
            'user_id' => $user->id,
            'event_type' => 'click',
            'target_type' => 'mountain',
            'target_id' => $gunung->id,
        ]);
    }

    public function test_recommendation_event_rejects_invalid_event_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/recommendations/events', [
            'event_type' => 'unknown',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['event_type']);
    }

    public function test_recommendation_uses_ai_when_configured(): void
    {
        Cache::flush();
        config([
            'services.chatbot.enabled' => true,
            'services.chatbot.api_key' => 'test-key',
            'services.chatbot.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.chatbot.model' => 'gemini-test',
            'services.chatbot.timeout' => 5,
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode(['mountains' => [], 'equipments' => []])],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $gunung = Gunung::factory()->create(['status' => true]);
        Basecamp::factory()->create(['gunung_id' => $gunung->id]);

        $response = $this->getJson('/api/user/recommendations?refresh=1');

        $response->assertStatus(200)
                 ->assertJsonPath('data.source', 'hybrid');
    }
}
