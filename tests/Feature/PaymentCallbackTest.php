<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Basecamp;
use App\Models\BasecampKuota;
use App\Models\Booking;
use App\Models\Peralatan;
use App\Models\KategoriPeralatan;
use App\Models\DetailSewa;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_callback_confirms_pending_booking(): void
    {
        $booking = Booking::factory()->create([
            'order_id' => 'ORDER-SETTLE-1',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payment/callback', [
            'order_id' => 'ORDER-SETTLE-1',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'confirmed');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_expire_callback_cancels_booking_and_restores_resources(): void
    {
        $user = User::factory()->create();
        $basecamp = Basecamp::factory()->create();
        $tanggal = now()->addDays(5)->format('Y-m-d');

        $kuota = BasecampKuota::factory()->create([
            'basecamp_id' => $basecamp->id,
            'tanggal' => $tanggal,
            'kuota' => 10,
            'kuota_terpakai' => 4,
        ]);

        $kategori = KategoriPeralatan::factory()->create();
        $peralatan = Peralatan::factory()->create([
            'basecamp_id' => $basecamp->id,
            'kategori_id' => $kategori->id,
            'stok' => 3,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'basecamp_id' => $basecamp->id,
            'tanggal_naik' => $tanggal,
            'jumlah_pendaki' => 2,
            'status' => 'pending',
            'order_id' => 'ORDER-EXPIRE-1',
        ]);

        DetailSewa::create([
            'booking_id' => $booking->id,
            'peralatan_id' => $peralatan->id,
            'jumlah' => 2,
            'harga_satuan' => 10000,
            'subtotal' => 20000,
        ]);

        $response = $this->postJson('/api/payment/callback', [
            'order_id' => 'ORDER-EXPIRE-1',
            'transaction_status' => 'expire',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'cancelled');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(2, $kuota->fresh()->kuota_terpakai);
        $this->assertSame(5, $peralatan->fresh()->stok);
    }

    public function test_callback_returns_404_for_unknown_order(): void
    {
        $response = $this->postJson('/api/payment/callback', [
            'order_id' => 'ORDER-NOT-FOUND',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(404)
                 ->assertJsonPath('message', 'Booking not found');
    }

    public function test_callback_rejects_invalid_signature_when_server_key_set(): void
    {
        putenv('MIDTRANS_SERVER_KEY=test-server-key');
        $_ENV['MIDTRANS_SERVER_KEY'] = 'test-server-key';
        $_SERVER['MIDTRANS_SERVER_KEY'] = 'test-server-key';

        try {
            $booking = Booking::factory()->create([
                'order_id' => 'ORDER-SIG-1',
                'status' => 'pending',
            ]);

            $response = $this->postJson('/api/payment/callback', [
                'order_id' => 'ORDER-SIG-1',
                'transaction_status' => 'settlement',
                'status_code' => '200',
                'gross_amount' => '50000.00',
                'signature_key' => 'invalid-signature',
            ]);

            $response->assertStatus(403)
                     ->assertJsonPath('message', 'Invalid signature');

            $this->assertSame('pending', $booking->fresh()->status);
        } finally {
            putenv('MIDTRANS_SERVER_KEY');
            unset($_ENV['MIDTRANS_SERVER_KEY'], $_SERVER['MIDTRANS_SERVER_KEY']);
        }
    }
}