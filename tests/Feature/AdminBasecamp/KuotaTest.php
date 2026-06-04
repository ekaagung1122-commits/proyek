<?php

namespace Tests\Feature\AdminBasecamp;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Gunung;
use App\Models\Basecamp;
use App\Models\BasecampKuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class KuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_basecamp_can_view_kuota_list()
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

        BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 100,
            'kuota_terpakai' => 0
        ]);

        $response = $this->getJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas"
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Kuota'
                 ]);
    }

    public function test_admin_basecamp_can_view_kuota_detail()
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

        $kuota = BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 150,
            'kuota_terpakai' => 10
        ]);

        $response = $this->getJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas/{$kuota->id}"
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Detail Kuota'
                 ]);
    }

    public function test_admin_basecamp_can_create_kuota()
    {
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

        $response = $this->postJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas",
            [
                'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
                'kuota' => 200
            ]
        );

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Kuota berhasil disimpan'
                 ]);

        $this->assertDatabaseHas('basecamp_kuotas', [
            'basecamp_id' => $basecamp->id,
            'kuota' => 200
        ]);
    }

    public function test_store_kuota_requires_valid_data()
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
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas",
            [
                'tanggal' => Carbon::yesterday()->format('Y-m-d'),
                'kuota' => -1
            ]
        );

        $response->assertStatus(422);
    }

    public function test_admin_basecamp_can_update_kuota()
    {
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

        $kuota = BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 100,
            'kuota_terpakai' => 0
        ]);

        $response = $this->putJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas/{$kuota->id}",
            [
                'tanggal' => Carbon::addDays(2)->format('Y-m-d'),
                'kuota' => 300
            ]
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Kuota berhasil diperbarui'
                 ]);

        $this->assertDatabaseHas('basecamp_kuotas', [
            'id' => $kuota->id,
            'kuota' => 300
        ]);
    }

    public function test_admin_basecamp_can_delete_kuota()
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

        $kuota = BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 80,
            'kuota_terpakai' => 5
        ]);

        $response = $this->deleteJson(
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas/{$kuota->id}"
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Kuota berhasil dihapus'
                 ]);

        $this->assertDatabaseMissing('basecamp_kuotas', [
            'id' => $kuota->id
        ]);
    }

    public function test_other_admin_cannot_access_kuota()
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
            "/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas"
        );

        $response->assertStatus(404);
    }
}