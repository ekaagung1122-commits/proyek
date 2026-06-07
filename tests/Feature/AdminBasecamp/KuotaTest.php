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

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat role admin_basecamp dan lekatkan ke aktor penguji
        $role = Role::create(['name' => 'admin_basecamp']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);

        Sanctum::actingAs($this->admin);
    }

    public function test_admin_basecamp_can_view_kuota_list()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 100,
            'kuota_terpakai' => 0
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Kuota'
                 ]);
    }

    public function test_admin_basecamp_can_view_kuota_detail()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        $kuota = BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 150,
            'kuota_terpakai' => 10
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas/{$kuota->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Detail Kuota'
                 ]);
    }

    public function test_admin_basecamp_can_create_kuota()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson("/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas", [
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 200
        ]);

        // Mendukung toleransi respons 200 atau 201 sesuai setelan kontroler Anda
        $this->assertTrue(in_array($response->getStatusCode(), [200, 201]));

        $this->assertDatabaseHas('basecamp_kuotas', [
            'basecamp_id' => $basecamp->id,
            'kuota' => 200
        ]);
    }

    public function test_admin_basecamp_can_update_kuota()
    {
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'admin_basecamp_id' => $this->admin->id
        ]);

        $kuota = BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'kuota' => 100,
            'kuota_terpakai' => 0
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->putJson("/api/admin-basecamp/basecamps/{$basecamp->id}/kuotas/{$kuota->id}", [
            'tanggal' => Carbon::tomorrow()->addDays(1)->format('Y-m-d'),
            'kuota' => 300
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('basecamp_kuotas', [
            'id' => $kuota->id,
            'kuota' => 300
        ]);
    }
}