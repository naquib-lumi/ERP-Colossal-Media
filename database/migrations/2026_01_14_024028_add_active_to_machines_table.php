<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveToMachinesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('machines', function (Blueprint $table) {
            // Add new column after machine_type
            $table->tinyInteger('active')
                  ->default(1)
                  ->after('machine_type')
                  ->comment('1 = active, 0 = inactive');
        });

        // Ensure all existing rows are active by default
        DB::table('machines')->update(['active' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}