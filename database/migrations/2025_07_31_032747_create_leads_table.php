<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_person_id');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->text('notes')->nullable();
            $table->enum('status', ['new', 'contacted', 'in_progress', 'converted', 'lost'])->default('new');
            $table->timestamps();

            $table->foreign('sales_person_id')->references('id')->on('sales_people')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};