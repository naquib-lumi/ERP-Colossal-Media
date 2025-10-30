<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id(); // machine_id
            $table->unsignedBigInteger('user_id')->nullable(); // FK -> users.id
            $table->string('machine_name');
            $table->enum('machine_type', ['printer', 'cutter', 'lamination']);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();   // if user deleted, keep machine but set user_id = null
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};