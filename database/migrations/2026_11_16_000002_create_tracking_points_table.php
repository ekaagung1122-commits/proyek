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
        Schema::create('tracking_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tracking_session_id')
                ->constrained('tracking_sessions')
                ->cascadeOnDelete();

            // ID unik dari aplikasi agar pengiriman ulang (offline queue)
            // tidak menghasilkan titik duplikat.
            $table->string('client_uuid', 64);

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy')->nullable();
            $table->float('altitude')->nullable();
            $table->float('speed')->nullable();

            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tracking_session_id', 'client_uuid']);
            $table->index(['tracking_session_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_points');
    }
};
