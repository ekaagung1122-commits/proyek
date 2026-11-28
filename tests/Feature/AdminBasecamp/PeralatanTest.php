<?php

namespace Tests\Feature\AdminBasecamp;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\Peralatan;
use App\Models\KategoriPeralatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PeralatanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Basecamp $basecamp;
    private KategoriPeralatan $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin_basecamp']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);
        Sanctum::actingAs($this->admin);

        $gunung = Gunung::factory()->create();
        $this->basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id,
        ]);
        $this->kategori = KategoriPeralatan::factory()->create(['nama_kategori' => 'Tenda']);
    }

    public function test_can_list_kategori_peralatan(): void
    {
        KategoriPeralatan::factory()->create(['nama_kategori' => 'Carrier']);

        $response = $this->getJson('/api/admin-basecamp/kategori-peralatan');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_can_list_peralatan_for_owned_basecamp(): void
    {
        Peralatan::factory()->create([
            'basecamp_id' => $this->basecamp->id,
            'kategori_id' => $this->kategori->id,
            'nama_alat' => 'Tenda Dome',
        ]);

        $response = $this->getJson("/api/admin-basecamp/basecamps/{$this->basecamp->id}/peralatans");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Daftar peralatan')
                 ->assertJsonFragment(['nama_alat' => 'Tenda Dome']);
    }

    public function test_can_create_peralatan(): void
    {
        $response = $this->postJson("/api/admin-basecamp/basecamps/{$this->basecamp->id}/peralatans", [
            'nama' => 'Carrier 60L',
            'kategori_id' => $this->kategori->id,
            'harga_sewa' => 35000,
            'stok' => 5,
            'deskripsi' => 'Carrier nyaman',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Peralatan berhasil ditambahkan')
                 ->assertJsonPath('data.nama_alat', 'Carrier 60L');

        $this->assertDatabaseHas('peralatans', [
            'basecamp_id' => $this->basecamp->id,
            'nama_alat' => 'Carrier 60L',
            'harga_sewa' => 35000,
            'stok' => 5,
        ]);
    }

    public function test_create_peralatan_requires_fields(): void
    {
        $response = $this->postJson("/api/admin-basecamp/basecamps/{$this->basecamp->id}/peralatans", [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nama', 'harga_sewa']);
    }

    public function test_can_show_update_and_delete_peralatan(): void
    {
        $peralatan = Peralatan::factory()->create([
            'basecamp_id' => $this->basecamp->id,
            'kategori_id' => $this->kategori->id,
            'nama_alat' => 'Kompor Lama',
            'harga_sewa' => 20000,
            'stok' => 3,
        ]);

        $this->getJson("/api/admin-basecamp/basecamps/{$this->basecamp->id}/peralatans/{$peralatan->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.nama_alat', 'Kompor Lama');

        $update = $this->putJson("/api/admin-basecamp/basecamps/{$this->basecamp->id}/peralatans/{$peralatan->id}", [
            'nama' => 'Kompor Baru',
            'harga_sewa' => 25000,
            'stok' => 4,
            'kategori_id' => $this->kategori->id,
            'deskripsi' => 'Update',
        ], ['Accept' => 'application/json']);

        $update->assertStatus(200)
               ->assertJsonPath('message', 'Peralatan berhasil diperbarui');

        $this->assertDatabaseHas('peralatans', [
            'id' => $peralatan->id,
            'nama_alat' => 'Kompor Baru',
            'harga_sewa' => 25000,
            'stok' => 4,
        ]);

        $this->deleteJson("/api/admin-basecamp/basecamps/{$this->basecamp->id}/peralatans/{$peralatan->id}")
             ->assertStatus(200)
             ->assertJsonPath('message', 'Peralatan berhasil dihapus');

        $this->assertDatabaseMissing('peralatans', ['id' => $peralatan->id]);
    }

    public function test_cannot_access_peralatan_of_unowned_basecamp(): void
    {
        $otherBasecamp = Basecamp::factory()->create();
        $peralatan = Peralatan::factory()->create(['basecamp_id' => $otherBasecamp->id]);

        $this->getJson("/api/admin-basecamp/basecamps/{$otherBasecamp->id}/peralatans", ['Accept' => 'application/json'])
             ->assertStatus(404);

        $this->postJson("/api/admin-basecamp/basecamps/{$otherBasecamp->id}/peralatans", [
            'nama' => 'X', 'harga_sewa' => 1000,
        ], ['Accept' => 'application/json'])->assertStatus(404);

        $this->deleteJson("/api/admin-basecamp/basecamps/{$otherBasecamp->id}/peralatans/{$peralatan->id}", [], ['Accept' => 'application/json'])
             ->assertStatus(404);
    }
}