<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id('MaterialID');
            $table->string('materialName');
            $table->text('materialDescription')->nullable();
            $table->string('unitType')->nullable();     // e.g. sheet, roll, pcs
            $table->decimal('unitCost', 12, 2)->nullable();
            $table->string('pastUsageReference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
