<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'admin',
                'salesperson',
                'operations-manager',
                'artist',
                'head-artist',
                'operations-delivery',
                'operations-printing',
                'operations-installation'
            ])->default('salesperson')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'salesperson', 'operation'])->default('salesperson')->change();
        });
    }
};