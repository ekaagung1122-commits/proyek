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
        Schema::table('basecamps', function (Blueprint $table) {
            $table->decimal('harga_tiket', 10, 2)->after('lokasi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basecamps', function (Blueprint $table) {
            $table->dropColumn('harga_tiket');
        });
    }
};
