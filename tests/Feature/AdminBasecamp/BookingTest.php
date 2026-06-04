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

    public function test_checkin_fails_if_booking_not_confirmed()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Lawu',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkin");

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Booking belum dikonfirmasi'
                 ]);
    }

    public function test_checkin_fails_if_already_checked_in()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Papandayan',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed',
            'checkin_at' => now()
        ]);

        $response = $this->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkin");

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Booking sudah di-check-in'
                 ]);
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

    public function test_checkout_fails_if_not_checked_in()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Slamet',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed'
        ]);

        $response = $this->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkout");

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Pendaki belum check-in'
                 ]);
    }

    public function test_checkout_fails_if_already_checked_out()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Ciremai',
            'admin_basecamp_id' => $admin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed',
            'checkin_at' => now(),
            'checkout_at' => now()
        ]);

        $response = $this->postJson("/api/admin-basecamp/bookings/{$booking->id}/checkout");

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Pendaki sudah check-out'
                 ]);
    }

    public function test_admin_cannot_access_other_basecamp_booking()
    {
        $admin = User::factory()->create();

        Sanctum::actingAs($admin);

        $otherAdmin = User::factory()->create();

        $user = User::factory()->create();

        $basecamp = Basecamp::create([
            'nama' => 'Basecamp Bromo',
            'admin_basecamp_id' => $otherAdmin->id
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'status' => 'confirmed'
        ]);

        $response = $this->getJson("/api/admin-basecamp/bookings/{$booking->id}");

        $response->assertStatus(404);
    }
}