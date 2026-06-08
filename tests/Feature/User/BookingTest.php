<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Basecamp;
use App\Models\BasecampKuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_booking_history()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Booking::factory()->count(3)->create([
            'user_id' => $user->id
        ]);

        $response = $this->getJson('/api/user/bookings');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Riwayat Booking'
                 ]);
    }

    public function test_user_can_view_hiking_history()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Booking::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'completed'
        ]);

        // Dipastikan memanggil endpoint riwayat pendakian yang selesai
        $response = $this->getJson('/api/user/bookings/history');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Riwayat Pendakian'
                 ]);
    }

    public function test_user_can_view_booking_detail()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $booking = Booking::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->getJson("/api/user/bookings/{$booking->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Detail Booking'
                 ]);
    }

    public function test_user_can_create_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create([
            'harga_tiket' => 15000
        ]);

        $tanggalNaik = now()->addDay()->toDateString();

        BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => $tanggalNaik,
            'kuota' => 20,
            'kuota_terpakai' => 0
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/user/bookings', [
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $tanggalNaik,
            'jumlah_pendaki' => 2
        ]);

        $response->dump();

        $this->assertTrue(in_array($response->getStatusCode(), [200, 201]));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'jumlah_pendaki' => 2,
            'status' => 'pending'
        ]);
    }

    public function test_user_can_cancel_pending_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        // Disinkronkan menggunakan patchJson sesuai standar routing API
        $response = $this->patchJson("/api/user/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Booking berhasil dibatalkan'
                 ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled' // Pastikan di controller mengubah status menjadi cancelled
        ]);
    }

    public function test_user_can_reschedule_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create();

        $oldDate = now()->addDay()->toDateString();
        $newDate = now()->addDays(2)->toDateString();

        BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => $newDate,
            'kuota' => 10,
            'kuota_terpakai' => 0
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $oldDate,
            'jumlah_pendaki' => 2,
            'status' => 'pending'
        ]);

        // Disinkronkan menggunakan patchJson agar tidak memicu error 405 Method Not Allowed
        $response = $this->patchJson("/api/user/bookings/{$booking->id}/reschedule", [
            'tanggal_naik' => $newDate
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Booking berhasil dijadwal ulang'
                 ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'tanggal_naik' => $newDate
        ]);
    }
}