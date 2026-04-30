<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('harga_per_orang', 10, 2)->after('jumlah_pendaki')->default(0);
            $table->decimal('total_price', 10, 2)->after('harga_per_orang')->default(0);
            $table->string('order_id')->after('status')->nullable();
            $table->text('snap_token')->after('order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'harga_per_orang',
                'total_price',
                'order_id',
                'snap_token'
            ]);
        });
    }
};
