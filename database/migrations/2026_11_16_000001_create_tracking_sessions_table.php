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
        Schema::create('tracking_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('basecamp_id')->constrained()->cascadeOnDelete();

            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason')->nullable();

            // Jarak titik start ke basecamp (meter) saat tracking dimulai.
            $table->unsignedInteger('start_distance_m')->nullable();

            // Posisi terakhir (didenormalisasi agar daftar admin cepat).
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->float('last_accuracy')->nullable();
            $table->float('last_altitude')->nullable();
            $table->float('last_speed')->nullable();
            $table->unsignedTinyInteger('last_battery')->nullable();
            $table->timestamp('last_recorded_at')->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['basecamp_id', 'status']);
            $table->index('last_recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_sessions');
    }
};
