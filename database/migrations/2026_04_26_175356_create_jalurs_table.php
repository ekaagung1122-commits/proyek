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
        Schema::create('jalurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basecamp_id')->constrained()->cascadeOnDelete();
            $table->string('nama_jalur');
            $table->integer('estimaasi_waktu')->unsigned();
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jalurs');
    }
};
