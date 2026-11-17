<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Status 'completed' dipakai oleh proses checkout, laporan, dashboard,
     * dan penilaian ulasan, tetapi belum ada pada definisi enum awal.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE bookings MODIFY COLUMN status "
            . "ENUM('pending', 'confirmed', 'cancelled', 'completed') "
            . "NOT NULL DEFAULT 'pending'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE bookings MODIFY COLUMN status "
            . "ENUM('pending', 'confirmed', 'cancelled') "
            . "NOT NULL DEFAULT 'pending'"
        );
    }
};
