<?php

namespace Tests\Feature\AdminGunung;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gunung;
use App\Models\GunungGaleri;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GunungTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gunung_can_view_gunung_list()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        Gunung::create([
            'nama' => 'Gunung Semeru',
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $response = $this->getJson('/api/admin-gunung/gunungs');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Gunung'
                 ]);
    }

    public function test_admin_gunung_can_view_gunung_detail()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Rinjani',
            'lokasi' => 'Lombok',
            'ketinggian' => 3726,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $response = $this->getJson("/api/admin-gunung/gunungs/{$gunung->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Detail Gunung'
                 ]);
    }

    public function test_admin_gunung_can_create_gunung()
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('gunung.jpg');

        $response = $this->postJson('/api/admin-gunung/gunungs', [
            'nama' => 'Gunung Merbabu',
            'lokasi' => 'Jawa Tengah',
            'ketinggian' => 3145,
            'deskripsi' => 'Gunung indah',
            'foto_utama' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Gunung berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('gunungs', [
            'nama' => 'Gunung Merbabu'
        ]);
    }

    public function test_create_gunung_requires_required_fields()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin-gunung/gunungs', []);

        $response->assertStatus(422);
    }
    
    public function test_admin_gunung_can_update_gunung_photo()
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Prau',
            'lokasi' => 'Wonosobo',
            'ketinggian' => 2565,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $file = UploadedFile::fake()->image('new-gunung.jpg');

        $response = $this->putJson("/api/admin-gunung/gunungs/{$gunung->id}", [
            'foto_utama' => $file
        ]);

        $response->assertStatus(200);

        $this->assertNotNull(
            $gunung->fresh()->foto_utama
        );
    }

    public function test_admin_gunung_can_delete_gunung()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Papandayan',
            'lokasi' => 'Garut',
            'ketinggian' => 2665,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $response = $this->deleteJson("/api/admin-gunung/gunungs/{$gunung->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Gunung berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('gunungs', [
            'id' => $gunung->id
        ]);
    }

    public function test_admin_gunung_can_add_galeri()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Gede',
            'lokasi' => 'Jawa Barat',
            'ketinggian' => 2958,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $response = $this->postJson("/api/admin-gunung/gunungs/{$gunung->id}/galeri", [
            'foto' => 'galeri1.jpg',
            'caption' => 'Pemandangan indah'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Galeri berhasil ditambahkan'
                 ]);

        $this->assertDatabaseHas('gunung_galeris', [
            'gunung_id' => $gunung->id,
            'foto' => 'galeri1.jpg'
        ]);
    }
}