<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gunung;
use App\Models\GunungGaleri;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GunungTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_gunung_list()
    {
        Gunung::factory()->count(3)->create();

        $response = $this->getJson('/api/user/gunungs');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Gunung'
                 ]);
    }

    public function test_user_can_search_gunung_by_name()
    {
        Gunung::create([
            'nama' => 'Gunung Semeru',
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'status' => 1
        ]);

        Gunung::create([
            'nama' => 'Gunung Merbabu',
            'lokasi' => 'Jawa Tengah',
            'ketinggian' => 3145,
            'status' => 1
        ]);

        $response = $this->getJson('/api/user/gunungs?search=Semeru');

        $response->assertStatus(200);

        $this->assertStringContainsString(
            'Gunung Semeru',
            $response->getContent()
        );
    }

    public function test_user_can_view_gunung_detail()
    {
        $gunung = Gunung::create([
            'nama' => 'Gunung Semeru',
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'status' => 1
        ]);

        GunungGaleri::create([
            'gunung_id' => $gunung->id,
            'foto' => 'foto1.jpg',
            'caption' => 'Puncak Semeru'
        ]);

        $response = $this->getJson("/api/user/gunungs/{$gunung->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Detail Gunung'
                 ]);
    }
}