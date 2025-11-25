<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop FK on materials.unit_id
        Schema::table('materials', function (Blueprint $table) {
            // Replace the FK name below with your actual one if needed
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        // 2. Now safe to drop units table
        Schema::dropIfExists('units');
    }

    public function down(): void
    {
        // Recreate the units table (if rolling back)
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->timestamps();
        });

        // Re-add unit_id column and foreign key
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained('units');
        });
    }
};
