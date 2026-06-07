<?php

namespace Tests\Feature\AdminBasecamp;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Gunung;
use App\Models\Basecamp;
use App\Models\Jalur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class JalurTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin_basecamp']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);

        Sanctum::actingAs($this->admin);
    }

    public function test_admin_basecamp_can_view_jalur_list()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        Jalur::create([
            'nama_jalur' => 'Jalur Ayek-ayek',
            'estimasi_waktu' => 8,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Jalur'
                 ]);
    }

    public function test_admin_basecamp_can_view_jalur_detail()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        $jalur = Jalur::create([
            'nama_jalur' => 'Jalur Sembalun',
            'estimasi_waktu' => 10,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs/{$jalur->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Detail Jalur'
                 ]);
    }

    public function test_admin_basecamp_can_create_jalur()
    {
        Storage::fake('public');

        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        $file = UploadedFile::fake()->image('jalur.jpg');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson("/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs", [
            'nama_jalur' => 'Jalur Bambangan',
            'estimasi_waktu' => 7,
            'status' => 'buka',
            'deskripsi' => 'Jalur favorit pendaki',
            'foto_utama' => $file
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 201]));

        $this->assertDatabaseHas('jalurs', [
            'nama_jalur' => 'Jalur Bambangan',
            'basecamp_id' => $basecamp->id
        ]);
    }

    public function test_admin_basecamp_can_update_jalur()
    {
        Storage::fake('public');

        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        $jalur = Jalur::create([
            'nama_jalur' => 'Jalur Lama',
            'estimasi_waktu' => 5,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $file = UploadedFile::fake()->image('update.jpg');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->putJson("/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs/{$jalur->id}", [
            'nama_jalur' => 'Jalur Baru',
            'estimasi_waktu' => 6,
            'status' => 'tutup',
            'deskripsi' => 'Sedang perbaikan',
            'foto_utama' => $file
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('jalurs', [
            'id' => $jalur->id,
            'nama_jalur' => 'Jalur Baru',
            'status' => 'tutup'
        ]);
    }
}