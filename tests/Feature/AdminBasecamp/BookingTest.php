<?php

namespace Tests\Feature\AdminBasecamp;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Basecamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_basecamp_can_view_booking_list()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Semeru',
            'admin_basecamp_id' => $admin->id
        ]);

        Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed'
        ]);

        $response = $this->getJson('/api/admin-basecamp/bookings');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Daftar Booking'
                 ]);
    }

    public function test_admin_basecamp_can_view_booking_detail()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Rinjani',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed'
        ]);

        $response = $this->getJson("/api/admin-basecamp/bookings/{$booking->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Detail Booking'
                 ]);
    }

    public function test_admin_basecamp_can_checkin_booking()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Merbabu',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed'
        ]);

        $response = $this->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkin");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Check-in berhasil'
                 ]);

        $this->assertNotNull(
            $booking->fresh()->checkin_at
        );
    }

    public function test_admin_basecamp_can_checkout_booking()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Prau',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed',
            'checkin_at' => now()
        ]);

        $response = $this->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkout");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Check-out berhasil'
                 ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed'
        ]);
    }
}