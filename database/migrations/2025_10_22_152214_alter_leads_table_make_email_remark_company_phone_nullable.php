<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('company_phone')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->text('remark')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('company_phone')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->text('remark')->nullable(false)->change();
        });
    }
};