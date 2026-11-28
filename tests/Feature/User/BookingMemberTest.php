<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Basecamp;
use App\Models\BasecampKuota;
use App\Models\Booking;
use App\Models\BookingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class BookingMemberTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Basecamp $basecamp, string $tanggal, int $jumlah = 1, array $overrides = []): array
    {
        $anggota = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $anggota[] = [
                'nama' => 'Pendaki ' . ($i + 1),
                'alamat' => 'Alamat ' . ($i + 1),
                'no_hp' => '0812345678' . $i,
                'tanggal_lahir' => '2000-01-0' . ($i + 1),
                'jenis_kelamin' => $i % 2 === 0 ? 'L' : 'P',
                'identitas' => '320123456789000' . $i,
            ];
        }

        return array_merge([
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $tanggal,
            'jumlah_pendaki' => $jumlah,
            'anggota' => $anggota,
        ], $overrides);
    }

    public function test_booking_requires_anggota(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $basecamp = Basecamp::factory()->create();
        $tanggal = now()->addDays(5)->format('Y-m-d');
        BasecampKuota::factory()->create(['basecamp_id' => $basecamp->id, 'tanggal' => $tanggal, 'kuota' => 10]);

        $response = $this->postJson('/api/user/bookings', [
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $tanggal,
            'jumlah_pendaki' => 1,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['anggota']);
    }

    public function test_user_can_create_booking_with_members_and_no_hp(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create(['harga_tiket' => 50000]);
        $tanggal = now()->addDays(7)->format('Y-m-d');
        $kuota = BasecampKuota::factory()->create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => $tanggal,
            'kuota' => 10,
            'kuota_terpakai' => 0,
        ]);

        $payload = $this->validPayload($basecamp, $tanggal, 2);
        $payload['anggota'][0]['foto_identitas'] = UploadedFile::fake()->image('ktp.jpg');

        $response = $this->post('/api/user/bookings', $payload, ['Accept' => 'application/json']);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Booking berhasil');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $tanggal . ' 00:00:00',
            'jumlah_pendaki' => 2,
            'status' => 'pending',
        ]);

        $booking = Booking::where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseCount('booking_members', 2);
        $this->assertDatabaseHas('booking_members', [
            'booking_id' => $booking->id,
            'nama' => 'Pendaki 1',
            'no_hp' => '08123456780',
        ]);

        $member = BookingMember::where('booking_id', $booking->id)->where('nama', 'Pendaki 1')->firstOrFail();
        $this->assertNotNull($member->foto_identitas);
        Storage::disk('public')->assertExists($member->foto_identitas);

        $this->assertSame(2, $kuota->fresh()->kuota_terpakai);
    }

    public function test_booking_rejects_duplicate_active_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $basecamp = Basecamp::factory()->create();
        $tanggal = now()->addDays(10)->format('Y-m-d');
        BasecampKuota::factory()->create(['basecamp_id' => $basecamp->id, 'tanggal' => $tanggal, 'kuota' => 10]);

        Booking::factory()->create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $tanggal,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/user/bookings', $this->validPayload($basecamp, $tanggal, 1), ['Accept' => 'application/json']);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Anda sudah punya booking aktif untuk tanggal ini.']);
    }

    public function test_booking_fails_when_quota_not_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $basecamp = Basecamp::factory()->create();
        $tanggal = now()->addDays(12)->format('Y-m-d');

        $response = $this->postJson('/api/user/bookings', $this->validPayload($basecamp, $tanggal, 1), ['Accept' => 'application/json']);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Kuota belum diatur oleh admin.']);
    }
}