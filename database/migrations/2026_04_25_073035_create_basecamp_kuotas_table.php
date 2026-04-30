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
        Schema::create('basecamp_kuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basecamp_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->integer('kuota');
            $table->timestamps();

            $table->unique(['basecamp_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basecamp_kuotas');
    }
};
