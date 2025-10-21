<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_remarks', function (Blueprint $table) {
            // Add the user_id column after ProductID
            $table->unsignedBigInteger('user_id')->nullable()->after('ProductID');

            // Add foreign key constraint linking to users.id
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); // if user deleted, keep remark but set user_id = null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_remarks', function (Blueprint $table) {
            // Drop the foreign key and column during rollback
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
