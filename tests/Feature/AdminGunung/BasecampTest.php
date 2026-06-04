<?php

namespace Tests\Feature\AdminGunung;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gunung;
use App\Models\Basecamp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BasecampTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gunung_can_view_basecamp_list()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Semeru',
            'created_by' => $admin->id
        ]);

        Basecamp::create([
            'nama' => 'Basecamp Ranu Pane',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Lumajang',
            'harga_tiket' => 15000
        ]);

        $response = $this->getJson('/api/admin-gunung/basecamps');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Basecamp'
                 ]);
    }

    public function test_admin_gunung_can_view_basecamp_detail()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Merbabu',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Selo',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Boyolali',
            'harga_tiket' => 20000
        ]);

        $response = $this->getJson("/api/admin-gunung/basecamps/{$basecamp->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Detail Basecamp'
                 ]);
    }

    public function test_admin_gunung_can_create_basecamp()
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Slamet',
            'created_by' => $admin->id
        ]);

        $file = UploadedFile::fake()->image('basecamp.jpg');

        $response = $this->postJson('/api/admin-gunung/basecamps', [
            'nama' => 'Basecamp Bambangan',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Purbalingga',
            'harga_tiket' => 25000,
            'foto_utama' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Basecamp berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('basecamps', [
            'nama' => 'Basecamp Bambangan'
        ]);
    }

    public function test_create_basecamp_requires_required_fields()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin-gunung/basecamps', []);

        $response->assertStatus(422);
    }

    public function test_admin_cannot_create_basecamp_for_other_admin_mountain()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $otherAdmin = User::factory()->create();

        $gunung = Gunung::create([
            'nama' => 'Gunung Rinjani',
            'created_by' => $otherAdmin->id
        ]);

        $response = $this->postJson('/api/admin-gunung/basecamps', [
            'nama' => 'Basecamp Sembalun',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Lombok',
            'harga_tiket' => 30000
        ]);

        $response->assertStatus(404);
    }

    public function test_admin_gunung_can_update_basecamp()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Prau',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Patak Banteng',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Wonosobo',
            'harga_tiket' => 15000
        ]);

        $response = $this->putJson("/api/admin-gunung/basecamps/{$basecamp->id}", [
            'harga_tiket' => 20000
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Basecamp berhasil diperbarui'
                 ]);

        $this->assertDatabaseHas('basecamps', [
            'id' => $basecamp->id,
            'harga_tiket' => 20000
        ]);
    }

    public function test_admin_gunung_can_delete_basecamp()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Lawu',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Cemoro Kandang',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Karanganyar',
            'harga_tiket' => 20000
        ]);

        $response = $this->deleteJson("/api/admin-gunung/basecamps/{$basecamp->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Basecamp berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('basecamps', [
            'id' => $basecamp->id
        ]);
    }

    public function test_admin_gunung_can_assign_admin_basecamp()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $targetUser = User::factory()->create();

        $gunung = Gunung::create([
            'nama' => 'Gunung Papandayan',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Camp David',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Garut',
            'harga_tiket' => 35000
        ]);

        $response = $this->putJson("/api/admin-gunung/basecamps/{$basecamp->id}/assign-admin", [
            'admin_basecamp_id' => $targetUser->id
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Admin Basecamp berhasil ditugaskan'
                 ]);

        $this->assertDatabaseHas('basecamps', [
            'id' => $basecamp->id,
            'admin_basecamp_id' => $targetUser->id
        ]);
    }

    public function test_assign_admin_requires_valid_user()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Ciremai',
            'created_by' => $admin->id
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Linggarjati',
            'gunung_id' => $gunung->id,
            'lokasi' => 'Kuningan',
            'harga_tiket' => 25000
        ]);

        $response = $this->putJson("/api/admin-gunung/basecamps/{$basecamp->id}/assign-admin", [
            'admin_basecamp_id' => 999
        ]);

        $response->assertStatus(422);
    }
}