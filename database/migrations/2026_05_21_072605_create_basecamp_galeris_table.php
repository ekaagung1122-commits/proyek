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
        Schema::create('basecamp_galeris', function (Blueprint $table) {
            $table->id();
            $table->string('foto');
            $table->string('caption')->nullable();
            $table->foreignId('basecamp_id')->constrained('basecamps')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basecamp_galeris');
    }
};
