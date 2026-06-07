<?php

namespace Tests\Feature\AdminGunung;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Gunung;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GunungTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat role dan aktor admin_gunung agar lolos middleware CheckRole
        $role = Role::create(['name' => 'admin_gunung']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);

        Sanctum::actingAs($this->admin);
    }

    public function test_admin_gunung_can_view_gunung_list()
    {
        Gunung::factory()->create([
            'nama' => 'Gunung Semeru',
            'created_by' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/admin-gunung/gunungs');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Gunung'
                 ]);
    }

    public function test_admin_gunung_can_view_gunung_detail()
    {
        $gunung = Gunung::factory()->create([
            'nama' => 'Gunung Rinjani',
            'created_by' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-gunung/gunungs/{$gunung->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Detail Gunung'
                 ]);
    }

    public function test_admin_gunung_can_create_gunung()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('gunung.jpg');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/admin-gunung/gunungs', [
            'nama' => 'Gunung Merbabu',
            'lokasi' => 'Jawa Tengah',
            'ketinggian' => 3145,
            'deskripsi' => 'Gunung indah',
            'foto_utama' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Gunung berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('gunungs', [
            'nama' => 'Gunung Merbabu'
        ]);
    }

    public function test_create_gunung_requires_required_fields()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/admin-gunung/gunungs', []);

        $response->assertStatus(422);
    }
    
    public function test_admin_gunung_can_update_gunung_photo()
    {
        Storage::fake('public');

        $gunung = Gunung::factory()->create([
            'created_by' => $this->admin->id
        ]);

        $file = UploadedFile::fake()->image('new-gunung.jpg');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->putJson("/api/admin-gunung/gunungs/{$gunung->id}", [
            'nama' => $gunung->nama, // sertakan field required untuk update aman
            'lokasi' => $gunung->lokasi,
            'ketinggian' => $gunung->ketinggian,
            'foto_utama' => $file
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($gunung->fresh()->foto_utama);
    }

    public function test_admin_gunung_can_delete_gunung()
    {
        $gunung = Gunung::factory()->create([
            'created_by' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->deleteJson("/api/admin-gunung/gunungs/{$gunung->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Gunung berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('gunungs', [
            'id' => $gunung->id
        ]);
    }

    public function test_admin_gunung_can_add_galeri()
    {
        Storage::fake('public');
        
        $gunung = Gunung::factory()->create([
            'created_by' => $this->admin->id
        ]);

        // Ubah string mentah menjadi File Upload palsu agar lolos validasi request image
        $file = UploadedFile::fake()->image('galeri1.jpg');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson("/api/admin-gunung/gunungs/{$gunung->id}/galeri", [
            'foto' => $file,
            'caption' => 'Pemandangan indah'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Galeri berhasil ditambahkan'
                 ]);

        $this->assertDatabaseHas('gunung_galeris', [
            'gunung_id' => $gunung->id
        ]);
    }
}