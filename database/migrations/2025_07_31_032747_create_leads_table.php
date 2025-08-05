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
            $table->unsignedBigInteger('salesperson_id'); // Renamed from user_id to salesperson_id
            $table->string('company_name');
            $table->string('company_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->date('date')->nullable(); // Date field for lead creation or follow-up
            $table->enum('status', ['accept', 'reject', 'followup', 'new'])->default('new');
            $table->string('opportunity')->nullable(); // e.g., High, Medium, Low
            $table->text('remark')->nullable(); // Additional remarks or notes
            $table->timestamps();

            $table->foreign('salesperson_id')->references('id')->on('users')->onDelete('cascade')
                  ->constrained()->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};