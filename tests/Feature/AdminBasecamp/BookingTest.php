<?php

namespace Tests\Feature\AdminBasecamp;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Booking;
use App\Models\Basecamp;
use App\Models\Gunung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingTest extends TestCase
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

    public function test_admin_basecamp_can_view_booking_list()
    {
        $user = User::factory()->create();
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Semeru',
            'admin_basecamp_id' => $this->admin->id
        ]);

        Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'order_id' => 'BK-TEST001',
            'tanggal_naik' => now()->addDays(5)->format('Y-m-d'),
            'jumlah_pendaki' => 3,
            'total_price' => 75000,
            'status' => 'confirmed',
            'checkout_by' => null // Pastikan diisi null jika migration mengizinkan, atau gunakan $this->admin->id jika wajib
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/admin-basecamp/bookings');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daftar Booking'
                 ]);
    }

    public function test_admin_basecamp_can_view_booking_detail()
    {
        $user = User::factory()->create();
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Rinjani',
            'admin_basecamp_id' => $this->admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'order_id' => 'BK-TEST002',
            'tanggal_naik' => now()->addDays(5)->format('Y-m-d'),
            'jumlah_pendaki' => 2,
            'total_price' => 50000,
            'status' => 'confirmed',
            'checkout_by' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/admin-basecamp/bookings/{$booking->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Detail Booking'
                 ]);
    }

    public function test_admin_basecamp_can_checkin_booking()
    {
        $user = User::factory()->create();
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Merbabu',
            'admin_basecamp_id' => $this->admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'order_id' => 'BK-TEST003',
            'tanggal_naik' => now()->addDays(5)->format('Y-m-d'),
            'jumlah_pendaki' => 4,
            'total_price' => 100000,
            'status' => 'confirmed',
            'checkout_by' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkin");

        $response->assertStatus(200);
        $this->assertNotNull($booking->fresh()->checkin_at);
    }

    public function test_admin_basecamp_can_checkout_booking()
    {
        $user = User::factory()->create();
        $gunung = Gunung::factory()->create(['created_by' => $this->admin->id]);
        
        $basecamp = Basecamp::factory()->create([
            'gunung_id' => $gunung->id,
            'nama' => 'Basecamp Prau',
            'admin_basecamp_id' => $this->admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'order_id' => 'BK-TEST004',
            'tanggal_naik' => now()->addDays(5)->format('Y-m-d'),
            'jumlah_pendaki' => 1,
            'total_price' => 25000,
            'status' => 'confirmed',
            'checkin_at' => now(),
            'checkout_by' => null
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkout");

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed'
        ]);
    }
}