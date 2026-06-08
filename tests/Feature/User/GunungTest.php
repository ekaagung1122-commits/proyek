<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gunung;
use App\Models\GunungGaleri;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GunungTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_gunung_list()
    {
        // Menggunakan factory, kolom created_by otomatis terisi aman
        Gunung::factory()->count(3)->create(['status' => 1]);

        $response = $this->getJson('/api/user/gunungs');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Gunung'
                 ]);
    }

    public function test_user_can_search_gunung_by_name()
    {
        // Diubah menggunakan factory agar tidak melanggar aturan NOT NULL created_by
        Gunung::factory()->create([
            'nama' => 'Gunung Semeru',
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'status' => 1
        ]);

        Gunung::factory()->create([
            'nama' => 'Gunung Merbabu',
            'lokasi' => 'Jawa Tengah',
            'ketinggian' => 3145,
            'status' => 1
        ]);

        $response = $this->getJson('/api/user/gunungs?search=Semeru');

        $response->assertStatus(200);
        $this->assertStringContainsString('Gunung Semeru', $response->getContent());
        $this->assertStringNotContainsString('Gunung Merbabu', $response->getContent());
    }

    public function test_user_can_view_gunung_detail()
    {
        // Diubah menggunakan factory agar aman dari constraint database
        $gunung = Gunung::factory()->create([
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
                 ->assertJsonFragment([
                     'message' => 'Detail Gunung'
                 ]);
    }
}