<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('file_size')->unsigned(); // Size in bytes
            $table->string('file_location'); // Path to file
            $table->string('file_extension'); // e.g., pdf, doc, jpg
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade')
                  ->constrained()->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')
                  ->constrained()->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_attachments');
    }
};