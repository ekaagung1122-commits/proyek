<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Basecamp;
use App\Models\BasecampKuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

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
                 ->assertJson([
                     'message' => 'Riwayat Booking'
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
                 ->assertJson([
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

        BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => now()->addDay()->toDateString(),
            'kuota' => 20,
            'kuota_terpakai' => 0
        ]);

        $response = $this->postJson('/api/user/bookings', [
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => now()->addDay()->toDateString(),
            'jumlah_pendaki' => 2
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Booking berhasil'
                 ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'jumlah_pendaki' => 2,
            'status' => 'pending'
        ]);
    }

    public function test_user_cannot_create_duplicate_booking()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create();

        Booking::create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => now()->addDay()->toDateString(),
            'jumlah_pendaki' => 1,
            'harga_per_orang' => 10000,
            'total_price' => 10000,
            'status' => 'pending'
        ]);

        $response = $this->postJson('/api/user/bookings', [
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => now()->addDay()->toDateString(),
            'jumlah_pendaki' => 2
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Anda sudah punya booking aktif'
                 ]);
    }

    public function test_booking_fails_if_quota_not_set()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create();

        $response = $this->postJson('/api/user/bookings', [
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => now()->addDay()->toDateString(),
            'jumlah_pendaki' => 2
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Kuota belum diatur'
                 ]);
    }

    public function test_booking_fails_if_quota_insufficient()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create();

        BasecampKuota::create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => now()->addDay()->toDateString(),
            'kuota' => 2,
            'kuota_terpakai' => 1
        ]);

        $response = $this->postJson('/api/user/bookings', [
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => now()->addDay()->toDateString(),
            'jumlah_pendaki' => 5
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Kuota tidak mencukupi'
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

        $response = $this->postJson("/api/user/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Booking berhasil dibatalkan'
                 ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_user_cannot_cancel_confirmed_booking()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed'
        ]);

        $response = $this->postJson("/api/user/bookings/{$booking->id}/cancel");

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Hanya booking dengan status pending yang bisa dibatalkan'
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

        $response = $this->getJson('/api/user/bookings/history');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Riwayat Pendakian'
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

        $response = $this->postJson("/api/user/bookings/{$booking->id}/reschedule", [
            'tanggal_naik' => $newDate
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Booking berhasil dijadwal ulang'
                 ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'tanggal_naik' => $newDate
        ]);
    }

    public function test_reschedule_fails_if_same_date()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $date = now()->addDay()->toDateString();

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'tanggal_naik' => $date,
            'status' => 'pending'
        ]);

        $response = $this->postJson("/api/user/bookings/{$booking->id}/reschedule", [
            'tanggal_naik' => $date
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Tanggal naik baru tidak boleh sama dengan tanggal naik sebelumnya'
                 ]);
    }

    public function test_download_pdf_fails_if_booking_not_confirmed()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->get("/api/user/bookings/{$booking->id}/pdf");

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Hanya booking dengan status confirmed yang bisa diunduh tiketnya'
                 ]);
    }
}