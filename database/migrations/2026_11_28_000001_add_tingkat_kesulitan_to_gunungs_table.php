<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gunungs', function (Blueprint $table) {
            $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit'])->nullable()->after('ketinggian');
        });
    }

    public function down(): void
    {
        Schema::table('gunungs', function (Blueprint $table) {
            $table->dropColumn('tingkat_kesulitan');
        });
    }
};
