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

    public function test_admin_basecamp_can_view_jalur_list()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach($role->id);

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Semeru',
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Ranu Pane',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $admin->id,
            'lokasi' => 'Lumajang',
            'harga_tiket' => 25000
        ]);

        Jalur::create([
            'nama_jalur' => 'Jalur Ayek-ayek',
            'estimasi_waktu' => 8,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $response = $this->getJson("/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Jalur'
                 ]);
    }

    public function test_admin_basecamp_can_view_jalur_detail()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach($role->id);

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Rinjani',
            'lokasi' => 'NTB',
            'ketinggian' => 3726,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Sembalun',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $admin->id,
            'lokasi' => 'Lombok',
            'harga_tiket' => 30000
        ]);

        $jalur = Jalur::create([
            'nama_jalur' => 'Jalur Sembalun',
            'estimasi_waktu' => 10,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $response = $this->getJson("/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs/{$jalur->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Detail Jalur'
                 ]);
    }

    public function test_admin_basecamp_can_create_jalur()
    {
        Storage::fake('public');

        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach($role->id);

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Slamet',
            'lokasi' => 'Jawa Tengah',
            'ketinggian' => 3428,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Bambangan',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $admin->id,
            'lokasi' => 'Purbalingga',
            'harga_tiket' => 20000
        ]);

        $file = UploadedFile::fake()->image('jalur.jpg');

        $response = $this->postJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs",
            [
                'nama_jalur' => 'Jalur Bambangan',
                'estimasi_waktu' => 7,
                'status' => 'buka',
                'deskripsi' => 'Jalur favorit pendaki',
                'foto_utama' => $file
            ]
        );

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Jalur berhasil dibuat'
                 ]);

        $this->assertDatabaseHas('jalurs', [
            'nama_jalur' => 'Jalur Bambangan',
            'basecamp_id' => $basecamp->id
        ]);
    }

    public function test_create_jalur_requires_required_fields()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach($role->id);

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Lawu',
            'lokasi' => 'Jawa Tengah',
            'ketinggian' => 3265,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Cemoro Sewu',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $admin->id,
            'lokasi' => 'Karanganyar',
            'harga_tiket' => 15000
        ]);

        $response = $this->postJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs",
            []
        );

        $response->assertStatus(422);
    }

    public function test_admin_basecamp_can_update_jalur()
    {
        Storage::fake('public');

        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach($role->id);

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Prau',
            'lokasi' => 'Wonosobo',
            'ketinggian' => 2565,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Patak Banteng',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $admin->id,
            'lokasi' => 'Dieng',
            'harga_tiket' => 10000
        ]);

        $jalur = Jalur::create([
            'nama_jalur' => 'Jalur Lama',
            'estimasi_waktu' => 5,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $file = UploadedFile::fake()->image('update.jpg');

        $response = $this->putJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs/{$jalur->id}",
            [
                'nama_jalur' => 'Jalur Baru',
                'estimasi_waktu' => 6,
                'status' => 'tutup',
                'deskripsi' => 'Sedang perbaikan',
                'foto_utama' => $file
            ]
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Jalur berhasil diperbarui'
                 ]);

        $this->assertDatabaseHas('jalurs', [
            'id' => $jalur->id,
            'nama_jalur' => 'Jalur Baru',
            'status' => 'tutup'
        ]);
    }

    public function test_admin_basecamp_can_delete_jalur()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $admin = User::factory()->create();

        $admin->roles()->attach($role->id);

        Sanctum::actingAs($admin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Papandayan',
            'lokasi' => 'Garut',
            'ketinggian' => 2665,
            'created_by' => $admin->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Cisurupan',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $admin->id,
            'lokasi' => 'Garut',
            'harga_tiket' => 25000
        ]);

        $jalur = Jalur::create([
            'nama_jalur' => 'Jalur Papandayan',
            'estimasi_waktu' => 4,
            'status' => 'buka',
            'basecamp_id' => $basecamp->id
        ]);

        $response = $this->deleteJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/jalurs/{$jalur->id}"
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Jalur berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('jalurs', [
            'id' => $jalur->id
        ]);
    }

    public function test_other_admin_basecamp_cannot_access_jalur()
    {
        $role = Role::create([
            'name' => 'admin_basecamp'
        ]);

        $owner = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $owner->roles()->attach($role->id);
        $otherAdmin->roles()->attach($role->id);

        Sanctum::actingAs($otherAdmin);

        $gunung = Gunung::create([
            'nama' => 'Gunung Gede',
            'lokasi' => 'Jawa Barat',
            'ketinggian' => 2958,
            'created_by' => $owner->id,
            'status' => 1
        ]);

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Cibodas',
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $owner->id,
            'lokasi' => 'Cianjur',
            'harga_tiket' => 30000
        ]);

        $response = $this->getJson(
            "/appi/admin-basecamp/basecamps/{$basecamp->id}/jalurs"
        );

        $response->assertStatus(404);
    }
}