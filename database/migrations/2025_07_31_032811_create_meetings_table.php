<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('type')->nullable(); // e.g., Zoom, In-person
            $table->string('location')->nullable(); // Location or URL
            $table->string('attendees_email')->nullable(); // Comma-separated emails
            $table->text('note')->nullable(); // Additional notes
            $table->enum('status', ['scheduled', 'canceled', 'postponed'])->default('scheduled');
            $table->dateTime('new_start_time')->nullable(); // For rescheduling
            $table->dateTime('new_end_time')->nullable(); // For rescheduling
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};