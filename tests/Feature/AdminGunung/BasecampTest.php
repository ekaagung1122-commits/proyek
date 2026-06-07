<?php

namespace Tests\Feature\AdminGunung;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Gunung;
use App\Models\Basecamp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BasecampTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin_gunung']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);

        Sanctum::actingAs($this->admin);
    }

    public function test_admin_gunung_can_view_basecamp_list()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Ranu Pane'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/admin-gunung/basecamps');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Basecamp'
                 ]);
    }

    public function test_admin_gunung_can_view_basecamp_detail()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Selo'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-gunung/basecamps/{$basecamp->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Detail Basecamp'
                 ]);
    }

    public function test_admin_gunung_can_create_basecamp()
    {
        Storage::fake('public');
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $file = UploadedFile::fake()->image('basecamp.jpg');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/admin-gunung/basecamps', [
            'nama' => 'Basecamp Bambangan',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Purbalingga',
            'harga_tiket' => 25000,
            'foto_utama' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Basecamp berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('basecamps', [
            'nama' => 'Basecamp Bambangan'
        ]);
    }

    public function test_admin_gunung_can_update_basecamp()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Patak Banteng'
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->putJson("/api/admin-gunung/basecamps/{$basecamp->id}", [
            'nama' => $basecamp->nama, // sertakan field required lengkap
            'gunung_id' => $basecamp->gunung_id,
            'lokasi' => $basecamp->lokasi,
            'harga_tiket' => 20000
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Basecamp berhasil diperbarui'
                 ]);

        $this->assertDatabaseHas('basecamps', [
            'id' => $basecamp->id,
            'harga_tiket' => 20000
        ]);
    }

    public function test_admin_gunung_can_delete_basecamp()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create(['gunung_id' => $gunung->id]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->deleteJson("/api/admin-gunung/basecamps/{$basecamp->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Basecamp berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('basecamps', [
            'id' => $basecamp->id
        ]);
    }

    public function test_admin_gunung_can_assign_admin_basecamp()
    {
        $targetUser = User::factory()->create();
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create(['gunung_id' => $gunung->id]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->putJson("/api/admin-gunung/basecamps/{$basecamp->id}/assign-admin", [
            'admin_basecamp_id' => $targetUser->id
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Admin Basecamp berhasil ditugaskan'
                 ]);

        $this->assertDatabaseHas('basecamps', [
            'id' => $basecamp->id,
            'admin_basecamp_id' => $targetUser->id
        ]);
    }
}