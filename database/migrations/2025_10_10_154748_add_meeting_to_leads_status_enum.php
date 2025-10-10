<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('status', ['accept', 'reject', 'followup', 'new', 'meeting'])->default('new')->change();
        });
    }

    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('status', ['accept', 'reject', 'followup', 'new'])->default('new')->change();
        });
    }
};