<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('specifications', function (Blueprint $table) {
            $table->id('SpecificationID');
            $table->unsignedBigInteger('ItemID'); // FK → product_items.ItemID

            $table->string('lamination')->nullable(); // e.g. Gloss, Matte, Soft-touch
            $table->string('printer')->nullable();    // e.g. HP Latex, Roland…
            $table->string('cutter')->nullable();     // e.g. Guillotine, Die Cut

            $table->timestamps();

            $table->foreign('ItemID')
                  ->references('ItemID')->on('product_items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specifications');
    }
};
